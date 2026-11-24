<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Production\Actions\CreateProduksiAction;
use App\Livewire\Reports\ProductionReport;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Hollow']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);

    $this->produk = Produk::factory()->create([
        'nama' => 'Hollow Produksi Sendiri',
        'satuan' => SatuanProduk::Batang,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 15000,
        'harga_jual' => 22000,
        'stok_minimum' => 10,
    ]);

    $this->prod = app(CreateProduksiAction::class)->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->toDateString(),
        'catatan' => 'Batch uji coba',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'jumlah' => 250,
            ],
        ],
    ]);
});

test('TEST 1 — Production report shows batch counts and total output units', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProductionReport::class)
        ->assertStatus(200)
        ->assertSee($this->prod->no_produksi)
        ->assertSee('1 Batch')
        ->assertSee('250,000 Unit');
});
