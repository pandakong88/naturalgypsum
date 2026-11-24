<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Models\User;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the inventory seeds.
     */
    public function run(): void
    {
        $stockService = app(StockService::class);
        $docService = app(DocumentNumberService::class);

        $owner = User::where('email', 'owner@naturalgypsum.test')->first() ?? User::first();
        $gudangs = Gudang::all();
        $produks = Produk::all();

        if ($gudangs->isEmpty() || $produks->isEmpty() || ! $owner) {
            return;
        }

        $tokoUtama = $gudangs->firstWhere('nama_gudang', 'Toko Utama') ?? $gudangs->first();
        $gudangSelatan = $gudangs->firstWhere('nama_gudang', 'Gudang Selatan') ?? $gudangs->get(1) ?? $tokoUtama;
        $gudangUtara = $gudangs->firstWhere('nama_gudang', 'Gudang Utara') ?? $gudangs->get(2) ?? $tokoUtama;

        foreach ($produks as $index => $produk) {
            $refNumber = $docService->generate('PB');
            $stockQty = 50 + ($index * 15);

            // Initial stock in Toko Utama via PembelianMasuk
            $stockService->increase(
                produk: $produk,
                gudang: $tokoUtama,
                qty: $stockQty,
                jenis: JenisMutasiStok::PembelianMasuk,
                referensi: $refNumber,
                referensiType: 'pembelian',
                referensiId: $index + 1,
                userId: $owner->id,
                tanggal: Carbon::now()->subDays(10)
            );

            // Branch warehouse initial movement (Gudang Selatan & Gudang Utara)
            if ($gudangSelatan->id !== $tokoUtama->id && $index % 2 === 0) {
                $branchRef = $docService->generate('PB');
                $stockService->increase(
                    produk: $produk,
                    gudang: $gudangSelatan,
                    qty: 20 + ($index * 5),
                    jenis: JenisMutasiStok::PembelianMasuk,
                    referensi: $branchRef,
                    referensiType: 'pembelian',
                    referensiId: $index + 100,
                    userId: $owner->id,
                    tanggal: Carbon::now()->subDays(5)
                );
            }

            if ($gudangUtara->id !== $tokoUtama->id && $index % 2 !== 0) {
                $northRef = $docService->generate('PB');
                $stockService->increase(
                    produk: $produk,
                    gudang: $gudangUtara,
                    qty: 30 + ($index * 5),
                    jenis: JenisMutasiStok::PembelianMasuk,
                    referensi: $northRef,
                    referensiType: 'pembelian',
                    referensiId: $index + 200,
                    userId: $owner->id,
                    tanggal: Carbon::now()->subDays(5)
                );
            }
        }
    }
}
