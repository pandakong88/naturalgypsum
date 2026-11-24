<?php

use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Actions\CancelTransferGudangAction;
use App\Domains\Inventory\Actions\CreateTransferGudangAction;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Models\TransferGudang;
use App\Domains\Inventory\Services\StockService;
use App\Livewire\Inventory\TransferCreate;
use App\Livewire\Inventory\TransferDetail;
use App\Livewire\Inventory\TransferIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('Staff Gudang');

    $this->unauthorizedUser = User::factory()->create();

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural PVC']);

    $this->gudangAsal = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->gudangTujuan = Gudang::factory()->create(['nama_gudang' => 'Gudang Cabang']);

    $this->produkA = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Papan PVC Putih 4m',
    ]);

    $this->produkB = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'List PVC 3m',
    ]);

    $this->stockService = app(StockService::class);
    $this->createAction = app(CreateTransferGudangAction::class);
    $this->cancelAction = app(CancelTransferGudangAction::class);

    // Seed initial stock in gudangAsal
    $this->stockService->increase($this->produkA, $this->gudangAsal, 100, JenisMutasiStok::PenyesuaianOpname, 'Initial Stock', userId: $this->owner->id);
    $this->stockService->increase($this->produkB, $this->gudangAsal, 50, JenisMutasiStok::PenyesuaianOpname, 'Initial Stock', userId: $this->owner->id);
});

test('TEST 1: successful transfer creation decreases source and increases destination', function () {
    $transfer = $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'keterangan' => 'Transfer stok proyek A',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 20],
            ['produk_id' => $this->produkB->id, 'jumlah' => 10],
        ],
        'user_id' => $this->owner->id,
    ]);

    expect($transfer)->toBeInstanceOf(TransferGudang::class)
        ->and($transfer->no_transfer)->toMatch('/^TR-2026-\d{6}$/')
        ->and($transfer->details)->toHaveCount(2);

    // Verify stock levels
    $stockAsalA = Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangAsal->id)->value('jumlah');
    $stockTujuanA = Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangTujuan->id)->value('jumlah');
    expect((float) $stockAsalA)->toBe(80.0)
        ->and((float) $stockTujuanA)->toBe(20.0);

    // Verify mutation records
    $mutations = MutasiStok::where('referensi_type', 'transfer_gudang')->where('referensi_id', $transfer->id)->get();
    expect($mutations)->toHaveCount(4); // 2 per item: 1 negative (keluar) + 1 positive (masuk)
});

test('TEST 2: transfer validation fails when same source and destination warehouse', function () {
    $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangAsal->id,
        'tanggal' => '2026-09-17',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 10],
        ],
    ]);
})->throws(InvalidArgumentException::class);

test('TEST 3: transfer validation fails when items are empty', function () {
    $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'items' => [],
    ]);
})->throws(InvalidArgumentException::class);

test('TEST 4: transfer fails with InvalidArgumentException when item quantity is zero or negative', function () {
    $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => -5],
        ],
        'user_id' => $this->owner->id,
    ]);
})->throws(InvalidArgumentException::class);

test('TEST 5: cancellation reverses transfer and restores original stock levels', function () {
    $transfer = $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 25],
        ],
        'user_id' => $this->owner->id,
    ]);

    expect((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangAsal->id)->value('jumlah'))->toBe(75.0)
        ->and((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangTujuan->id)->value('jumlah'))->toBe(25.0);

    // Cancel
    $cancelled = $this->cancelAction->execute($transfer, $this->owner->id);

    expect($cancelled->trashed())->toBeTrue();

    // Verify stock restored
    expect((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangAsal->id)->value('jumlah'))->toBe(100.0)
        ->and((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangTujuan->id)->value('jumlah'))->toBe(0.0);
});

test('TEST 6: Livewire TransferIndex renders list with authorization', function () {
    $this->actingAs($this->owner);

    Livewire::test(TransferIndex::class)
        ->assertOk()
        ->assertSee('Transfer Antar Gudang');
});

