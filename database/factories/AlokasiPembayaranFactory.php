<?php

namespace Database\Factories;

use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Sales\Models\Penjualan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlokasiPembayaran>
 */
class AlokasiPembayaranFactory extends Factory
{
    protected $model = AlokasiPembayaran::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pembayaran_id' => Pembayaran::factory(),
            'penjualan_id' => Penjualan::factory(),
            'jumlah_dialokasikan' => fake()->randomFloat(2, 10000, 500000),
        ];
    }
}
