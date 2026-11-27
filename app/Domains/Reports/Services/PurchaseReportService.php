<?php

namespace App\Domains\Reports\Services;

use App\Domains\Purchasing\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchaseReportService
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
     * Get filtered query for purchase report (excluding cancelled POs by default).
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = Pembelian::query()
            ->with(['supplier', 'creator', 'details.produk', 'details.gudang'])
            ->latest('tanggal');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', '!=', 'dibatalkan');
        }

        if (! empty($filters['status_pembayaran'])) {
            $query->where('status_pembayaran', $filters['status_pembayaran']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('no_pembelian', 'like', "%{$search}%")
                    ->orWhere('no_faktur_supplier', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('nama_supplier', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['gudang_id'])) {
            $query->whereHas('details', fn ($dq) => $dq->where('gudang_id', $filters['gudang_id']));
        }

        $dates = $this->parseDateRange($filters);
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    /**
     * Get summary metrics for purchase report based on applied filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);

        $totalPurchases = (float) (clone $query)->sum('total');
        $totalTransactions = (int) (clone $query)->count();
        $totalPaid = (float) (clone $query)->sum('nominal_bayar');
        $totalDebt = (float) (clone $query)->sum('sisa_hutang');
        $unpaidPoCount = (int) (clone $query)->where('sisa_hutang', '>', 0)->count();

        return [
            'total_pembelian' => $totalPurchases,
            'total_transaksi' => $totalTransactions,
            'total_dibayar_supplier' => $totalPaid,
            'total_hutang_supplier' => $totalDebt,
            'total_po_hutang' => $unpaidPoCount,
        ];
    }

    /**
     * Get top suppliers by purchase volume and outstanding payable balance.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function getTopSuppliers(array $filters = [], int $limit = 5): Collection
    {
        $pembelianIdsQuery = $this->getFilteredQuery($filters)->reorder()->select('pembelian.id');

        return Pembelian::query()
            ->join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
            ->whereIn('pembelian.id', $pembelianIdsQuery)
            ->selectRaw('supplier.id, supplier.nama_supplier, COUNT(pembelian.id) as total_po, COALESCE(SUM(pembelian.total), 0) as total_belanja, COALESCE(SUM(pembelian.sisa_hutang), 0) as sisa_hutang')
            ->groupBy('supplier.id', 'supplier.nama_supplier')
            ->orderByDesc('total_belanja')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'supplier_id' => (int) $row->id,
                'nama_supplier' => (string) $row->nama_supplier,
                'total_po' => (int) $row->total_po,
                'total_belanja' => (float) $row->total_belanja,
                'sisa_hutang' => (float) $row->sisa_hutang,
            ]);
    }
}
