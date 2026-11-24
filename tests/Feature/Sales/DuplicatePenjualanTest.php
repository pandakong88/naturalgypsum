<?php

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Livewire\Sales\PenjualanCreate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->pelanggan = Pelanggan::factory()->create([
        'nama' => 'CV Mandiri Sejahtera',
        'tipe' => TipePelanggan::Langganan,
        'limit_piutang' => 20000000,
    ]);

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'PVC Panel']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Panda PVC']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Depo 1']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Motif Kayu 4m',
        'harga_beli' => 15000.00,
        'harga_jual' => 25000.00,
        'harga_aplikator' => 24000.00,
        'harga_grosir' => 22000.00,
    ]);

    $this->stockService = app(StockService::class);
    $this->stockService->increase($this->produk, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT-STOCK');
});

test('membuka form penjualan dengan duplicate_id berhasil menyalin pelanggan, item, dan driver info', function () {
    $this->actingAs($this->owner);

    // 1. Buat faktur lama
    $sale = app(CreatePenjualanAction::class)->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-10',
        'biaya_ongkir' => 15000.00,
        'nama_sopir' => 'Bambang',
        'plat_nomor' => 'B 1234 CD',
        'alamat_pengiriman' => 'Jl. Merdeka No. 10',
        'keterangan' => 'Kirim siang',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'harga_satuan' => 22000.00,
                'jumlah' => 10,
            ],
        ],
    ]);

    // 2. Mount PenjualanCreate with duplicate_id parameter
    $test = Livewire::test(PenjualanCreate::class, ['duplicate_id' => $sale->id])
        ->assertSet('pelanggan_id', $this->pelanggan->id)
        ->assertSet('gudang_id', $this->gudang->id)
        ->assertSet('biaya_ongkir', 15000.0)
        ->assertSet('nama_sopir', 'Bambang')
        ->assertSet('plat_nomor', 'B 1234 CD')
        ->assertSet('alamat_pengiriman', 'Jl. Merdeka No. 10')
        ->assertSet('keterangan', "Duplikat dari {$sale->no_penjualan}: Kirim siang");

    $items = $test->get('items');
    expect($items)->toHaveCount(1)
        ->and($items[0]['produk_id'])->toBe($this->produk->id)
        ->and((float) $items[0]['jumlah'])->toBe(10.00);
});

test('membuka URL route penjualan.create?duplicate_id=X via browser GET request berhasil menduplikasi pesanan', function () {
    $this->actingAs($this->owner);

    $sale = app(CreatePenjualanAction::class)->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-10',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'harga_satuan' => 22000.00,
                'jumlah' => 10,
            ],
        ],
    ]);

    $response = $this->get(route('penjualan.create', ['duplicate_id' => $sale->id]));
    $response->assertOk();
    $response->assertSee($this->pelanggan->nama);
    $response->assertSee($this->produk->nama);
});
