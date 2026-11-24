<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Purchasing\Actions\CreatePembelianAction;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use App\Livewire\Purchasing\PembelianCreate;
use App\Livewire\Purchasing\PembelianEdit;
use App\Livewire\Purchasing\PembelianIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('Staff Gudang'); // Has view_purchases, but NOT view_purchase_prices or create_purchases

    $this->unauthorizedUser = User::factory()->create();

    $this->supplier = Supplier::factory()->create(['nama_supplier' => 'PT Sumber Gypsum']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural PVC']);

    $this->gudangUtama = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->gudangBelakang = Gudang::factory()->create(['nama_gudang' => 'Gudang Belakang']);

    $this->produkA = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Papan Gypsum Standar 9mm',
        'satuan' => SatuanProduk::Lembar,
        'harga_beli' => 65000.00,
    ]);

    $this->produkB = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'PVC Doff Putih 4m',
        'harga_beli' => 28000.00,
    ]);

    $this->action = app(CreatePembelianAction::class);
});

test('TEST 1: successful purchase creation with multiple items', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'no_kontainer' => 'CONT-12345',
        'keterangan' => 'Pembelian batch 1 kontainer',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 100,
                'harga_beli_satuan' => 65000,
            ],
            [
                'produk_id' => $this->produkB->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 200,
                'harga_beli_satuan' => 28000,
            ],
        ],
    ]);

    expect($pembelian)->toBeInstanceOf(Pembelian::class)
        ->and($pembelian->supplier_id)->toBe($this->supplier->id)
        ->and($pembelian->no_kontainer)->toBe('CONT-12345')
        ->and($pembelian->details)->toHaveCount(2)
        ->and((float) $pembelian->total)->toBe(12100000.00); // (100*65000) + (200*28000) = 6500000 + 5600000 = 12100000

    $this->assertDatabaseHas('pembelian', [
        'id' => $pembelian->id,
        'supplier_id' => $this->supplier->id,
        'no_kontainer' => 'CONT-12345',
        'total' => 12100000.00,
    ]);

    $this->assertDatabaseHas('detail_pembelian', [
        'pembelian_id' => $pembelian->id,
        'produk_id' => $this->produkA->id,
        'gudang_id' => $this->gudangUtama->id,
        'jumlah' => 100.000,
        'harga_beli_satuan' => 65000.00,
        'subtotal' => 6500000.00,
    ]);
});

test('TEST 2: sequential PB-YYYY-000001 document number generation', function () {
    $pembelian1 = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]);

    $pembelian2 = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkB->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 20,
                'harga_beli_satuan' => 30000,
            ],
        ],
    ]);

    expect($pembelian1->no_pembelian)->toBe('PB-2026-000001')
        ->and($pembelian2->no_pembelian)->toBe('PB-2026-000002');
});

test('TEST 3: stock service increase called for each detail line item', function () {
    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 75,
                'harga_beli_satuan' => 60000,
            ],
        ],
    ]);

    $stok = Stok::where('produk_id', $this->produkA->id)
        ->where('gudang_id', $this->gudangUtama->id)
        ->first();

    expect($stok)->not->toBeNull()
        ->and((float) $stok->jumlah)->toBe(75.000);
});

test('TEST 4: stock ledger mutasi_stok populated with pembelian_masuk and correct amounts', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 120,
                'harga_beli_satuan' => 60000,
            ],
        ],
    ]);

    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->produkA->id,
        'gudang_id' => $this->gudangUtama->id,
        'jenis' => JenisMutasiStok::PembelianMasuk->value,
        'jumlah' => 120.000,
        'referensi' => $pembelian->no_pembelian,
    ]);
});

test('TEST 5: reference metadata recorded (referensi_type = pembelian, referensi_id = pembelian->id)', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 50,
                'harga_beli_satuan' => 60000,
            ],
        ],
    ]);

    $mutasi = MutasiStok::where('referensi', $pembelian->no_pembelian)->first();

    expect($mutasi)->not->toBeNull()
        ->and($mutasi->referensi_type)->toBe('pembelian')
        ->and($mutasi->referensi_id)->toBe($pembelian->id);
});