test('TEST 7: Livewire TransferCreate creates transfer successfully', function () {
    $this->actingAs($this->staffGudang);

    Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->set('tanggal', '2026-09-17')
        ->set('keterangan', 'Transfer melalui UI')
        ->set('items', [
            ['produk_id' => $this->produkA->id, 'jumlah' => 15],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('transfer_gudang', [
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
    ]);
});

test('TEST 8: Livewire TransferDetail displays transfer details and allows cancellation', function () {
    $this->actingAs($this->owner);

    $transfer = $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 10],
        ],
        'user_id' => $this->owner->id,
    ]);

    Livewire::test(TransferDetail::class, ['id' => $transfer->id])
        ->assertOk()
        ->assertSee($transfer->no_transfer)
        ->assertSee('Papan PVC Putih 4m')
        ->call('openCancelModal')
        ->assertSet('isCancelModalOpen', true)
        ->assertSee('Batalkan Transfer Ini?')
        ->assertSee('Dampak Pembatalan')
        ->call('closeCancelModal')
        ->assertSet('isCancelModalOpen', false)
        ->call('openCancelModal')
        ->call('cancel')
        ->assertRedirect(route('transfer.index'));

    expect($transfer->fresh()->trashed())->toBeTrue();
});

test('TEST 9: Livewire TransferIndex displays KPI metrics, quick preview modal, reset filters, and exports Excel', function () {
    $this->actingAs($this->owner);

    $transfer = $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'keterangan' => 'Transfer pengujian metrik',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 25],
            ['produk_id' => $this->produkB->id, 'jumlah' => 15],
        ],
        'user_id' => $this->owner->id,
    ]);

    $component = Livewire::test(TransferIndex::class)
        ->assertSee('Total Dokumen Transfer')
        ->assertSee('Total Variasi SKU')
        ->assertSee('Total Kuantitas Fisik')
        ->assertSee('Rute Transfer Terpopuler')
        ->assertSee('Gudang Utama → Gudang Cabang')
        ->assertSee($transfer->no_transfer)
        // Quick preview modal
        ->call('openQuickModal', $transfer->id)
        ->assertSet('isQuickModalOpen', true)
        ->assertSee('Pratinjau Transfer')
        ->assertSee('Papan PVC Putih 4m')
        ->call('closeQuickModal')
        ->assertSet('isQuickModalOpen', false)
        // Custom cancel modal from index
        ->call('promptCancelTransfer', $transfer->id)
        ->assertSet('isCancelModalOpen', true)
        ->assertSee('Batalkan Transfer Ini?')
        ->call('closeCancelModal')
        ->assertSet('isCancelModalOpen', false)
        // Filter & reset
        ->set('search', $transfer->no_transfer)
        ->assertSee('Reset Filter')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('startDate', '')
        ->assertSet('endDate', '');

    // Excel Export
    $download = $component->call('exportExcel');
    $download->assertFileDownloaded();
});

test('TEST 10: Livewire TransferCreate provides real-time available stock checking, max fill, and warehouse conflict handling', function () {
    $this->actingAs($this->staffGudang);

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->set('items.0.produk_id', $this->produkA->id);

    // Initial stock was 100 in gudangAsal
    expect($component->instance()->getAvailableStock($this->produkA->id))->toBe(100.0);

    // Fill Max Quantity button
    $component->call('fillMaxQuantity', 0);
    expect($component->get('items.0.jumlah'))->toBe('100');
    expect($component->instance()->totalTransferredQuantity)->toBe(100.0);

    // Destination stock check
    expect($component->instance()->getDestinationStock($this->produkA->id))->toBe(0.0);

    // If gudang_tujuan_id same as gudang_asal_id, auto-resets
    $component->set('gudang_tujuan_id', $this->gudangAsal->id)
        ->set('gudang_asal_id', $this->gudangAsal->id);
    expect($component->get('gudang_tujuan_id'))->toBeNull();
});

test('TEST 11: Livewire TransferCreate handles confirmation modal review, duplicates, and onlyAvailableStock filter', function () {
    $this->actingAs($this->staffGudang);

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->set('tanggal', '2026-09-17')
        ->set('items.0.produk_id', $this->produkA->id)
        ->set('items.0.jumlah', 15)
        ->set('onlyAvailableStock', true)
        ->assertSet('onlyAvailableStock', true)
        // Test confirmation modal prompt
        ->call('promptConfirmation')
        ->assertSet('isConfirmModalOpen', true)
        ->assertSee('Konfirmasi Perpindahan Stok')
        ->call('closeConfirmationModal')
        ->assertSet('isConfirmModalOpen', false)
        // Submit via modal
        ->call('promptConfirmation')
        ->call('save')
        ->assertRedirect();

    $this->assertDatabaseHas('transfer_gudang', [
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
    ]);
});

