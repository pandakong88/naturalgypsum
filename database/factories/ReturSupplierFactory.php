<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Returns\Models\ReturSupplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturSupplier>
 */
class ReturSupplierFactory extends Factory
{
    protected $model = ReturSupplier::class;

    public function definition(): array
    {
        return [
            'no_retur' => 'RS-'.Carbon::now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::now()->toDateString(),
            'supplier_id' => Supplier::factory(),
            'produk_id' => Produk::factory(),
            'gudang_id' => Gudang::factory(),
            'user_id' => User::factory(),
            'pembelian_id' => null,
            'jumlah' => fake()->randomFloat(3, 1, 50),
            'alasan' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
