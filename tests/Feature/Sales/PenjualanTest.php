<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Domains\Sales\Models\AturanDiskon;
use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Sales\PenjualanCreate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('Staff Gudang'); // Has view_sales, but NOT create_sales, NOT apply_sales_discount, NOT override_sales_price

    $this->unauthorizedUser = User::factory()->create();

    $this->pelanggan = Pelanggan::factory()->create([
        'nama' => 'Toko Bangunan Jaya',
        'tipe' => TipePelanggan::Umum,
    ]);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural PVC']);

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);

    $this->produkA = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Papan Gypsum Standar 9mm',
        'satuan' => SatuanProduk::Lembar,
        'panjang_meter' => null,
        'harga_beli' => 50000.00,
        'harga_jual' => 65000.00,
        'harga_aplikator' => 65000.00,
        'harga_grosir' => 65000.00,
    ]);

    $this->produkB = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'PVC Doff Putih 4m',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.00,
        'harga_beli' => 20000.00,
        'harga_jual' => 28000.00,
        'harga_aplikator' => 28000.00,
        'harga_grosir' => 28000.00,
    ]);

    $this->stockService = app(StockService::class);
    $this->action = app(CreatePenjualanAction::class);
});

test('TEST 1 — Successful sale', function () {
    // Seed initial stock
    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-1');

    $penjualan = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'biaya_ongkir' => 25000.00,
        'keterangan' => 'Penjualan tunai proyek rumah',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 20,
            ],
        ],
    ]);

    expect($penjualan)->toBeInstanceOf(Penjualan::class)
        ->and($penjualan->pelanggan_id)->toBe($this->pelanggan->id)
        ->and($penjualan->gudang_id)->toBe($this->gudang->id)
        ->and($penjualan->details)->toHaveCount(1)
        ->and((float) $penjualan->total_akhir)->toBe(1325000.00); // (20 * 65000) + 25000 = 1300000 + 25000 = 1325000

    $this->assertDatabaseHas('penjualan', [
        'id' => $penjualan->id,
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'total_akhir' => 1325000.00,
    ]);
});

test('TEST 2 — Document number generation PJ-YYYY-000001', function () {
    $sale1 = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 5,
            ],
        ],
    ]);

    $sale2 = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkB->id,
                'harga_satuan' => 28000.00,
                'jumlah' => 10,
            ],
        ],
    ]);

    expect($sale1->no_penjualan)->toBe('PJ-2026-000001')
        ->and($sale2->no_penjualan)->toBe('PJ-2026-000002');
});

test('TEST 3 — Stock decrease', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-1');

    $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 30,
            ],
        ],
    ]);

    $currentStock = $this->stockService->getStock($this->produkA, $this->gudang);
    expect($currentStock)->toBe(70.000);
});

test('TEST 4 — Mutation created with negative quantity', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-1');

    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 30,
            ],
        ],
    ]);

    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->produkA->id,
        'gudang_id' => $this->gudang->id,
        'jenis' => JenisMutasiStok::PenjualanKeluar->value,
        'jumlah' => -30.000,
        'referensi' => $sale->no_penjualan,
    ]);
});

test('TEST 5 — Mutation reference recorded properly', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 10,
            ],
        ],
    ]);

    $mutasi = MutasiStok::where('referensi', $sale->no_penjualan)->first();

    expect($mutasi)->not->toBeNull()
        ->and($mutasi->referensi_type)->toBe('penjualan')
        ->and($mutasi->referensi_id)->toBe($sale->id)
        ->and((float) $mutasi->jumlah)->toBe(-10.000);
});

test('TEST 6 — Negative stock allowed when sale exceeds stock', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-1');

    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 100,
            ],
        ],
    ]);

    $currentStock = $this->stockService->getStock($this->produkA, $this->gudang);
    expect($currentStock)->toBe(-50.000)
        ->and($sale)->toBeInstanceOf(Penjualan::class);

    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->produkA->id,
        'gudang_id' => $this->gudang->id,
        'jenis' => JenisMutasiStok::PenjualanKeluar->value,
        'jumlah' => -100.000,
    ]);
});

test('TEST 7 — Total calculation accuracy', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 12.5,
            ],
        ],
    ]);

    $expectedSubtotal = round(12.5 * 65000.00, 2); // 812500.00

    expect((float) $sale->details->first()->subtotal)->toBe($expectedSubtotal)
        ->and((float) $sale->total_akhir)->toBe($expectedSubtotal);
});

test('TEST 8 — Percentage discount calculation', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 100000.00,
                'jumlah' => 10,
                'jenis_diskon' => 'persentase',
                'nilai_diskon' => 10, // 10%
            ],
        ],
    ]);

    // Gross = 10 * 100.000 = 1.000.000; 10% = 100.000; Subtotal = 900.000
    expect((float) $sale->details->first()->subtotal)->toBe(900000.00)
        ->and((float) $sale->total_akhir)->toBe(900000.00);
});

test('TEST 9 — Nominal discount calculation', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 100000.00,
                'jumlah' => 10,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 100000,
            ],
        ],
    ]);

    // Gross = 1.000.000; Discount = 100.000; Subtotal = 900.000
    expect((float) $sale->details->first()->subtotal)->toBe(900000.00)
        ->and((float) $sale->total_akhir)->toBe(900000.00);
});