test('TEST 12: Livewire TransferCreate supports quick search, quantity steppers, bulk catalog selection, and item clearing', function () {
    $this->actingAs($this->staffGudang);

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id);

    // 1. Quick search
    $component->set('quickSearchQuery', 'Papan')
        ->assertSee('Papan PVC Putih 4m');
    expect($component->instance()->quickSearchResults)->not->toBeEmpty();

    // 2. Add via quick search
    $component->call('addQuickProduct', $this->produkA->id);
    expect($component->get('items.0.produk_id'))->toBe($this->produkA->id);
    expect((float) $component->get('items.0.jumlah'))->toBe(1.0);
    expect($component->get('quickSearchQuery'))->toBe('');

    // Adding same product again increments quantity
    $component->call('addQuickProduct', $this->produkA->id);
    expect((float) $component->get('items.0.jumlah'))->toBe(2.0);

    // 3. Stepper controls: increment and decrement
    $component->call('incrementQuantity', 0, 3.0);
    expect((float) $component->get('items.0.jumlah'))->toBe(5.0);

    $component->call('decrementQuantity', 0, 2.0);
    expect((float) $component->get('items.0.jumlah'))->toBe(3.0);

    // Decrement doesn't go below 1.0
    $component->call('decrementQuantity', 0, 10.0);
    expect((float) $component->get('items.0.jumlah'))->toBe(1.0);

    // 4. Bulk Catalog Modal
    $component->call('openCatalogModal')
        ->assertSet('isCatalogModalOpen', true)
        ->assertSee('Katalog Produk')
        ->call('toggleCatalogProduct', $this->produkB->id)
        ->assertSet('catalogSelectedProductIds', [$this->produkB->id])
        ->call('addSelectedCatalogProducts')
        ->assertSet('isCatalogModalOpen', false);

    // ProdukB is now in items
    expect(count($component->get('items')))->toBe(2);
    expect($component->get('items.1.produk_id'))->toBe($this->produkB->id);

    // 5. Clear all items
    $component->call('clearAllItems');
    expect(count($component->get('items')))->toBe(1);
    expect($component->get('items.0.produk_id'))->toBeNull();

    // 6. In-Row Select2 selection
    $component->call('selectProductForRow', 0, $this->produkA->id);
    expect($component->get('items.0.produk_id'))->toBe($this->produkA->id);
    expect((float) $component->get('items.0.jumlah'))->toBe(1.0);
});

test('TEST 13: Livewire TransferCreate supports Smart Packaging Calculator modal with dynamic conversion', function () {
    $this->actingAs($this->staffGudang);

    $this->produkA->update([
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.0,
        'isi_per_kemasan' => 15,
        'kemasan' => 'Dus',
    ]);
    $this->stockService->increase($this->produkA, $this->gudangAsal, 100, JenisMutasiStok::PenyesuaianOpname, 'Extra Stock', userId: $this->owner->id);

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->set('items.0.produk_id', $this->produkA->id)
        ->set('items.0.jumlah', '1');

    // Open packaging calculator
    $component->call('openPackagingCalculator', 0)
        ->assertSet('isPackagingCalcOpen', true)
        ->assertSet('calcItemIndex', 0)
        ->assertSet('calcItemName', $this->produkA->nama)
        ->assertSee('Kalkulator Kemasan Fisik Transfer Gudang');

    // Set 2 Dus (2 * 15 * 4m = 120m) and 1 lembar eceran (1 * 4m = 4m) -> Total = 124m
    $component->set('calcJmlKemasan', 2)
        ->set('calcJmlEceran', 1);

    expect($component->get('computedPhysicalTransferQty'))->toBe(124.0)
        ->and($component->get('isCalcQtyOverStock'))->toBeFalse();

    // Apply calculation
    $component->call('applyPackagingCalculation')
        ->assertSet('isPackagingCalcOpen', false);

    expect((float) $component->get('items.0.jumlah'))->toBe(124.0);

    // Verify UI displays the dynamic getKonversiFisik badge
    $component->assertSee('2 Dus + 1 lbr (total 31 lbr)');
});

