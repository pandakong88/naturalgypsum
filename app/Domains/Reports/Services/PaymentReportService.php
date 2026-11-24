<?php

namespace App\Domains\Reports\Services;

use App\Domains\Receivables\Models\Pembayaran;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PaymentReportService
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
     * Get filtered query for payment report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = Pembayaran::query()
            ->with(['pelanggan', 'creator', 'alokasi.penjualan'])
            ->latest('tanggal');

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('no_pembayaran', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn ($pq) => $pq->where('nama', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['pelanggan_id'])) {
            $query->where('pelanggan_id', $filters['pelanggan_id']);
        }

        if (! empty($filters['metode'])) {
            $query->where('metode', $filters['metode']);
        }

        $dates = $this->parseDateRange($filters);
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    /**
     * Get summary aggregates for payment report.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);

        $totalPayments = (float) (clone $query)->sum('jumlah_bayar');
        $totalTransactions = (clone $query)->count();

        $totalTunai = (float) (clone $query)->where('metode', 'tunai')->sum('jumlah_bayar');
        $totalTransfer = (float) (clone $query)->where('metode', 'transfer')->sum('jumlah_bayar');
        $totalLainnya = (float) (clone $query)->whereNotIn('metode', ['tunai', 'transfer'])->sum('jumlah_bayar');

        return [
            'total_pembayaran' => $totalPayments,
            'total_transaksi' => $totalTransactions,
            'total_tunai' => $totalTunai,
            'total_transfer' => $totalTransfer,
            'total_lainnya' => $totalLainnya,
        ];
    }
}