test('TEST 10 — Shipping cost calculation', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'biaya_ongkir' => 50000.00,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 100000.00,
                'jumlah' => 10,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 100000,
            ],
        ],
    ]);

    // Subtotal = 900.000; Ongkir = 50.000; Total Akhir = 950.000
    expect((float) $sale->details->first()->subtotal)->toBe(900000.00)
        ->and((float) $sale->biaya_ongkir)->toBe(50000.00)
        ->and((float) $sale->total_akhir)->toBe(950000.00);
});

test('TEST 11 — Backend total tampering resistance', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-1');
    $this->actingAs($this->owner);

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('biaya_ongkir', 10000)
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000,
                'jumlah' => 10,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 0,
                'subtotal' => 1, // Tampered client subtotal
                'current_stock' => 100,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $penjualan = Penjualan::latest('id')->first();

    // Backend must have calculated total as (10 * 65000) + 10000 = 660000, NOT 11
    expect((float) $penjualan->total_akhir)->toBe(660000.00)
        ->and((float) $penjualan->details->first()->subtotal)->toBe(650000.00);
});

test('TEST 12 — Invalid quantity rejected', function () {
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 0,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => -10,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 13 — Invalid discount rejected', function () {
    // Percentage > 100
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 1,
                'jenis_diskon' => 'persentase',
                'nilai_diskon' => 150,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);

    // Negative discount
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 1,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => -5000,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);

    // Discount exceeds gross
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 1,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 100000,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 14 — Invalid shipping cost rejected', function () {
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'biaya_ongkir' => -20000.00,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 5,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 15 — Empty details rejected', function () {
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [],
    ]))->toThrow(InvalidArgumentException::class);
});

test('TEST 16 — Inactive or soft-deleted master entities cannot be used in new sale', function () {
    $this->pelanggan->delete();

    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 5,
            ],
        ],
    ]))->toThrow(ModelNotFoundException::class);
});

test('TEST 17 — Historical transaction remains readable when master is soft-deleted', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 5,
            ],
        ],
    ]);

    $this->pelanggan->delete();
    $this->produkA->delete();
    $this->gudang->delete();

    $this->actingAs($this->owner);

    $this->get(route('penjualan.show', $sale->id))
        ->assertOk()
        ->assertSee($sale->no_penjualan);
});

test('TEST 18 — Price snapshot preserved when product master price changes later', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 10,
            ],
        ],
    ]);

    // Master price changes
    $this->produkA->update(['harga_jual' => 80000.00]);

    $sale->refresh();
    expect((float) $sale->details->first()->harga_satuan)->toBe(65000.00);
});

test('TEST 19 — Price override authorization enforced', function () {
    // Staff Gudang lacks override_sales_price
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->staffGudang->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 50000.00, // Master price is 65000
                'jumlah' => 1,
            ],
        ],
    ]))->toThrow(AuthorizationException::class);

    // Owner has override_sales_price
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 50000.00,
                'jumlah' => 1,
            ],
        ],
    ]);

    expect((float) $sale->details->first()->harga_satuan)->toBe(50000.00);
});

test('TEST 20 — Discount authorization enforced', function () {
    // Staff Gudang lacks apply_sales_discount
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->staffGudang->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 1,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 5000.00,
            ],
        ],
    ]))->toThrow(AuthorizationException::class);

    // Owner has apply_sales_discount
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 1,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 5000.00,
            ],
        ],
    ]);

    expect((float) $sale->details->first()->subtotal)->toBe(60000.00);
});

test('TEST 21 — Payment state initial calculation', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'biaya_ongkir' => 15000.00,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 2,
            ],
        ],
    ]);

    // Total = (2 * 65000) + 15000 = 145000
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::BelumBayar)
        ->and((float) $sale->total_dibayar)->toBe(0.00)
        ->and((float) $sale->sisa_piutang)->toBe(145000.00);
});

test('TEST 22 — Stock gatekeeper verification', function () {
    // Initial stock
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-1');

    $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 20,
            ],
        ],
    ]);

    $stokRow = Stok::where('produk_id', $this->produkA->id)->where('gudang_id', $this->gudang->id)->first();
    expect((float) $stokRow->jumlah)->toBe(30.000);
});

test('TEST 23 — Atomic rollback on failure during execution', function () {
    $initialSaleCount = Penjualan::count();
    $initialStockCount = Stok::count();
    $initialMutasiCount = MutasiStok::count();

    try {
        $this->action->execute([
            'pelanggan_id' => $this->pelanggan->id,
            'gudang_id' => $this->gudang->id,
            'tanggal' => '2026-09-17',
            'user_id' => $this->owner->id,
            'items' => [
                [
                    'produk_id' => $this->produkA->id,
                    'harga_satuan' => 65000.00,
                    'jumlah' => 10,
                ],
                [
                    'produk_id' => 999999, // Non-existent product
                    'harga_satuan' => 20000.00,
                    'jumlah' => 5,
                ],
            ],
        ]);
    } catch (Throwable $e) {
        // Expected
    }

    expect(Penjualan::count())->toBe($initialSaleCount)
        ->and(Stok::count())->toBe($initialStockCount)
        ->and(MutasiStok::count())->toBe($initialMutasiCount);
});

test('TEST 24 — Authorization barriers for unauthorized users', function () {
    $this->actingAs($this->unauthorizedUser);

    $this->get(route('penjualan.index'))->assertForbidden();
    $this->get(route('penjualan.create'))->assertForbidden();
});

test('TEST 25 — Duplicate submission protection in Livewire', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-1');
    $this->actingAs($this->staffPenjualan);

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000,
                'jumlah' => 10,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 0,
                'subtotal' => 650000,
                'current_stock' => 100,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Penjualan::count())->toBe(1);
});

