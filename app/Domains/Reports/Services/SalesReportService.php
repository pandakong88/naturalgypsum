<?php

namespace App\Domains\Reports\Services;

use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
     * Get filtered query for sales report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = Penjualan::query()
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
     * Get summary metrics for sales report based on applied filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);

        $totalSales = (float) (clone $query)->sum('total_akhir');
        $totalTransactions = (clone $query)->count();
        $totalPaid = (float) (clone $query)->sum('total_dibayar');
        $totalReceivables = (float) (clone $query)->sum('sisa_piutang');

        return [
            'total_penjualan' => $totalSales,
            'total_transaksi' => $totalTransactions,
            'total_terbayar' => $totalPaid,
            'total_sisa_piutang' => $totalReceivables,
        ];
    }
}
