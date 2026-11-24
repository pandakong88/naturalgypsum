<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Produk;
use App\Domains\Sales\Models\DetailPenjualan;
use App\Domains\Sales\Models\Penjualan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailPenjualan>
 */
class DetailPenjualanFactory extends Factory
{
    protected $model = DetailPenjualan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jumlah = fake()->randomFloat(3, 1, 50);
        $harga = fake()->randomFloat(2, 10000, 100000);
        $subtotal = round($jumlah * $harga, 2);

        return [
            'penjualan_id' => Penjualan::factory(),
            'produk_id' => Produk::factory(),
            'harga_satuan' => $harga,
            'jenis_diskon' => null,
            'nilai_diskon' => 0.00,
            'jumlah' => $jumlah,
            'subtotal' => $subtotal,
        ];
    }
}
