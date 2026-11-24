<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Receivables\Actions\CreatePembayaranAction;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Livewire\Reports\SalesReport;
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
    $this->pelangganA = Pelanggan::factory()->create(['nama' => 'Pelanggan Alpha']);
    $this->pelangganB = Pelanggan::factory()->create(['nama' => 'Pelanggan Beta']);

    $this->produk = Produk::factory()->create([
        'nama' => 'Papan Gypsum',
        'satuan' => SatuanProduk::Lembar,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 50000,
        'harga_jual' => 70000,
        'stok_minimum' => 10,
    ]);

    app(StockService::class)->increase($this->produk, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT');

    $salesAction = app(CreatePenjualanAction::class);

    // Sale 1: 10 items * 70,000 = 700,000
    $this->sale1 = $salesAction->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->toDateString(),
        'biaya_ongkir' => 0,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'jumlah' => 10,
                'harga_satuan' => 70000,
                'diskon_persen' => 0,
            ],
        ],
    ]);

    // Pay Sale 1 completely
    app(CreatePembayaranAction::class)->execute([
        'pelanggan_id' => $this->pelangganA->id,
        'tanggal' => now()->toDateString(),
        'jumlah_bayar' => 700000,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'allocations' => [
            [
                'penjualan_id' => $this->sale1->id,
                'jumlah_alokasi' => 700000,
            ],
        ],
    ]);

    // Sale 2: 20 items * 70,000 = 1,400,000 (Unpaid)
    $this->sale2 = $salesAction->execute([
        'pelanggan_id' => $this->pelangganB->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->toDateString(),
        'biaya_ongkir' => 0,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'jumlah' => 20,
                'harga_satuan' => 70000,
                'diskon_persen' => 0,
            ],
        ],
    ]);
});

test('TEST 1 — Sales report shows total sales, paid, and receivables correctly', function () {
    $this->actingAs($this->owner);

    Livewire::test(SalesReport::class)
        ->assertStatus(200)
        ->assertSee($this->sale1->no_faktur)
        ->assertSee($this->sale2->no_faktur)
        ->assertSee('Rp 2.100.000') // Total Penjualan
        ->assertSee('Rp 700.000')   // Total Terbayar
        ->assertSee('Rp 1.400.000') // Total Sisa Piutang
        ->set('pelanggan_id', $this->pelangganA->id)
        ->assertSee($this->sale1->no_faktur)
        ->assertDontSee($this->sale2->no_faktur);
});
