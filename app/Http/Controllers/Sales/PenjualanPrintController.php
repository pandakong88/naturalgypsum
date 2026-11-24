<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Sales\Models\Penjualan;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PenjualanPrintController extends Controller
{
    /**
     * Cetak Faktur Penjualan resmi (A4 / A5).
     */
    public function faktur(Penjualan $penjualan): View
    {
        Gate::authorize('view', $penjualan);

        $penjualan->load([
            'pelanggan',
            'gudang',
            'user',
            'creator',
            'details.produk.kategori',
            'details.produk.merek',
            'details.gudang',
        ]);

        return view('print.faktur', [
            'penjualan' => $penjualan,
        ]);
    }

    /**
     * Cetak Surat Jalan / Bukti Muat Barang Gudang & Sopir (A4 / A5, Tanpa Nominal Uang).
     */
    public function suratJalan(Penjualan $penjualan): View
    {
        Gate::authorize('view', $penjualan);

        $penjualan->load([
            'pelanggan',
            'gudang',
            'user',
            'creator',
            'details.produk.kategori',
            'details.produk.merek',
            'details.gudang',
        ]);

        return view('print.surat-jalan', [
            'penjualan' => $penjualan,
        ]);
    }

    /**
     * Cetak Struk Kasir Thermal (80mm / 58mm POS).
     */
    public function thermal(Penjualan $penjualan): View
    {
        Gate::authorize('view', $penjualan);

        $penjualan->load([
            'pelanggan',
            'gudang',
            'user',
            'creator',
            'details.produk',
        ]);

        return view('print.thermal', [
            'penjualan' => $penjualan,
        ]);
    }
}
