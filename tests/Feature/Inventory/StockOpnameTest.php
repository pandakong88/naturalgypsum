<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Actions\CancelStockOpnameAction;
use App\Domains\Inventory\Actions\CompleteStockOpnameAction;
use App\Domains\Inventory\Actions\CreateStockOpnameAction;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\DetailStockOpname;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Inventory\Services\StockOpnameExcelService;
use App\Domains\Inventory\Services\StockService;
use App\Livewire\Inventory\StockOpnameCreate;
use App\Livewire\Inventory\StockOpnameDetail;
use App\Livewire\Inventory\StockOpnameIndex;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('Staff Gudang');

    $this->unauthorizedUser = User::factory()->create();

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural PVC']);

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);

    $this->produkA = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Papan PVC 4m',
    ]);

    $this->produkB = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'List PVC 3m',
    ]);

    $this->produkC = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Hollow Gypsum 4x4',
    ]);

    $this->stockService = app(StockService::class);
    $this->createAction = app(CreateStockOpnameAction::class);
    $this->completeAction = app(CompleteStockOpnameAction::class);
    $this->cancelAction = app(CancelStockOpnameAction::class);

    // Initial stock setup: A=100, B=50, C=30
    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PenyesuaianOpname, 'Initial Stock', userId: $this->owner->id);
    $this->stockService->increase($this->produkB, $this->gudang, 50, JenisMutasiStok::PenyesuaianOpname, 'Initial Stock', userId: $this->owner->id);
    $this->stockService->increase($this->produkC, $this->gudang, 30, JenisMutasiStok::PenyesuaianOpname, 'Initial Stock', userId: $this->owner->id);
});

test('TEST 1: sequential document number SO-YYYY-000001 is generated properly', function () {
    $this->actingAs($this->owner);

    $so1 = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 100],
        ],
    ]);

    $so2 = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 100],
        ],
    ]);

    $year = Carbon::now()->format('Y');
    expect($so1->no_stock_opname)->toBe("SO-{$year}-000001")
        ->and($so2->no_stock_opname)->toBe("SO-{$year}-000002");
});

test('TEST 2: draft stock opname does not alter stock or create mutations', function () {
    $this->actingAs($this->owner);

    $initialMutasiCount = MutasiStok::count();

    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Draft,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 150], // +50 diff in draft
        ],
    ]);

    expect($so->status)->toBe(StatusStockOpname::Draft)
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(100.0)
        ->and(MutasiStok::count())->toBe($initialMutasiCount);
});

test('TEST 3: complete stock opname with mixed differences adjusts stock and mutasi correctly', function () {
    $this->actingAs($this->owner);

    // Initial: A=100, B=50, C=30
    // Physical count: A=115 (+15 surplus), B=40 (-10 deficit), C=30 (0 exact)
    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'catatan' => 'Opname Bulanan',
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 115, 'catatan' => 'Temuan di rak atas'],
            ['produk_id' => $this->produkB->id, 'stok_fisik' => 40, 'catatan' => 'Rusak/hilang'],
            ['produk_id' => $this->produkC->id, 'stok_fisik' => 30, 'catatan' => 'Sesuai'],
        ],
    ]);

    expect($so->status)->toBe(StatusStockOpname::Selesai)
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(115.0)
        ->and($this->stockService->getStock($this->produkB, $this->gudang))->toBe(40.0)
        ->and($this->stockService->getStock($this->produkC, $this->gudang))->toBe(30.0);

    // Verify detail snapshot
    $detailA = $so->details()->where('produk_id', $this->produkA->id)->first();
    expect((float) $detailA->stok_sistem)->toBe(100.0)
        ->and((float) $detailA->stok_fisik)->toBe(115.0)
        ->and((float) $detailA->selisih)->toBe(15.0);

    $detailB = $so->details()->where('produk_id', $this->produkB->id)->first();
    expect((float) $detailB->stok_sistem)->toBe(50.0)
        ->and((float) $detailB->stok_fisik)->toBe(40.0)
        ->and((float) $detailB->selisih)->toBe(-10.0);

    $detailC = $so->details()->where('produk_id', $this->produkC->id)->first();
    expect((float) $detailC->stok_sistem)->toBe(30.0)
        ->and((float) $detailC->stok_fisik)->toBe(30.0)
        ->and((float) $detailC->selisih)->toBe(0.0);

    // Verify mutasi stok: A has +15, B has -10, C has NO mutation
    $mutasiA = MutasiStok::where('referensi', $so->no_stock_opname)
        ->where('produk_id', $this->produkA->id)
        ->first();
    expect($mutasiA)->not->toBeNull()
        ->and((float) $mutasiA->jumlah)->toBe(15.0)
        ->and($mutasiA->jenis)->toBe(JenisMutasiStok::PenyesuaianOpname);

    $mutasiB = MutasiStok::where('referensi', $so->no_stock_opname)
        ->where('produk_id', $this->produkB->id)
        ->first();
    expect($mutasiB)->not->toBeNull()
        ->and((float) $mutasiB->jumlah)->toBe(-10.0)
        ->and($mutasiB->jenis)->toBe(JenisMutasiStok::PenyesuaianOpname);

    $mutasiC = MutasiStok::where('referensi', $so->no_stock_opname)
        ->where('produk_id', $this->produkC->id)
        ->first();
    expect($mutasiC)->toBeNull(); // Zero difference should NOT produce ledger row
});

