<?php

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Receivables\Actions\CreatePembayaranAction;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Returns\Models\ReturPelanggan;
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

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan'); // has no cancel_sales

    $this->pelanggan = Pelanggan::factory()->create([
        'nama' => 'CV Abadi Perkasa',
        'tipe' => TipePelanggan::Umum,
        'limit_piutang' => 10000000,
    ]);

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Gypsum Board']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Gypsum 9mm Jayaboard',
        'harga_beli' => 60000.00,
        'harga_jual' => 80000.00,
        'harga_aplikator' => 80000.00,
        'harga_grosir' => 80000.00,
    ]);

    $this->stockService = app(StockService::class);
    $this->stockService->increase($this->produk, $this->gudang, 50, JenisMutasiStok::PembelianMasuk, 'INIT-STOCK');

    $this->createAction = app(CreatePenjualanAction::class);
    $this->cancelAction = app(CancelPenjualanAction::class);
});

test('pembatalan nota berhasil membalikkan stok ke gudang asal', function () {
    // 1. Create sale: 10 items
    $penjualan = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-21',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'harga_satuan' => 80000.00,
                'jumlah' => 10,
            ],
        ],
    ]);

    // Stock should be 50 - 10 = 40
    expect($this->stockService->getStock($this->produk, $this->gudang))->toBe(40.00);

    // 2. Cancel sale
    $cancelled = $this->cancelAction->execute($penjualan, 'Salah input jumlah barang', $this->owner->id);

    // Stock should be restored to 50
    expect($this->stockService->getStock($this->produk, $this->gudang))->toBe(50.00)
        ->and($cancelled->trashed())->toBeTrue()
        ->and($cancelled->alasan_batal)->toBe('Salah input jumlah barang')
        ->and($cancelled->dibatalkan_by)->toBe($this->owner->id)
        ->and($cancelled->dibatalkan_at)->not->toBeNull()
        ->and((float) $cancelled->sisa_piutang)->toBe(0.00);
});

test('pembatalan nota membatalkan pencatatan pembayaran kasir yang terikat', function () {
    // 1. Create sale: 5 items = 400.000
    $penjualan = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-21',
        'user_id' => $this->owner->id,
        'metode_pembayaran' => 'tunai',
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'harga_satuan' => 80000.00,
                'jumlah' => 5,
            ],
        ],
    ]);

    // Pay full in cash
    $pembayaran = app(CreatePembayaranAction::class)->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'tanggal' => '2026-10-21',
        'jumlah_bayar' => 400000.00,
        'metode' => MetodePembayaran::Tunai,
        'alokasi' => [
            [
                'penjualan_id' => $penjualan->id,
                'jumlah_dialokasikan' => 400000.00,
            ],
        ],
    ]);

    expect(AlokasiPembayaran::where('penjualan_id', $penjualan->id)->exists())->toBeTrue()
        ->and($pembayaran->trashed())->toBeFalse();

    // 2. Cancel sale
    $this->cancelAction->execute($penjualan, 'Pembeli batal belanja', $this->owner->id);

    // Payment should be soft deleted, allocations deleted
    expect(AlokasiPembayaran::where('penjualan_id', $penjualan->id)->exists())->toBeFalse()
        ->and($pembayaran->fresh()->trashed())->toBeTrue();
});

test('pembatalan nota ditolak jika terdapat transaksi retur pelanggan aktif', function () {
    $penjualan = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-21',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'harga_satuan' => 80000.00,
                'jumlah' => 10,
            ],
        ],
    ]);

    // Create a return linked to this sale
    ReturPelanggan::create([
        'no_retur' => 'RT-2026-0001',
        'tanggal' => '2026-10-21',
        'pelanggan_id' => $this->pelanggan->id,
        'penjualan_id' => $penjualan->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => 2,
        'harga_retur' => 80000.00,
        'alasan' => 'Barang cacat sedikit',
    ]);

    expect(function () use ($penjualan) {
        $this->cancelAction->execute($penjualan, 'Batal nota ini', $this->owner->id);
    })->toThrow(InvalidArgumentException::class, 'memiliki riwayat retur barang pelanggan');
});

test('staff penjualan tanpa izin cancel_sales tidak dapat membuka modal pembatalan di livewire', function () {
    $penjualan = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-21',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'harga_satuan' => 80000.00,
                'jumlah' => 5,
            ],
        ],
    ]);

    $this->actingAs($this->staffPenjualan);

    Livewire::test(PenjualanIndex::class)
        ->call('openCancelModal', $penjualan->id)
        ->assertForbidden();
});

test('owner dengan izin cancel_sales dapat membatalkan nota via livewire component', function () {
    $penjualan = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-10-21',
        'user_id' => $this->owner->id,
        'items' => [
            [
                'produk_id' => $this->produk->id,
                'harga_satuan' => 80000.00,
                'jumlah' => 4,
            ],
        ],
    ]);

    $this->actingAs($this->owner);

    Livewire::test(PenjualanIndex::class)
        ->call('openCancelModal', $penjualan->id)
        ->assertSet('isCancelModalOpen', true)
        ->assertSet('cancellingPenjualanId', $penjualan->id)
        ->set('alasanBatal', 'Salah ukuran gypsum, nota dibatalkan')
        ->call('confirmCancelPenjualan')
        ->assertSet('isCancelModalOpen', false)
        ->assertSee('berhasil dibatalkan');

    expect($penjualan->fresh()->trashed())->toBeTrue();
});
