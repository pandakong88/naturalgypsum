<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Services\StockService;
use App\Livewire\Inventory\MutasiStokIndex;
use App\Livewire\Inventory\StokIndex;
use App\Livewire\Inventory\TransferCreate;
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

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Papan Plafon']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Pusat']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Gypsum Jayaboard 9mm',
        'kode_katalog' => 'GYP-JB-09',
        'stok_minimum' => 20.000,
    ]);

    $this->stockService = app(StockService::class);
});

test('TEST 14: guest cannot access stok or mutasi pages', function () {
    $this->get(route('stok.index'))->assertRedirect(route('login'));
    $this->get(route('stok.mutasi.index'))->assertRedirect(route('login'));
});

test('TEST 14: authorized user can view stok and mutasi pages', function () {
    $this->actingAs($this->owner)
        ->get(route('stok.index'))
        ->assertOk()
        ->assertSee('Stok Saat Ini');

    $this->actingAs($this->owner)
        ->get(route('stok.mutasi.index'))
        ->assertOk()
        ->assertSee('Riwayat Mutasi Stok');
});

test('TEST 14: unauthorized user is forbidden from viewing stok and mutasi pages', function () {
    $this->actingAs($this->unauthorizedUser)
        ->get(route('stok.index'))
        ->assertForbidden();

    $this->actingAs($this->unauthorizedUser)
        ->get(route('stok.mutasi.index'))
        ->assertForbidden();
});

test('stok index livewire component filters and searches accurately', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        50.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-2026-000001',
        userId: $this->owner->id
    );

    $this->actingAs($this->staffGudang);

    Livewire::test(StokIndex::class)
        ->assertSee('Gypsum Jayaboard 9mm')
        ->assertSee('50,00')
        ->assertSee('Aman')
        ->set('search', 'GYP-JB')
        ->assertSee('Gypsum Jayaboard 9mm')
        ->set('search', 'NONEXISTENT')
        ->assertDontSee('Gypsum Jayaboard 9mm')
        ->assertSee('Tidak ada data stok');
});

test('stok index multi-warehouse detail modal renders breakdown correctly', function () {
    $gudang2 = Gudang::factory()->create(['nama_gudang' => 'Gudang Cabang']);

    $this->stockService->increase($this->produk, $this->gudang, 30.000, JenisMutasiStok::PembelianMasuk, 'PB-01', userId: $this->owner->id);
    $this->stockService->increase($this->produk, $gudang2, 15.000, JenisMutasiStok::PembelianMasuk, 'PB-02', userId: $this->owner->id);

    $this->actingAs($this->owner);

    Livewire::test(StokIndex::class)
        ->call('openDetailModal', $this->produk->id)
        ->assertSet('isDetailModalOpen', true)
        ->assertSee('Rincian Stok Lintas Gudang')
        ->assertSee('45,00') // Total 30 + 15
        ->assertSee('Gudang Pusat')
        ->assertSee('Gudang Cabang')
        ->call('closeDetailModal')
        ->assertSet('isDetailModalOpen', false);
});

test('mutasi stok index livewire component displays history and detail modal', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        100.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-2026-000088',
        userId: $this->owner->id
    );

    $mutation = MutasiStok::where('referensi', 'PB-2026-000088')->firstOrFail();

    $this->actingAs($this->owner);

    Livewire::test(MutasiStokIndex::class)
        ->assertSee('PB-2026-000088')
        ->assertSee('+100,00')
        ->assertSee('Pembelian Masuk')
        ->call('openDetailModal', $mutation->id)
        ->assertSet('isDetailModalOpen', true)
        ->assertSee('Detail Mutasi Stok')
        ->call('closeDetailModal')
        ->assertSet('isDetailModalOpen', false);
});

test('stok index displays KPI metrics and handles status filter click', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        10.000, // <= stok_minimum (20) -> Menipis
        JenisMutasiStok::PembelianMasuk,
        'PB-INIT-MENIPIS',
        userId: $this->owner->id
    );

    $this->actingAs($this->owner);

    Livewire::test(StokIndex::class)
        ->assertSee('Total SKU')
        ->assertSee('Total Unit Fisik')
        ->assertSee('Stok Menipis')
        ->assertSee('Stok Habis')
        ->assertSee('Estimasi Valuasi Aset')
        ->call('filterByStatus', 'menipis')
        ->assertSet('filterStatus', 'menipis')
        ->assertSee('Gypsum Jayaboard 9mm')
        ->call('filterByStatus', 'menipis')
        ->assertSet('filterStatus', '');
});

