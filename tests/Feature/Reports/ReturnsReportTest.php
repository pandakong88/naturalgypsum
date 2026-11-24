<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Returns\Actions\CreateReturPelangganAction;
use App\Domains\Returns\Actions\CreateReturSupplierAction;
use App\Livewire\Reports\ReturnsReport;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Gypsum']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $this->supplier = Supplier::factory()->create(['nama_supplier' => 'Supplier Mitra']);
    $this->pelanggan = Pelanggan::factory()->create(['nama' => 'Pelanggan Setia']);

    $this->produk = Produk::factory()->create([
        'nama' => 'Gypsum Board Retur',
        'satuan' => SatuanProduk::Lembar,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 50000,
        'harga_jual' => 70000,
        'stok_minimum' => 10,
    ]);

    app(StockService::class)->increase($this->produk, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT');

    $this->retSup = app(CreateReturSupplierAction::class)->execute([
        'supplier_id' => $this->supplier->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->toDateString(),
        'jumlah' => 5,
        'alasan' => 'Barang pecah',
        'user_id' => $this->owner->id,
    ]);

    $this->retCust = app(CreateReturPelangganAction::class)->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->toDateString(),
        'jumlah' => 2,
        'harga_retur' => 70000,
        'alasan' => 'Kelebihan order',
        'user_id' => $this->owner->id,
    ]);
});

test('TEST 1 — Returns report displays supplier and customer return tabs and summaries', function () {
    $this->actingAs($this->owner);

    Livewire::test(ReturnsReport::class)
        ->assertStatus(200)
        ->assertSee($this->retSup->no_retur)
        ->assertSee('Barang pecah')
        ->set('type', 'pelanggan')
        ->assertSee($this->retCust->no_retur)
        ->assertSee('Kelebihan order');
});
