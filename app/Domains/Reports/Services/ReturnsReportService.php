<?php

namespace App\Domains\Reports\Services;

use App\Domains\Returns\Models\ReturPelanggan;
use App\Domains\Returns\Models\ReturSupplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReturnsReportService
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
     * Get filtered query for supplier returns.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredSupplierQuery(array $filters = []): Builder
    {
        $query = ReturSupplier::query()
            ->with(['pembelian', 'supplier', 'gudang', 'creator', 'produk'])
            ->latest('tanggal');

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('no_retur', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('nama_supplier', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['gudang_id'])) {
            $query->where('gudang_id', $filters['gudang_id']);
        }

        $dates = $this->parseDateRange($filters);
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    /**
     * Get filtered query for customer returns.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredCustomerQuery(array $filters = []): Builder
    {
        $query = ReturPelanggan::query()
            ->with(['penjualan', 'pelanggan', 'gudang', 'creator', 'produk'])
            ->latest('tanggal');

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('no_retur', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn ($pq) => $pq->where('nama', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['pelanggan_id'])) {
            $query->where('pelanggan_id', $filters['pelanggan_id']);
        }

        if (! empty($filters['gudang_id'])) {
            $query->where('gudang_id', $filters['gudang_id']);
        }

        $dates = $this->parseDateRange($filters);
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween('tanggal', [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    /**
     * Get summary metrics for returns report.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $supQuery = $this->getFilteredSupplierQuery($filters);
        $custQuery = $this->getFilteredCustomerQuery($filters);

        $docSupplier = (clone $supQuery)->count();
        $qtySupplier = (float) (clone $supQuery)->sum('jumlah');

        $docPelanggan = (clone $custQuery)->count();
        $qtyPelanggan = (float) (clone $custQuery)->sum('jumlah');

        return [
            'dokumen_supplier' => $docSupplier,
            'total_qty_supplier' => $qtySupplier,
            'dokumen_pelanggan' => $docPelanggan,
            'total_qty_pelanggan' => $qtyPelanggan,
        ];
    }
}