test('stok index handles sorting, per page, and reset filters', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        50.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-INIT-01',
        userId: $this->owner->id
    );

    $this->actingAs($this->owner);

    Livewire::test(StokIndex::class)
        ->set('search', 'Gypsum')
        ->set('sortBy', 'stok_asc')
        ->set('perPage', 25)
        ->assertSet('sortBy', 'stok_asc')
        ->assertSet('perPage', 25)
        ->assertSee('Reset Filter')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('sortBy', 'terbaru')
        ->assertSet('perPage', 15);
});

test('stok index can export Excel data', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        50.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-INIT-02',
        userId: $this->owner->id
    );

    $this->actingAs($this->owner);

    $component = Livewire::test(StokIndex::class);
    $response = $component->call('exportExcel');

    $response->assertFileDownloaded();
});

test('mutasi stok index automatically filters from query string url attributes', function () {
    $this->actingAs($this->owner);

    Livewire::withQueryParams([
        'filterProduk' => (string) $this->produk->id,
        'filterGudang' => (string) $this->gudang->id,
    ])
        ->test(MutasiStokIndex::class)
        ->assertSet('filterProduk', (string) $this->produk->id)
        ->assertSet('filterGudang', (string) $this->gudang->id);
});

test('mutasi stok index displays KPI metrics and supports direction filter', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        60.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-2026-IN01',
        userId: $this->owner->id
    );

    $this->stockService->decrease(
        $this->produk,
        $this->gudang,
        15.000,
        JenisMutasiStok::PenjualanKeluar,
        'PJ-2026-OUT01',
        userId: $this->owner->id
    );

    $this->actingAs($this->owner);

    Livewire::test(MutasiStokIndex::class)
        ->assertSee('Total Mutasi Transaksi')
        ->assertSee('Total Barang Masuk (+)')
        ->assertSee('Total Barang Keluar (-)')
        ->assertSee('Net Pergerakan Bersih')
        ->assertSee('+60,00')
        ->assertSee('-15,00')
        ->call('filterByArah', 'masuk')
        ->assertSet('filterArah', 'masuk')
        ->assertSee('PB-2026-IN01')
        ->assertDontSee('PJ-2026-OUT01')
        ->call('filterByArah', 'masuk')
        ->assertSet('filterArah', '')
        ->assertSee('PJ-2026-OUT01')
        ->call('filterByArah', 'keluar')
        ->assertSet('filterArah', 'keluar')
        ->assertSee('PJ-2026-OUT01')
        ->assertDontSee('PB-2026-IN01')
        ->call('resetFilters')
        ->assertSet('filterArah', '')
        ->assertSet('search', '');
});

test('mutasi stok index can export Excel data', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        30.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-2026-EXCEL',
        userId: $this->owner->id
    );

    $this->actingAs($this->owner);

    $component = Livewire::test(MutasiStokIndex::class);
    $response = $component->call('exportExcel');

    $response->assertFileDownloaded();
});

test('stok index exports excel data successfully with streaming cursor', function () {
    $this->stockService->increase(
        $this->produk,
        $this->gudang,
        25.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-2026-STOK-EXCEL',
        userId: $this->owner->id
    );

    $this->actingAs($this->owner);

    $component = Livewire::test(StokIndex::class);
    $response = $component->call('exportExcel');

    $response->assertFileDownloaded();
});

test('transfer create component pre-fills warehouse and product from mount query parameters', function () {
    $gudangTujuan = Gudang::factory()->create(['nama_gudang' => 'Gudang Tujuan']);

    $this->actingAs($this->owner);

    Livewire::test(TransferCreate::class, [
        'from_gudang_id' => $this->gudang->id,
        'to_gudang_id' => $gudangTujuan->id,
        'produk_id' => $this->produk->id,
    ])
        ->assertSet('gudang_asal_id', $this->gudang->id)
        ->assertSet('gudang_tujuan_id', $gudangTujuan->id)
        ->assertSet('items.0.produk_id', $this->produk->id);
});

