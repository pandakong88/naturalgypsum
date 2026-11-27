<?php

namespace App\Livewire\Reports;

use App\Domains\Inventory\Models\Stok;
use App\Domains\Production\Models\Produksi;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Sales\Models\DetailPenjualan;
use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Receivables\PiutangIndex;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hub Laporan & Analisis')]
class ReportHub extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $canViewPurchasePrices = $user->can('view_purchase_prices');

        $startOfThisMonth = Carbon::now()->startOfMonth()->startOfDay();
        $endOfThisMonth = Carbon::now()->endOfMonth()->endOfDay();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth()->startOfDay();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth()->endOfDay();

        // 1. Sales & Gross Profit Snapshot (This Month vs Last Month)
        $thisMonthSalesQuery = Penjualan::whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->whereBetween('tanggal', [$startOfThisMonth, $endOfThisMonth]);

        $omsetBulanIni = (float) (clone $thisMonthSalesQuery)->sum('total_akhir');
        $transaksiBulanIni = (int) (clone $thisMonthSalesQuery)->count();

        $omsetBulanLalu = (float) Penjualan::whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->whereBetween('tanggal', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_akhir');

        $pertumbuhanOmset = $omsetBulanLalu > 0
            ? round((($omsetBulanIni - $omsetBulanLalu) / $omsetBulanLalu) * 100, 1)
            : ($omsetBulanIni > 0 ? 100.0 : 0.0);

        $labaKotorBulanIni = 0.0;
        $marginBulanIni = 0.0;

        if ($canViewPurchasePrices) {
            $profitRow = DetailPenjualan::query()
                ->join('penjualan', 'detail_penjualan.penjualan_id', '=', 'penjualan.id')
                ->join('produk', 'detail_penjualan.produk_id', '=', 'produk.id')
                ->whereNull('penjualan.deleted_at')
                ->whereNull('penjualan.dibatalkan_at')
                ->whereBetween('penjualan.tanggal', [$startOfThisMonth, $endOfThisMonth])
                ->selectRaw('COALESCE(SUM(detail_penjualan.subtotal), 0) as revenue, COALESCE(SUM(detail_penjualan.jumlah * produk.harga_beli), 0) as cogs')
                ->first();

            $rev = (float) ($profitRow->revenue ?? 0);
            $cogs = (float) ($profitRow->cogs ?? 0);
            $labaKotorBulanIni = $rev - $cogs;
            $marginBulanIni = $rev > 0 ? round(($labaKotorBulanIni / $rev) * 100, 1) : 0.0;
        }

        // 2. Cash In (Payments This Month)
        $kasMasukBulanIni = (float) Pembayaran::whereNull('deleted_at')
            ->whereBetween('tanggal', [$startOfThisMonth, $endOfThisMonth])
            ->sum('jumlah_bayar');

        // 3. Receivables & 14-Day Overdue Snapshot
        $unpaidQuery = Penjualan::whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->where('sisa_piutang', '>', 0);

        $totalPiutang = (float) (clone $unpaidQuery)->sum('sisa_piutang');
        $fakturBelumLunas = (int) (clone $unpaidQuery)->count();
        $cutoff14Days = Carbon::today()->subDays(PiutangIndex::TEMPO_STANDAR_HARI)->toDateString();
        $piutangLewatTempo = (float) (clone $unpaidQuery)->whereDate('tanggal', '<', $cutoff14Days)->sum('sisa_piutang');
        $fakturLewatTempo = (int) (clone $unpaidQuery)->whereDate('tanggal', '<', $cutoff14Days)->count();

        // 4. Stock & Inventory Valuation Snapshot
        $stockQuery = Stok::query()->whereHas('produk')->whereHas('gudang');
        $totalSkuStok = (int) (clone $stockQuery)->count();
        $stokKritisCount = (int) (clone $stockQuery)->whereHas('produk', function ($q) {
            $q->whereColumn('stok.jumlah', '<=', 'produk.stok_minimum');
        })->count();

        $nilaiModalStok = 0.0;
        if ($canViewPurchasePrices) {
            $nilaiModalStok = (float) (clone $stockQuery)
                ->join('produk', 'stok.produk_id', '=', 'produk.id')
                ->selectRaw('COALESCE(SUM(stok.jumlah * produk.harga_beli), 0) as val')
                ->value('val');
        }

        // 5. Purchasing & Supplier Payables Snapshot
        $purchaseMonthQuery = Pembelian::whereNull('deleted_at')
            ->where('status', '!=', 'dibatalkan')
            ->whereBetween('tanggal', [$startOfThisMonth, $endOfThisMonth]);

        $totalPoBulanIni = (int) (clone $purchaseMonthQuery)->count();
        $belanjaBulanIni = $canViewPurchasePrices ? (float) (clone $purchaseMonthQuery)->sum('total') : 0.0;
        $hutangSupplier = $canViewPurchasePrices
            ? (float) Pembelian::whereNull('deleted_at')->where('status', '!=', 'dibatalkan')->where('sisa_hutang', '>', 0)->sum('sisa_hutang')
            : 0.0;

        // 6. Production Snapshot This Month
        $batchProduksiBulanIni = (int) Produksi::whereNull('deleted_at')
            ->whereBetween('tanggal', [$startOfThisMonth, $endOfThisMonth])
            ->count();

        return view('livewire.reports.hub', [
            'canViewPurchasePrices' => $canViewPurchasePrices,
            'omsetBulanIni' => $omsetBulanIni,
            'transaksiBulanIni' => $transaksiBulanIni,
            'omsetBulanLalu' => $omsetBulanLalu,
            'pertumbuhanOmset' => $pertumbuhanOmset,
            'labaKotorBulanIni' => $labaKotorBulanIni,
            'marginBulanIni' => $marginBulanIni,
            'kasMasukBulanIni' => $kasMasukBulanIni,
            'totalPiutang' => $totalPiutang,
            'fakturBelumLunas' => $fakturBelumLunas,
            'piutangLewatTempo' => $piutangLewatTempo,
            'fakturLewatTempo' => $fakturLewatTempo,
            'totalSkuStok' => $totalSkuStok,
            'stokKritisCount' => $stokKritisCount,
            'nilaiModalStok' => $nilaiModalStok,
            'totalPoBulanIni' => $totalPoBulanIni,
            'belanjaBulanIni' => $belanjaBulanIni,
            'hutangSupplier' => $hutangSupplier,
            'batchProduksiBulanIni' => $batchProduksiBulanIni,
        ]);
    }
}
