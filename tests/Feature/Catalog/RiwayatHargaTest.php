<?php

use App\Domains\Catalog\Actions\UpdateProdukAction;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\RiwayatHargaProduk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Actions\CreatePembelianAction;
use App\Domains\Purchasing\Actions\ReceivePembelianAction;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Livewire\Catalog\ProdukDetail;
use App\Livewire\Catalog\RiwayatHargaIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->staffPenjualan = User::factory()->create(['name' => 'Staff Penjualan']);
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Vifon']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->supplier = Supplier::factory()->create(['nama_supplier' => 'PT Vifon Nusantara']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'PVC-VF-101',
        'nama' => 'Plafon PVC Vifon Putih Glossy 4m',
        'satuan' => 'meter',
        'harga_beli' => 25000.00,
        'harga_jual' => 35000.00,
        'harga_aplikator' => 33000.00,
        'harga_grosir' => 31000.00,
    ]);
});

test('updating product prices via UpdateProdukAction records all changed price tiers', function () {
    $this->actingAs($this->owner);

    app(UpdateProdukAction::class)->execute($this->produk, [
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'PVC-VF-101',
        'nama' => 'Plafon PVC Vifon Putih Glossy 4m',
        'satuan' => 'meter',
        'harga_beli' => 27000.00, // +2000 (+8%)
        'harga_jual' => 38000.00, // +3000
        'harga_aplikator' => 35000.00, // +2000
        'harga_grosir' => 31000.00, // unchanged
        'stok_minimum' => 10,
    ]);

    $histories = RiwayatHargaProduk::where('produk_id', $this->produk->id)->get();
    expect($histories)->toHaveCount(3);

    $beliHistory = $histories->firstWhere('jenis_harga', 'harga_beli');
    expect((float) $beliHistory->harga_lama)->toBe(25000.0)
        ->and((float) $beliHistory->harga_baru)->toBe(27000.0)
        ->and((float) $beliHistory->selisih_nominal)->toBe(2000.0)
        ->and((float) $beliHistory->selisih_persen)->toBe(8.0)
        ->and($beliHistory->sumber)->toBe('master_update');
});

test('creating or receiving purchase with update_master_harga_beli records HPP price history with supplier reference', function () {
    $this->actingAs($this->owner);

    // 1. Direct received purchase updates HPP from 25000 to 26500
    $pembelian = app(CreatePembelianAction::class)->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => now()->toDateString(),
        'status' => StatusPembelian::Received,
        'update_master_harga_beli' => true,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 60,
                'harga_beli_satuan' => 26500,
            ],
        ],
    ]);

    $history1 = RiwayatHargaProduk::where('produk_id', $this->produk->id)->latest('id')->first();
    expect($history1)->not->toBeNull()
        ->and($history1->jenis_harga)->toBe('harga_beli')
        ->and((float) $history1->harga_lama)->toBe(25000.0)
        ->and((float) $history1->harga_baru)->toBe(26500.0)
        ->and($history1->sumber)->toBe('pembelian')
        ->and($history1->referensi)->toBe($pembelian->no_pembelian)
        ->and($history1->supplier_id)->toBe($this->supplier->id);

    // 2. PO received later updates HPP from 26500 to 24000
    $po = app(CreatePembelianAction::class)->execute([
        'supplier_id' => $this->supplier->id,
        'tanggal' => now()->toDateString(),
        'status' => StatusPembelian::Ordered,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'gudang_id' => $this->gudang->id,
                'jumlah' => 120,
                'harga_beli_satuan' => 24000,
            ],
        ],
    ]);

    app(ReceivePembelianAction::class)->execute(
        pembelian: $po,
        userId: $this->owner->id,
        tanggalDiterima: now(),
        updateMasterHargaBeli: true
    );

    $history2 = RiwayatHargaProduk::where('produk_id', $this->produk->id)->latest('id')->first();
    expect((float) $history2->harga_lama)->toBe(26500.0)
        ->and((float) $history2->harga_baru)->toBe(24000.0)
        ->and((float) $history2->selisih_nominal)->toBe(-2500.0)
        ->and($history2->referensi)->toBe($po->no_pembelian);
});

test('RiwayatHargaIndex and ProdukDetail hide HPP history from users without view_purchase_prices permission', function () {
    $this->actingAs($this->owner);

    app(UpdateProdukAction::class)->execute($this->produk, [
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'PVC-VF-101',
        'nama' => 'Plafon PVC Vifon Putih Glossy 4m',
        'satuan' => 'meter',
        'harga_beli' => 28123.00,
        'harga_jual' => 39456.00,
        'harga_aplikator' => 33000.00,
        'harga_grosir' => 31000.00,
        'stok_minimum' => 10,
    ]);

    // Owner sees both purchase price and selling price history
    Livewire::test(RiwayatHargaIndex::class)
        ->assertStatus(200)
        ->assertSee('28.123')
        ->assertSee('39.456')
        ->assertSee('Harga Beli (HPP)');

    // Staff Penjualan ONLY sees selling price history, never HPP history
    $this->actingAs($this->staffPenjualan);

    Livewire::test(RiwayatHargaIndex::class)
        ->assertStatus(200)
        ->assertDontSee('28.123')
        ->assertDontSee('Harga Beli (HPP)')
        ->assertSee('39.456')
        ->assertSee('Harga Jual Umum');

    Livewire::test(ProdukDetail::class, ['id' => $this->produk->id])
        ->assertStatus(200)
        ->assertDontSee('28.123')
        ->assertSee('39.456');
});
