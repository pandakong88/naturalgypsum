<?php

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Livewire\Dashboard;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->staffPenjualan = User::factory()->create(['name' => 'Staff Jual']);
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->staffGudang = User::factory()->create(['name' => 'Staff Gudang']);
    $this->staffGudang->assignRole('Staff Gudang');

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Gypsum']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $this->supplier = Supplier::factory()->create(['nama_supplier' => 'PT Supplier Jaya']);
    $this->pelanggan = Pelanggan::factory()->create(['nama' => 'Toko Bangunan A']);

    $this->produk = Produk::factory()->create([
        'nama' => 'Papan Gypsum 9mm',
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 50000,
        'harga_jual' => 65000,
        'stok_minimum' => 10,
    ]);

    app(StockService::class)->increase($this->produk, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT');
});

test('TEST 1 — Owner can access Dashboard and view full KPIs including purchase prices', function () {
    $this->actingAs($this->owner);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Operasional')
        ->assertSee('Total Item Produk')
        ->assertSee('Estimasi Nilai Stok');

    Livewire::test(Dashboard::class)
        ->assertStatus(200)
        ->assertSee('Ringkasan Stok')
        ->assertSee('Ringkasan Penjualan');
});

test('TEST 2 — Staff without view_purchase_prices cannot see purchase KPIs or estimated stock value on dashboard', function () {
    $this->actingAs($this->staffPenjualan);

    Livewire::test(Dashboard::class)
        ->assertStatus(200)
        ->assertDontSee('Estimasi Nilai Stok')
        ->assertDontSee('Total Pembelian');
});

test('TEST 3 — Dashboard period filters update KPI data without error', function () {
    $this->actingAs($this->owner);

    app(CreatePenjualanAction::class)->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->toDateString(),
        'biaya_ongkir' => 0,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'jumlah' => 10,
                'harga_satuan' => 65000,
                'diskon_persen' => 0,
            ],
        ],
    ]);

    Livewire::test(Dashboard::class)
        ->set('period', 'today')
        ->assertSee('Rp 650.000')
        ->set('period', 'last_month')
        ->assertSet('period', 'last_month');
});