test('TEST 4: cancellation reverses applied adjustments cleanly', function () {
    $this->actingAs($this->owner);

    // Initial: A=100, B=50
    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 120], // +20
            ['produk_id' => $this->produkB->id, 'stok_fisik' => 30],  // -20
        ],
    ]);

    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(120.0)
        ->and($this->stockService->getStock($this->produkB, $this->gudang))->toBe(30.0);

    // Cancel opname
    $cancelledSo = $this->cancelAction->execute($so, 'Salah input batch', $this->owner->id);

    expect($cancelledSo->status)->toBe(StatusStockOpname::Dibatalkan)
        ->and($cancelledSo->dibatalkan_by)->toBe($this->owner->id)
        ->and($cancelledSo->alasan_batal)->toBe('Salah input batch')
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(100.0)
        ->and($this->stockService->getStock($this->produkB, $this->gudang))->toBe(50.0);

    // Check reversal mutations
    $reversalMutasiA = MutasiStok::where('referensi', 'Batal '.$so->no_stock_opname)
        ->where('produk_id', $this->produkA->id)
        ->first();
    expect($reversalMutasiA)->not->toBeNull()
        ->and((float) $reversalMutasiA->jumlah)->toBe(-20.0);

    $reversalMutasiB = MutasiStok::where('referensi', 'Batal '.$so->no_stock_opname)
        ->where('produk_id', $this->produkB->id)
        ->first();
    expect($reversalMutasiB)->not->toBeNull()
        ->and((float) $reversalMutasiB->jumlah)->toBe(20.0);
});

test('TEST 5: cannot cancel already cancelled stock opname', function () {
    $this->actingAs($this->owner);

    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 105],
        ],
    ]);

    $this->cancelAction->execute($so, 'Batal pertama', $this->owner->id);

    expect(fn () => $this->cancelAction->execute($so, 'Batal kedua', $this->owner->id))
        ->toThrow(InvalidArgumentException::class);
});

test('TEST 6: validation rejects negative physical stock or duplicate products', function () {
    $this->actingAs($this->owner);

    // Negative physical stock
    expect(fn () => $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => -5],
        ],
    ]))->toThrow(InvalidArgumentException::class);

    // Duplicate products
    expect(fn () => $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 10],
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 20],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 7: Livewire StockOpnameIndex renders, filters, and searches', function () {
    $this->actingAs($this->owner);

    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'catatan' => 'Audit kuartal',
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 100],
        ],
    ]);

    Livewire::test(StockOpnameIndex::class)
        ->assertStatus(200)
        ->assertSee($so->no_stock_opname)
        ->assertSee('Gudang Utama')
        ->set('search', 'Audit kuartal')
        ->assertSee($so->no_stock_opname)
        ->set('search', 'NonExistentDoc')
        ->assertDontSee($so->no_stock_opname);
});

