<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Actions\CreatePembelianAction;
use App\Livewire\Reports\PurchaseReport;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create(['name' => 'Staff Gudang']);
    $this->staffGudang->assignRole('Staff Gudang');

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Papan']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $this->supplierA = Supplier::factory()->create(['nama_supplier' => 'Supplier Alpha']);
    $this->supplierB = Supplier::factory()->create(['nama_supplier' => 'Supplier Beta']);

    $this->produk1 = Produk::factory()->create([
        'nama' => 'Papan Gypsum 9mm',
        'satuan' => SatuanProduk::Lembar,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 50000,
        'harga_jual' => 70000,
        'stok_minimum' => 10,
    ]);

    $this->produk2 = Produk::factory()->create([
        'nama' => 'Papan Gypsum 12mm',
        'satuan' => SatuanProduk::Lembar,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 60000,
        'harga_jual' => 85000,
        'stok_minimum' => 10,
    ]);

    $pembelianAction = app(CreatePembelianAction::class);

    // PO 1: 100 * 50,000 = 5,000,000
    $this->po1 = $pembelianAction->execute([
        'supplier_id' => $this->supplierA->id,
        'tanggal' => now()->toDateString(),
        'no_kontainer' => 'CONT-01',
        'keterangan' => 'PO Alpha',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk1->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 100,
                'harga_beli_satuan' => 50000,
            ],
        ],
    ]);

    // PO 2: 50 * 60,000 = 3,000,000
    $this->po2 = $pembelianAction->execute([
        'supplier_id' => $this->supplierB->id,
        'tanggal' => now()->toDateString(),
        'no_kontainer' => 'CONT-02',
        'keterangan' => 'PO Beta',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk2->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 50,
                'harga_beli_satuan' => 60000,
            ],
        ],
    ]);
});

test('TEST 1 — Purchase report calculates total purchases and filters by supplier', function () {
    $this->actingAs($this->owner);

    Livewire::test(PurchaseReport::class)
        ->assertStatus(200)
        ->assertSee($this->po1->no_pembelian)
        ->assertSee($this->po2->no_pembelian)
        ->assertSee('Rp 8.000.000')
        ->set('supplier_id', $this->supplierA->id)
        ->assertSee($this->po1->no_pembelian)
        ->assertDontSee($this->po2->no_pembelian);
});

test('TEST 2 — User without view_purchase_reports cannot access purchase report route', function () {
    $this->actingAs($this->staffGudang);

    $this->get(route('laporan.pembelian'))->assertForbidden();
});
