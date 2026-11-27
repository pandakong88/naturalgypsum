<?php

namespace App\Domains\Reports\Services;

use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Receivables\PiutangIndex;
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
     * Get filtered query for outstanding receivables (sisa_piutang > 0 and not cancelled).
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = Penjualan::query()
            ->whereNull('dibatalkan_at')
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

        $aging = $filters['aging'] ?? '';
        if ($aging !== '') {
            $today = Carbon::today();
            $date14 = $today->copy()->subDays(PiutangIndex::TEMPO_STANDAR_HARI)->toDateString();
            $date30 = $today->copy()->subDays(30)->toDateString();
            $date60 = $today->copy()->subDays(60)->toDateString();

            if ($aging === 'lancar') {
                $query->whereDate('tanggal', '>=', $date14);
            } elseif ($aging === 'jatuh_tempo') {
                $query->whereDate('tanggal', '<', $date14)->whereDate('tanggal', '>=', $date30);
            } elseif ($aging === 'menunggak') {
                $query->whereDate('tanggal', '<', $date30)->whereDate('tanggal', '>=', $date60);
            } elseif ($aging === 'macet') {
                $query->whereDate('tanggal', '<', $date60);
            } elseif ($aging === 'lewat_tempo') {
                $query->whereDate('tanggal', '<', $date14);
            }
        }

        $dates = $this->parseDateRange($filters);
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    /**
     * Get summary metrics for receivables report including 14-day aging breakdown.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);

        $totalOutstanding = (float) (clone $query)->sum('sisa_piutang');
        $unpaidInvoicesCount = (int) (clone $query)->count();
        $totalCustomers = (int) (clone $query)->distinct('pelanggan_id')->count('pelanggan_id');

        $today = Carbon::today();
        $date14 = $today->copy()->subDays(PiutangIndex::TEMPO_STANDAR_HARI)->toDateString();
        $date30 = $today->copy()->subDays(30)->toDateString();
        $date60 = $today->copy()->subDays(60)->toDateString();

        $piutangLancar = (float) (clone $query)->whereDate('tanggal', '>=', $date14)->sum('sisa_piutang');
        $piutangJatuhTempo = (float) (clone $query)->whereDate('tanggal', '<', $date14)->whereDate('tanggal', '>=', $date30)->sum('sisa_piutang');
        $piutangMenunggak = (float) (clone $query)->whereDate('tanggal', '<', $date30)->whereDate('tanggal', '>=', $date60)->sum('sisa_piutang');
        $piutangMacet = (float) (clone $query)->whereDate('tanggal', '<', $date60)->sum('sisa_piutang');

        return [
            'total_piutang' => $totalOutstanding,
            'total_faktur_belum_lunas' => $unpaidInvoicesCount,
            'total_pelanggan_berhutang' => $totalCustomers,
            'piutang_lancar' => $piutangLancar,
            'piutang_jatuh_tempo' => $piutangJatuhTempo,
            'piutang_menunggak' => $piutangMenunggak,
            'piutang_macet' => $piutangMacet,
            'piutang_lewat_tempo' => $piutangJatuhTempo + $piutangMenunggak + $piutangMacet,
        ];
    }

    /**
     * Get top debtors by total outstanding receivables (excluding cancelled invoices).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getTopDebtors(int $limit = 5): Collection
    {
        return Penjualan::join('pelanggan', 'penjualan.pelanggan_id', '=', 'pelanggan.id')
            ->whereNull('penjualan.dibatalkan_at')
            ->where('penjualan.sisa_piutang', '>', 0)
            ->select('pelanggan.id', 'pelanggan.nama', DB::raw('SUM(penjualan.sisa_piutang) as total_debt'), DB::raw('COUNT(penjualan.id) as invoice_count'), DB::raw('MIN(penjualan.tanggal) as oldest_date'))
            ->groupBy('pelanggan.id', 'pelanggan.nama')
            ->orderByDesc('total_debt')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'pelanggan_id' => (int) $row->id,
                'nama' => (string) $row->nama,
                'total_debt' => (float) $row->total_debt,
                'invoice_count' => (int) $row->invoice_count,
                'oldest_date' => $row->oldest_date,
            ]);
    }
}