test('TEST 8: Livewire StockOpnameCreate saves draft and complete correctly', function () {
    $this->actingAs($this->owner);

    Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->set('selectedProdukId', $this->produkA->id)
        ->call('addProduct')
        ->set('items.0.stok_fisik', 110)
        ->call('saveComplete')
        ->assertRedirect();

    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(110.0);
});

test('TEST 9: Livewire StockOpnameDetail allows cancellation by authorized user', function () {
    $this->actingAs($this->owner);

    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 125],
        ],
    ]);

    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(125.0);

    Livewire::test(StockOpnameDetail::class, ['id' => $so->id])
        ->assertStatus(200)
        ->assertSee($so->no_stock_opname)
        ->set('alasan_batal', 'Koreksi data fisik')
        ->call('cancelStockOpname')
        ->assertRedirect();

    expect($so->fresh()->status)->toBe(StatusStockOpname::Dibatalkan)
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(100.0);
});

test('TEST 10: unauthorized user cannot access stock opname module', function () {
    $this->actingAs($this->unauthorizedUser);

    Livewire::test(StockOpnameIndex::class)
        ->assertForbidden();

    Livewire::test(StockOpnameCreate::class)
        ->assertForbidden();
});

test('TEST 11: stale snapshot protection rejects draft completion if intermediate movements occurred', function () {
    $this->actingAs($this->owner);

    // 1. Create draft when stock A=100
    $draft = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Draft,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 95], // Stored snapshot = 100
        ],
    ]);

    expect((float) $draft->details->first()->stok_sistem)->toBe(100.0);

    // 2. Intermediate transaction: stock A decreases 100 -> 80 (e.g. sales)
    $this->stockService->decrease($this->produkA, $this->gudang, 20, JenisMutasiStok::PenjualanKeluar, 'PJ-2026-000001');
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(80.0);

    // 3. Attempt to finalize draft without reconciling snapshot -> must be rejected/blocked!
    expect(fn () => $this->completeAction->execute($draft))
        ->toThrow(InvalidArgumentException::class);

    // Stock must remain intact at 80, draft remains Draft
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(80.0)
        ->and($draft->fresh()->status)->toBe(StatusStockOpname::Draft);
});

test('TEST 12: decimal precision up to 3 decimal places is handled accurately', function () {
    $this->actingAs($this->owner);

    // Set stock to decimal 15.375
    $this->stockService->increase($this->produkA, $this->gudang, 15.375, JenisMutasiStok::PenyesuaianOpname, 'Decimal Test');
    // Total stock now 100 + 15.375 = 115.375

    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 120.875], // Difference +5.500
        ],
    ]);

    $detail = $so->details->first();
    expect((float) $detail->stok_sistem)->toBe(115.375)
        ->and((float) $detail->stok_fisik)->toBe(120.875)
        ->and((float) $detail->selisih)->toBe(5.500)
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(120.875);
});

test('TEST 13: cancellation scenario with subsequent sales preserves intermediate transactions (100 -> opname 95 -> sale 85 -> cancel 90)', function () {
    $this->actingAs($this->owner);

    // Initial stock = 100
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(100.0);

    // 1. Stock Opname: physical = 95 (difference = -5)
    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 95],
        ],
    ]);

    // Stock after opname: 100 -> 95
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(95.0);

    // 2. Subsequent sales transaction: -10
    $this->stockService->decrease($this->produkA, $this->gudang, 10, JenisMutasiStok::PenjualanKeluar, 'PJ-2026-000002');

    // Current stock after sales: 95 -> 85
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(85.0);

    // 3. Cancel Stock Opname
    $cancelledSo = $this->cancelAction->execute($so, 'Pembatalan audit berkala', $this->owner->id);

    // Expected final stock: 85 + 5 = 90 (NOT 100!)
    expect($cancelledSo->status)->toBe(StatusStockOpname::Dibatalkan)
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(90.0);

    // Verify mutations ledger:
    // Original opname mutation (-5) remains
    $originalMutasi = MutasiStok::where('referensi', $so->no_stock_opname)
        ->where('produk_id', $this->produkA->id)
        ->first();
    expect($originalMutasi)->not->toBeNull()
        ->and((float) $originalMutasi->jumlah)->toBe(-5.0);

    // Sales mutation (-10) remains
    $salesMutasi = MutasiStok::where('referensi', 'PJ-2026-000002')
        ->where('produk_id', $this->produkA->id)
        ->first();
    expect($salesMutasi)->not->toBeNull()
        ->and((float) $salesMutasi->jumlah)->toBe(-10.0);

    // Reversal mutation (+5) is recorded
    $reversalMutasi = MutasiStok::where('referensi', 'Batal '.$so->no_stock_opname)
        ->where('produk_id', $this->produkA->id)
        ->first();
    expect($reversalMutasi)->not->toBeNull()
        ->and((float) $reversalMutasi->jumlah)->toBe(5.0);
});

