<?php

namespace Database\Factories;

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Pelanggan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelanggan>
 */
class PelangganFactory extends Factory
{
    protected $model = Pelanggan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'kontak' => fake()->phoneNumber(),
            'tipe' => fake()->randomElement(TipePelanggan::cases()),
            'limit_piutang' => fake()->randomFloat(2, 0, 10000000),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
