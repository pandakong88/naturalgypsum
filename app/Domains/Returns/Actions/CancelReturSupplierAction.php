<?php

namespace App\Domains\Returns\Actions;

use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Returns\Models\ReturSupplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelReturSupplierAction
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function execute(int|ReturSupplier $retur, ?int $userId = null): ReturSupplier
    {
        $returId = $retur instanceof ReturSupplier ? $retur->id : (int) $retur;
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($returId, $userId) {
            /** @var ReturSupplier $record */
            $record = ReturSupplier::where('id', $returId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $record->load(['produk', 'gudang']);
            $now = Carbon::now();

            // Reverse: Increase stock back to warehouse
            $this->stockService->increase(
                produk: $record->produk,
                gudang: $record->gudang,
                qty: $record->jumlah,
                jenis: JenisMutasiStok::ReturKeSupplier,
                referensi: $record->no_retur.' (Batal)',
                referensiType: 'retur_supplier',
                referensiId: $record->id,
                userId: $userId,
                tanggal: $now
            );

            $record->update(['updated_by' => $userId]);
            $record->delete();

            return $record;
        });
    }
}
