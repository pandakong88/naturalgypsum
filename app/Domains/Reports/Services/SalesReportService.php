<?php

namespace App\Domains\Reports\Services;

use App\Domains\Sales\Models\DetailPenjualan;
use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesReportService
{
    /**
     * Parse date range filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    protected function parseDateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'this_month';

        if ($period === 'today') {
            return [
                'start' => Carbon::today()->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
            ];
        }

        if ($period === 'last_7_days') {
            return [
                'start' => Carbon::today()->subDays(6)->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
            ];
        }

        if ($period === 'this_month') {
            return [
                'start' => Carbon::now()->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->endOfMonth()->endOfDay(),
            ];
        }

        if ($period === 'last_month') {
            return [
                'start' => Carbon::now()->subMonth()->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->subMonth()->endOfMonth()->endOfDay(),
            ];
        }

        if ($period === 'custom' && ! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            return [
                'start' => Carbon::parse($filters['start_date'])->startOfDay(),
                'end' => Carbon::parse($filters['end_date'])->endOfDay(),
            ];
        }

        return ['start' => null, 'end' => null];
    }

    /**
     * Get filtered query for sales report (excluding cancelled/voided invoices).
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = Penjualan::query()
            ->whereNull('dibatalkan_at')
            ->with(['pelanggan', 'gudang', 'creator', 'details.produk'])
            ->latest('tanggal');

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('no_penjualan', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn ($pq) => $pq->where('nama', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['pelanggan_id'])) {
            $query->where('pelanggan_id', $filters['pelanggan_id']);
        }

        if (! empty($filters['gudang_id'])) {
            $query->where('gudang_id', $filters['gudang_id']);
        }

        if (! empty($filters['status_pembayaran'])) {
            $query->where('status_pembayaran', $filters['status_pembayaran']);
        }

        $dates = $this->parseDateRange($filters);
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    /**
     * Get summary metrics for sales report based on applied filters, including estimated gross profit.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);

        $totalSales = (float) (clone $query)->sum('total_akhir');
        $totalTransactions = (int) (clone $query)->count();
        $totalPaid = (float) (clone $query)->sum('total_dibayar');
        $totalReceivables = (float) (clone $query)->sum('sisa_piutang');

        // Calculate estimated gross profit (Revenue of items - Qty * Harga Beli)
        $penjualanIdsQuery = (clone $query)->reorder()->select('penjualan.id');
        $profitRow = DetailPenjualan::query()
            ->join('produk', 'detail_penjualan.produk_id', '=', 'produk.id')
            ->whereIn('detail_penjualan.penjualan_id', $penjualanIdsQuery)
            ->selectRaw('COALESCE(SUM(detail_penjualan.subtotal), 0) as item_revenue, COALESCE(SUM(detail_penjualan.jumlah * produk.harga_beli), 0) as item_cogs, COALESCE(SUM(detail_penjualan.jumlah), 0) as total_qty')
            ->first();

        $itemRevenue = (float) ($profitRow->item_revenue ?? 0);
        $itemCogs = (float) ($profitRow->item_cogs ?? 0);
        $grossProfit = $itemRevenue - $itemCogs;
        $marginPercentage = $itemRevenue > 0 ? round(($grossProfit / $itemRevenue) * 100, 1) : 0.0;

        return [
            'total_penjualan' => $totalSales,
            'total_transaksi' => $totalTransactions,
            'total_terbayar' => $totalPaid,
            'total_sisa_piutang' => $totalReceivables,
            'total_hpp_estimasi' => $itemCogs,
            'estimasi_laba_kotor' => $grossProfit,
            'margin_persen' => $marginPercentage,
            'total_unit_terjual' => (float) ($profitRow->total_qty ?? 0),
        ];
    }

    /**
     * Get top selling products for the filtered sales period.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function getTopProducts(array $filters = [], int $limit = 5): Collection
    {
        $penjualanIdsQuery = $this->getFilteredQuery($filters)->reorder()->select('penjualan.id');

        return DetailPenjualan::query()
            ->join('produk', 'detail_penjualan.produk_id', '=', 'produk.id')
            ->whereIn('detail_penjualan.penjualan_id', $penjualanIdsQuery)
            ->selectRaw('produk.id, produk.nama, produk.kode_katalog, COALESCE(SUM(detail_penjualan.jumlah), 0) as total_qty, COALESCE(SUM(detail_penjualan.subtotal), 0) as total_revenue, COALESCE(SUM(detail_penjualan.subtotal - (detail_penjualan.jumlah * produk.harga_beli)), 0) as total_profit')
            ->groupBy('produk.id', 'produk.nama', 'produk.kode_katalog')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'produk_id' => (int) $row->id,
                'nama' => (string) $row->nama,
                'kode_katalog' => (string) ($row->kode_katalog ?? '-'),
                'total_qty' => (float) $row->total_qty,
                'total_revenue' => (float) $row->total_revenue,
                'total_profit' => (float) $row->total_profit,
            ]);
    }

    /**
     * Get top customers by purchase volume for the filtered sales period.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function getTopCustomers(array $filters = [], int $limit = 5): Collection
    {
        $penjualanIdsQuery = $this->getFilteredQuery($filters)->reorder()->select('penjualan.id');

        return Penjualan::query()
            ->join('pelanggan', 'penjualan.pelanggan_id', '=', 'pelanggan.id')
            ->whereIn('penjualan.id', $penjualanIdsQuery)
            ->selectRaw('pelanggan.id, pelanggan.nama, pelanggan.tipe, COUNT(penjualan.id) as total_faktur, COALESCE(SUM(penjualan.total_akhir), 0) as total_belanja, COALESCE(SUM(penjualan.sisa_piutang), 0) as sisa_piutang')
            ->groupBy('pelanggan.id', 'pelanggan.nama', 'pelanggan.tipe')
            ->orderByDesc('total_belanja')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'pelanggan_id' => (int) $row->id,
                'nama' => (string) $row->nama,
                'tipe' => (string) ($row->tipe ?? 'umum'),
                'total_faktur' => (int) $row->total_faktur,
                'total_belanja' => (float) $row->total_belanja,
                'sisa_piutang' => (float) $row->sisa_piutang,
            ]);
    }
}