test('TEST 14: zero-difference opname cancelled does not create reversal mutation', function () {
    $this->actingAs($this->owner);

    // Initial stock = 100
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(100.0);

    // Opname with exact match (physical = 100, difference = 0)
    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 100],
        ],
    ]);

    $mutasiCountBefore = MutasiStok::count();

    // Cancel opname
    $cancelledSo = $this->cancelAction->execute($so, 'Batal opname cocok', $this->owner->id);

    expect($cancelledSo->status)->toBe(StatusStockOpname::Dibatalkan)
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(100.0)
        ->and(MutasiStok::count())->toBe($mutasiCountBefore); // No new mutation added
});

test('TEST 15: StockOpnameIndex KPI metrics, date filters, preview modal, and Excel export', function () {
    $this->actingAs($this->owner);

    $so1 = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->subDays(5)->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 110], // +10 diff
        ],
    ]);

    $so2 = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Draft,
        'items' => [
            ['produk_id' => $this->produkB->id, 'stok_fisik' => 45], // -5 diff
        ],
    ]);

    $component = Livewire::test(StockOpnameIndex::class)
        ->assertStatus(200)
        ->assertSee('Total Dokumen Opname')
        ->assertSee($so1->no_stock_opname)
        ->assertSee($so2->no_stock_opname);

    // Test Quick Preview Modal
    $component->call('openQuickModal', $so1->id)
        ->assertSet('isQuickModalOpen', true)
        ->assertSee('Pratinjau: '.$so1->no_stock_opname)
        ->call('closeQuickModal')
        ->assertSet('isQuickModalOpen', false);

    // Test Date Filter
    $component->set('startDate', Carbon::now()->subDays(2)->toDateString())
        ->assertDontSee($so1->no_stock_opname)
        ->assertSee($so2->no_stock_opname)
        ->call('resetFilters')
        ->assertSee($so1->no_stock_opname);

    // Test Excel Export
    $export = $component->call('exportExcel');
    $export->assertFileDownloaded();
});

test('TEST 16: StockOpnameCreate quick search, catalog modal, and batch helpers', function () {
    $this->actingAs($this->owner);

    $test = Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id);

    // Quick Search & Add
    $test->set('quickSearchQuery', 'Papan')
        ->assertCount('quickSearchResults', 1)
        ->call('addQuickProduct', $this->produkA->id)
        ->assertCount('items', 1)
        ->assertSet('quickSearchQuery', '');

    // Bulk Catalog Modal
    $test->call('openCatalogModal')
        ->assertSet('isCatalogModalOpen', true)
        ->call('toggleCatalogProduct', $this->produkB->id)
        ->assertSet('catalogSelectedIds', [$this->produkB->id])
        ->call('addSelectedCatalogProducts')
        ->assertSet('isCatalogModalOpen', false)
        ->assertCount('items', 2);

    // Batch Helpers
    // 1. Clear all physical stock
    $test->call('clearAllPhysicalStock');
    $items = $test->get('items');
    expect($items[0]['stok_fisik'])->toBe(0)
        ->and($items[1]['stok_fisik'])->toBe(0);

    // 2. Set all matched
    $test->call('setAllMatched');
    $itemsMatched = $test->get('items');
    expect((float) $itemsMatched[0]['stok_fisik'])->toBe((float) $itemsMatched[0]['stok_sistem'])
        ->and((float) $itemsMatched[1]['stok_fisik'])->toBe((float) $itemsMatched[1]['stok_sistem']);

    // 3. Clear all items
    $test->call('clearAllItems')
        ->assertCount('items', 0);
});