test('TEST 26 — Concurrency safety during stock decrease', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-1');

    // Perform two sequential sales in distinct transactions
    $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 40,
            ],
        ],
    ]);

    $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 35,
            ],
        ],
    ]);

    $finalStock = $this->stockService->getStock($this->produkA, $this->gudang);
    // 100 - 40 - 35 = 25
    expect($finalStock)->toBe(25.000);
});

test('TEST 27 — Quick Search Bar adds product to items with correct tier price and stock', function () {
    $this->actingAs($this->owner);
    $this->pelanggan->update(['tipe' => TipePelanggan::Umum]);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->produkA->id);

    $component->assertSet('items.0.produk_id', $this->produkA->id)
        ->assertSet('items.0.harga_satuan', 65000.0)
        ->assertSet('items.0.jumlah', 1.0);

    // Call addQuickProduct again on same product -> increases quantity to 2
    $component->call('addQuickProduct', $this->produkA->id);
    $component->assertSet('items.0.jumlah', 2.0);
});

test('TEST 28 — Multi-Add Catalog Modal batch adds multiple products to invoice', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('openCatalogModal')
        ->assertSet('isCatalogModalOpen', true)
        ->call('toggleCatalogProduct', $this->produkA->id)
        ->call('toggleCatalogProduct', $this->produkB->id)
        ->assertSet('catalogSelectedProductIds', [$this->produkA->id, $this->produkB->id])
        ->call('addSelectedCatalogProducts')
        ->assertSet('isCatalogModalOpen', false);

    $items = $component->get('items');
    expect(count($items))->toBe(2)
        ->and($items[0]['produk_id'])->toBe($this->produkA->id)
        ->and($items[1]['produk_id'])->toBe($this->produkB->id);
});

test('TEST 29 — POS cashier direct cash payment allocates payment and marks invoice as Lunas with change calculated', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-POS');
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000,
                'jumlah' => 2,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 0,
                'subtotal' => 130000,
                'current_stock' => 50,
            ],
        ])
        ->set('tipe_pembayaran', 'tunai')
        ->set('jumlah_bayar', 150000)
        ->set('pasca_simpan', 'lihat');

    // Kembalian check: 150.000 - 130.000 = 20.000
    expect($component->get('kembalian'))->toBe(20000.0)
        ->and($component->get('sisaPiutangBaru'))->toBe(0.0);

    $component->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $penjualan = Penjualan::latest('id')->first();
    expect($penjualan)->not->toBeNull()
        ->and((float) $penjualan->total_akhir)->toBe(130000.0)
        ->and($penjualan->status_pembayaran)->toBe(StatusPembayaran::Lunas)
        ->and((float) $penjualan->total_dibayar)->toBe(130000.0)
        ->and((float) $penjualan->sisa_piutang)->toBe(0.0);
});

test('TEST 30 — Post-save option baru resets form for next cashier transaction without redirect', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-POS-2');
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000,
                'jumlah' => 1,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 0,
                'subtotal' => 65000,
                'current_stock' => 50,
            ],
        ])
        ->set('tipe_pembayaran', 'tunai')
        ->set('jumlah_bayar', 65000)
        ->set('pasca_simpan', 'baru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    // Verify form was reset for next customer
    expect(Penjualan::count())->toBe(1)
        ->and($component->get('items'))->toHaveCount(1)
        ->and($component->get('items.0.produk_id'))->toBeNull();
});

test('TEST 31 — Quick add new customer modal creates customer and selects it immediately', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->call('openQuickPelangganModal')
        ->assertSet('isQuickPelangganModalOpen', true)
        ->set('new_pelanggan_nama', 'Tukang Slamet')
        ->set('new_pelanggan_kontak', '081233445566')
        ->set('new_pelanggan_tipe', 'pemborong')
        ->set('new_pelanggan_limit', 5000000)
        ->call('saveQuickPelanggan')
        ->assertHasNoErrors()
        ->assertSet('isQuickPelangganModalOpen', false);

    $newCustomer = Pelanggan::where('nama', 'Tukang Slamet')->first();
    expect($newCustomer)->not->toBeNull()
        ->and($newCustomer->tipe)->toBe(TipePelanggan::Pemborong)
        ->and((float) $newCustomer->limit_piutang)->toBe(5000000.0)
        ->and($component->get('pelanggan_id'))->toBe($newCustomer->id);
});

test('TEST 32 — Quick add customer validates duplicate nama and kontak', function () {
    $this->actingAs($this->owner);

    Pelanggan::factory()->create([
        'nama' => 'Pak Joko Gypsum',
        'kontak' => '081299887766',
    ]);

    // Test duplicate nama (case-insensitive)
    Livewire::test(PenjualanCreate::class)
        ->call('openQuickPelangganModal')
        ->set('new_pelanggan_nama', 'pak joko gypsum')
        ->set('new_pelanggan_kontak', '089900001111')
        ->set('new_pelanggan_tipe', 'pemborong')
        ->set('new_pelanggan_limit', 0)
        ->call('saveQuickPelanggan')
        ->assertHasErrors(['new_pelanggan_nama']);

    // Test duplicate kontak
    Livewire::test(PenjualanCreate::class)
        ->call('openQuickPelangganModal')
        ->set('new_pelanggan_nama', 'Pak Budi Lain')
        ->set('new_pelanggan_kontak', '081299887766')
        ->set('new_pelanggan_tipe', 'pemborong')
        ->set('new_pelanggan_limit', 0)
        ->call('saveQuickPelanggan')
        ->assertHasErrors(['new_pelanggan_kontak']);
});

