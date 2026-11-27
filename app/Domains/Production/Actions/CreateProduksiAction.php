<?php

namespace App\Domains\Production\Actions;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Production\Models\DetailProduksi;
use App\Domains\Production\Models\Produksi;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateProduksiAction
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Execute production record creation (Finished goods output + optional raw materials consumption).
     *
     * @param  array{
     *     gudang_id: int,
     *     tanggal: string|Carbon,
     *     catatan?: string|null,
     *     items: array<int, array{
     *         produk_id: int,
     *         jumlah: float|int|string
     *     }>,
     *     materials?: array<int, array{
     *         produk_id: int,
     *         jumlah: float|int|string
     *     }>,
     *     user_id?: int|null
     * }  $data
     */
    public function execute(array $data): Produksi
    {
        $gudangId = (int) ($data['gudang_id'] ?? 0);
        if ($gudangId <= 0) {
            throw new InvalidArgumentException('Gudang tujuan hasil produksi wajib dipilih.');
        }

        $gudang = Gudang::whereNull('deleted_at')->findOrFail($gudangId);

        $rawItems = $data['items'] ?? [];
        if (empty($rawItems)) {
            throw new InvalidArgumentException('Produksi harus memiliki minimal satu baris barang.');
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();
        $catatan = isset($data['catatan']) ? trim($data['catatan']) : null;

        // Process finished goods output items & validate
        $mergedQuantities = [];
        foreach ($rawItems as $item) {
            $produkId = (int) ($item['produk_id'] ?? 0);
            $qty = (float) ($item['jumlah'] ?? 0);

            if ($produkId <= 0) {
                continue;
            }

            if ($qty <= 0) {
                throw new InvalidArgumentException('Jumlah barang hasil produksi harus lebih besar dari nol.');
            }

            if (! isset($mergedQuantities[$produkId])) {
                $mergedQuantities[$produkId] = 0.0;
            }
            $mergedQuantities[$produkId] += $qty;
        }

        if (empty($mergedQuantities)) {
            throw new InvalidArgumentException('Produksi harus memiliki minimal satu baris barang valid.');
        }

        $processedItems = [];
        foreach ($mergedQuantities as $produkId => $totalQty) {
            $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);
            $processedItems[] = [
                'produk' => $produk,
                'jumlah' => $totalQty,
            ];
        }

        // Process optional raw materials consumed (Pemakaian Internal)
        $rawMaterials = $data['materials'] ?? [];
        $mergedMaterials = [];
        foreach ($rawMaterials as $mat) {
            $matProdukId = (int) ($mat['produk_id'] ?? 0);
            $matQty = (float) ($mat['jumlah'] ?? 0);

            if ($matProdukId <= 0 || $matQty <= 0) {
                continue;
            }

            if (! isset($mergedMaterials[$matProdukId])) {
                $mergedMaterials[$matProdukId] = 0.0;
            }
            $mergedMaterials[$matProdukId] += $matQty;
        }

        $processedMaterials = [];
        foreach ($mergedMaterials as $matProdukId => $totalMatQty) {
            $matProduk = Produk::whereNull('deleted_at')->findOrFail($matProdukId);
            $processedMaterials[] = [
                'produk' => $matProduk,
                'jumlah' => $totalMatQty,
            ];
        }

        return DB::transaction(function () use (
            $gudang,
            $userId,
            $tanggal,
            $catatan,
            $processedItems,
            $processedMaterials
        ) {
            $noProduksi = $this->documentNumberService->generate('PRD', (int) $tanggal->format('Y'));

            /** @var Produksi $produksi */
            $produksi = Produksi::create([
                'no_produksi' => $noProduksi,
                'tanggal' => $tanggal->toDateString(),
                'gudang_id' => $gudang->id,
                'user_id' => $userId,
                'catatan' => $catatan ?: null,
                'created_by' => $userId,
            ]);

            foreach ($processedItems as $item) {
                DetailProduksi::create([
                    'produksi_id' => $produksi->id,
                    'produk_id' => $item['produk']->id,
                    'jumlah' => $item['jumlah'],
                ]);

                // Increase warehouse stock for finished goods output
                $this->stockService->increase(
                    produk: $item['produk'],
                    gudang: $gudang,
                    qty: $item['jumlah'],
                    jenis: JenisMutasiStok::ProduksiMasuk,
                    referensi: $produksi->no_produksi,
                    referensiType: 'produksi',
                    referensiId: $produksi->id,
                    userId: $userId,
                    tanggal: $tanggal
                );
            }

            foreach ($processedMaterials as $mat) {
                // Decrease warehouse stock for consumed raw materials
                $this->stockService->decrease(
                    produk: $mat['produk'],
                    gudang: $gudang,
                    qty: $mat['jumlah'],
                    jenis: JenisMutasiStok::PemakaianInternal,
                    referensi: $produksi->no_produksi,
                    referensiType: 'produksi_bahan',
                    referensiId: $produksi->id,
                    userId: $userId,
                    tanggal: $tanggal
                );
            }

            return $produksi->load(['gudang', 'details.produk', 'bahanMutations.produk', 'user']);
        });
    }
}