test('TEST 17: StockOpnameCreate filter tabs and modern confirmation modal', function () {
    $this->actingAs($this->owner);

    $test = Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->produkA->id)
        ->call('addQuickProduct', $this->produkB->id)
        ->set('items.0.stok_fisik', 120) // Diff: +20
        ->set('items.1.stok_fisik', 50); // Match: diff 0

    // Filter tabs
    $test->set('tableFilter', 'diff');
    expect($test->get('filteredItems'))->toHaveCount(1);

    $test->set('tableFilter', 'match');
    expect($test->get('filteredItems'))->toHaveCount(1);

    $test->set('tableFilter', 'all');
    expect($test->get('filteredItems'))->toHaveCount(2);

    // Modern Confirmation Modal
    $test->call('promptConfirmation')
        ->assertSet('isConfirmModalOpen', true)
        ->assertSee('Selesaikan Stok Opname?')
        ->call('closeConfirmationModal')
        ->assertSet('isConfirmModalOpen', false);
});

test('TEST 18: StockOpnameDetail displays product info and opens cancellation modal', function () {
    $this->actingAs($this->owner);

    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Selesai,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 110],
        ],
    ]);

    Livewire::test(StockOpnameDetail::class, ['id' => $so->id])
        ->assertStatus(200)
        ->assertSee($this->produkA->nama)
        ->assertSee('Cetak Berita Acara')
        ->call('promptCancelModal')
        ->assertSet('isCancelModalOpen', true)
        ->assertSee('Batalkan Stok Opname Ini?')
        ->call('closeCancelModal')
        ->assertSet('isCancelModalOpen', false);
});

test('TEST 19: user can permanently delete draft stock opname from detail page', function () {
    $this->actingAs($this->owner);

    $draft = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Draft,
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 150],
        ],
    ]);

    expect(StockOpname::where('id', $draft->id)->exists())->toBeTrue()
        ->and(DetailStockOpname::where('stock_opname_id', $draft->id)->exists())->toBeTrue();

    Livewire::test(StockOpnameDetail::class, ['id' => $draft->id])
        ->assertStatus(200)
        ->assertSee('Hapus Draft')
        ->call('promptDeleteDraftModal')
        ->assertSet('isDeleteDraftModalOpen', true)
        ->assertSee('Hapus Draft Opname?')
        ->call('deleteDraft')
        ->assertRedirect(route('inventory.stock-opname.index'));

    expect(StockOpname::where('id', $draft->id)->exists())->toBeFalse()
        ->and(DetailStockOpname::where('stock_opname_id', $draft->id)->exists())->toBeFalse()
        ->and($this->stockService->getStock($this->produkA, $this->gudang))->toBe(100.0);
});

test('TEST 20: user can delete draft stock opname directly from index page', function () {
    $this->actingAs($this->owner);

    $draft = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'status' => StatusStockOpname::Draft,
        'items' => [
            ['produk_id' => $this->produkB->id, 'stok_fisik' => 60],
        ],
    ]);

    Livewire::test(StockOpnameIndex::class)
        ->assertStatus(200)
        ->assertSee($draft->no_stock_opname)
        ->call('promptDeleteDraft', $draft->id)
        ->assertSet('isDeleteDraftModalOpen', true)
        ->assertSee('Hapus Draft Opname?')
        ->call('executeDeleteDraft')
        ->assertSet('isDeleteDraftModalOpen', false);

    expect(StockOpname::where('id', $draft->id)->exists())->toBeFalse()
        ->and(DetailStockOpname::where('stock_opname_id', $draft->id)->exists())->toBeFalse();
});