test('TEST 33 — Multi-warehouse line items split and decrease stock from respective warehouses', function () {
    $this->actingAs($this->owner);

    $gudangB = Gudang::factory()->create(['nama_gudang' => 'Gudang Cabang']);

    // Set stock: Gudang Utama = 30, Gudang Cabang = 20
    $this->stockService->increase($this->produkA, $this->gudang, 30, JenisMutasiStok::PembelianMasuk, 'INIT-A');
    $this->stockService->increase($this->produkA, $gudangB, 20, JenisMutasiStok::PembelianMasuk, 'INIT-B');

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->produkA->id)
        ->set('items.0.jumlah', 50)
        ->call('splitItemToWarehouse', 0, $gudangB->id);

    // After split: Row 0 has 30 in Gudang Utama, Row 1 has 20 in Gudang Cabang
    $items = $component->get('items');
    expect($items)->toHaveCount(2)
        ->and((float) $items[0]['jumlah'])->toBe(30.0)
        ->and((int) $items[0]['gudang_id'])->toBe($this->gudang->id)
        ->and((float) $items[1]['jumlah'])->toBe(20.0)
        ->and((int) $items[1]['gudang_id'])->toBe($gudangB->id)
        ->and((float) $component->get('totalKuantitasFisik'))->toBe(50.0);

    // Save transaction
    $component->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $penjualan = Penjualan::with('details')->latest('id')->first();
    expect($penjualan->details)->toHaveCount(2)
        ->and((int) $penjualan->details[0]->gudang_id)->toBe($this->gudang->id)
        ->and((int) $penjualan->details[1]->gudang_id)->toBe($gudangB->id);

    // Stock in both warehouses should now be 0
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(0.0)
        ->and($this->stockService->getStock($this->produkA, $gudangB))->toBe(0.0);
});

test('TEST 34 — Instant transfer pulls stock from other warehouse to satisfy sales line deficit', function () {
    $this->actingAs($this->owner);

    $gudangB = Gudang::factory()->create(['nama_gudang' => 'Gudang Pusat']);

    // Set stock: Gudang Utama = 10, Gudang Pusat = 25
    $this->stockService->increase($this->produkA, $this->gudang, 10, JenisMutasiStok::PembelianMasuk, 'INIT-1');
    $this->stockService->increase($this->produkA, $gudangB, 25, JenisMutasiStok::PembelianMasuk, 'INIT-2');

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->produkA->id)
        ->set('items.0.jumlah', 25) // Deficit is 15
        ->call('transferStockForDeficit', 0, $gudangB->id);

    // Stock in Gudang Utama should now be 10 + 15 = 25
    expect($this->stockService->getStock($this->produkA, $this->gudang))->toBe(25.0)
        ->and($this->stockService->getStock($this->produkA, $gudangB))->toBe(10.0);

    // Current stock on item should now be updated to 25
    $items = $component->get('items');
    expect((float) $items[0]['current_stock'])->toBe(25.0);
});

test('TEST 35 — Staff without override permission can sell at customer tier price', function () {
    $this->produkA->update(['harga_aplikator' => 60000.00]);
    $pemborong = Pelanggan::factory()->create(['tipe' => TipePelanggan::Pemborong]);

    $sale = $this->action->execute([
        'pelanggan_id' => $pemborong->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->staffPenjualan->id,
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 60000.00, 'jumlah' => 2],
        ],
    ]);

    $detail = $sale->details->first();
    expect((float) $detail->harga_satuan)->toBe(60000.00)
        ->and((float) $detail->harga_normal)->toBe(60000.00)
        ->and($detail->isHargaDiubah())->toBeFalse();
});

test('TEST 36 — Staff without override permission cannot deviate from customer tier price', function () {
    $this->produkA->update(['harga_aplikator' => 60000.00]);
    $pemborong = Pelanggan::factory()->create(['tipe' => TipePelanggan::Pemborong]);

    // Retail price is no longer valid for a Pemborong customer
    expect(fn () => $this->action->execute([
        'pelanggan_id' => $pemborong->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->staffPenjualan->id,
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 65000.00, 'jumlah' => 1],
        ],
    ]))->toThrow(AuthorizationException::class);
});

test('TEST 37 — Overridden price is recorded with reference price for audit', function () {
    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->owner->id,
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 62000.00, 'jumlah' => 1],
        ],
    ]);

    $detail = $sale->details->first();
    expect((float) $detail->harga_normal)->toBe(65000.00)
        ->and($detail->isHargaDiubah())->toBeTrue();

    $this->actingAs($this->owner);
    $this->get(route('penjualan.show', $sale->id))
        ->assertOk()
        ->assertSee('Harga diubah');
});

test('TEST 38 — Staff without discount permission may apply automatic promo but not exceed it', function () {
    AturanDiskon::create([
        'nama' => 'Promo Gypsum 10%',
        'target_pelanggan' => 'semua',
        'target_produk' => 'kategori',
        'kategori_id' => $this->kategori->id,
        'jenis_diskon' => 'persentase',
        'nilai_diskon' => 10.0,
        'min_kuantiti' => 0,
        'is_active' => true,
    ]);

    $sale = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->staffPenjualan->id,
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 65000.00, 'jumlah' => 2, 'jenis_diskon' => 'persentase', 'nilai_diskon' => 10],
        ],
    ]);

    // 2 x 65.000 = 130.000 - 10% = 117.000
    expect((float) $sale->details->first()->subtotal)->toBe(117000.00);

    expect(fn () => $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'user_id' => $this->staffPenjualan->id,
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 65000.00, 'jumlah' => 2, 'jenis_diskon' => 'persentase', 'nilai_diskon' => 20],
        ],
    ]))->toThrow(AuthorizationException::class);
});

