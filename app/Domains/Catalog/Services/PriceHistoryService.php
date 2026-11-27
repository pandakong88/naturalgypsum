<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\RiwayatHargaProduk;
use Illuminate\Support\Facades\Auth;

class PriceHistoryService
{
    /**
     * Tracked price fields on Produk model.
     *
     * @var array<int, string>
     */
    public const PRICE_FIELDS = [
        'harga_beli',
        'harga_jual',
        'harga_aplikator',
        'harga_grosir',
    ];

    /**
     * Compare old vs new price values on a product and record all changed fields.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function recordProductPriceChanges(
        Produk $produk,
        array $oldValues,
        array $newValues,
        string $sumber = 'master_update',
        ?string $referensi = null,
        ?string $referensiType = 'produk',
        ?int $referensiId = null,
        ?int $supplierId = null,
        ?string $keterangan = null,
        ?int $userId = null
    ): int {
        $recorded = 0;
        $actorId = $userId ?? Auth::id();

        foreach (self::PRICE_FIELDS as $field) {
            if (! array_key_exists($field, $oldValues) && ! array_key_exists($field, $newValues)) {
                continue;
            }

            $oldRaw = $oldValues[$field] ?? null;
            $newRaw = $newValues[$field] ?? null;

            $valOld = ($oldRaw !== null && $oldRaw !== '') ? (float) $oldRaw : null;
            $valNew = ($newRaw !== null && $newRaw !== '') ? (float) $newRaw : null;

            if ($valOld === null && $valNew === null) {
                continue;
            }

            if (abs(($valNew ?? 0.0) - ($valOld ?? 0.0)) > 0.001) {
                $this->recordSingleChange(
                    produk: $produk,
                    jenisHarga: $field,
                    hargaLama: $valOld,
                    hargaBaru: $valNew,
                    sumber: $sumber,
                    referensi: $referensi,
                    referensiType: $referensiType,
                    referensiId: $referensiId ?? $produk->id,
                    supplierId: $supplierId,
                    keterangan: $keterangan,
                    userId: $actorId
                );
                $recorded++;
            }
        }

        return $recorded;
    }

    /**
     * Record a single price change event for a product.
     */
    public function recordSingleChange(
        Produk $produk,
        string $jenisHarga,
        ?float $hargaLama,
        ?float $hargaBaru,
        string $sumber = 'master_update',
        ?string $referensi = null,
        ?string $referensiType = null,
        ?int $referensiId = null,
        ?int $supplierId = null,
        ?string $keterangan = null,
        ?int $userId = null
    ): ?RiwayatHargaProduk {
        $oldNum = $hargaLama ?? 0.0;
        $newNum = $hargaBaru ?? 0.0;

        if ($hargaLama !== null && $hargaBaru !== null && abs($newNum - $oldNum) <= 0.001) {
            return null;
        }

        $diff = round($newNum - $oldNum, 2);
        $pct = ($hargaLama !== null && $hargaLama > 0)
            ? round(($diff / $hargaLama) * 100, 2)
            : null;

        return RiwayatHargaProduk::create([
            'produk_id' => $produk->id,
            'jenis_harga' => $jenisHarga,
            'harga_lama' => $hargaLama,
            'harga_baru' => $hargaBaru,
            'selisih_nominal' => $diff,
            'selisih_persen' => $pct,
            'sumber' => $sumber,
            'referensi' => $referensi,
            'referensi_type' => $referensiType,
            'referensi_id' => $referensiId,
            'supplier_id' => $supplierId,
            'keterangan' => $keterangan,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }
}
