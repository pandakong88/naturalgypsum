<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        $demoUsers = [
            'Owner' => [
                'name' => 'Owner Natural Gypsum',
                'email' => 'owner@naturalgypsum.test',
            ],
            'Manager' => [
                'name' => 'Manager Operasional',
                'email' => 'manager@naturalgypsum.test',
            ],
            'Staff Penjualan' => [
                'name' => 'Staff Penjualan (Kasir)',
                'email' => 'penjualan@naturalgypsum.test',
            ],
            'Staff Gudang' => [
                'name' => 'Staff Gudang (Logistik)',
                'email' => 'gudang@naturalgypsum.test',
            ],
            'Staff Produksi' => [
                'name' => 'Staff Produksi',
                'email' => 'produksi@naturalgypsum.test',
            ],
        ];

        foreach ($demoUsers as $roleName => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }

        $this->call([
            MasterDataSeeder::class,
            InventorySeeder::class,
            RealisticPenjualanMutasiSeeder::class,
            ProfilPerusahaanSeeder::class,
        ]);
    }
}