test('TEST 6: multi-warehouse distribution in a single purchase transaction', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 500,
                'harga_beli_satuan' => 65000,
            ],
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangBelakang->id,
                'jumlah' => 300,
                'harga_beli_satuan' => 65000,
            ],
        ],
    ]);

    $stokUtama = Stok::where('produk_id', $this->produkA->id)
        ->where('gudang_id', $this->gudangUtama->id)
        ->first();

    $stokBelakang = Stok::where('produk_id', $this->produkA->id)
        ->where('gudang_id', $this->gudangBelakang->id)
        ->first();

    expect((float) $stokUtama->jumlah)->toBe(500.000)
        ->and((float) $stokBelakang->jumlah)->toBe(300.000)
        ->and((float) $pembelian->total)->toBe(52000000.00); // (500*65000) + (300*65000)
});

test('TEST 7: total and subtotal backend computation accuracy', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 12.5,
                'harga_beli_satuan' => 65432.10,
            ],
        ],
    ]);

    $expectedSubtotal = round(12.5 * 65432.10, 2); // 817901.25

    expect((float) $pembelian->details->first()->subtotal)->toBe($expectedSubtotal)
        ->and((float) $pembelian->total)->toBe($expectedSubtotal);
});

test('TEST 8: total tampering resistance (client side total ignored and recalculated on backend)', function () {
    // Livewire component test
    $this->actingAs($this->owner);

    Livewire::test(PembelianCreate::class)
        ->set('supplier_id', $this->supplier->id)
        ->set('tanggal', '2026-09-17')
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 50000,
                'subtotal' => 1, // Tampered client subtotal
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $pembelian = Pembelian::latest('id')->first();

    // Backend must have calculated total as 10 * 50000 = 500000, NOT 1
    expect((float) $pembelian->total)->toBe(500000.00)
        ->and((float) $pembelian->details->first()->subtotal)->toBe(500000.00);
});

test('TEST 9: validation fails on non-positive quantity (zero or negative)', function () {
    expect(fn () => $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 0,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => -5,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 10: validation fails on negative unit price', function () {
    expect(fn () => $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => -5000,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 11: validation fails on empty detail items', function () {
    expect(fn () => $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 12: inactive or soft-deleted supplier rejection', function () {
    $this->supplier->delete(); // Soft-deleted

    expect(fn () => $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]))->toThrow(ModelNotFoundException::class);
});

test('TEST 13: inactive or soft-deleted product rejection', function () {
    $this->produkA->delete(); // Soft-deleted

    expect(fn () => $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]))->toThrow(ModelNotFoundException::class);
});

test('TEST 14: inactive or soft-deleted warehouse rejection', function () {
    $this->gudangUtama->delete(); // Soft-deleted

    expect(fn () => $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]))->toThrow(ModelNotFoundException::class);
});

test('TEST 15: atomic rollback on failure during stock increase or database exception', function () {
    $initialPurchaseCount = Pembelian::count();
    $initialStockCount = Stok::count();
    $initialMutasiCount = MutasiStok::count();

    try {
        $this->action->execute([
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-09-17',
            'user_id' => $this->owner->id,
            'items' => [
                [
                    'produk_id' => $this->produkA->id,
                    'gudang_id' => $this->gudangUtama->id,
                    'jumlah' => 10,
                    'harga_beli_satuan' => 50000,
                ],
                [
                    'produk_id' => 999999, // Non-existent product will throw ModelNotFoundException
                    'gudang_id' => $this->gudangUtama->id,
                    'jumlah' => 5,
                    'harga_beli_satuan' => 20000,
                ],
            ],
        ]);
    } catch (Throwable $e) {
        // Exception expected
    }

    expect(Pembelian::count())->toBe($initialPurchaseCount)
        ->and(Stok::count())->toBe($initialStockCount)
        ->and(MutasiStok::count())->toBe($initialMutasiCount);
});

