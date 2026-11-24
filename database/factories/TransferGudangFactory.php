<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Inventory\Models\TransferGudang;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferGudang>
 */
class TransferGudangFactory extends Factory
{
    protected $model = TransferGudang::class;

    public function definition(): array
    {
        return [
            'no_transfer' => 'TR-'.Carbon::now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::now()->toDateString(),
            'gudang_asal_id' => Gudang::factory(),
            'gudang_tujuan_id' => Gudang::factory(),
            'user_id' => User::factory(),
            'keterangan' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
