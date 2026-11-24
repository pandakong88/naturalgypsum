<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use App\Models\User;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpname>
 */
class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        $tanggal = Carbon::now();
        $noOpname = app(DocumentNumberService::class)->generate('SO', (int) $tanggal->format('Y'));

        return [
            'no_stock_opname' => $noOpname,
            'tanggal' => $tanggal->toDateString(),
            'gudang_id' => Gudang::factory(),
            'user_id' => User::factory(),
            'status' => StatusStockOpname::Selesai,
            'catatan' => fake()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusStockOpname::Draft,
        ]);
    }

    public function dibatalkan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusStockOpname::Dibatalkan,
            'dibatalkan_at' => Carbon::now(),
            'dibatalkan_by' => User::factory(),
            'alasan_batal' => 'Salah input hitungan fisik',
        ]);
    }
}
