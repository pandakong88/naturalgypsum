<?php

use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Livewire\Catalog\ProdukDetail;
use App\Livewire\Catalog\ProdukIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner'); // has view_purchase_prices

    $this->staffPenjualan = User::factory()->create(['name' => 'Staff Penjualan']);
    $this->staffPenjualan->assignRole('Staff Penjualan'); // does NOT have view_purchase_prices

    $this->kategori = Kategori::factory()->create();
    $this->merek = Merek::factory()->create();

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Premium 4m',
        'harga_beli' => 75000.00,
        'harga_jual' => 95000.00,
    ]);
});

test('TEST 1 — User WITH view_purchase_prices CAN see purchase price in catalog', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProdukIndex::class)
        ->assertStatus(200)
        ->assertSee('Harga Beli')
        ->assertSee('75.000');

    Livewire::test(ProdukDetail::class, ['id' => $this->produk->id])
        ->assertStatus(200)
        ->assertSee('75.000');
});

test('TEST 2 — User WITHOUT view_purchase_prices CANNOT see purchase price in catalog', function () {
    $this->actingAs($this->staffPenjualan);

    Livewire::test(ProdukIndex::class)
        ->assertStatus(200)
        ->assertDontSee('Harga Beli')
        ->assertDontSee('75.000')
        ->assertSee('Harga Jual')
        ->assertSee('95.000');

    Livewire::test(ProdukDetail::class, ['id' => $this->produk->id])
        ->assertStatus(200)
        ->assertDontSee('75.000')
        ->assertSee('95.000');
});
