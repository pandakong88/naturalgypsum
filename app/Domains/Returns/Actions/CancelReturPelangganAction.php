<?php

namespace App\Domains\Returns\Actions;

use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Returns\Models\ReturPelanggan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelReturPelangganAction
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function execute(int|ReturPelanggan $retur, ?int $userId = null): ReturPelanggan
    {
        $returId = $retur instanceof ReturPelanggan ? $retur->id : (int) $retur;
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($returId, $userId) {
            /** @var ReturPelanggan $record */
            $record = ReturPelanggan::where('id', $returId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $record->load(['produk', 'gudang']);
            $now = Carbon::now();

            // Reverse: Decrease stock from destination warehouse
            $this->stockService->decrease(
                produk: $record->produk,
                gudang: $record->gudang,
                qty: $record->jumlah,
                jenis: JenisMutasiStok::ReturDariPelanggan,
                referensi: $record->no_retur.' (Batal)',
                referensiType: 'retur_pelanggan',
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