test('TEST 14: Livewire TransferCreate supports brand / merek filtering in catalog modal and bulk selection', function () {
    $this->actingAs($this->staffGudang);

    $merekVifon = Merek::factory()->create(['nama_merek' => 'Vifon']);
    $produkVifon = Produk::factory()->create([
        'merek_id' => $merekVifon->id,
        'kategori_id' => $this->kategori->id,
        'nama' => 'Plafon PVC Vifon Glossy 4m',
    ]);
    $this->stockService->increase($produkVifon, $this->gudangAsal, 150, JenisMutasiStok::PenyesuaianOpname, 'Initial Stock', userId: $this->owner->id);

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->call('openCatalogModal')
        ->assertSet('isCatalogModalOpen', true)
        ->assertSee('Semua Merek')
        ->assertSee('Vifon')
        ->set('catalogMerekId', $merekVifon->id)
        ->call('selectAllFoundCatalogProducts');

    // Should only select Vifon product, not produkA or produkB
    $selectedIds = $component->get('catalogSelectedProductIds');
    expect($selectedIds)->toContain($produkVifon->id)
        ->and($selectedIds)->not->toContain($this->produkA->id)
        ->and($selectedIds)->not->toContain($this->produkB->id);

    // Add selected to transfer items
    $component->call('addSelectedCatalogProducts');
    expect($component->get('isCatalogModalOpen'))->toBeFalse();

    $itemProductIds = array_column($component->get('items'), 'produk_id');
    expect($itemProductIds)->toContain($produkVifon->id);
});

test('TEST 15: Smart Packaging Calculator guards against transferring more than source warehouse stock', function () {
    $this->actingAs($this->staffGudang);

    $this->produkA->update([
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.0,
        'isi_per_kemasan' => 15,
        'kemasan' => 'Dus',
    ]);
    // Stock is 100 meters. 1 dus = 60 meters. 2 dus = 120 meters (> 100m)

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->set('items.0.produk_id', $this->produkA->id)
        ->set('items.0.jumlah', '10')
        ->call('openPackagingCalculator', 0)
        ->set('calcJmlKemasan', 2); // 120m > 100m

    expect($component->get('isCalcQtyOverStock'))->toBeTrue();

    // Calling applyPackagingCalculation should be blocked
    $component->call('applyPackagingCalculation');

    expect($component->get('isPackagingCalcOpen'))->toBeTrue()
        ->and((float) $component->get('items.0.jumlah'))->toBe(10.0);
});

test('TEST 16: Smart Packaging Calculator fillAllFullBoxesFromSource fills full boxes from source stock', function () {
    $this->actingAs($this->staffGudang);

    $this->produkA->update([
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.0,
        'isi_per_kemasan' => 15,
        'kemasan' => 'Dus',
    ]);
    // Gudang Asal stock: 100 meters.
    // 100 meters / 4m = 25 lembar = 1 dus (15 lbr = 60m) + 10 lembar sisa (40m)
    // full boxes = 1 dus

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->set('items.0.produk_id', $this->produkA->id)
        ->call('openPackagingCalculator', 0)
        ->call('fillAllFullBoxesFromSource');

    expect((float) $component->get('calcJmlKemasan'))->toBe(1.0)
        ->and((float) $component->get('calcJmlEceran'))->toBe(0.0)
        ->and((float) $component->get('computedPhysicalTransferQty'))->toBe(60.0);

    $component->call('applyPackagingCalculation');
    expect((float) $component->get('items.0.jumlah'))->toBe(60.0);
});

test('TEST 17: TransferDetail renders dynamic physical conversion badge instead of hardcoded / 15', function () {
    $this->actingAs($this->owner);

    $this->produkA->update([
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.0,
        'isi_per_kemasan' => 15,
        'kemasan' => 'Dus',
    ]);

    // Transfer 60 meters (1 dus pas)
    $transfer = $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'keterangan' => 'Transfer PVC',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 60],
        ],
        'user_id' => $this->owner->id,
    ]);

    Livewire::test(TransferDetail::class, ['id' => $transfer->id])
        ->assertOk()
        ->assertSee('60,00')
        ->assertSee('1 Dus (15 lbr)');
});

