<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MutasiStok>
 */
class MutasiStokFactory extends Factory
{
    protected $model = MutasiStok::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(JenisMutasiStok::cases());
        $qty = $jenis->isAddition()
            ? fake()->randomFloat(3, 10, 100)
            : -fake()->randomFloat(3, 1, 20);

        return [
            'produk_id' => Produk::factory(),
            'gudang_id' => Gudang::factory(),
            'user_id' => User::factory(),
            'jenis' => $jenis,
            'jumlah' => $qty,
            'referensi' => 'REF-'.fake()->unique()->numerify('######'),
            'referensi_type' => 'manual',
            'referensi_id' => fake()->randomNumber(5),
            'tanggal' => Carbon::now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }
}
