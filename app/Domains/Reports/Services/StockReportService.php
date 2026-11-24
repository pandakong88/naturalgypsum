<?php

namespace App\Domains\Reports\Services;

use App\Domains\Catalog\Models\Kategori;
use App\Domains\Inventory\Models\Stok;
use Illuminate\Database\Eloquent\Builder;

class StockReportService
{
    /**
     * Get filtered query for stock report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = Stok::query()
            ->with(['produk.kategori', 'produk.merek', 'gudang'])
            ->whereHas('produk')
            ->whereHas('gudang');

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->whereHas('produk', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_katalog', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['gudang_id'])) {
            $query->where('gudang_id', $filters['gudang_id']);
        }

        if (! empty($filters['kategori_id'])) {
            $selectedKat = Kategori::with('children')->find($filters['kategori_id']);
            $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                : [$filters['kategori_id']];

            $query->whereHas('produk', function ($q) use ($catIds) {
                $q->whereIn('kategori_id', $catIds);
            });
        }

        if (! empty($filters['merek_id'])) {
            $query->whereHas('produk', function ($q) use ($filters) {
                $q->where('merek_id', $filters['merek_id']);
            });
        }

        if (! empty($filters['stok_rendah'])) {
            $query->whereHas('produk', function ($q) {
                $q->whereColumn('stok.jumlah', '<=', 'produk.stok_minimum');
            });
        }

        return $query;
    }

    /**
     * Get summary metrics for stock report based on applied filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);

        $totalItems = (clone $query)->count();
        $totalStock = (float) (clone $query)->sum('jumlah');

        $lowStockCount = (clone $query)->whereHas('produk', function ($q) {
            $q->whereColumn('stok.jumlah', '<=', 'produk.stok_minimum')
                ->where('stok.jumlah', '>', 0);
        })->count();

        $outOfStockCount = (clone $query)->where('jumlah', '<=', 0)->count();

        // Estimated Stock Value: sum(stok.jumlah * produk.harga_beli)
        $totalEstimatedValue = (float) (clone $query)
            ->join('produk', 'stok.produk_id', '=', 'produk.id')
            ->selectRaw('COALESCE(SUM(stok.jumlah * produk.harga_beli), 0) as aggregate_value')
            ->value('aggregate_value');

        return [
            'total_items' => $totalItems,
            'total_stok' => $totalStock,
            'total_stok_rendah' => $lowStockCount,
            'total_stok_habis' => $outOfStockCount,
            'total_nilai_stok' => $totalEstimatedValue,
        ];
    }
}
