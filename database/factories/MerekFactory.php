<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Merek;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merek>
 */
class MerekFactory extends Factory
{
    protected $model = Merek::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_merek' => fake()->unique()->company(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
