<?php

namespace App\Domains\Reports\Services;

use App\Domains\Catalog\Models\Kategori;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Sales\Models\DetailPenjualan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            $query->where('stok.gudang_id', $filters['gudang_id']);
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

        $pergerakan = $filters['pergerakan'] ?? '';
        if ($pergerakan !== '') {
            $date30DaysAgo = Carbon::today()->subDays(30)->toDateString();

            if ($pergerakan === 'dead_stock') {
                $query->where('stok.jumlah', '>', 0)
                    ->whereNotExists(function ($sub) use ($date30DaysAgo) {
                        $sub->select(DB::raw(1))
                            ->from('detail_penjualan')
                            ->join('penjualan', 'detail_penjualan.penjualan_id', '=', 'penjualan.id')
                            ->whereColumn('detail_penjualan.produk_id', 'stok.produk_id')
                            ->whereNull('penjualan.deleted_at')
                            ->whereNull('penjualan.dibatalkan_at')
                            ->whereDate('penjualan.tanggal', '>=', $date30DaysAgo);
                    });
            } elseif ($pergerakan === 'fast_moving') {
                $query->whereExists(function ($sub) use ($date30DaysAgo) {
                    $sub->select(DB::raw(1))
                        ->from('detail_penjualan')
                        ->join('penjualan', 'detail_penjualan.penjualan_id', '=', 'penjualan.id')
                        ->whereColumn('detail_penjualan.produk_id', 'stok.produk_id')
                        ->whereNull('penjualan.deleted_at')
                        ->whereNull('penjualan.dibatalkan_at')
                        ->whereDate('penjualan.tanggal', '>=', $date30DaysAgo);
                });
            } elseif ($pergerakan === 'habis') {
                $query->where('stok.jumlah', '<=', 0);
            }
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

        $totalItems = (int) (clone $query)->count();
        $totalStock = (float) (clone $query)->sum('jumlah');

        $lowStockCount = (int) (clone $query)->whereHas('produk', function ($q) {
            $q->whereColumn('stok.jumlah', '<=', 'produk.stok_minimum')
                ->where('stok.jumlah', '>', 0);
        })->count();

        $outOfStockCount = (int) (clone $query)->where('jumlah', '<=', 0)->count();

        $date30DaysAgo = Carbon::today()->subDays(30)->toDateString();
        $deadStockCount = (int) (clone $query)
            ->where('stok.jumlah', '>', 0)
            ->whereNotExists(function ($sub) use ($date30DaysAgo) {
                $sub->select(DB::raw(1))
                    ->from('detail_penjualan')
                    ->join('penjualan', 'detail_penjualan.penjualan_id', '=', 'penjualan.id')
                    ->whereColumn('detail_penjualan.produk_id', 'stok.produk_id')
                    ->whereNull('penjualan.deleted_at')
                    ->whereNull('penjualan.dibatalkan_at')
                    ->whereDate('penjualan.tanggal', '>=', $date30DaysAgo);
            })->count();

        // Estimated Stock Value (Cost & Retail Potential)
        $valuationRow = (clone $query)
            ->join('produk', 'stok.produk_id', '=', 'produk.id')
            ->selectRaw('COALESCE(SUM(stok.jumlah * produk.harga_beli), 0) as cost_value, COALESCE(SUM(stok.jumlah * produk.harga_jual), 0) as retail_value')
            ->first();

        $totalEstimatedValue = (float) ($valuationRow->cost_value ?? 0);
        $totalRetailValue = (float) ($valuationRow->retail_value ?? 0);
        $potentialMargin = max(0.0, $totalRetailValue - $totalEstimatedValue);

        return [
            'total_items' => $totalItems,
            'total_stok' => $totalStock,
            'total_stok_rendah' => $lowStockCount,
            'total_stok_habis' => $outOfStockCount,
            'total_dead_stock' => $deadStockCount,
            'total_nilai_stok' => $totalEstimatedValue,
            'total_nilai_jual_stok' => $totalRetailValue,
            'potensi_margin_stok' => $potentialMargin,
        ];
    }

    /**
     * Get stock valuation and SKU distribution breakdown by warehouse.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getWarehouseBreakdown(): Collection
    {
        return Stok::query()
            ->join('produk', 'stok.produk_id', '=', 'produk.id')
            ->join('gudang', 'stok.gudang_id', '=', 'gudang.id')
            ->whereNull('produk.deleted_at')
            ->whereNull('gudang.deleted_at')
            ->selectRaw('gudang.id, gudang.nama_gudang, COUNT(stok.id) as total_sku, SUM(CASE WHEN stok.jumlah <= produk.stok_minimum THEN 1 ELSE 0 END) as kritis_count, COALESCE(SUM(stok.jumlah * produk.harga_beli), 0) as nilai_modal, COALESCE(SUM(stok.jumlah * produk.harga_jual), 0) as nilai_jual')
            ->groupBy('gudang.id', 'gudang.nama_gudang')
            ->orderByDesc('nilai_modal')
            ->get()
            ->map(fn ($row) => [
                'gudang_id' => (int) $row->id,
                'nama_gudang' => (string) $row->nama_gudang,
                'total_sku' => (int) $row->total_sku,
                'kritis_count' => (int) $row->kritis_count,
                'nilai_modal' => (float) $row->nilai_modal,
                'nilai_jual' => (float) $row->nilai_jual,
            ]);
    }

    /**
     * Get 30-day sales quantity map keyed by produk_id to avoid N+1 queries.
     *
     * @param  array<int, int>  $produkIds
     * @return array<int, float>
     */
    public function getSales30DaysMap(array $produkIds): array
    {
        if (empty($produkIds)) {
            return [];
        }

        $date30DaysAgo = Carbon::today()->subDays(30)->toDateString();

        return DetailPenjualan::query()
            ->join('penjualan', 'detail_penjualan.penjualan_id', '=', 'penjualan.id')
            ->whereIn('detail_penjualan.produk_id', $produkIds)
            ->whereNull('penjualan.deleted_at')
            ->whereNull('penjualan.dibatalkan_at')
            ->whereDate('penjualan.tanggal', '>=', $date30DaysAgo)
            ->selectRaw('detail_penjualan.produk_id, COALESCE(SUM(detail_penjualan.jumlah), 0) as qty_30d')
            ->groupBy('detail_penjualan.produk_id')
            ->pluck('qty_30d', 'produk_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}