test('TEST 18: Smart Packaging Calculator for Roll product converts rolls and extra meters correctly without pvc double multiplication', function () {
    $this->actingAs($this->staffGudang);

    $kasaRoll = Produk::factory()->create([
        'nama' => 'Kasa Gypsum Fiber Mesh Tape Perekat 2 inch x 90m',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => null,
        'panjang_per_roll' => 90.0,
        'isi_per_kemasan' => 90.0,
        'kemasan' => 'Roll',
    ]);

    // Give gudang asal 200m
    $this->stockService->increase($kasaRoll, $this->gudangAsal, 200, JenisMutasiStok::PenyesuaianOpname, 'Saldo Awal Kasa', userId: $this->owner->id);

    $component = Livewire::test(TransferCreate::class)
        ->set('gudang_asal_id', $this->gudangAsal->id)
        ->set('gudang_tujuan_id', $this->gudangTujuan->id)
        ->set('items.0.produk_id', $kasaRoll->id)
        ->call('openPackagingCalculator', 0);

    expect($component->get('isPackagingCalcOpen'))->toBeTrue()
        ->and($component->get('calcPanjangMeter'))->toBeNull()
        ->and((float) $component->get('calcIsiPerKemasan'))->toBe(90.0)
        ->and($component->get('calcKemasanLabel'))->toBe('Roll');

    // 1 Roll + 6m eceran = 96m (NOT 8640m)
    $component->set('calcJmlKemasan', 1)
        ->set('calcJmlEceran', 6);

    expect((float) $component->get('computedPhysicalTransferQty'))->toBe(96.0)
        ->and($component->get('isCalcQtyOverStock'))->toBeFalse();

    $component->call('applyPackagingCalculation');

    expect((float) $component->get('items.0.jumlah'))->toBe(96.0);
});

test('TEST 19: transfer creation records entry in ActivityLog', function () {
    $transfer = $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'keterangan' => 'Audit log check transfer',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 15],
        ],
        'user_id' => $this->owner->id,
    ]);

    $log = ActivityLog::where('subject_type', TransferGudang::class)
        ->where('subject_id', $transfer->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('transfer')
        ->and($log->user_id)->toBe($this->owner->id)
        ->and($log->subject_label)->toBe($transfer->no_transfer)
        ->and($log->description)->toContain($transfer->no_transfer)
        ->and($log->new_values['no_transfer'])->toBe($transfer->no_transfer)
        ->and($log->new_values['gudang_asal'])->toBe($this->gudangAsal->nama_gudang)
        ->and($log->new_values['gudang_tujuan'])->toBe($this->gudangTujuan->nama_gudang);
});

test('TEST 20: transfer cancellation reverses stock and records entry in ActivityLog', function () {
    $transfer = $this->createAction->execute([
        'gudang_asal_id' => $this->gudangAsal->id,
        'gudang_tujuan_id' => $this->gudangTujuan->id,
        'tanggal' => '2026-09-17',
        'keterangan' => 'Transfer to be cancelled',
        'items' => [
            ['produk_id' => $this->produkA->id, 'jumlah' => 25],
        ],
        'user_id' => $this->owner->id,
    ]);

    // Asal had 100, now 75. Tujuan now has 25.
    expect((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangAsal->id)->value('jumlah'))->toBe(75.0);
    expect((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangTujuan->id)->value('jumlah'))->toBe(25.0);

    $cancelled = $this->cancelAction->execute($transfer, $this->staffGudang->id);

    // Stock reversed: Asal back to 100, Tujuan back to 0
    expect((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangAsal->id)->value('jumlah'))->toBe(100.0);
    expect((float) Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudangTujuan->id)->value('jumlah'))->toBe(0.0);

    $cancelLog = ActivityLog::where('subject_type', TransferGudang::class)
        ->where('subject_id', $transfer->id)
        ->where('action', 'cancel')
        ->first();

    expect($cancelLog)->not->toBeNull()
        ->and($cancelLog->user_id)->toBe($this->staffGudang->id)
        ->and($cancelLog->subject_label)->toBe($transfer->no_transfer)
        ->and($cancelLog->description)->toContain('Membatalkan transfer stok #'.$transfer->no_transfer)
        ->and($cancelLog->old_values['no_transfer'])->toBe($transfer->no_transfer);
});
