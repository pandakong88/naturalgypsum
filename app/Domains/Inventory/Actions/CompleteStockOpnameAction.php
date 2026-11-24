<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\DetailStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Inventory\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteStockOpnameAction
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Finalize a draft stock opname, verify stale snapshot protection, and apply stock adjustments.
     *
     * @param  array<int, array{
     *     produk_id: int,
     *     stok_sistem: float|int|string,
     *     stok_fisik: float|int|string,
     *     catatan?: string|null
     * }>|null  $items
     */
    public function execute(StockOpname $stockOpname, ?array $items = null, ?int $userId = null): StockOpname
    {
        if ($stockOpname->status !== StatusStockOpname::Draft) {
            throw new InvalidArgumentException('Hanya dokumen stok opname berstatus Draft yang dapat diselesaikan.');
        }

        $operatorId = $userId ?? Auth::id();

        return DB::transaction(function () use ($stockOpname, $items, $operatorId) {
            $stockOpname->loadMissing(['gudang', 'details.produk']);
            $gudang = $stockOpname->gudang;

            // If items array is supplied (from livewire edit), prepare dataset
            if ($items !== null) {
                if (empty($items)) {
                    throw new InvalidArgumentException('Minimal satu produk harus dimasukkan ke dalam stok opname.');
                }

                $stockOpname->details()->delete();

                foreach ($items as $item) {
                    $produkId = (int) ($item['produk_id'] ?? 0);
                    $stokFisik = (float) ($item['stok_fisik'] ?? 0);
                    $draftSnapshot = (float) ($item['stok_sistem'] ?? 0);
                    $catatan = isset($item['catatan']) ? trim($item['catatan']) : null;

                    if ($produkId <= 0) {
                        continue;
                    }

                    if ($stokFisik < 0) {
                        throw new InvalidArgumentException('Stok fisik tidak boleh bernilai negatif.');
                    }

                    $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);

                    // Stale Snapshot Protection: check live stock against stored draft snapshot
                    $liveStock = (float) $this->stockService->getStock($produk, $gudang);
                    if (abs($liveStock - $draftSnapshot) > 0.0005) {
                        throw new InvalidArgumentException("Stok produk [{$produk->nama}] telah berubah dari snapshot draft ({$draftSnapshot}) menjadi ({$liveStock}). Silakan perbarui data draft terlebih dahulu sebelum menyelesaikan opname.");
                    }

                    $selisih = $stokFisik - $liveStock;

                    DetailStockOpname::create([
                        'stock_opname_id' => $stockOpname->id,
                        'produk_id' => $produk->id,
                        'stok_sistem' => $liveStock,
                        'stok_fisik' => $stokFisik,
                        'selisih' => $selisih,
                        'catatan' => $catatan,
                    ]);

                    if (abs($selisih) > 0.0005) {
                        $this->stockService->apply(
                            produk: $produk,
                            gudang: $gudang,
                            mutationQty: $selisih,
                            jenis: JenisMutasiStok::PenyesuaianOpname,
                            referensi: $stockOpname->no_stock_opname,
                            referensiType: StockOpname::class,
                            referensiId: $stockOpname->id,
                            userId: $operatorId,
                            tanggal: $stockOpname->tanggal
                        );
                    }
                }
            } else {
                // Use existing stored details
                $details = $stockOpname->details;
                if ($details->isEmpty()) {
                    throw new InvalidArgumentException('Dokumen stok opname tidak memiliki baris produk.');
                }

                foreach ($details as $detail) {
                    $produk = $detail->produk;
                    $stokFisik = (float) $detail->stok_fisik;
                    $draftSnapshot = (float) $detail->stok_sistem;

                    // Stale Snapshot Protection
                    $liveStock = (float) $this->stockService->getStock($produk, $gudang);
                    if (abs($liveStock - $draftSnapshot) > 0.0005) {
                        throw new InvalidArgumentException("Stok produk [{$produk->nama}] telah berubah dari snapshot draft ({$draftSnapshot}) menjadi ({$liveStock}). Silakan perbarui data draft terlebih dahulu sebelum menyelesaikan opname.");
                    }

                    $selisih = $stokFisik - $liveStock;
                    $detail->update([
                        'stok_sistem' => $liveStock,
                        'selisih' => $selisih,
                    ]);

                    if (abs($selisih) > 0.0005) {
                        $this->stockService->apply(
                            produk: $produk,
                            gudang: $gudang,
                            mutationQty: $selisih,
                            jenis: JenisMutasiStok::PenyesuaianOpname,
                            referensi: $stockOpname->no_stock_opname,
                            referensiType: StockOpname::class,
                            referensiId: $stockOpname->id,
                            userId: $operatorId,
                            tanggal: $stockOpname->tanggal
                        );
                    }
                }
            }

            $stockOpname->update([
                'status' => StatusStockOpname::Selesai,
                'updated_by' => $operatorId,
            ]);

            app(ActivityLogService::class)->log(
                action: 'complete',
                description: "Menyelesaikan stok opname {$stockOpname->no_stock_opname} di {$gudang->nama_gudang}",
                subject: $stockOpname,
                newValues: [
                    'no_stock_opname' => $stockOpname->no_stock_opname,
                    'gudang' => $gudang->nama_gudang,
                    'status' => StatusStockOpname::Selesai->value,
                ],
                userId: $operatorId
            );

            return $stockOpname->fresh(['details.produk', 'gudang', 'user']);
        });
    }
}