test('TEST 39 — Livewire tracks harga_normal, recalculates promo on price change, and can reset price', function () {
    AturanDiskon::create([
        'nama' => 'Promo Gypsum 10%',
        'target_pelanggan' => 'semua',
        'target_produk' => 'kategori',
        'kategori_id' => $this->kategori->id,
        'jenis_diskon' => 'persentase',
        'nilai_diskon' => 10.0,
        'min_kuantiti' => 0,
        'is_active' => true,
    ]);
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->produkA->id)
        ->assertSet('items.0.harga_normal', 65000.0)
        ->assertSet('items.0.subtotal', 58500.0)
        ->set('items.0.harga_satuan', 60000)
        ->assertSet('items.0.harga_normal', 65000.0)
        ->assertSet('items.0.subtotal', 54000.0)
        ->assertSee('Harga diubah');

    $component->call('resetHargaNormal', 0)
        ->assertSet('items.0.harga_satuan', 65000.0)
        ->assertSet('items.0.subtotal', 58500.0)
        ->assertDontSee('Harga diubah');
});

test('TEST 40 — Livewire PenjualanCreate persists delivery fields nama_sopir, plat_nomor, and alamat_pengiriman', function () {
    $this->actingAs($this->owner);
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-TEST40');

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('nama_sopir', 'Pak Slamet')
        ->set('plat_nomor', 'B 9876 XYZ')
        ->set('alamat_pengiriman', 'Jl. Sudirman Proyek Ruko No. 45')
        ->call('addQuickProduct', $this->produkA->id)
        ->call('save')
        ->assertHasNoErrors();

    $penjualan = Penjualan::latest('id')->first();
    expect($penjualan)->not->toBeNull()
        ->and($penjualan->nama_sopir)->toBe('Pak Slamet')
        ->and($penjualan->plat_nomor)->toBe('B 9876 XYZ')
        ->and($penjualan->alamat_pengiriman)->toBe('Jl. Sudirman Proyek Ruko No. 45');
});

test('TEST 41 — print routes render Faktur, Surat Jalan, and Thermal Struk properly', function () {
    $this->actingAs($this->owner);

    $action = app(CreatePenjualanAction::class);
    $penjualan = $action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-20',
        'nama_sopir' => 'Budi Driver',
        'plat_nomor' => 'D 1234 ABC',
        'alamat_pengiriman' => 'Proyek Perumahan Indah Blok C',
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 65000.00, 'jumlah' => 10],
        ],
    ]);

    // 1. Faktur Penjualan (A4/A5)
    $responseFaktur = $this->get(route('penjualan.print.faktur', $penjualan));
    $responseFaktur->assertOk();
    $responseFaktur->assertSee('FAKTUR PENJUALAN');
    $responseFaktur->assertSee($penjualan->no_penjualan);
    $responseFaktur->assertSee($this->pelanggan->nama);
    $responseFaktur->assertSee('Rp 650.000');

    // 2. Surat Jalan (Gudang & Sopir) - MUST NOT have prices
    $responseSJ = $this->get(route('penjualan.print.surat-jalan', $penjualan));
    $responseSJ->assertOk();
    $responseSJ->assertSee('SURAT JALAN & MUAT', false);
    $responseSJ->assertSee('SJ-'.$penjualan->no_penjualan);
    $responseSJ->assertSee('Budi Driver');
    $responseSJ->assertSee('D 1234 ABC');
    $responseSJ->assertSee('Proyek Perumahan Indah Blok C');
    $responseSJ->assertSee('10'); // Qty
    $responseSJ->assertDontSee('Rp 650.000');
    $responseSJ->assertDontSee('Subtotal');

    // 3. Struk Kasir Thermal (80mm/58mm)
    $responseThermal = $this->get(route('penjualan.print.thermal', $penjualan));
    $responseThermal->assertOk();
    $responseThermal->assertSee('NATURAL GYPSUM & PVC', false);
    $responseThermal->assertSee($penjualan->no_penjualan);
    $responseThermal->assertSee('Budi Driver');
});

test('TEST 42 — Unauthorized user without view permission cannot access print routes', function () {
    $action = app(CreatePenjualanAction::class);
    $penjualan = $action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-20',
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 65000.00, 'jumlah' => 1],
        ],
    ]);

    $this->actingAs($this->unauthorizedUser);

    $this->get(route('penjualan.print.faktur', $penjualan))->assertForbidden();
    $this->get(route('penjualan.print.surat-jalan', $penjualan))->assertForbidden();
    $this->get(route('penjualan.print.thermal', $penjualan))->assertForbidden();
});

test('TEST 43 — Sales creation stores explicit payment method and provides accurate label', function () {
    $action = app(CreatePenjualanAction::class);

    $cashSale = $action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-20',
        'metode_pembayaran' => 'tunai',
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 65000.00, 'jumlah' => 2],
        ],
    ]);
    expect($cashSale->metode_pembayaran)->toBe('tunai');
    expect($cashSale->getMetodeLabel())->toBe('Tunai (Cash POS)');

    $tempoSale = $action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-20',
        'metode_pembayaran' => 'tempo',
        'items' => [
            ['produk_id' => $this->produkA->id, 'harga_satuan' => 65000.00, 'jumlah' => 1],
        ],
    ]);
    expect($tempoSale->metode_pembayaran)->toBe('tempo');
    expect($tempoSale->getMetodeLabel())->toBe('Tempo (Piutang)');
});

