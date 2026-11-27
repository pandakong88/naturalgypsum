<?php

namespace App\Http\Controllers\Payments;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Sales\Models\Penjualan;
use App\Http\Controllers\Controller;
use App\Livewire\Receivables\PiutangIndex;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PembayaranPrintController extends Controller
{
    /**
     * Cetak Surat Tagihan / Rekapitulasi Piutang Pelanggan (Statement of Account A4).
     */
    public function tagihanPelanggan(Pelanggan $pelanggan): View
    {
        abort_if(! auth()->user()->can('view_receivables') && ! auth()->user()->can('view_payments'), 403);

        $invoices = Penjualan::where('pelanggan_id', $pelanggan->id)
            ->whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->where('sisa_piutang', '>', 0)
            ->with([
                'details.produk.merek',
                'gudang',
                'alokasiPembayarans' => fn ($q) => $q->whereHas('pembayaran', fn ($pq) => $pq->whereNull('deleted_at')),
                'alokasiPembayarans.pembayaran',
            ])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $totalTagihan = (float) $invoices->sum('sisa_piutang');

        return view('print.tagihan-piutang', [
            'pelanggan' => $pelanggan,
            'invoices' => $invoices,
            'totalTagihan' => $totalTagihan,
            'tempoStandarHari' => PiutangIndex::TEMPO_STANDAR_HARI,
            'terbilang' => self::terbilang($totalTagihan),
        ]);
    }

    /**
     * Cetak Kuitansi Pembayaran Piutang resmi (A4 / A5).
     */
    public function kuitansi(Pembayaran $pembayaran): View
    {
        Gate::authorize('view', $pembayaran);

        $pembayaran->load([
            'pelanggan',
            'user',
            'creator',
            'alokasi.penjualan',
        ]);

        return view('print.kuitansi', [
            'pembayaran' => $pembayaran,
            'terbilang' => self::terbilang((float) $pembayaran->jumlah_bayar),
        ]);
    }

    /**
     * Cetak Struk Kuitansi Kasir Thermal (58mm / 80mm POS).
     */
    public function thermal(Pembayaran $pembayaran): View
    {
        Gate::authorize('view', $pembayaran);

        $pembayaran->load([
            'pelanggan',
            'user',
            'creator',
            'alokasi.penjualan',
        ]);

        return view('print.kuitansi-thermal', [
            'pembayaran' => $pembayaran,
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