test('TEST 16: authorization barrier: unauthorized users cannot access create or view purchases', function () {
    $this->actingAs($this->unauthorizedUser);

    $this->get(route('pembelian.index'))->assertForbidden();
    $this->get(route('pembelian.create'))->assertForbidden();
});

test('TEST 17: sensitive purchase price protection (user without view_purchase_prices cannot see price on index and detail)', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 65000,
            ],
        ],
    ]);

    // Staff Gudang has view_purchases, but NOT view_purchase_prices
    $this->actingAs($this->staffGudang);

    $this->get(route('pembelian.index'))
        ->assertOk()
        ->assertSee('Rp ***')
        ->assertDontSee('650.000');

    $this->get(route('pembelian.show', $pembelian->id))
        ->assertOk()
        ->assertSee('Rp ***')
        ->assertDontSee('650.000');

    // Owner has view_purchase_prices
    $this->actingAs($this->owner);

    $this->get(route('pembelian.index'))
        ->assertOk()
        ->assertSee('650.000,00');

    $this->get(route('pembelian.show', $pembelian->id))
        ->assertOk()
        ->assertSee('650.000,00');
});

test('TEST 18: master product harga_beli is not overwritten when purchase unit price differs', function () {
    $initialMasterPrice = (float) $this->produkA->harga_beli; // 65000

    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 70000, // Different from master price
            ],
        ],
    ]);

    $this->produkA->refresh();
    expect((float) $this->produkA->harga_beli)->toBe($initialMasterPrice);
});

test('TEST 19: stock gatekeeper verification (stok table reflects correct numbers)', function () {
    // Initial purchase 100
    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 100,
                'harga_beli_satuan' => 65000,
            ],
        ],
    ]);

    // Second purchase 50 to same warehouse
    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 50,
                'harga_beli_satuan' => 65000,
            ],
        ],
    ]);

    $stok = Stok::where('produk_id', $this->produkA->id)
        ->where('gudang_id', $this->gudangUtama->id)
        ->first();

    expect((float) $stok->jumlah)->toBe(150.000);
});

test('TEST 20: update_master_harga_beli updates master product harga_beli when set to true', function () {
    expect((float) $this->produkA->harga_beli)->toBe(65000.00);

    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-10-30',
        'user_id' => $this->owner->id,
        'update_master_harga_beli' => true,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 20,
                'harga_beli_satuan' => 72500.00,
            ],
        ],
    ]);

    $this->produkA->refresh();
    expect((float) $this->produkA->harga_beli)->toBe(72500.00);
});

test('TEST 21: PembelianIndex calculates and displays 4 KPI summary cards', function () {
    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-10-30',
        'no_kontainer' => 'CONT-KPI-01',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 50000.00,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    Livewire::test(PembelianIndex::class)
        ->assertSee('Total Nilai Pembelian')
        ->assertSee('Total Transaksi')
        ->assertSee('Sisa Hutang Supplier')
        ->assertSee('PO Menunggu Masuk')
        ->assertSee('500.000');
});

test('TEST 22: PembelianIndex search matches product name and SKU in purchase items', function () {
    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-10-30',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id, // 'Papan Gypsum Standar 9mm'
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 5,
                'harga_beli_satuan' => 60000.00,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    Livewire::test(PembelianIndex::class)
        ->set('search', 'Gypsum Standar')
        ->assertSee($this->supplier->nama_supplier)
        ->set('search', 'ProdukYangTidakAda999')
        ->assertSee('Tidak ada data pembelian yang ditemukan');
});