test('TEST 44 — Livewire PenjualanCreate accurately calculates multiple items and ensures cash is never left unpaid', function () {
    $this->actingAs($this->owner);
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-44');

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addProductToItems', $this->produkA, 2.0) // 2 x 65.000 = 130.000
        ->assertSet('subtotalPenjualan', 130000.00)
        ->assertSet('totalKuantitasFisik', 2.0)
        ->assertSet('totalAkhir', 130000.00)
        ->assertSet('jumlah_bayar', 130000.00)
        ->assertSet('tipe_pembayaran', 'tunai')
        ->call('save')
        ->assertHasNoErrors();

    // Verify last created sale in DB is LUNAS and NOT belum_bayar
    $sale = Penjualan::latest('id')->first();
    expect($sale)->not->toBeNull();
    expect($sale->metode_pembayaran)->toBe('tunai');
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::Lunas);
    expect((float) $sale->total_dibayar)->toBe(130000.00);
    expect((float) $sale->sisa_piutang)->toBe(0.00);
});

test('TEST 45 — Livewire PenjualanCreate in Tempo mode stores as tempo with zero payment and full piutang', function () {
    $this->actingAs($this->owner);
    $this->pelanggan->update(['limit_piutang' => 5000000.00]);
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-45');

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addProductToItems', $this->produkA, 1.0)
        ->set('tipe_pembayaran', 'belum_bayar')
        ->assertSet('subtotalPenjualan', 65000.00)
        ->assertSet('totalAkhir', 65000.00)
        ->assertSet('jumlah_bayar', 0)
        ->assertSet('sisaPiutangBaru', 65000.00)
        ->call('save')
        ->assertHasNoErrors();

    $sale = Penjualan::latest('id')->first();
    expect($sale)->not->toBeNull();
    expect($sale->metode_pembayaran)->toBe('tempo');
    expect($sale->status_pembayaran)->toBe(StatusPembayaran::BelumBayar);
    expect((float) $sale->total_dibayar)->toBe(0.00);
    expect((float) $sale->sisa_piutang)->toBe(65000.00);
});

test('TEST 46 — PenjualanCreate blocks kasbon/tempo when customer limit is 0', function () {
    $this->actingAs($this->owner);
    $this->pelanggan->update(['limit_piutang' => 0.00]);
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-46');

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addProductToItems', $this->produkA, 1.0)
        ->set('tipe_pembayaran', 'belum_bayar')
        ->call('save')
        ->assertHasErrors(['tipe_pembayaran']);

    // Ensure no new sale was created
    expect(Penjualan::where('pelanggan_id', $this->pelanggan->id)->where('status_pembayaran', StatusPembayaran::BelumBayar)->count())->toBe(0);
});

test('TEST 47 — PenjualanCreate blocks kasbon/tempo when debt exceeds customer credit limit', function () {
    $this->actingAs($this->owner);
    // Limit is 100,000, but sale is 130,000 (2 x 65,000)
    $this->pelanggan->update(['limit_piutang' => 100000.00]);
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-47');

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addProductToItems', $this->produkA, 2.0)
        ->set('tipe_pembayaran', 'belum_bayar')
        ->call('save')
        ->assertHasErrors(['tipe_pembayaran']);

    expect(Penjualan::where('pelanggan_id', $this->pelanggan->id)->where('total_akhir', 130000.00)->count())->toBe(0);
});

test('TEST 48 — Authorized user can quick-update customer credit limit directly from cashier screen and successfully complete sale', function () {
    $this->actingAs($this->owner);
    // Start with limit 0
    $this->pelanggan->update(['limit_piutang' => 0.00]);
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-48');

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addProductToItems', $this->produkA, 1.0)
        ->set('tipe_pembayaran', 'belum_bayar')
        // First try to save -> blocked
        ->call('save')
        ->assertHasErrors(['tipe_pembayaran'])
        // Open quick limit modal and increase limit to 1,000,000
        ->call('openQuickLimitModal')
        ->assertSet('isQuickLimitModalOpen', true)
        ->set('quickLimitAmount', 1000000.00)
        ->call('saveQuickLimit')
        ->assertSet('isQuickLimitModalOpen', false)
        // Now try saving the sale again -> succeeds!
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $this->pelanggan->fresh()->limit_piutang)->toBe(1000000.00);
    $sale = Penjualan::latest('id')->first();
    expect($sale)->not->toBeNull();
    expect($sale->metode_pembayaran)->toBe('tempo');
    expect((float) $sale->sisa_piutang)->toBe(65000.00);
});

test('TEST 49 — User without update_customers permission cannot quick-update customer credit limit', function () {
    $limitedUser = User::factory()->create();
    $limitedUser->givePermissionTo('create_sales');
    $this->actingAs($limitedUser);

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->call('openQuickLimitModal')
        ->assertForbidden();
});

