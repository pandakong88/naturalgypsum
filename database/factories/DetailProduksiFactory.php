<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Produk;
use App\Domains\Production\Models\DetailProduksi;
use App\Domains\Production\Models\Produksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailProduksi>
 */
class DetailProduksiFactory extends Factory
{
    protected $model = DetailProduksi::class;

    public function definition(): array
    {
        return [
            'produksi_id' => Produksi::factory(),
            'produk_id' => Produk::factory(),
            'jumlah' => fake()->randomFloat(3, 1, 50),
        ];
    }
}
