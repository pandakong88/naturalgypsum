<?php

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Sales\Actions\CancelPenjualanAction;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Livewire\Sales\PenjualanIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->pelanggan1 = Pelanggan::factory()->create(['nama' => 'Pak Haji Komar', 'kontak' => '081299887766']);
    $this->pelanggan2 = Pelanggan::factory()->create(['nama' => 'Toko Cahaya Gypsum', 'kontak' => '085511223344']);

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Rangka Metal']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Boral']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Pusat']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Hollow 4x4 Tebal 0.3mm',
        'harga_beli' => 18000.00,
        'harga_jual' => 24000.00,
        'harga_aplikator' => 24000.00,
        'harga_grosir' => 24000.00,
    ]);

    $this->stockService = app(StockService::class);
    $this->stockService->increase($this->produk, $this->gudang, 200, JenisMutasiStok::PembelianMasuk, 'INIT');

    $this->createAction = app(CreatePenjualanAction::class);

    // Sale 1: Tunai
    $this->sale1 = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan1->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->toDateString(),
        'metode_pembayaran' => 'tunai',
        'nama_sopir' => 'Sopir Supri',
        'plat_nomor' => 'D 9999 AA',
        'user_id' => $this->owner->id,
        'items' => [['produk_id' => $this->produk->id, 'harga_satuan' => 24000.00, 'jumlah' => 10]],
    ]);

    // Sale 2: Tempo
    $this->sale2 = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan2->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->subDays(5)->toDateString(),
        'metode_pembayaran' => 'tempo',
        'user_id' => $this->owner->id,
        'items' => [['produk_id' => $this->produk->id, 'harga_satuan' => 24000.00, 'jumlah' => 5]],
    ]);
});

test('filter metode pembayaran menyaring transaksi sesuai pilihan', function () {
    $this->actingAs($this->owner);

    // Filter tunai
    Livewire::test(PenjualanIndex::class)
        ->set('metodePembayaranFilter', 'tunai')
        ->assertSee($this->sale1->no_penjualan)
        ->assertDontSee($this->sale2->no_penjualan);

    // Filter tempo
    Livewire::test(PenjualanIndex::class)
        ->set('metodePembayaranFilter', 'tempo')
        ->assertSee($this->sale2->no_penjualan)
        ->assertDontSee($this->sale1->no_penjualan);
});

test('pencarian menemukan transaksi berdasarkan plat nomor dan kontak pelanggan', function () {
    $this->actingAs($this->owner);

    // Search by plat nomor
    Livewire::test(PenjualanIndex::class)
        ->set('search', 'D 9999 AA')
        ->assertSee($this->sale1->no_penjualan)
        ->assertDontSee($this->sale2->no_penjualan);

    // Search by nomor kontak pelanggan 2
    Livewire::test(PenjualanIndex::class)
        ->set('search', '085511223344')
        ->assertSee($this->sale2->no_penjualan)
        ->assertDontSee($this->sale1->no_penjualan);
});

test('filter status transaksi menampilkan transaksi aktif atau dibatalkan', function () {
    $this->actingAs($this->owner);

    // Batalkan sale 1
    app(CancelPenjualanAction::class)->execute($this->sale1, 'Batal uji coba', $this->owner->id);

    // Default 'aktif' -> sale 1 hidden, sale 2 shown
    Livewire::test(PenjualanIndex::class)
        ->assertSet('statusTransaksiFilter', 'aktif')
        ->assertDontSee($this->sale1->no_penjualan)
        ->assertSee($this->sale2->no_penjualan);

    // Status 'dibatalkan' -> sale 1 shown with BATAL, sale 2 hidden
    Livewire::test(PenjualanIndex::class)
        ->set('statusTransaksiFilter', 'dibatalkan')
        ->assertSee($this->sale1->no_penjualan)
        ->assertSee('BATAL')
        ->assertDontSee($this->sale2->no_penjualan);

    // Status 'semua' -> both shown
    Livewire::test(PenjualanIndex::class)
        ->set('statusTransaksiFilter', 'semua')
        ->assertSee($this->sale1->no_penjualan)
        ->assertSee($this->sale2->no_penjualan);
});