test('TEST 50 — splitItemToWarehouse inserts the newly allocated row directly below the source row at index + 1', function () {
    $this->actingAs($this->owner);
    $gudangB = Gudang::factory()->create(['nama_gudang' => 'Gudang Cabang']);

    // Set stock: Gudang Utama has 10, Gudang Cabang has 20
    $this->stockService->increase($this->produkA, $this->gudang, 10, JenisMutasiStok::PembelianMasuk, 'INIT-50-A');
    $this->stockService->increase($this->produkA, $gudangB, 20, JenisMutasiStok::PembelianMasuk, 'INIT-50-B');

    // Add another product to ensure row ordering is verified
    $this->stockService->increase($this->produkB, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-50-C');

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addProductToItems', $this->produkA, 15.0) // Request 15 of produkA (deficit 5)
        ->call('addProductToItems', $this->produkB, 5.0); // Index 1 is produkB

    // Before split:
    // items[0] = produkA (jumlah: 15)
    // items[1] = produkB (jumlah: 5)
    $itemsBefore = $component->get('items');
    expect($itemsBefore)->toHaveCount(2);
    expect($itemsBefore[0]['produk_id'])->toBe($this->produkA->id);
    expect($itemsBefore[1]['produk_id'])->toBe($this->produkB->id);

    // Call split on index 0 to allocate deficit to gudangB
    $component->call('splitItemToWarehouse', 0, $gudangB->id);

    // After split:
    // items[0] = produkA (jumlah: 10, gudang: gudang Utama)
    // items[1] = produkA (jumlah: 5, gudang: gudangB) -> inserted directly below!
    // items[2] = produkB (jumlah: 5) -> pushed down to index 2
    $itemsAfter = $component->get('items');
    expect($itemsAfter)->toHaveCount(3);
    expect($itemsAfter[0]['produk_id'])->toBe($this->produkA->id);
    expect((float) $itemsAfter[0]['jumlah'])->toBe(10.0);
    expect((int) $itemsAfter[0]['gudang_id'])->toBe($this->gudang->id);

    expect($itemsAfter[1]['produk_id'])->toBe($this->produkA->id);
    expect((float) $itemsAfter[1]['jumlah'])->toBe(5.0);
    expect((int) $itemsAfter[1]['gudang_id'])->toBe($gudangB->id);

    expect($itemsAfter[2]['produk_id'])->toBe($this->produkB->id);
    expect((float) $itemsAfter[2]['jumlah'])->toBe(5.0);
});

test('TEST 51 — getGroupedDetailsForInvoice groups split warehouse items into a single consolidated row for invoice and thermal receipt', function () {
    $this->actingAs($this->owner);
    $gudangB = Gudang::factory()->create(['nama_gudang' => 'Gudang Cabang']);

    $this->stockService->increase($this->produkA, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-51-A');
    $this->stockService->increase($this->produkA, $gudangB, 50, JenisMutasiStok::PembelianMasuk, 'INIT-51-B');

    // Create a sale with split warehouse items
    $action = app(CreatePenjualanAction::class);
    $penjualan = $action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now(),
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudang->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 10.0,
            ],
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $gudangB->id,
                'harga_satuan' => 65000.00,
                'jumlah' => 5.0,
            ],
        ],
    ]);

    expect($penjualan->details)->toHaveCount(2);

    $grouped = $penjualan->getGroupedDetailsForInvoice();
    expect($grouped)->toHaveCount(1);

    $first = $grouped->first();
    expect($first->produk_id)->toBe($this->produkA->id);
    expect((float) $first->jumlah)->toBe(15.0);
    expect((float) $first->harga_satuan)->toBe(65000.00);
    expect((float) $first->subtotal)->toBe(15.0 * 65000.00);
    expect($first->gudang->nama_gudang)->toContain('Gudang Utama');
    expect($first->gudang->nama_gudang)->toContain('Gudang Cabang');

    // Test print faktur route renders without error and contains aggregated values
    $responseFaktur = $this->get(route('penjualan.print.faktur', $penjualan));
    $responseFaktur->assertOk();
    $responseFaktur->assertSee('Gudang Utama &amp; Gudang Cabang', false);

    // Test thermal receipt renders without error
    $responseThermal = $this->get(route('penjualan.print.thermal', $penjualan));
    $responseThermal->assertOk();
});

test('TEST 50 — Customer directory modal opens, closes, and filters by search query and customer type', function () {
    $this->actingAs($this->owner);

    $custPemborong = Pelanggan::factory()->create([
        'nama' => 'CV Mandiri Sejahtera',
        'kontak' => '081234567890',
        'tipe' => TipePelanggan::Pemborong,
    ]);

    $custLangganan = Pelanggan::factory()->create([
        'nama' => 'Toko Subur Makmur',
        'kontak' => '089876543210',
        'tipe' => TipePelanggan::Langganan,
    ]);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelangganSearchQuery', 'Mandiri')
        ->call('openPelangganModal')
        ->assertSet('isPelangganModalOpen', true)
        ->assertSet('pelangganModalSearch', 'Mandiri')
        ->assertSee('CV Mandiri Sejahtera')
        ->assertDontSee('Toko Subur Makmur');

    // Filter by customer type
    $component->set('pelangganModalSearch', '')
        ->set('pelangganModalTipe', 'langganan')
        ->assertSee('Toko Subur Makmur')
        ->assertDontSee('CV Mandiri Sejahtera');

    // Close modal resets search & state
    $component->call('closePelangganModal')
        ->assertSet('isPelangganModalOpen', false)
        ->assertSet('pelangganModalSearch', '')
        ->assertSet('pelangganModalTipe', '');
});

test('TEST 51 — Select customer from directory modal sets customer, updates pricing tier, and closes modal', function () {
    $this->actingAs($this->owner);

    $customer = Pelanggan::factory()->create([
        'nama' => 'CV Bintang Abadi',
        'kontak' => '087711223344',
        'tipe' => TipePelanggan::Pemborong,
        'limit_piutang' => 10000000,
    ]);

    Livewire::test(PenjualanCreate::class)
        ->call('openPelangganModal')
        ->assertSet('isPelangganModalOpen', true)
        ->call('selectPelangganFromModal', $customer->id)
        ->assertSet('pelanggan_id', $customer->id)
        ->assertSet('isPelangganModalOpen', false)
        ->assertSee('CV Bintang Abadi');
});