test('stok index displays roll conversion and negative stock warning correctly', function () {
    $pvcProduk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Motif Kayu 4M',
        'kode_katalog' => 'PVC-KAYU-04',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.00,
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 15.00,
        'panjang_per_roll' => 4.00,
        'stok_minimum' => 40.000,
    ]);

    $this->stockService->increase(
        $pvcProduk,
        $this->gudang,
        120.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-2026-PVC',
        userId: $this->owner->id
    );

    // Also test negative stock
    $negProduk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Produk Minus Test',
        'kode_katalog' => 'PRD-MINUS-01',
        'stok_minimum' => 10.000,
    ]);

    Stok::updateOrCreate(
        ['produk_id' => $negProduk->id, 'gudang_id' => $this->gudang->id],
        ['jumlah' => -5.000]
    );

    $this->actingAs($this->owner);

    Livewire::test(StokIndex::class)
        ->assertSee('Plafon PVC Motif Kayu 4M')
        ->assertSee('120,00')
        ->assertSee('30 roll pas')
        ->assertSee('Produk Minus Test')
        ->assertSee('-5,00')
        ->assertSee('Minus (Perlu Restock)');
});

test('stok index opens and closes product image lightbox modal', function () {
    $produkWithPhoto = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Produk Foto Test',
        'kode_katalog' => 'PRD-FOTO-01',
        'foto' => 'produk/test-sample.jpg',
    ]);

    $this->stockService->increase(
        $produkWithPhoto,
        $this->gudang,
        10.000,
        JenisMutasiStok::PembelianMasuk,
        'PB-FOTO-01',
        userId: $this->owner->id
    );

    $this->actingAs($this->owner);

    Livewire::test(StokIndex::class)
        ->assertSet('isImageModalOpen', false)
        ->call('openImageModal', $produkWithPhoto->id)
        ->assertSet('isImageModalOpen', true)
        ->assertSee('PRD-FOTO-01')
        ->assertSee('Produk Foto Test')
        ->assertSee('Ukuran Penuh')
        ->call('closeImageModal')
        ->assertSet('isImageModalOpen', false)
        ->assertSet('imageModalProduk', null);
});

test('stok index consolidates stock across warehouses in default view and separates in filtered view', function () {
    $gudangCabang = Gudang::factory()->create(['nama_gudang' => 'Gudang Cabang Selatan']);

    $multiProduk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Vifon Multi Gudang Sample',
        'kode_katalog' => 'VIFON-MULTI-01',
        'stok_minimum' => 25.000,
    ]);

    // Gudang Pusat has 5 (which is <= 25, Menipis)
    $this->stockService->increase($multiProduk, $this->gudang, 5.000, JenisMutasiStok::PembelianMasuk, 'PB-M-01', userId: $this->owner->id);
    // Gudang Cabang has 70 (Total is 75 > 25, Aman)
    $this->stockService->increase($multiProduk, $gudangCabang, 70.000, JenisMutasiStok::PembelianMasuk, 'PB-M-02', userId: $this->owner->id);

    $this->actingAs($this->owner);

    // 1. Default View: "Semua Gudang" (Consolidated)
    Livewire::test(StokIndex::class)
        ->set('search', 'VIFON-MULTI-01')
        ->assertSee('Vifon Multi Gudang Sample')
        ->assertSee('75,00')
        ->assertSee('2 Gudang')
        ->assertSee('Aman');

    // 2. Filtered View: "Gudang Pusat" (Granular)
    Livewire::test(StokIndex::class)
        ->set('filterGudang', (string) $this->gudang->id)
        ->set('search', 'VIFON-MULTI-01')
        ->assertSee('Vifon Multi Gudang Sample')
        ->assertSee('5,00')
        ->assertSee('Menipis');

    // 3. Filtered View: "Gudang Cabang" (Granular)
    Livewire::test(StokIndex::class)
        ->set('filterGudang', (string) $gudangCabang->id)
        ->set('search', 'VIFON-MULTI-01')
        ->assertSee('Vifon Multi Gudang Sample')
        ->assertSee('70,00')
        ->assertSee('Aman');
});
