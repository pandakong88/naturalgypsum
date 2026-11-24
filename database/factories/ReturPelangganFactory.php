<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Returns\Models\ReturPelanggan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturPelanggan>
 */
class ReturPelangganFactory extends Factory
{
    protected $model = ReturPelanggan::class;

    public function definition(): array
    {
        return [
            'no_retur' => 'RP-'.Carbon::now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::now()->toDateString(),
            'pelanggan_id' => Pelanggan::factory(),
            'penjualan_id' => null,
            'produk_id' => Produk::factory(),
            'gudang_id' => Gudang::factory(),
            'user_id' => User::factory(),
            'jumlah' => fake()->randomFloat(3, 1, 50),
            'harga_retur' => fake()->randomFloat(2, 10000, 100000),
            'alasan' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
