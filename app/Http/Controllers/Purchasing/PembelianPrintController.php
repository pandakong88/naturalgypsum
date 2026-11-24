<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Purchasing\Models\Pembelian;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PembelianPrintController extends Controller
{
    /**
     * Cetak Bukti Penerimaan Barang / Faktur Pembelian (A4 / A5).
     */
    public function faktur(Pembelian $pembelian): View
    {
        Gate::authorize('view', $pembelian);

        $pembelian->load([
            'supplier',
            'user',
            'creator',
            'updater',
            'details.produk.kategori',
            'details.produk.merek',
            'details.gudang',
        ]);

        return view('print.pembelian', [
            'pembelian' => $pembelian,
            'terbilang' => self::terbilang((float) $pembelian->total),
            'isPO' => false,
        ]);
    }

    /**
     * Cetak Surat Pesanan / Purchase Order (PO).
     */
    public function po(Pembelian $pembelian): View
    {
        Gate::authorize('view', $pembelian);

        $pembelian->load([
            'supplier',
            'user',
            'creator',
            'updater',
            'details.produk.kategori',
            'details.produk.merek',
            'details.gudang',
        ]);

        return view('print.pembelian', [
            'pembelian' => $pembelian,
            'terbilang' => self::terbilang((float) $pembelian->total),
            'isPO' => true,
        ]);
    }

    /**
     * Helper konversi angka rupiah ke teks terbilang bahasa Indonesia.
     */
    public static function terbilang(float|int $angka): string
    {
        $angka = abs((int) floor($angka));
        if ($angka === 0) {
            return 'Nol Rupiah';
        }

        $result = trim(self::convertTerbilang($angka));
        $result = preg_replace('/\s+/', ' ', $result);

        return $result.' Rupiah';
    }

    private static function convertTerbilang(int $angka): string
    {
        $bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($angka < 12) {
            return $bilangan[$angka];
        }
        if ($angka < 20) {
            return self::convertTerbilang($angka - 10).' Belas';
        }
        if ($angka < 100) {
            return self::convertTerbilang((int) ($angka / 10)).' Puluh '.self::convertTerbilang($angka % 10);
        }
        if ($angka < 200) {
            return 'Seratus '.self::convertTerbilang($angka - 100);
        }
        if ($angka < 1000) {
            return self::convertTerbilang((int) ($angka / 100)).' Ratus '.self::convertTerbilang($angka % 100);
        }
        if ($angka < 2000) {
            return 'Seribu '.self::convertTerbilang($angka - 1000);
        }
        if ($angka < 1000000) {
            return self::convertTerbilang((int) ($angka / 1000)).' Ribu '.self::convertTerbilang($angka % 1000);
        }
        if ($angka < 1000000000) {
            return self::convertTerbilang((int) ($angka / 1000000)).' Juta '.self::convertTerbilang($angka % 1000000);
        }
        if ($angka < 1000000000000) {
            return self::convertTerbilang((int) ($angka / 1000000000)).' Miliar '.self::convertTerbilang(fmod($angka, 1000000000));
        }

        return self::convertTerbilang((int) ($angka / 1000000000000)).' Triliun '.self::convertTerbilang(fmod($angka, 1000000000000));
    }
}
