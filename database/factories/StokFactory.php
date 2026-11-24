<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\Stok;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stok>
 */
class StokFactory extends Factory
{
    protected $model = Stok::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'produk_id' => Produk::factory(),
            'gudang_id' => Gudang::factory(),
            'jumlah' => fake()->randomFloat(3, 10, 200),
        ];
    }
}