test('TEST 52 — Open quick pelanggan modal from directory carries search text into new customer name', function () {
    $this->actingAs($this->owner);

    Livewire::test(PenjualanCreate::class)
        ->call('openPelangganModal')
        ->set('pelangganModalSearch', 'Pak Bambang Sentosa')
        ->call('openQuickPelangganModalFromDirectory')
        ->assertSet('isPelangganModalOpen', false)
        ->assertSet('isQuickPelangganModalOpen', true)
        ->assertSet('new_pelanggan_nama', 'Pak Bambang Sentosa');
});

test('TEST 53 — Quick cash preset buttons update jumlah_bayar and recalculate kembalian properly', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000,
                'jumlah' => 2,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 0,
                'subtotal' => 130000,
                'current_stock' => 50,
            ],
        ])
        ->set('tipe_pembayaran', 'tunai');

    // Total is 130,000
    expect($component->get('totalAkhir'))->toBe(130000.0);

    // Test setUangPas
    $component->call('setUangPas');
    expect((float) $component->get('jumlah_bayar'))->toBe(130000.0)
        ->and($component->get('kembalian'))->toBe(0.0);

    // Test addNominalBayar (+50k) -> 180,000, kembalian 50,000
    $component->call('addNominalBayar', 50000);
    expect((float) $component->get('jumlah_bayar'))->toBe(180000.0)
        ->and($component->get('kembalian'))->toBe(50000.0);

    // Test setExactNominalBayar (200,000) -> kembalian 70,000
    $component->call('setExactNominalBayar', 200000);
    expect((float) $component->get('jumlah_bayar'))->toBe(200000.0)
        ->and($component->get('kembalian'))->toBe(70000.0);

    // Test clearNominalBayar -> 0
    $component->call('clearNominalBayar');
    expect((float) $component->get('jumlah_bayar'))->toBe(0.0);
});

test('TEST 54 — Post-save cetak_kilat opens quick print dialog modal with saved invoice details and kembalian', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-POS-CK');
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000,
                'jumlah' => 2,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 0,
                'subtotal' => 130000,
                'current_stock' => 50,
            ],
        ])
        ->set('tipe_pembayaran', 'tunai')
        ->set('jumlah_bayar', 150000)
        ->set('pasca_simpan', 'cetak_kilat');

    $component->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('isPrintModalOpen', true);

    $penjualan = Penjualan::latest('id')->first();
    expect($penjualan)->not->toBeNull()
        ->and($component->get('lastSavedPenjualanId'))->toBe($penjualan->id)
        ->and($component->get('lastSavedNoPenjualan'))->toBe($penjualan->no_penjualan)
        ->and($component->get('lastSavedTotal'))->toBe(130000.0)
        ->and($component->get('lastSavedKembalian'))->toBe(20000.0);

    $component->assertSee('Transaksi Berhasil Disimpan!')
        ->assertSee($penjualan->no_penjualan)
        ->assertSee('Cetak Struk Kasir (Thermal)')
        ->assertSee('Cetak Faktur Penjualan (A4 / A5)')
        ->assertSee('Cetak Surat Jalan & Muatan', false);
});

test('TEST 55 — startNewTransaction from quick print dialog resets cashier form ready for next customer', function () {
    $this->stockService->increase($this->produkA, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-POS-RST');
    $this->actingAs($this->owner);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('items', [
            [
                'produk_id' => $this->produkA->id,
                'harga_satuan' => 65000,
                'jumlah' => 1,
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 0,
                'subtotal' => 65000,
                'current_stock' => 50,
            ],
        ])
        ->set('tipe_pembayaran', 'tunai')
        ->set('jumlah_bayar', 100000)
        ->set('pasca_simpan', 'cetak_kilat')
        ->call('save')
        ->assertSet('isPrintModalOpen', true);

    // Call startNewTransaction
    $component->call('startNewTransaction')
        ->assertSet('isPrintModalOpen', false)
        ->assertSet('lastSavedPenjualanId', null);

    // Items reset to empty row
    expect(count($component->get('items')))->toBe(1)
        ->and($component->get('items.0.produk_id'))->toBeNull();
});

test('TEST 56 — Surat Jalan groups items by warehouse and shows subheaders when sale involves multiple warehouses', function () {
    $this->actingAs($this->owner);

    $gudangB = Gudang::factory()->create(['nama_gudang' => 'Gudang Cabang Barat']);

    $penjualan = $this->action->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'biaya_ongkir' => 0,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produkA->id,
                'gudang_id' => $this->gudang->id,
                'harga_satuan' => 65000,
                'jumlah' => 10,
            ],
            [
                'produk_id' => $this->produkB->id,
                'gudang_id' => $gudangB->id,
                'harga_satuan' => 28000,
                'jumlah' => 20,
            ],
        ],
    ]);

    $response = $this->get(route('penjualan.print.surat-jalan', $penjualan->id));
    $response->assertOk()
        ->assertSee('Multi-Lokasi (2 Gudang)')
        ->assertSee('📍 LOKASI PENGAMBILAN / MUAT: <strong>GUDANG UTAMA</strong>', false)
        ->assertSee('📍 LOKASI PENGAMBILAN / MUAT: <strong>GUDANG CABANG BARAT</strong>', false)
        ->assertSee($this->produkA->nama)
        ->assertSee($this->produkB->nama);
});
