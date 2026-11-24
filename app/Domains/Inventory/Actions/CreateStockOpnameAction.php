<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\DetailStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Inventory\Services\StockService;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateStockOpnameAction
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Execute stock opname creation, snapshot calculation, and atomic adjustments.
     *
     * @param  array{
     *     gudang_id: int,
     *     tanggal: string|Carbon,
     *     catatan?: string|null,
     *     status?: string|StatusStockOpname|null,
     *     items: array<int, array{
     *         produk_id: int,
     *         stok_fisik: float|int|string,
     *         catatan?: string|null
     *     }>,
     *     user_id?: int|null
     * } $data
     */
    public function execute(array $data): StockOpname
    {
        $gudangId = (int) ($data['gudang_id'] ?? 0);
        if ($gudangId <= 0) {
            throw new InvalidArgumentException('Gudang wajib dipilih untuk stock opname.');
        }

        $gudang = Gudang::whereNull('deleted_at')->findOrFail($gudangId);

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new InvalidArgumentException('Stock opname harus memiliki minimal satu baris barang.');
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();
        $catatan = isset($data['catatan']) ? trim($data['catatan']) : null;
        $status = $data['status'] ?? StatusStockOpname::Selesai;
        if (is_string($status)) {
            $status = StatusStockOpname::from($status);
        }

        // Validate products and prevent duplicate product rows in same opname
        $processedProducts = [];
        $validatedItems = [];

        foreach ($items as $item) {
            $produkId = (int) ($item['produk_id'] ?? 0);
            $stokFisik = (float) ($item['stok_fisik'] ?? 0);
            $itemCatatan = isset($item['catatan']) ? trim($item['catatan']) : null;

            if ($produkId <= 0) {
                continue;
            }

            if ($stokFisik < 0) {
                throw new InvalidArgumentException('Stok fisik tidak boleh bernilai negatif.');
            }

            if (isset($processedProducts[$produkId])) {
                throw new InvalidArgumentException('Produk yang sama tidak boleh diinput lebih dari satu kali dalam satu opname.');
            }
            $processedProducts[$produkId] = true;

            $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);

            $validatedItems[] = [
                'produk' => $produk,
                'stok_fisik' => $stokFisik,
                'catatan' => $itemCatatan,
            ];
        }

        if (empty($validatedItems)) {
            throw new InvalidArgumentException('Stock opname harus memiliki minimal satu baris barang valid.');
        }

        return DB::transaction(function () use (
            $gudang,
            $tanggal,
            $userId,
            $catatan,
            $status,
            $validatedItems
        ) {
            $noStockOpname = $this->documentNumberService->generate('SO', (int) $tanggal->format('Y'));

            $stockOpname = StockOpname::create([
                'no_stock_opname' => $noStockOpname,
                'tanggal' => $tanggal->toDateString(),
                'gudang_id' => $gudang->id,
                'user_id' => $userId,
                'status' => $status,
                'catatan' => $catatan,
                'created_by' => $userId,
            ]);

            foreach ($validatedItems as $entry) {
                /** @var Produk $produk */
                $produk = $entry['produk'];
                $stokFisik = $entry['stok_fisik'];
                $itemCatatan = $entry['catatan'];

                // Snapshot accurate system stock at this exact moment
                $stokSistem = (float) $this->stockService->getStock($produk, $gudang);
                $selisih = $stokFisik - $stokSistem;

                DetailStockOpname::create([
                    'stock_opname_id' => $stockOpname->id,
                    'produk_id' => $produk->id,
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $selisih,
                    'catatan' => $itemCatatan,
                ]);

                // Apply mutation ONLY if status is Selesai and there is a non-zero difference
                if ($status === StatusStockOpname::Selesai && abs($selisih) > 0.0001) {
                    $this->stockService->apply(
                        produk: $produk,
                        gudang: $gudang,
                        mutationQty: $selisih,
                        jenis: JenisMutasiStok::PenyesuaianOpname,
                        referensi: $stockOpname->no_stock_opname,
                        referensiType: StockOpname::class,
                        referensiId: $stockOpname->id,
                        userId: $userId,
                        tanggal: $tanggal
                    );
                }
            }

            app(ActivityLogService::class)->log(
                action: $status === StatusStockOpname::Draft ? 'create_draft' : 'create',
                description: "Membuat stok opname {$stockOpname->no_stock_opname} di {$gudang->nama_gudang} (Status: {$status->label()})",
                subject: $stockOpname,
                newValues: [
                    'no_stock_opname' => $stockOpname->no_stock_opname,
                    'gudang' => $gudang->nama_gudang,
                    'status' => $status->value,
                    'total_items' => count($validatedItems),
                ],
                userId: $userId
            );

            return $stockOpname->load(['details.produk', 'gudang', 'user']);
        });
    }
}