test('TEST 21: Smart Packaging Calculator accurately computes physical stock for PVC (Dus & Lembar to Meter) and Sekrup (Dus & Pcs)', function () {
    $this->actingAs($this->owner);

    $pvcProduk = Produk::factory()->create([
        'nama' => 'Plafon PVC Glossy 4m',
        'kode_katalog' => 'PVC-GL-001',
        'satuan' => 'meter',
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 15,
        'panjang_meter' => 4.00,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
    ]);

    $sekrupProduk = Produk::factory()->create([
        'nama' => 'Sekrup Gypsum 6x1',
        'kode_katalog' => 'SKR-001',
        'satuan' => 'pcs',
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 1000,
        'panjang_meter' => null,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
    ]);

    $pakuProduk = Produk::factory()->create([
        'nama' => 'Paku Beton Marabu 2 inch (100 pcs)',
        'kode_katalog' => 'PKU-001',
        'satuan' => SatuanProduk::Dus,
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 100,
        'panjang_meter' => null,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
    ]);

    $kasaProduk = Produk::factory()->create([
        'nama' => 'Kasa Gypsum Fiber Mesh Tape Perekat 2 inch x 90m',
        'kode_katalog' => 'KSA-GY-090',
        'satuan' => SatuanProduk::Meter,
        'kemasan' => 'Roll',
        'isi_per_kemasan' => 90,
        'panjang_per_roll' => 90,
        'panjang_meter' => null,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
    ]);

    // Test in StockOpnameCreate
    $comp = Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->set('selectedProdukId', $pvcProduk->id)
        ->call('addProduct')
        ->set('selectedProdukId', $sekrupProduk->id)
        ->call('addProduct')
        ->set('selectedProdukId', $pakuProduk->id)
        ->call('addProduct')
        ->set('selectedProdukId', $kasaProduk->id)
        ->call('addProduct');

    // 1. PVC Calculator: 10 Dus + 3 Lembar @ 4m = (10 * 15 * 4) + (3 * 4) = 600 + 12 = 612 meters
    $comp->call('openPackagingCalculator', 0)
        ->assertSet('isPackagingCalcOpen', true)
        ->assertSet('calcItemName', 'Plafon PVC Glossy 4m')
        ->assertSet('calcIsiPerKemasan', 15.0)
        ->assertSet('calcPanjangMeter', 4.0)
        ->set('calcJmlKemasan', 10)
        ->set('calcJmlEceran', 3);

    expect($comp->get('computedPhysicalStock'))->toBe(612.0);

    $comp->call('applyPackagingCalculation')
        ->assertSet('isPackagingCalcOpen', false);

    expect($comp->get('items.0.stok_fisik'))->toBe(612.0);

    // 2. Sekrup Calculator (Satuan pcs, Kemasan Dus 1000): 2 Dus + 350 Pcs = (2 * 1000) + 350 = 2350 pcs
    $comp->call('openPackagingCalculator', 1)
        ->assertSet('isPackagingCalcOpen', true)
        ->set('calcJmlKemasan', 2)
        ->set('calcJmlEceran', 350);

    expect($comp->get('computedPhysicalStock'))->toBe(2350.0);

    $comp->call('applyPackagingCalculation')
        ->assertSet('isPackagingCalcOpen', false);

    expect($comp->get('items.1.stok_fisik'))->toBe(2350.0);

    // 3. Paku Beton Calculator (Satuan Dus, Kemasan Dus 100 pcs): 6 Dus + 8 pcs eceran = 6 + (8/100) = 6.08 Dus
    $comp->call('openPackagingCalculator', 2)
        ->assertSet('isPackagingCalcOpen', true)
        ->set('calcJmlKemasan', 6)
        ->set('calcJmlEceran', 8);

    expect($comp->get('computedPhysicalStock'))->toBe(6.08);

    $comp->call('applyPackagingCalculation')
        ->assertSet('isPackagingCalcOpen', false);

    expect($comp->get('items.2.stok_fisik'))->toBe(6.08);

    // 4. Kasa Gypsum Roll Calculator (Satuan Meter, Kemasan Roll 90m): 1 Roll + 6m = (1 * 90) + 6 = 96 meters (NOT 8640m)
    $comp->call('openPackagingCalculator', 3)
        ->assertSet('isPackagingCalcOpen', true)
        ->assertSet('calcPanjangMeter', null)
        ->assertSet('calcIsiPerKemasan', 90.0)
        ->set('calcJmlKemasan', 1)
        ->set('calcJmlEceran', 6);

    expect($comp->get('computedPhysicalStock'))->toBe(96.0);

    $comp->call('applyPackagingCalculation')
        ->assertSet('isPackagingCalcOpen', false);

    expect($comp->get('items.3.stok_fisik'))->toBe(96.0);
});

