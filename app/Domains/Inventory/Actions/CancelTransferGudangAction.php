<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\TransferGudang;
use App\Domains\Inventory\Services\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelTransferGudangAction
{
    public function __construct(
        protected StockService $stockService,
        protected ?ActivityLogService $activityLog = null
    ) {
        $this->activityLog ??= app(ActivityLogService::class);
    }

    public function execute(int|TransferGudang $transfer, ?int $userId = null): TransferGudang
    {
        $transferId = $transfer instanceof TransferGudang ? $transfer->id : (int) $transfer;
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($transferId, $userId) {
            /** @var TransferGudang $record */
            $record = TransferGudang::where('id', $transferId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $record->load(['details.produk', 'gudangAsal', 'gudangTujuan']);
            $now = Carbon::now();

            foreach ($record->details as $detail) {
                // Reverse: Increase source back
                $this->stockService->increase(
                    produk: $detail->produk,
                    gudang: $record->gudangAsal,
                    qty: $detail->jumlah,
                    jenis: JenisMutasiStok::Transfer,
                    referensi: $record->no_transfer.' (Batal)',
                    referensiType: 'transfer_gudang',
                    referensiId: $record->id,
                    userId: $userId,
                    tanggal: $now
                );

                // Reverse: Decrease destination back
                $this->stockService->decrease(
                    produk: $detail->produk,
                    gudang: $record->gudangTujuan,
                    qty: $detail->jumlah,
                    jenis: JenisMutasiStok::Transfer,
                    referensi: $record->no_transfer.' (Batal)',
                    referensiType: 'transfer_gudang',
                    referensiId: $record->id,
                    userId: $userId,
                    tanggal: $now
                );
            }

            $this->activityLog->log(
                action: 'cancel',
                description: "Membatalkan transfer stok #{$record->no_transfer} ({$record->gudangAsal->nama_gudang} \u{2192} {$record->gudangTujuan->nama_gudang}) dan mengembalikan stok",
                subject: $record,
                oldValues: [
                    'no_transfer' => $record->no_transfer,
                    'gudang_asal' => $record->gudangAsal->nama_gudang,
                    'gudang_tujuan' => $record->gudangTujuan->nama_gudang,
                    'total_items' => $record->details->count(),
                ],
                userId: $userId
            );

            $record->update(['updated_by' => $userId]);
            $record->delete();

            return $record;
        });
    }
}
