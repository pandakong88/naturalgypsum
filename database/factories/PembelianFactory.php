<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Models\Pembelian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembelian>
 */
class PembelianFactory extends Factory
{
    protected $model = Pembelian::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'no_pembelian' => 'PB-'.Carbon::now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'no_kontainer' => 'CONT-'.strtoupper(fake()->bothify('????-######')),
            'tanggal' => Carbon::now()->toDateString(),
            'total' => 0.00,
            'keterangan' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
