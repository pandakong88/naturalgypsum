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
                'slogan' => 'Distributor & Toko Material Plafon, Gypsum, Rangka Hollow & PVC',
                'alamat' => 'Jl. Raya Utama Toko Natural Gypsum',
                'telepon' => '0812-3456-7890',
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