test('TEST 23: PembelianIndex filters by warehouse and allows filter reset', function () {
    $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-10-30',
        'no_kontainer' => 'CONT-W1',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangBelakang->id,
                'jumlah' => 5,
                'harga_beli_satuan' => 60000.00,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    Livewire::test(PembelianIndex::class)
        ->set('gudangFilter', (string) $this->gudangBelakang->id)
        ->assertSee('CONT-W1')
        ->set('gudangFilter', (string) $this->gudangUtama->id)
        ->assertDontSee('CONT-W1')
        ->call('resetFilters')
        ->assertSet('gudangFilter', '')
        ->assertSet('search', '');
});

test('TEST 24: PembelianIndex dynamic column sorting', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianIndex::class)
        ->call('sortBy', 'tanggal')
        ->assertSet('sortField', 'tanggal')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'tanggal')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'supplier')
        ->assertSet('sortField', 'supplier')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'total')
        ->assertSet('sortField', 'total')
        ->assertSet('sortDirection', 'asc');
});

test('TEST 25: PembelianCreate quick search adds product to line items', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianCreate::class)
        ->set('quickSearchQuery', 'Papan Gypsum')
        ->call('addQuickProduct', $this->produkA->id)
        ->assertSet('items.0.produk_id', $this->produkA->id)
        ->assertSet('items.0.harga_beli_satuan', 65000.00)
        ->assertSet('items.0.jumlah', 1.0);
});

test('TEST 26: PembelianCreate catalog modal selects and adds multiple products', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianCreate::class)
        ->call('openCatalogModal')
        ->assertSet('isCatalogModalOpen', true)
        ->call('toggleCatalogProduct', $this->produkA->id)
        ->call('toggleCatalogProduct', $this->produkB->id)
        ->assertSet('catalogSelectedProductIds', [$this->produkA->id, $this->produkB->id])
        ->call('addSelectedCatalogProducts')
        ->assertSet('isCatalogModalOpen', false)
        ->assertCount('items', 2);
});

test('TEST 27: PembelianCreate applies default warehouse to all items', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianCreate::class)
        ->call('addItem')
        ->call('addItem')
        ->set('items.0.produk_id', $this->produkA->id)
        ->set('items.0.gudang_id', $this->gudangUtama->id)
        ->set('items.1.produk_id', $this->produkB->id)
        ->set('items.1.gudang_id', $this->gudangUtama->id)
        ->set('default_gudang_id', $this->gudangBelakang->id)
        ->call('applyDefaultGudangToAll')
        ->assertSet('items.0.gudang_id', $this->gudangBelakang->id)
        ->assertSet('items.1.gudang_id', $this->gudangBelakang->id);
});

test('TEST 28: PembelianCreate quick supplier creation creates and selects new supplier', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianCreate::class)
        ->call('openQuickSupplierModal')
        ->assertSet('isQuickSupplierModalOpen', true)
        ->set('new_nama_supplier', 'CV Gypsum Anugerah Baru')
        ->set('new_kontak_supplier', '0812-9988-7766')
        ->call('saveQuickSupplier')
        ->assertSet('isQuickSupplierModalOpen', false);

    $newSupplier = Supplier::where('nama_supplier', 'CV Gypsum Anugerah Baru')->first();
    expect($newSupplier)->not->toBeNull()
        ->and($newSupplier->kontak)->toBe('0812-9988-7766');
});

test('TEST 29: PembelianPrintController renders Goods Receipt document and masks prices appropriately', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-10-30',
        'no_kontainer' => 'CONT-PRINT-01',
        'keterangan' => 'Barang masuk kontainer dari pabrik',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 65000.00,
            ],
        ],
    ]);

    // Owner (has view_purchase_prices) sees price and Terbilang
    $this->actingAs($this->owner);
    $responseOwner = $this->get(route('pembelian.print.faktur', $pembelian->id));

    $responseOwner->assertOk()
        ->assertSee('BUKTI PENERIMAAN BARANG')
        ->assertSee($pembelian->no_pembelian)
        ->assertSee('CONT-PRINT-01')
        ->assertSee($this->supplier->nama_supplier)
        ->assertSee($this->produkA->nama)
        ->assertSee('Enam Ratus Lima Puluh Ribu Rupiah')
        ->assertSee('650.000,00');

    // Staff Gudang (has view_purchases but NOT view_purchase_prices) does NOT see price numbers
    $this->actingAs($this->staffGudang);
    $responseStaff = $this->get(route('pembelian.print.faktur', $pembelian->id));

    $responseStaff->assertOk()
        ->assertSee('BUKTI PENERIMAAN BARANG')
        ->assertSee($pembelian->no_pembelian)
        ->assertDontSee('Enam Ratus Lima Puluh Ribu Rupiah')
        ->assertDontSee('650.000,00');
});

