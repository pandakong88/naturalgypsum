<?php

use App\Domains\Administration\Models\ProfilPerusahaan;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Sales\Models\DetailPenjualan;
use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Sales\PenjualanCreate;
use App\Models\User;
use Database\Seeders\ProfilPerusahaanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(ProfilPerusahaanSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->pelanggan = Pelanggan::factory()->create([
        'nama' => 'Pelanggan Umum',
        'tipe' => TipePelanggan::Umum,
        'limit_piutang' => 10000000,
    ]);

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Toko Utama']);

    $this->pvcProduct = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Mahoni Wood 4m',
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.0,
        'isi_per_kemasan' => 15,
        'kemasan' => 'Dus',
        'harga_jual' => 12000.00,
    ]);

    $this->hollowProduct = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Hollow 4x4 Galvanis',
        'satuan' => SatuanProduk::Batang,
        'panjang_meter' => null,
        'isi_per_kemasan' => 20,
        'kemasan' => 'Ikat',
        'harga_jual' => 25000.00,
    ]);

    $this->pakuProduct = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Paku Beton Putih Marabu 1 inch (100 pcs)',
        'satuan' => SatuanProduk::Dus,
        'panjang_meter' => null,
        'isi_per_kemasan' => 100,
        'kemasan' => 'Dus',
        'harga_jual' => 18000.00,
    ]);

    $stockService = app(StockService::class);
    $stockService->increase($this->pvcProduct, $this->gudang, 500.0, JenisMutasiStok::PembelianMasuk, 'Initial Stock PVC');
    $stockService->increase($this->hollowProduct, $this->gudang, 500.0, JenisMutasiStok::PembelianMasuk, 'Initial Stock Hollow');
    $stockService->increase($this->pakuProduct, $this->gudang, 50.0, JenisMutasiStok::PembelianMasuk, 'Initial Stock Paku');
});

test('Kasir dapat menggunakan kalkulator kemasan untuk konversi Dus Plafon PVC ke Meter', function () {
    $this->actingAs($this->staffPenjualan);

    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->pvcProduct->id)
        ->call('openCalculator', 0)
        ->assertSet('isCalculatorModalOpen', true)
        ->assertSet('calcMode', 'dus_pvc')
        ->assertSet('pvcPanjang', 4.0)
        ->assertSet('packSize', 15.0)
        ->set('calcJmlKemasan', 5) // 5 dus = 75 lembar = 300 meter
        ->set('calcJmlEceran', 0)
        ->call('applyCalculation')
        ->assertSet('isCalculatorModalOpen', false);

    // Kuantitas di baris tabel harus 300 meter
    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->pvcProduct->id)
        ->call('openCalculator', 0)
        ->set('calcJmlKemasan', 5)
        ->set('calcJmlEceran', 0)
        ->call('applyCalculation');

    expect($component->get('items.0.jumlah'))->toBe(300.0)
        ->and($component->get('items.0.kemasan_deskripsi'))->toContain('5 Dus');
});

test('Kasir dapat menggunakan kalkulator kemasan untuk desimal eceran Plafon PVC (contoh 1.5 lembar = 6 meter)', function () {
    $this->actingAs($this->staffPenjualan);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->pvcProduct->id)
        ->call('openCalculator', 0)
        ->assertSet('isCalculatorModalOpen', true)
        ->set('calcJmlKemasan', 0)
        ->set('calcJmlEceran', '1.5') // 1.5 lembar x 4m = 6m
        ->call('applyCalculation');

    expect($component->get('items.0.jumlah'))->toBe(6.0)
        ->and($component->get('items.0.kemasan_deskripsi'))->toContain('1 lbr + 2m potongan');
});

test('Kasir dapat menggunakan kalkulator kemasan untuk konversi Ikat Hollow ke Batang', function () {
    $this->actingAs($this->staffPenjualan);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->hollowProduct->id)
        ->call('openCalculator', 0)
        ->assertSet('isCalculatorModalOpen', true)
        ->assertSet('calcMode', 'kemasan')
        ->assertSet('packSize', 20.0)
        ->set('calcJmlKemasan', 4) // 4 ikat * 20 = 80 batang
        ->set('calcJmlEceran', 5)  // 5 batang lepas = total 85 batang
        ->call('applyCalculation');

    expect($component->get('items.0.jumlah'))->toBe(85.0)
        ->and($component->get('items.0.kemasan_deskripsi'))->toContain('4 Ikat');
});

test('Kasir dapat menggunakan kalkulator kemasan untuk produk Paku/Sekrup Dus dengan eceran pcs', function () {
    $this->actingAs($this->staffPenjualan);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->pakuProduct->id)
        ->call('openCalculator', 0)
        ->assertSet('isCalculatorModalOpen', true)
        ->assertSet('calcMode', 'kemasan_dus')
        ->assertSet('packLabel', 'Dus')
        ->assertSet('packSize', 100.0)
        ->assertSee('Isi per Dus')
        ->assertSee('100 Pcs (biji)')
        ->assertDontSee('1 Dus = 100 Dus')
        ->set('calcJmlKemasan', 1)  // 1 dus utuh
        ->set('calcJmlEceran', 25)  // 25 pcs lepas = 0.25 dus -> total 1.25 dus
        ->call('applyCalculation');

    expect($component->get('items.0.jumlah'))->toBe(1.25)
        ->and($component->get('items.0.kemasan_deskripsi'))->toBe('1 Dus + 25 pcs');
});

