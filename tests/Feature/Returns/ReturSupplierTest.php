<?php

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Returns\Actions\CancelReturSupplierAction;
use App\Domains\Returns\Actions\CreateReturSupplierAction;
use App\Domains\Returns\Models\ReturSupplier;
use App\Livewire\Returns\ReturSupplierCreate;
use App\Livewire\Returns\ReturSupplierIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('Staff Gudang');

    $this->supplier = Supplier::factory()->create(['nama_supplier' => 'PT Mitra Supplier']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Papan PVC Cacat Motif',
    ]);

    $this->stockService = app(StockService::class);
    $this->createAction = app(CreateReturSupplierAction::class);
    $this->cancelAction = app(CancelReturSupplierAction::class);

    // Initial stock in warehouse: 50
    $this->stockService->increase($this->produk, $this->gudang, 50, JenisMutasiStok::PenyesuaianOpname, 'Initial Stock', userId: $this->owner->id);
});

test('TEST 1: successful return to supplier deducts warehouse stock and generates RS-YYYY-000001', function () {
    $retur = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'jumlah' => 15,
        'alasan' => 'Motif cacat dan tergores',
        'user_id' => $this->owner->id,
    ]);

    expect($retur)->toBeInstanceOf(ReturSupplier::class)
        ->and($retur->no_retur)->toMatch('/^RS-2026-\d{6}$/')
        ->and((float) $retur->jumlah)->toBe(15.0);

    // Verify stock deducted
    $stock = Stok::where('produk_id', $this->produk->id)->where('gudang_id', $this->gudang->id)->value('jumlah');
    expect((float) $stock)->toBe(35.0); // 50 - 15

    // Verify mutation
    $mutasi = MutasiStok::where('referensi_type', 'retur_supplier')->where('referensi_id', $retur->id)->first();
    expect($mutasi)->not->toBeNull()
        ->and($mutasi->jenis)->toBe(JenisMutasiStok::ReturKeSupplier)
        ->and((float) $mutasi->jumlah)->toBe(-15.0);
});

test('TEST 2: return to supplier with optional pembelian_id linkage', function () {
    $pembelian = Pembelian::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->owner->id,
        'no_pembelian' => 'PB-2026-000001',
    ]);

    $retur = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'pembelian_id' => $pembelian->id,
        'tanggal' => '2026-09-17',
        'jumlah' => 10,
        'user_id' => $this->owner->id,
    ]);

    expect($retur->pembelian_id)->toBe($pembelian->id)
        ->and($retur->pembelian->no_pembelian)->toBe('PB-2026-000001');
});

test('TEST 3: return to supplier fails when quantity is zero or negative', function () {
    $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'jumlah' => 0,
    ]);
})->throws(InvalidArgumentException::class);

test('TEST 4: return to supplier cancellation restores warehouse stock', function () {
    $retur = $this->createAction->execute([
        'supplier_id' => $this->supplier->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'jumlah' => 20,
        'user_id' => $this->owner->id,
    ]);

    expect((float) Stok::where('produk_id', $this->produk->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(30.0);

    $cancelled = $this->cancelAction->execute($retur, $this->owner->id);
    expect($cancelled->trashed())->toBeTrue();

    // Stock restored to 50
    expect((float) Stok::where('produk_id', $this->produk->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(50.0);
});

test('TEST 5: Livewire ReturSupplier components work smoothly', function () {
    $this->actingAs($this->staffGudang);

    Livewire::test(ReturSupplierIndex::class)->assertOk();

    Livewire::test(ReturSupplierCreate::class)
        ->set('supplier_id', $this->supplier->id)
        ->set('produk_id', $this->produk->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('jumlah', 5)
        ->set('alasan', 'Rusak fisik')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();
});
