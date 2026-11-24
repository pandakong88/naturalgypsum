<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    /**
     * Increase current stock by a positive quantity and record an incoming mutation.
     */
    public function increase(
        Produk $produk,
        Gudang $gudang,
        float|int|string $qty,
        JenisMutasiStok $jenis,
        string $referensi,
        ?string $referensiType = null,
        ?int $referensiId = null,
        ?int $userId = null,
        ?Carbon $tanggal = null
    ): Stok {
        $amount = (float) $qty;
        if ($amount <= 0) {
            throw new InvalidArgumentException('Quantity to increase must be greater than zero.');
        }

        return $this->apply(
            produk: $produk,
            gudang: $gudang,
            mutationQty: $amount,
            jenis: $jenis,
            referensi: $referensi,
            referensiType: $referensiType,
            referensiId: $referensiId,
            userId: $userId,
            tanggal: $tanggal
        );
    }

    /**
     * Decrease current stock by a positive quantity and record an outgoing mutation (negative mutation qty).
     */
    public function decrease(
        Produk $produk,
        Gudang $gudang,
        float|int|string $qty,
        JenisMutasiStok $jenis,
        string $referensi,
        ?string $referensiType = null,
        ?int $referensiId = null,
        ?int $userId = null,
        ?Carbon $tanggal = null
    ): Stok {
        $amount = (float) $qty;
        if ($amount <= 0) {
            throw new InvalidArgumentException('Quantity to decrease must be greater than zero.');
        }

        return $this->apply(
            produk: $produk,
            gudang: $gudang,
            mutationQty: -$amount,
            jenis: $jenis,
            referensi: $referensi,
            referensiType: $referensiType,
            referensiId: $referensiId,
            userId: $userId,
            tanggal: $tanggal
        );
    }

    /**
     * Adjust stock directly to a physical count and record a difference mutation (stok_fisik - stok_sistem).
     */
    public function adjust(
        Produk $produk,
        Gudang $gudang,
        float|int|string $physicalQty,
        string $referensi,
        ?string $referensiType = null,
        ?int $referensiId = null,
        ?int $userId = null,
        ?Carbon $tanggal = null
    ): Stok {
        $targetQty = (float) $physicalQty;

        return DB::transaction(function () use ($produk, $gudang, $targetQty, $referensi, $referensiType, $referensiId, $userId, $tanggal) {
            $stok = $this->getAndLockStock($produk->id, $gudang->id);
            $currentQty = (float) $stok->jumlah;
            $difference = $targetQty - $currentQty;

            // If no difference, return current stock without zero-mutation
            if (abs($difference) < 0.0001) {
                return $stok;
            }

            return $this->apply(
                produk: $produk,
                gudang: $gudang,
                mutationQty: $difference,
                jenis: JenisMutasiStok::PenyesuaianOpname,
                referensi: $referensi,
                referensiType: $referensiType,
                referensiId: $referensiId,
                userId: $userId,
                tanggal: $tanggal
            );
        });
    }

    /**
     * Core atomic stock engine method.
     * Applies a mutation quantity (+/-), updates row with pessimistic locking, and records ledger mutation.
     */
    public function apply(
        Produk $produk,
        Gudang $gudang,
        float|int|string $mutationQty,
        JenisMutasiStok $jenis,
        string $referensi,
        ?string $referensiType = null,
        ?int $referensiId = null,
        ?int $userId = null,
        ?Carbon $tanggal = null
    ): Stok {
        $diff = (float) $mutationQty;
        $resolvedUserId = $userId ?? Auth::id();
        $mutationDate = $tanggal ?? Carbon::now();

        return DB::transaction(function () use ($produk, $gudang, $diff, $jenis, $referensi, $referensiType, $referensiId, $resolvedUserId, $mutationDate) {
            $stok = $this->getAndLockStock($produk->id, $gudang->id);

            $newJumlah = (float) $stok->jumlah + $diff;
            $stok->update(['jumlah' => $newJumlah]);

            MutasiStok::create([
                'produk_id' => $produk->id,
                'gudang_id' => $gudang->id,
                'user_id' => $resolvedUserId,
                'jenis' => $jenis,
                'jumlah' => $diff,
                'referensi' => trim($referensi),
                'referensi_type' => $referensiType,
                'referensi_id' => $referensiId,
                'tanggal' => $mutationDate,
            ]);

            return $stok->fresh();
        });
    }

    /**
     * Get stock for a specific product and warehouse.
     */
    public function getStock(Produk $produk, Gudang $gudang): float
    {
        $stok = Stok::where('produk_id', $produk->id)
            ->where('gudang_id', $gudang->id)
            ->first();

        return $stok ? (float) $stok->jumlah : 0.000;
    }

    /**
     * Get aggregated total stock for a product across all warehouses.
     */
    public function getTotalStock(Produk $produk): float
    {
        return (float) Stok::where('produk_id', $produk->id)->sum('jumlah');
    }

    /**
     * Retrieve stock row with row-level pessimistic locking, creating the row if it does not yet exist.
     */
    protected function getAndLockStock(int $produkId, int $gudangId): Stok
    {
        // First try to find and lock
        /** @var Stok|null $stok */
        $stok = Stok::where('produk_id', $produkId)
            ->where('gudang_id', $gudangId)
            ->lockForUpdate()
            ->first();

        if ($stok) {
            return $stok;
        }

        // If not found, create new row
        Stok::firstOrCreate(
            ['produk_id' => $produkId, 'gudang_id' => $gudangId],
            ['jumlah' => 0.000]
        );

        // Re-query with lockForUpdate to ensure serial access
        /** @var Stok $lockedStok */
        $lockedStok = Stok::where('produk_id', $produkId)
            ->where('gudang_id', $gudangId)
            ->lockForUpdate()
            ->firstOrFail();

        return $lockedStok;
    }
}
