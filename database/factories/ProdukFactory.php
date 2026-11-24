<?php

namespace Database\Factories;

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $satuan = fake()->randomElement(SatuanProduk::cases());
        $hargaJual = fake()->randomFloat(2, 20000, 150000);
        $hargaBeli = round($hargaJual * 0.75, 2);
        $hargaAplikator = round($hargaJual * 0.90, 2);
        $hargaGrosir = round($hargaJual * 0.85, 2);

        return [
            'kategori_id' => Kategori::factory(),
            'merek_id' => Merek::factory(),
            'kode_katalog' => strtoupper(fake()->unique()->bothify('KAT-###??')),
            'nama' => fake()->words(3, true),
            'ukuran' => fake()->randomElement(['2.5m x 20cm', '3m x 20cm', '4m x 20cm', null]),
            'satuan' => $satuan,
            'harga_beli' => $hargaBeli,
            'harga_jual' => $hargaJual,
            'harga_aplikator' => $hargaAplikator,
            'harga_grosir' => $hargaGrosir,
            'stok_minimum' => fake()->randomFloat(3, 5, 50),
            'panjang_meter' => $satuan === SatuanProduk::Meter ? fake()->randomElement([3.00, 4.00, 5.00, 6.00]) : null,
            'kemasan' => match ($satuan) {
                SatuanProduk::Meter => 'Dus',
                SatuanProduk::Batang => 'Ikat',
                SatuanProduk::Dus => 'Dus',
                SatuanProduk::Zak => 'Sak',
                default => null,
            },
            'isi_per_kemasan' => match ($satuan) {
                SatuanProduk::Meter => 15.00,
                SatuanProduk::Batang => 20.00,
                SatuanProduk::Dus => 1000.00,
                SatuanProduk::Zak => 1.00,
                default => null,
            },
            'panjang_per_roll' => null,
            'foto' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
