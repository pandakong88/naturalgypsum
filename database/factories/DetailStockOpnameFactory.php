<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\DetailStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailStockOpname>
 */
class DetailStockOpnameFactory extends Factory
{
    protected $model = DetailStockOpname::class;

    public function definition(): array
    {
        $stokSistem = fake()->numberBetween(10, 100);
        $stokFisik = fake()->numberBetween(10, 100);
        $selisih = $stokFisik - $stokSistem;

        return [
            'stock_opname_id' => StockOpname::factory(),
            'produk_id' => Produk::factory(),
            'stok_sistem' => $stokSistem,
            'stok_fisik' => $stokFisik,
            'selisih' => $selisih,
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
