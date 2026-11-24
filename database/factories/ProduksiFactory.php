<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Production\Models\Produksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produksi>
 */
class ProduksiFactory extends Factory
{
    protected $model = Produksi::class;

    public function definition(): array
    {
        return [
            'no_produksi' => 'PRD-'.Carbon::now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::now()->toDateString(),
            'gudang_id' => Gudang::factory(),
            'user_id' => User::factory(),
            'catatan' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
