<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\Stok;
use App\Livewire\Reports\ReportHub;
use App\Livewire\Reports\StockReport;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create(['name' => 'Staff Gudang']);
    $this->staffGudang->assignRole('Staff Gudang');

    $this->plainUser = User::factory()->create(['name' => 'Plain']);

    $this->gudangA = Gudang::factory()->create(['nama_gudang' => 'Gudang Barat']);
    $this->gudangB = Gudang::factory()->create(['nama_gudang' => 'Gudang Timur']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Hollow']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);

    $this->produk1 = Produk::factory()->create([
        'nama' => 'Hollow 2x4 0.3mm',
        'satuan' => SatuanProduk::Batang,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 20000,
        'harga_jual' => 25000,
        'stok_minimum' => 50,
    ]);

    $this->produk2 = Produk::factory()->create([
        'nama' => 'Hollow 4x4 0.3mm',
        'satuan' => SatuanProduk::Batang,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 30000,
        'harga_jual' => 38000,
        'stok_minimum' => 20,
    ]);

    Stok::create(['produk_id' => $this->produk1->id, 'gudang_id' => $this->gudangA->id, 'jumlah' => 10]); // Low stock!
    Stok::create(['produk_id' => $this->produk2->id, 'gudang_id' => $this->gudangB->id, 'jumlah' => 100]);
});

test('TEST 1 — Report Hub is accessible to authorized user and lists report modules', function () {
    $this->actingAs($this->owner);

    $this->get(route('laporan.hub'))
        ->assertOk()
        ->assertSee('Hub Laporan')
        ->assertSee('Laporan Stok')
        ->assertSee('Laporan Penjualan');

    Livewire::test(ReportHub::class)->assertStatus(200);
});

test('TEST 2 — Stock report access control: unauthorized user receives 403', function () {
    $this->actingAs($this->plainUser);

    $this->get(route('laporan.stok'))->assertForbidden();
});

test('TEST 3 — Authorized user can filter stock report by warehouse and low stock', function () {
    $this->actingAs($this->owner);

    Livewire::test(StockReport::class)
        ->assertStatus(200)
        ->assertSee('Hollow 2x4')
        ->assertSee('Hollow 4x4')
        ->set('gudang_id', $this->gudangA->id)
        ->assertSee('Hollow 2x4')
        ->assertDontSee('Hollow 4x4')
        ->set('gudang_id', '')
        ->set('stok_rendah', true)
        ->assertSee('Hollow 2x4')
        ->assertDontSee('Hollow 4x4');
});

test('TEST 4 — Stock report sensitive price protection hides harga_beli and nilai_stok from Staff Gudang', function () {
    $this->actingAs($this->staffGudang);

    Livewire::test(StockReport::class)
        ->assertStatus(200)
        ->assertSee('Hollow 2x4')
        ->assertSee('Restricted')
        ->assertDontSee('Harga Beli Terkini')
        ->assertDontSee('Est. Nilai Stok');
});