test('TEST 22: Smart Bulk Load filters products by category and active warehouse stock > 0', function () {
    $this->actingAs($this->owner);

    $kategoriLain = Kategori::factory()->create(['nama_kategori' => 'Rangka Plafon']);
    $produkD = Produk::factory()->create([
        'kategori_id' => $kategoriLain->id,
        'nama' => 'Hollow Besi 2x4',
    ]);

    // Initial stock: A=100 (Plafon PVC), B=50 (Plafon PVC), C=30 (Plafon PVC), D=0 (Rangka Plafon)

    $comp = Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->call('openBulkLoadModal')
        ->assertSet('isBulkLoadModalOpen', true)
        ->set('bulkLoadKategoriId', $this->kategori->id)
        ->set('bulkLoadOnlyWithStock', true)
        ->set('bulkLoadMode', 'replace')
        ->call('executeBulkLoad')
        ->assertSet('isBulkLoadModalOpen', false);

    $items = $comp->get('items');
    $productIds = array_column($items, 'produk_id');

    expect($productIds)->toContain($this->produkA->id)
        ->and($productIds)->toContain($this->produkB->id)
        ->and($productIds)->toContain($this->produkC->id)
        ->and($productIds)->not->toContain($produkD->id);
});

test('TEST 23: Count sheet print modal toggles between blind and guided modes', function () {
    $this->actingAs($this->owner);

    Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->call('openPrintCountSheetModal')
        ->assertSet('isPrintCountSheetModalOpen', true)
        ->set('countSheetMode', 'blind')
        ->assertSee('BLIND COUNT (HITUNG BUTA)')
        ->set('countSheetMode', 'guided')
        ->assertSee('GUIDED COUNT')
        ->call('closePrintCountSheetModal')
        ->assertSet('isPrintCountSheetModalOpen', false);
});

test('TEST 24: StockOpnameExcelService exports template and parses uploaded count sheet', function () {
    $this->actingAs($this->owner);

    $excelService = app(StockOpnameExcelService::class);

    $items = [
        [
            'produk_id' => $this->produkA->id,
            'nama_produk' => $this->produkA->nama,
            'kode_produk' => $this->produkA->kode_katalog,
            'satuan' => 'Pcs',
            'stok_sistem' => 100.0,
            'stok_fisik' => 100.0,
            'catatan' => '',
        ],
        [
            'produk_id' => $this->produkB->id,
            'nama_produk' => $this->produkB->nama,
            'kode_produk' => $this->produkB->kode_katalog,
            'satuan' => 'Pcs',
            'stok_sistem' => 50.0,
            'stok_fisik' => 50.0,
            'catatan' => '',
        ],
    ];

    // 1. Export template test
    $response = $excelService->exportCountSheetTemplate('Gudang Utama', $items, false);
    expect($response->getStatusCode())->toBe(200);

    // 2. Prepare test file simulating edited excel count sheet
    $tempFile = tempnam(sys_get_temp_dir(), 'test_so_imp_').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($tempFile);
    $writer->addRow(Row::fromValues(StockOpnameExcelService::HEADERS));
    $writer->addRow(Row::fromValues([
        1,
        $this->produkA->kode_katalog,
        $this->produkA->nama,
        'Plafon PVC',
        'Natural PVC',
        'Pcs',
        '-',
        '-',
        '-',
        100,
        115, // Updated physical stock
        'Ada sisa di rak belakang',
    ]));
    $writer->addRow(Row::fromValues([
        2,
        $this->produkB->kode_katalog,
        $this->produkB->nama,
        'Plafon PVC',
        'Natural PVC',
        'Pcs',
        '-',
        '-',
        '-',
        50,
        45, // Updated physical stock
        'Hilang / rusak 5 pcs',
    ]));
    $writer->close();

    $uploadedFile = UploadedFile::fake()->createWithContent('count_sheet.xlsx', file_get_contents($tempFile));

    // 3. Test Livewire Import in StockOpnameCreate
    Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->set('items', $items)
        ->set('excelCountFile', $uploadedFile)
        ->call('importExcelCountSheet')
        ->assertHasNoErrors()
        ->assertSet('items.0.stok_fisik', 115.0)
        ->assertSet('items.0.catatan', 'Ada sisa di rak belakang')
        ->assertSet('items.1.stok_fisik', 45.0)
        ->assertSet('items.1.catatan', 'Hilang / rusak 5 pcs');

    if (file_exists($tempFile)) {
        @unlink($tempFile);
    }
});

