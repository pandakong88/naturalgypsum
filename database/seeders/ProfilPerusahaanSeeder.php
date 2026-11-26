<?php

namespace Database\Seeders;

use App\Domains\Administration\Models\ProfilPerusahaan;
use Illuminate\Database\Seeder;

class ProfilPerusahaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProfilPerusahaan::firstOrCreate(
            ['id' => 1],
            [
                'nama_toko' => 'NATURAL GYPSUM & PVC',
                'slogan' => 'Aplikator dan Penyedia Bahan Gypsum dan PVC',
                'alamat' => 'Jln. Piyungan Prambanan KM 2 Pucung',
                'telepon' => '082136702637',
                'email' => 'info@naturalgypsum.com',
                'website' => 'naturalgypsum.com',
                'catatan_kaki_faktur' => 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa perjanjian tertulis.',
                'rekening_bank' => [
                    [
                        'nama_bank' => 'BCA',
                        'nomor_rekening' => '123-456-7890',
                        'atas_nama' => 'Natural Gypsum',
                    ],
                    [
                        'nama_bank' => 'MANDIRI',
                        'nomor_rekening' => '987-654-3210',
                        'atas_nama' => 'Natural Gypsum',
                    ],
                ],
            ]
        );
    }
}