test('Konfirmasi stok minus mencatat alasan kasir ke keterangan faktur', function () {
    $this->actingAs($this->staffPenjualan);

    // Minta 1000 meter padahal stok hanya 500
    Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->pvcProduct->id)
        ->set('items.0.jumlah', 1000)
        ->call('save')
        ->assertSet('isShortageModalOpen', true)
        ->set('shortageAlasan', 'barang_fisik_tiba')
        ->call('confirmShortageAndSave');

    $penjualan = Penjualan::latest('id')->first();
    expect($penjualan)->not->toBeNull()
        ->and($penjualan->keterangan)->toContain('[Stok Minus: Barang fisik sudah tiba di toko');
});

test('Cetak faktur memuat identitas ProfilPerusahaan dan informasi rekening transfer', function () {
    $this->actingAs($this->owner);

    $profil = ProfilPerusahaan::getSettings();
    $profil->update([
        'nama_toko' => 'CV NATURAL GYPSUM UTAMA',
        'alamat' => 'Jl. Gypsum No. 99',
    ]);

    $penjualan = Penjualan::factory()->create([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'metode_pembayaran' => 'transfer',
        'keterangan' => 'Test Nota Faktur',
    ]);

    $response = $this->get(route('penjualan.print.faktur', $penjualan));
    $response->assertStatus(200)
        ->assertSee('CV NATURAL GYPSUM UTAMA')
        ->assertSee('Jl. Gypsum No. 99')
        ->assertSee('REKENING PEMBAYARAN TRANSFER')
        ->assertSee('BCA');
});

test('Input kuantitas manual secara responsif menguraikan kemasan Dus dan Lembar untuk PVC', function () {
    $this->actingAs($this->staffPenjualan);

    // PVC 4m (1 dus = 15 lbr = 60m)
    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->pvcProduct->id);

    // 1. Ketik 60 meter (1 dus pas = 15 lembar)
    $component->set('items.0.jumlah', 60)
        ->assertSee('1 Dus (15 lbr)');
    expect($component->get('items.0.kemasan_deskripsi'))->toBe('1 Dus (15 lbr)');

    // 2. Ketik 64 meter (1 dus + 1 lembar = total 16 lembar)
    $component->set('items.0.jumlah', 64)
        ->assertSee('1 Dus + 1 lbr');
    expect($component->get('items.0.kemasan_deskripsi'))->toContain('1 Dus + 1 lbr');

    // 3. Ketik 80 meter (1 dus + 5 lembar = total 20 lembar)
    $component->set('items.0.jumlah', 80)
        ->assertSee('1 Dus + 5 lbr');
    expect($component->get('items.0.kemasan_deskripsi'))->toContain('1 Dus + 5 lbr');

    // 4. Ketik 120 meter (2 dus pas = 30 lembar)
    $component->set('items.0.jumlah', 120)
        ->assertSee('2 Dus (30 lbr)');
    expect($component->get('items.0.kemasan_deskripsi'))->toBe('2 Dus (30 lbr)');

    // 5. Ketik 12 meter (3 lembar eceran)
    $component->set('items.0.jumlah', 12)
        ->assertSee('3 lbr eceran');
    expect($component->get('items.0.kemasan_deskripsi'))->toContain('3 lbr eceran');

    // 6. Ketik 6 meter (1 lembar utuh + potongan 2 meter sisa)
    $component->set('items.0.jumlah', 6)
        ->assertSee('1 lbr + 2m potongan');
    expect($component->get('items.0.kemasan_deskripsi'))->toBe('1 lbr + 2m potongan (6m)');
});

test('Input kuantitas manual secara responsif menguraikan kemasan Ikat untuk Hollow', function () {
    $this->actingAs($this->staffPenjualan);

    // Hollow (1 ikat = 20 btg)
    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('gudang_id', $this->gudang->id)
        ->call('addQuickProduct', $this->hollowProduct->id);

    // 1. Ketik 85 batang (4 ikat + 5 batang)
    $component->set('items.0.jumlah', 85)
        ->assertSee('4 Ikat + 5 btg');
    expect($component->get('items.0.kemasan_deskripsi'))->toContain('4 Ikat + 5 btg');

    // 2. Ketik 100 batang (5 ikat pas)
    $component->set('items.0.jumlah', 100)
        ->assertSee('5 Ikat pas');
    expect($component->get('items.0.kemasan_deskripsi'))->toContain('5 Ikat pas');

    // 3. Ketik 15 batang eceran
    $component->set('items.0.jumlah', 15)
        ->assertSee('15 btg eceran');
    expect($component->get('items.0.kemasan_deskripsi'))->toBe('15 btg eceran');
});

test('Cetak surat jalan dan faktur menampilkan uraian kemasan fisik barang', function () {
    $this->actingAs($this->staffPenjualan);

    $penjualan = Penjualan::factory()->create([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'metode_pembayaran' => 'tunai',
    ]);

    // Tambahkan baris penjualan PVC 64m (1 Dus + 1 lbr)
    DetailPenjualan::factory()->create([
        'penjualan_id' => $penjualan->id,
        'produk_id' => $this->pvcProduct->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => 64.0,
        'harga_satuan' => 12000.0,
        'subtotal' => 768000.0,
    ]);

    // Surat Jalan harus memuat breakdown 1 Dus + 1 lbr
    $sjResponse = $this->get(route('penjualan.print.surat-jalan', $penjualan));
    $sjResponse->assertStatus(200)
        ->assertSee('1 Dus + 1 lbr');

    // Faktur harus memuat breakdown 1 Dus + 1 lbr
    $fakturResponse = $this->get(route('penjualan.print.faktur', $penjualan));
    $fakturResponse->assertStatus(200)
        ->assertSee('1 Dus + 1 lbr');
});
