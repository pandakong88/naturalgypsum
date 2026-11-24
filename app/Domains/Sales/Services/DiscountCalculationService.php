<?php

namespace App\Domains\Sales\Services;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Sales\Models\AturanDiskon;

class DiscountCalculationService
{
    /**
     * Calculate optimal discount for a product, customer, and purchase quantity.
     *
     * @return array{
     *     has_discount: bool,
     *     aturan_id: ?int,
     *     aturan_nama: ?string,
     *     jenis_diskon: string,
     *     nilai_diskon: float,
     *     diskon_per_unit: float,
     *     harga_dasar: float,
     *     harga_setelah_diskon: float,
     *     total_diskon: float,
     *     subtotal: float
     * }
     */
    public function calculate(Produk $produk, ?Pelanggan $pelanggan, float $qty, ?float $customBasePrice = null): array
    {
        $hargaDasar = $customBasePrice !== null ? (float) $customBasePrice : $produk->getHargaUntukPelanggan($pelanggan?->tipe);

        $rules = AturanDiskon::active()
            ->validToday()
            ->with(['kategoris', 'mereks', 'produks'])
            ->orderByDesc('priority')
            ->get();

        $bestRule = null;
        $maxDiscountPerUnit = 0.0;

        foreach ($rules as $rule) {
            if ($rule->appliesTo($produk, $pelanggan, $qty)) {
                $discountAmount = $rule->calculateUnitDiscount($hargaDasar);
                if ($discountAmount > $maxDiscountPerUnit) {
                    $maxDiscountPerUnit = $discountAmount;
                    $bestRule = $rule;
                }
            }
        }

        if ($bestRule && $maxDiscountPerUnit > 0) {
            $hargaSetelahDiskon = max(0.0, $hargaDasar - $maxDiscountPerUnit);
            $totalDiskon = $maxDiscountPerUnit * $qty;
            $subtotal = $hargaSetelahDiskon * $qty;

            return [
                'has_discount' => true,
                'aturan_id' => $bestRule->id,
                'aturan_nama' => $bestRule->nama,
                'jenis_diskon' => (string) $bestRule->jenis_diskon,
                'nilai_diskon' => (float) $bestRule->nilai_diskon,
                'diskon_per_unit' => $maxDiscountPerUnit,
                'harga_dasar' => $hargaDasar,
                'harga_setelah_diskon' => $hargaSetelahDiskon,
                'total_diskon' => round($totalDiskon, 2),
                'subtotal' => round($subtotal, 2),
            ];
        }

        return [
            'has_discount' => false,
            'aturan_id' => null,
            'aturan_nama' => null,
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 0.0,
            'diskon_per_unit' => 0.0,
            'harga_dasar' => $hargaDasar,
            'harga_setelah_diskon' => $hargaDasar,
            'total_diskon' => 0.0,
            'subtotal' => round($hargaDasar * $qty, 2),
        ];
    }
}
