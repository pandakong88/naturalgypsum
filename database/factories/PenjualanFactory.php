<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Domains\Sales\Models\Penjualan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penjualan>
 */
class PenjualanFactory extends Factory
{
    protected $model = Penjualan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pelanggan_id' => Pelanggan::factory(),
            'gudang_id' => Gudang::factory(),
            'user_id' => User::factory(),
            'no_penjualan' => 'PJ-'.Carbon::now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::now()->toDateString(),
            'biaya_ongkir' => 0.00,
            'total_akhir' => 0.00,
            'status_pembayaran' => StatusPembayaran::BelumBayar,
            'total_dibayar' => 0.00,
            'sisa_piutang' => 0.00,
            'keterangan' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
