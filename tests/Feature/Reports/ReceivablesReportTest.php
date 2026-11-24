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
use App\Livewire\Reports\ReceivablesReport;
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
    $this->pelanggan = Pelanggan::factory()->create(['nama' => 'Toko Subur']);

    $this->produk = Produk::factory()->create([
        'nama' => 'Papan Gypsum',
        'satuan' => SatuanProduk::Lembar,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 50000,
        'harga_jual' => 100000,
        'stok_minimum' => 10,
    ]);

    app(StockService::class)->increase($this->produk, $this->gudang, 100, JenisMutasiStok::PembelianMasuk, 'INIT');

    $salesAction = app(CreatePenjualanAction::class);

    // Invoice 1: 20 * 100,000 = 2,000,000, partially paid 500,000 -> sisa 1,500,000
    $this->saleUnpaid = $salesAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->subDays(5)->toDateString(),
        'biaya_ongkir' => 0,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'jumlah' => 20,
                'harga_satuan' => 100000,
                'diskon_persen' => 0,
            ],
        ],
    ]);

    app(CreatePembayaranAction::class)->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'tanggal' => now()->toDateString(),
        'jumlah_bayar' => 500000,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $this->saleUnpaid->id,
                'jumlah_dialokasikan' => 500000,
            ],
        ],
    ]);

    // Invoice 2: 10 * 100,000 = 1,000,000, fully paid 1,000,000 -> sisa 0
    $this->salePaid = $salesAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => now()->subDays(10)->toDateString(),
        'biaya_ongkir' => 0,
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'jumlah' => 10,
                'harga_satuan' => 100000,
                'diskon_persen' => 0,
            ],
        ],
    ]);

    app(CreatePembayaranAction::class)->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'tanggal' => now()->toDateString(),
        'jumlah_bayar' => 1000000,
        'metode' => MetodePembayaran::Tunai,
        'user_id' => $this->owner->id,
        'alokasi' => [
            [
                'penjualan_id' => $this->salePaid->id,
                'jumlah_dialokasikan' => 1000000,
            ],
        ],
    ]);
});

test('TEST 1 — Receivables report only lists unpaid or partially paid invoices with sisa_piutang > 0', function () {
    $this->actingAs($this->owner);

    Livewire::test(ReceivablesReport::class)
        ->assertStatus(200)
        ->assertSee($this->saleUnpaid->no_faktur)
        ->assertDontSee($this->salePaid->no_faktur)
        ->assertSee('Rp 1.500.000');
});
