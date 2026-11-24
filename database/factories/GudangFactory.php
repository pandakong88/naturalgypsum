<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gudang>
 */
class GudangFactory extends Factory
{
    protected $model = Gudang::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_gudang' => 'Gudang '.fake()->unique()->city(),
            'lokasi' => fake()->address(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