test('TEST 30: Guest or unauthorized user cannot access print Goods Receipt', function () {
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-10-30',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 5,
                'harga_beli_satuan' => 65000.00,
            ],
        ],
    ]);

    // Guest redirected to login
    $this->get(route('pembelian.print.faktur', $pembelian->id))
        ->assertRedirect(route('login'));

    // Unauthorized user gets 403 Forbidden
    $this->actingAs($this->unauthorizedUser);
    $this->get(route('pembelian.print.faktur', $pembelian->id))
        ->assertForbidden();
});

test('TEST 31: PembelianCreate duplicates line item with product and price preserved', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianCreate::class)
        ->call('addQuickProduct', $this->produkA->id)
        ->assertCount('items', 1)
        ->call('duplicateItem', 0)
        ->assertCount('items', 2)
        ->assertSet('items.1.produk_id', $this->produkA->id)
        ->assertSet('items.1.harga_beli_satuan', 65000.00);
});

test('TEST 32: PembelianCreate packaging calculator calculates pack and roll conversions correctly', function () {
    $this->actingAs($this->owner);

    // Mode kemasan: 20 ikat x 25 batang = 500 batang
    Livewire::test(PembelianCreate::class)
        ->call('addQuickProduct', $this->produkA->id)
        ->call('openCalculator', 0)
        ->assertSet('isCalculatorModalOpen', true)
        ->set('calcMode', 'kemasan')
        ->set('packCount', 20)
        ->set('packSize', 25)
        ->call('applyCalculation')
        ->assertSet('isCalculatorModalOpen', false)
        ->assertSet('items.0.jumlah', 500.0)
        ->assertSet('items.0.subtotal', 500.0 * 65000.00);

    // Mode roll: 4 roll x 50 meter = 200 meter
    Livewire::test(PembelianCreate::class)
        ->call('addQuickProduct', $this->produkB->id)
        ->call('openCalculator', 0)
        ->set('calcMode', 'roll')
        ->set('rollCount', 4)
        ->set('rollLength', 50)
        ->call('applyCalculation')
        ->assertSet('items.0.jumlah', 200.0);
});

test('TEST 33: PembelianCreate physical summary calculates total quantities by unit accurately', function () {
    $this->actingAs($this->owner);

    $this->produkA->update(['satuan' => SatuanProduk::Lembar]);
    $this->produkB->update(['satuan' => SatuanProduk::Batang]);

    $test = Livewire::test(PembelianCreate::class)
        ->call('addQuickProduct', $this->produkA->id)
        ->call('addQuickProduct', $this->produkB->id)
        ->set('items.0.jumlah', 150)
        ->set('items.1.jumlah', 75);

    $summary = $test->get('physicalSummary');
    expect($summary)->toBeArray()
        ->and($summary[$this->produkA->satuan->label()])->toBe(150.0)
        ->and($summary[$this->produkB->satuan->label()])->toBe(75.0);
});

test('TEST 34: PembelianCreate clear all items removes all rows and resets state', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianCreate::class)
        ->call('addQuickProduct', $this->produkA->id)
        ->call('addQuickProduct', $this->produkB->id)
        ->assertCount('items', 2)
        ->call('clearAllItems')
        ->assertCount('items', 0)
        ->assertSet('total', 0.0);
});

