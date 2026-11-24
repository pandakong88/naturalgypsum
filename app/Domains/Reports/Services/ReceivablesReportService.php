<?php

namespace App\Domains\Reports\Services;

use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReceivablesReportService
{
    /**
     * Parse date range filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    protected function parseDateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'all';

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
     * Get filtered query for outstanding receivables (sisa_piutang > 0).
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = Penjualan::query()
            ->with(['pelanggan', 'gudang', 'creator'])
            ->where('sisa_piutang', '>', 0)
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

        $dates = $this->parseDateRange($filters);
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    /**
     * Get summary metrics for receivables report.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);

        $totalOutstanding = (float) (clone $query)->sum('sisa_piutang');
        $unpaidInvoicesCount = (clone $query)->count();
        $totalCustomers = (clone $query)->distinct('pelanggan_id')->count('pelanggan_id');

        return [
            'total_piutang' => $totalOutstanding,
            'total_faktur_belum_lunas' => $unpaidInvoicesCount,
            'total_pelanggan_berhutang' => $totalCustomers,
        ];
    }

    /**
     * Get top debtors by total outstanding receivables.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getTopDebtors(int $limit = 5): Collection
    {
        return Penjualan::join('pelanggan', 'penjualan.pelanggan_id', '=', 'pelanggan.id')
            ->where('penjualan.sisa_piutang', '>', 0)
            ->select('pelanggan.id', 'pelanggan.nama', DB::raw('SUM(penjualan.sisa_piutang) as total_debt'), DB::raw('COUNT(penjualan.id) as invoice_count'))
            ->groupBy('pelanggan.id', 'pelanggan.nama')
            ->orderByDesc('total_debt')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'pelanggan_id' => $row->id,
                'nama' => $row->nama,
                'total_debt' => (float) $row->total_debt,
                'invoice_count' => (int) $row->invoice_count,
            ]);
    }
}
