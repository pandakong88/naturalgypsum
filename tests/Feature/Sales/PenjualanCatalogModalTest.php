<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\Stok;
use App\Livewire\Sales\PenjualanCreate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Pusat']);

    $this->merekA = Merek::factory()->create(['nama_merek' => 'Natural PVC']);
    $this->merekB = Merek::factory()->create(['nama_merek' => 'Aplus']);

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);

    $this->produkA = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merekA->id,
        'nama' => 'Plafon Natural White 4m',
        'kode_katalog' => 'NAT-001',
        'ukuran' => '4m x 20cm',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.0,
        'harga_jual' => 15000.00,
        'harga_aplikator' => 13500.00,
        'harga_grosir' => 14000.00,
    ]);

    $this->produkB = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merekB->id,
        'nama' => 'Hollow Aplus 2x4',
        'kode_katalog' => 'APL-204',
        'ukuran' => '4m x 0.3mm',
        'satuan' => SatuanProduk::Batang,
        'panjang_meter' => null,
        'harga_jual' => 25000.00,
        'harga_aplikator' => 22000.00,
        'harga_grosir' => 23000.00,
    ]);

    // Stok: produkA ready (100), produkB kosong (0)
    Stok::factory()->create([
        'produk_id' => $this->produkA->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => 100.000,
    ]);

    Stok::factory()->create([
        'produk_id' => $this->produkB->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => 0.000,
    ]);
});

test('modal katalog dapat dibuka dan mereset filter dengan benar', function () {
    Livewire::actingAs($this->owner)
        ->test(PenjualanCreate::class)
        ->set('catalogSearchQuery', 'keyword lama')
        ->set('catalogKategoriId', 99)
        ->set('catalogMerekId', 99)
        ->set('catalogStockFilter', 'ready')
        ->call('openCatalogModal')
        ->assertSet('isCatalogModalOpen', true)
        ->assertSet('catalogSearchQuery', '')
        ->assertSet('catalogKategoriId', null)
        ->assertSet('catalogMerekId', null)
        ->assertSet('catalogStockFilter', 'all')
        ->assertSet('catalogSelectedProductIds', [])
        ->call('closeCatalogModal')
        ->assertSet('isCatalogModalOpen', false);
});

test('modal katalog dapat memfilter produk berdasarkan merek', function () {
    Livewire::actingAs($this->owner)
        ->test(PenjualanCreate::class)
        ->call('openCatalogModal')
        ->set('catalogMerekId', $this->merekA->id)
        ->assertSee('Plafon Natural White 4m')
        ->assertDontSee('Hollow Aplus 2x4')
        ->set('catalogMerekId', $this->merekB->id)
        ->assertSee('Hollow Aplus 2x4')
        ->assertDontSee('Plafon Natural White 4m');
});

test('modal katalog dapat memfilter produk berdasarkan ketersediaan stok ready', function () {
    Livewire::actingAs($this->owner)
        ->test(PenjualanCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->call('openCatalogModal')
        ->set('catalogStockFilter', 'ready')
        ->assertSee('Plafon Natural White 4m')
        ->assertDontSee('Hollow Aplus 2x4');
});

test('modal katalog menampilkan harga sesuai tier pelanggan yang aktif', function () {
    $pemborong = Pelanggan::factory()->create([
        'nama' => 'Budi Mandor Jaya',
        'tipe' => TipePelanggan::Pemborong,
    ]);

    Livewire::actingAs($this->owner)
        ->test(PenjualanCreate::class)
        ->set('pelanggan_id', $pemborong->id)
        ->call('openCatalogModal')
        ->assertSee('Harga Pemborong')
        ->assertSee(number_format(13500, 0, ',', '.'));
});

test('modal katalog menampilkan indikator sudah di faktur jika barang telah ada di tabel kasir', function () {
    Livewire::actingAs($this->owner)
        ->test(PenjualanCreate::class)
        ->call('addQuickProduct', $this->produkA->id)
        ->call('openCatalogModal')
        ->assertSee('Sudah di Faktur');
});

test('centang semua yang tampil dan tambahkan banyak barang ke faktur bekerja dengan sempurna', function () {
    Livewire::actingAs($this->owner)
        ->test(PenjualanCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->call('openCatalogModal')
        ->call('selectAllFoundCatalogProducts')
        ->assertCount('catalogSelectedProductIds', 2)
        ->call('addSelectedCatalogProducts')
        ->assertSet('isCatalogModalOpen', false)
        ->assertCount('items', 2);
});