test('TEST 35: PembelianCreate multi-select catalog can filter by stock status (kritis, habis, aman)', function () {
    $this->actingAs($this->owner);

    // Setup stock minimum and actual quantities
    $this->produkA->update([
        'satuan' => SatuanProduk::Pcs,
        'panjang_meter' => null,
        'stok_minimum' => 20,
    ]);
    Stok::create([
        'produk_id' => $this->produkA->id,
        'gudang_id' => $this->gudangUtama->id,
        'jumlah' => 5,
    ]); // stok = 5 <= 20 (kritis & menipis)

    $this->produkB->update([
        'satuan' => SatuanProduk::Pcs,
        'panjang_meter' => null,
        'stok_minimum' => 10,
    ]);
    Stok::create([
        'produk_id' => $this->produkB->id,
        'gudang_id' => $this->gudangUtama->id,
        'jumlah' => 50,
    ]); // stok = 50 > 10 (aman)

    $produkHabis = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Produk Tanpa Stok',
        'satuan' => SatuanProduk::Pcs,
        'panjang_meter' => null,
        'stok_minimum' => 10,
    ]); // stok = 0 (habis & kritis)

    $component = Livewire::test(PembelianCreate::class)
        ->call('openCatalogModal')
        ->set('catalogStatusStok', 'kritis');

    $viewData = $component->viewData('catalogProducts');
    expect($viewData->pluck('id')->all())->toContain($this->produkA->id, $produkHabis->id)
        ->and($viewData->pluck('id')->all())->not->toContain($this->produkB->id);

    // Filter habis
    $component->set('catalogStatusStok', 'habis');
    $habisData = $component->viewData('catalogProducts');
    expect($habisData->pluck('id')->all())->toContain($produkHabis->id)
        ->and($habisData->pluck('id')->all())->not->toContain($this->produkA->id)
        ->and($habisData->pluck('id')->all())->not->toContain($this->produkB->id);

    // Filter aman
    $component->set('catalogStatusStok', 'aman');
    $amanData = $component->viewData('catalogProducts');
    expect($amanData->pluck('id')->all())->toContain($this->produkB->id)
        ->and($amanData->pluck('id')->all())->not->toContain($this->produkA->id)
        ->and($amanData->pluck('id')->all())->not->toContain($produkHabis->id);

    // selectAllFoundCatalogProducts with kritis filter
    $component->set('catalogStatusStok', 'kritis')
        ->call('selectAllFoundCatalogProducts');

    expect($component->get('catalogSelectedProductIds'))->toContain($this->produkA->id, $produkHabis->id)
        ->and($component->get('catalogSelectedProductIds'))->not->toContain($this->produkB->id);

    // Add selected items to order
    $component->call('addSelectedCatalogProducts');
    expect($component->get('items'))->toHaveCount(2)
        ->and((float) $component->get('items.0.jumlah'))->toBe(1.0)
        ->and((float) $component->get('items.1.jumlah'))->toBe(1.0);
});

test('TEST 36: PembelianEdit multi-select catalog can filter by stock status', function () {
    $this->actingAs($this->owner);

    $this->produkA->update(['stok_minimum' => 20]);
    Stok::create([
        'produk_id' => $this->produkA->id,
        'gudang_id' => $this->gudangUtama->id,
        'jumlah' => 5,
    ]);

    $this->produkB->update(['stok_minimum' => 10]);
    Stok::create([
        'produk_id' => $this->produkB->id,
        'gudang_id' => $this->gudangUtama->id,
        'jumlah' => 50,
    ]);

    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'status' => StatusPembelian::Ordered,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkB->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 10,
                'harga_beli_satuan' => 28000,
            ],
        ],
    ]);

    $component = Livewire::test(PembelianEdit::class, ['id' => $pembelian->id])
        ->call('openCatalogModal')
        ->set('catalogStatusStok', 'kritis');

    $viewData = $component->viewData('catalogProducts');
    expect($viewData->pluck('id')->all())->toContain($this->produkA->id)
        ->and($viewData->pluck('id')->all())->not->toContain($this->produkB->id);
});

