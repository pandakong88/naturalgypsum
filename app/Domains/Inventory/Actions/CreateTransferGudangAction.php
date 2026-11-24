<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\DetailTransferGudang;
use App\Domains\Inventory\Models\TransferGudang;
use App\Domains\Inventory\Services\StockService;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateTransferGudangAction
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumberService,
        protected ?ActivityLogService $activityLog = null
    ) {
        $this->activityLog ??= app(ActivityLogService::class);
    }

    /**
     * Execute inter-warehouse stock transfer.
     *
     * @param  array{
     *     gudang_asal_id: int,
     *     gudang_tujuan_id: int,
     *     tanggal: string|Carbon,
     *     keterangan?: string|null,
     *     items: array<int, array{
     *         produk_id: int,
     *         jumlah: float|int|string
     *     }>,
     *     user_id?: int|null
     * }  $data
     */
    public function execute(array $data): TransferGudang
    {
        $gudangAsalId = (int) ($data['gudang_asal_id'] ?? 0);
        $gudangTujuanId = (int) ($data['gudang_tujuan_id'] ?? 0);

        if ($gudangAsalId <= 0 || $gudangTujuanId <= 0) {
            throw new InvalidArgumentException('Gudang asal dan gudang tujuan wajib dipilih.');
        }

        if ($gudangAsalId === $gudangTujuanId) {
            throw new InvalidArgumentException('Gudang asal dan gudang tujuan tidak boleh sama.');
        }

        $gudangAsal = Gudang::whereNull('deleted_at')->findOrFail($gudangAsalId);
        $gudangTujuan = Gudang::whereNull('deleted_at')->findOrFail($gudangTujuanId);

        $rawItems = $data['items'] ?? [];
        if (empty($rawItems)) {
            throw new InvalidArgumentException('Transfer harus memiliki minimal satu baris barang.');
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();
        $keterangan = isset($data['keterangan']) ? trim($data['keterangan']) : null;

        // Process items & validate
        $processedItems = [];
        $mergedQuantities = [];

        foreach ($rawItems as $item) {
            $produkId = (int) ($item['produk_id'] ?? 0);
            $qty = (float) ($item['jumlah'] ?? 0);

            if ($produkId <= 0) {
                continue;
            }

            if ($qty <= 0) {
                throw new InvalidArgumentException('Jumlah barang transfer harus lebih besar dari nol.');
            }

            if (! isset($mergedQuantities[$produkId])) {
                $mergedQuantities[$produkId] = 0.0;
            }
            $mergedQuantities[$produkId] += $qty;
        }

        if (empty($mergedQuantities)) {
            throw new InvalidArgumentException('Transfer harus memiliki minimal satu baris barang valid.');
        }

        foreach ($mergedQuantities as $produkId => $totalQty) {
            $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);
            $processedItems[] = [
                'produk' => $produk,
                'jumlah' => $totalQty,
            ];
        }

        return DB::transaction(function () use (
            $gudangAsal,
            $gudangTujuan,
            $userId,
            $tanggal,
            $keterangan,
            $processedItems
        ) {
            $noTransfer = $this->documentNumberService->generate('TR', (int) $tanggal->format('Y'));

            /** @var TransferGudang $transfer */
            $transfer = TransferGudang::create([
                'no_transfer' => $noTransfer,
                'tanggal' => $tanggal->toDateString(),
                'gudang_asal_id' => $gudangAsal->id,
                'gudang_tujuan_id' => $gudangTujuan->id,
                'user_id' => $userId,
                'keterangan' => $keterangan ?: null,
                'created_by' => $userId,
            ]);

            foreach ($processedItems as $item) {
                DetailTransferGudang::create([
                    'transfer_gudang_id' => $transfer->id,
                    'produk_id' => $item['produk']->id,
                    'jumlah' => $item['jumlah'],
                ]);

                // Decrease source warehouse stock
                $this->stockService->decrease(
                    produk: $item['produk'],
                    gudang: $gudangAsal,
                    qty: $item['jumlah'],
                    jenis: JenisMutasiStok::Transfer,
                    referensi: $transfer->no_transfer,
                    referensiType: 'transfer_gudang',
                    referensiId: $transfer->id,
                    userId: $userId,
                    tanggal: $tanggal
                );

                // Increase destination warehouse stock
                $this->stockService->increase(
                    produk: $item['produk'],
                    gudang: $gudangTujuan,
                    qty: $item['jumlah'],
                    jenis: JenisMutasiStok::Transfer,
                    referensi: $transfer->no_transfer,
                    referensiType: 'transfer_gudang',
                    referensiId: $transfer->id,
                    userId: $userId,
                    tanggal: $tanggal
                );
            }

            $this->activityLog->log(
                action: 'transfer',
                description: "Transfer stok #{$transfer->no_transfer} dari {$gudangAsal->nama_gudang} ke {$gudangTujuan->nama_gudang} (".count($processedItems).' item)',
                subject: $transfer,
                newValues: [
                    'no_transfer' => $transfer->no_transfer,
                    'gudang_asal' => $gudangAsal->nama_gudang,
                    'gudang_tujuan' => $gudangTujuan->nama_gudang,
                    'total_items' => count($processedItems),
                    'keterangan' => $keterangan,
                ],
                userId: $userId
            );

            return $transfer->load(['gudangAsal', 'gudangTujuan', 'details.produk', 'user']);
        });
    }
}
