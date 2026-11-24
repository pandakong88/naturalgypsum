<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembayaran>
 */
class PembayaranFactory extends Factory
{
    protected $model = Pembayaran::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pelanggan_id' => Pelanggan::factory(),
            'user_id' => User::factory(),
            'no_pembayaran' => 'BY-'.Carbon::now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::now()->toDateString(),
            'jumlah_bayar' => fake()->randomFloat(2, 50000, 5000000),
            'metode' => fake()->randomElement(MetodePembayaran::cases()),
            'catatan' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