test('TEST 37: PembelianCreate smart packaging calculator calculates intact boxes and loose sheets correctly for PVC', function () {
    $this->actingAs($this->owner);

    $pvc = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Glossy White 4m',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.0,
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 15,
        'harga_beli' => 15000.00,
    ]);

    $component = Livewire::test(PembelianCreate::class)
        ->call('addItem', $pvc->id)
        ->call('openCalculator', 0);

    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and($component->get('calcKemasanLabel'))->toBe('Dus')
        ->and((float) $component->get('calcIsiPerKemasan'))->toBe(15.0)
        ->and((float) $component->get('calcPanjangMeter'))->toBe(4.0);

    // 2 Dus (30 lbr) + 1 Lembar loose = 31 lembar * 4m = 124 meter
    $component->set('calcJmlKemasan', 2)
        ->set('calcJmlEceran', 1)
        ->call('applyCalculation');

    expect($component->get('isCalculatorModalOpen'))->toBeFalse()
        ->and((float) $component->get('items.0.qty_lembar'))->toBe(31.0)
        ->and((float) $component->get('items.0.panjang_meter'))->toBe(4.0)
        ->and((float) $component->get('items.0.jumlah'))->toBe(124.0)
        ->and((float) $component->get('items.0.subtotal'))->toBe(1860000.00);
});

test('TEST 38: PembelianEdit smart packaging calculator pre-fills existing values and updates non-PVC items', function () {
    $this->actingAs($this->owner);

    $hollow = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Besi Hollow 4x4 Galvanis',
        'satuan' => SatuanProduk::Batang,
        'kemasan' => 'Ikat',
        'isi_per_kemasan' => 20,
        'harga_beli' => 30000.00,
    ]);

    // Initial purchase with 45 batang (which is 2 ikat + 5 eceran)
    $pembelian = $this->action->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => '2026-09-17',
        'status' => StatusPembelian::Ordered,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $hollow->id,
                'gudang_id' => $this->gudangUtama->id,
                'jumlah' => 45,
                'harga_beli_satuan' => 30000.00,
            ],
        ],
    ]);

    $component = Livewire::test(PembelianEdit::class, ['id' => $pembelian->id])
        ->call('openCalculator', 0);

    // Should prefill: 45 / 20 = 2 ikat, remainder = 5 batang
    expect($component->get('isCalculatorModalOpen'))->toBeTrue()
        ->and((float) $component->get('calcJmlKemasan'))->toBe(2.0)
        ->and((float) $component->get('calcJmlEceran'))->toBe(5.0);

    // Change to 5 ikat (100 batang) + 3 eceran = 103 batang
    $component->set('calcJmlKemasan', 5)
        ->set('calcJmlEceran', 3)
        ->call('applyCalculation');

    expect($component->get('isCalculatorModalOpen'))->toBeFalse()
        ->and((float) $component->get('items.0.jumlah'))->toBe(103.0)
        ->and((float) $component->get('items.0.subtotal'))->toBe(3090000.00);
});

test('TEST 39: PembelianIndex quick filter pills and date filter shortcuts work properly', function () {
    $this->actingAs($this->owner);

    Livewire::test(PembelianIndex::class)
        ->call('setQuickFilter', 'ordered')
        ->assertSet('statusFilter', 'ordered')
        ->assertSet('statusPembayaranFilter', '')
        ->call('setQuickFilter', 'tempo')
        ->assertSet('statusFilter', '')
        ->assertSet('statusPembayaranFilter', 'tempo')
        ->call('setDateFilter', 'today')
        ->assertSet('startDate', now()->toDateString())
        ->assertSet('endDate', now()->toDateString())
        ->call('clearDateFilter')
        ->assertSet('startDate', '')
        ->assertSet('endDate', '')
        ->call('resetFilters')
        ->assertSet('statusFilter', '')
        ->assertSet('statusPembayaranFilter', '');
});
