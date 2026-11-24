<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_supplier' => fake()->company().' Supplier',
            'kontak' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
            'email' => fake()->companyEmail(),
            'nama_bank' => fake()->randomElement(['BCA', 'Bank Mandiri', 'BRI', 'BNI']),
            'no_rekening' => fake()->numerify('##########'),
            'atas_nama_rekening' => fake()->company(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
