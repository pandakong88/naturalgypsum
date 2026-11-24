<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\DetailTransferGudang;
use App\Domains\Inventory\Models\TransferGudang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailTransferGudang>
 */
class DetailTransferGudangFactory extends Factory
{
    protected $model = DetailTransferGudang::class;

    public function definition(): array
    {
        return [
            'transfer_gudang_id' => TransferGudang::factory(),
            'produk_id' => Produk::factory(),
            'jumlah' => fake()->randomFloat(3, 1, 50),
        ];
    }
}
