<?php

namespace Database\Seeders;

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Receivables\Actions\CreatePembayaranAction;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RealisticPenjualanMutasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('email', 'owner@naturalgypsum.test')->first() ?? User::first();
        if (! $owner) {
            return;
        }

        $stockService = app(StockService::class);
        $penjualanAction = app(CreatePenjualanAction::class);
        $pembayaranAction = app(CreatePembayaranAction::class);

        // 1. Buat / Pastikan 3 Gudang sesuai permintaan user
        $tokoUtama = Gudang::firstOrCreate(
            ['nama_gudang' => 'Toko Utama'],
            [
                'lokasi' => 'Jl. Merdeka No. 45 - Ruko Display & Kasir Retail',
                'created_by' => $owner->id,
            ]
        );

        $gudangSelatan = Gudang::firstOrCreate(
            ['nama_gudang' => 'Gudang Selatan'],
            [
                'lokasi' => 'Kawasan Industri Selatan Kav. 12 - Gudang Papan & Hollow',
                'created_by' => $owner->id,
            ]
        );

        $gudangUtara = Gudang::firstOrCreate(
            ['nama_gudang' => 'Gudang Utara'],
            [
                'lokasi' => 'Jl. Lingkar Utara KM 5 - Gudang Transit PVC & Profil Gypsum',
                'created_by' => $owner->id,
            ]
        );

        // 2. Pastikan Pelanggan realistis dari 3 Tipe
        $pelangganUmum = Pelanggan::firstOrCreate(
            ['nama' => 'Bpk. Ahmad (Rumah Pribadi)'],
            [
                'kontak' => '0812-3344-5566',
                'tipe' => TipePelanggan::Umum,
                'limit_piutang' => 0.00,
                'created_by' => $owner->id,
            ]
        );

        $pelangganAplikator = Pelanggan::firstOrCreate(
            ['nama' => 'Bpk. Sugeng (Mandor Plafon Gypsum)'],
            [
                'kontak' => '0857-8899-1122',
                'tipe' => TipePelanggan::Pemborong, // Aplikator
                'limit_piutang' => 20000000.00,
                'created_by' => $owner->id,
            ]
        );

        $pelangganToko = Pelanggan::firstOrCreate(
            ['nama' => 'Toko Besi & Bangunan Rejeki Berkah'],
            [
                'kontak' => '0821-4455-6677',
                'tipe' => TipePelanggan::Langganan, // Toko
                'limit_piutang' => 50000000.00,
                'created_by' => $owner->id,
            ]
        );

        // 3. Ambil Produk Master
        $produks = Produk::all();
        if ($produks->isEmpty()) {
            return;
        }

        $papanGypsum = $produks->firstWhere('satuan.value', 'lembar') ?? $produks->first();
        $pvcBoard = $produks->firstWhere('satuan.value', 'meter') ?? $produks->skip(1)->first() ?? $papanGypsum;
        $kompon = $produks->firstWhere('satuan.value', 'zak') ?? $produks->skip(2)->first() ?? $papanGypsum;
        $sekrup = $produks->firstWhere('satuan.value', 'dus') ?? $produks->skip(3)->first() ?? $papanGypsum;

        // 4. Masukkan Stok Awal Realistis di 3 Gudang
        // Toko Utama: stok harian display
        $stockService->increase($papanGypsum, $tokoUtama, 40, JenisMutasiStok::PembelianMasuk, 'PB-INIT-01', 'pembelian', 1, $owner->id, Carbon::now()->subDays(15));
        $stockService->increase($pvcBoard, $tokoUtama, 80, JenisMutasiStok::PembelianMasuk, 'PB-INIT-02', 'pembelian', 2, $owner->id, Carbon::now()->subDays(15));
        $stockService->increase($kompon, $tokoUtama, 25, JenisMutasiStok::PembelianMasuk, 'PB-INIT-03', 'pembelian', 3, $owner->id, Carbon::now()->subDays(15));
        $stockService->increase($sekrup, $tokoUtama, 20, JenisMutasiStok::PembelianMasuk, 'PB-INIT-04', 'pembelian', 4, $owner->id, Carbon::now()->subDays(15));

        // Gudang Selatan: Pusat stok gypsum borongan
        $stockService->increase($papanGypsum, $gudangSelatan, 350, JenisMutasiStok::PembelianMasuk, 'PB-INIT-05', 'pembelian', 5, $owner->id, Carbon::now()->subDays(14));
        $stockService->increase($kompon, $gudangSelatan, 150, JenisMutasiStok::PembelianMasuk, 'PB-INIT-06', 'pembelian', 6, $owner->id, Carbon::now()->subDays(14));

        // Gudang Utara: Pusat stok PVC dan aksesoris
        $stockService->increase($pvcBoard, $gudangUtara, 500, JenisMutasiStok::PembelianMasuk, 'PB-INIT-07', 'pembelian', 7, $owner->id, Carbon::now()->subDays(14));
        $stockService->increase($sekrup, $gudangUtara, 100, JenisMutasiStok::PembelianMasuk, 'PB-INIT-08', 'pembelian', 8, $owner->id, Carbon::now()->subDays(14));

        // 5. TRANSAKSI 1: Penjualan Retail Tunai Kasir di Toko Utama (Pelanggan Umum)
        $pj1 = $penjualanAction->execute([
            'pelanggan_id' => $pelangganUmum->id,
            'gudang_id' => $tokoUtama->id,
            'tanggal' => Carbon::now()->subDays(3)->toDateString(),
            'biaya_ongkir' => 0,
            'keterangan' => 'Belanja eceran langsung bawa sendiri',
            'items' => [
                [
                    'produk_id' => $papanGypsum->id,
                    'harga_satuan' => (float) $papanGypsum->harga_jual,
                    'jumlah' => 5, // Keluar dari Toko Utama
                    'gudang_id' => $tokoUtama->id,
                ],
                [
                    'produk_id' => $kompon->id,
                    'harga_satuan' => (float) $kompon->harga_jual,
                    'jumlah' => 2,
                    'gudang_id' => $tokoUtama->id,
                ],
            ],
            'user_id' => $owner->id,
        ]);

        // Bayar lunas tunai di tempat
        $pembayaranAction->execute([
            'pelanggan_id' => $pelangganUmum->id,
            'tanggal' => Carbon::now()->subDays(3)->toDateString(),
            'jumlah_bayar' => (float) $pj1->total_akhir,
            'metode' => MetodePembayaran::Tunai,
            'catatan' => 'Pembayaran kasir lunas nota '.$pj1->no_penjualan,
            'alokasi' => [
                [
                    'penjualan_id' => $pj1->id,
                    'jumlah_dialokasikan' => (float) $pj1->total_akhir,
                ],
            ],
        ]);

        // 6. TRANSAKSI 2: Proyek Borongan Aplikator Multi-Gudang (Split Pengambilan)
        // Mandor Sugeng beli 80 lembar papan gypsum: 30 lembar dari Toko Utama, 50 lembar ambil di Gudang Selatan
        $pj2 = $penjualanAction->execute([
            'pelanggan_id' => $pelangganAplikator->id,
            'gudang_id' => $tokoUtama->id,
            'tanggal' => Carbon::now()->subDays(2)->toDateString(),
            'biaya_ongkir' => 50000,
            'keterangan' => 'Proyek Ruko Sentosa - Muatan diambil 30 di Toko Utama & 50 mampir di Gudang Selatan',
            'items' => [
                [
                    'produk_id' => $papanGypsum->id,
                    'harga_satuan' => (float) ($papanGypsum->harga_aplikator ?: $papanGypsum->harga_jual),
                    'jumlah' => 30,
                    'gudang_id' => $tokoUtama->id, // Potong stok Toko Utama
                ],
                [
                    'produk_id' => $papanGypsum->id,
                    'harga_satuan' => (float) ($papanGypsum->harga_aplikator ?: $papanGypsum->harga_jual),
                    'jumlah' => 50,
                    'gudang_id' => $gudangSelatan->id, // Potong stok Gudang Selatan
                ],
                [
                    'produk_id' => $sekrup->id,
                    'harga_satuan' => (float) ($sekrup->harga_aplikator ?: $sekrup->harga_jual),
                    'jumlah' => 4,
                    'gudang_id' => $tokoUtama->id,
                ],
            ],
            'user_id' => $owner->id,
        ]);

        // Pembayaran transfer DP sebagian, sisanya piutang tempo
        $dpAplikator = round((float) $pj2->total_akhir * 0.5, 2);
        $pembayaranAction->execute([
            'pelanggan_id' => $pelangganAplikator->id,
            'tanggal' => Carbon::now()->subDays(2)->toDateString(),
            'jumlah_bayar' => $dpAplikator,
            'metode' => MetodePembayaran::Transfer,
            'catatan' => 'Transfer DP Proyek nota '.$pj2->no_penjualan,
            'alokasi' => [
                [
                    'penjualan_id' => $pj2->id,
                    'jumlah_dialokasikan' => $dpAplikator,
                ],
            ],
        ]);

        // 7. TRANSAKSI 3: Pasokan Toko Langganan (Gudang Utara & Gudang Selatan)
        // Toko Rejeki Berkah order PVC Vinyl 150 meter dari Gudang Utara & Kompon 30 zak dari Gudang Selatan
        $penjualanAction->execute([
            'pelanggan_id' => $pelangganToko->id,
            'gudang_id' => $gudangUtara->id,
            'tanggal' => Carbon::now()->subDay()->toDateString(),
            'biaya_ongkir' => 100000,
            'keterangan' => 'Pengiriman armada truk toko ke Toko Rejeki Berkah (Tempo 30 Hari)',
            'items' => [
                [
                    'produk_id' => $pvcBoard->id,
                    'harga_satuan' => (float) ($pvcBoard->harga_grosir ?: $pvcBoard->harga_jual),
                    'jumlah' => 150,
                    'gudang_id' => $gudangUtara->id,
                ],
                [
                    'produk_id' => $kompon->id,
                    'harga_satuan' => (float) ($kompon->harga_grosir ?: $kompon->harga_jual),
                    'jumlah' => 30,
                    'gudang_id' => $gudangSelatan->id,
                ],
            ],
            'user_id' => $owner->id,
        ]);
    }
}
