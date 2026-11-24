<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Purchasing\Models\DetailPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailPembelian>
 */
class DetailPembelianFactory extends Factory
{
    protected $model = DetailPembelian::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jumlah = fake()->randomFloat(3, 1, 100);
        $harga = fake()->randomFloat(2, 5000, 50000);
        $subtotal = round($jumlah * $harga, 2);

        return [
            'pembelian_id' => Pembelian::factory(),
            'produk_id' => Produk::factory(),
            'gudang_id' => Gudang::factory(),
            'jumlah' => $jumlah,
            'harga_beli_satuan' => $harga,
            'subtotal' => $subtotal,
        ];
    }
}
