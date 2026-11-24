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
     * Execute production record creation (Finished goods output only).
     *
     * @param  array{
     *     gudang_id: int,
     *     tanggal: string|Carbon,
     *     catatan?: string|null,
     *     items: array<int, array{
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

        // Process items & validate
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

        return DB::transaction(function () use (
            $gudang,
            $userId,
            $tanggal,
            $catatan,
            $processedItems
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

                // Increase warehouse stock for output
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

            return $produksi->load(['gudang', 'details.produk', 'user']);
        });
    }
}
