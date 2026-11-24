<?php

namespace App\Domains\Production\Actions;

use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Production\Models\Produksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelProduksiAction
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function execute(int|Produksi $produksi, ?int $userId = null): Produksi
    {
        $produksiId = $produksi instanceof Produksi ? $produksi->id : (int) $produksi;
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($produksiId, $userId) {
            /** @var Produksi $record */
            $record = Produksi::where('id', $produksiId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $record->load(['details.produk', 'gudang']);
            $now = Carbon::now();

            foreach ($record->details as $detail) {
                // Reverse: Decrease destination warehouse stock
                $this->stockService->decrease(
                    produk: $detail->produk,
                    gudang: $record->gudang,
                    qty: $detail->jumlah,
                    jenis: JenisMutasiStok::ProduksiMasuk,
                    referensi: $record->no_produksi.' (Batal)',
                    referensiType: 'produksi',
                    referensiId: $record->id,
                    userId: $userId,
                    tanggal: $now
                );
            }

            $record->update(['updated_by' => $userId]);
            $record->delete();

            return $record;
        });
    }
}