test('TEST 25: StockOpnameExcelService exportHasilOpname exports multi-sheet completed reconciliation and index export includes KPI summary', function () {
    $this->actingAs($this->owner);

    $so = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => Carbon::now()->toDateString(),
        'items' => [
            ['produk_id' => $this->produkA->id, 'stok_fisik' => 110],
            ['produk_id' => $this->produkB->id, 'stok_fisik' => 45],
            ['produk_id' => $this->produkC->id, 'stok_fisik' => 30],
        ],
    ]);

    $excelService = app(StockOpnameExcelService::class);
    $response = $excelService->exportHasilOpname($so->fresh(['details.produk.kategori', 'details.produk.merek']));
    expect($response->getStatusCode())->toBe(200);

    Livewire::test(StockOpnameDetail::class, ['id' => $so->id])
        ->call('exportTemplate')
        ->assertFileDownloaded();

    Livewire::test(StockOpnameIndex::class)
        ->call('exportExcel')
        ->assertFileDownloaded();
});

test('TEST 26: StockOpnameCreate catalog modal filters products by brand, category, and quick brand pills', function () {
    $this->actingAs($this->owner);

    $merekLain = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $kategoriLain = Kategori::factory()->create(['nama_kategori' => 'Papan Gypsum']);

    $produkD = Produk::factory()->create([
        'kategori_id' => $kategoriLain->id,
        'merek_id' => $merekLain->id,
        'nama' => 'Papan Jayaboard 9mm',
    ]);

    $test = Livewire::test(StockOpnameCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->call('openCatalogModal')
        ->assertSet('isCatalogModalOpen', true)
        ->assertSet('catalogMerekId', null)
        ->assertSet('catalogKategoriId', null)
        ->assertSee('Papan PVC 4m')
        ->assertSee('Papan Jayaboard 9mm');

    // Filter by Merek (Brand)
    $test->set('catalogMerekId', $merekLain->id)
        ->assertSee('Papan Jayaboard 9mm')
        ->assertDontSee('Papan PVC 4m');

    // Quick brand pill toggle
    $test->call('setCatalogMerek', $this->merek->id)
        ->assertSet('catalogMerekId', $this->merek->id)
        ->assertSee('Papan PVC 4m')
        ->assertDontSee('Papan Jayaboard 9mm');

    // Toggle same brand again clears filter
    $test->call('setCatalogMerek', $this->merek->id)
        ->assertSet('catalogMerekId', null)
        ->assertSee('Papan Jayaboard 9mm')
        ->assertSee('Papan PVC 4m');

    // Filter by Kategori
    $test->set('catalogKategoriId', $kategoriLain->id)
        ->assertSee('Papan Jayaboard 9mm')
        ->assertDontSee('Papan PVC 4m');

    // Close modal resets filters
    $test->call('closeCatalogModal')
        ->assertSet('isCatalogModalOpen', false)
        ->assertSet('catalogMerekId', null)
        ->assertSet('catalogKategoriId', null);
});
