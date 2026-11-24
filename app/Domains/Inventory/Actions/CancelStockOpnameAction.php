<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Inventory\Services\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelStockOpnameAction
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Cancel an active stock opname, reverse all applied stock adjustments, and mark as Dibatalkan.
     */
    public function execute(StockOpname $stockOpname, ?string $alasanBatal = null, ?int $userId = null): StockOpname
    {
        if ($stockOpname->status === StatusStockOpname::Dibatalkan) {
            throw new InvalidArgumentException('Stock opname sudah dalam status dibatalkan.');
        }

        $cancellerId = $userId ?? Auth::id();
        $alasan = $alasanBatal ?? 'Pembatalan transaksi stock opname';

        return DB::transaction(function () use ($stockOpname, $cancellerId, $alasan) {
            $stockOpname->loadMissing(['details.produk', 'gudang']);

            // If it was already Selesai, reverse the adjustments
            if ($stockOpname->status === StatusStockOpname::Selesai) {
                foreach ($stockOpname->details as $detail) {
                    $selisih = (float) $detail->selisih;

                    // If there was an adjustment (selisih != 0), reverse it by applying -1 * selisih
                    if (abs($selisih) > 0.0001) {
                        $this->stockService->apply(
                            produk: $detail->produk,
                            gudang: $stockOpname->gudang,
                            mutationQty: -$selisih,
                            jenis: JenisMutasiStok::PenyesuaianOpname,
                            referensi: 'Batal '.$stockOpname->no_stock_opname,
                            referensiType: StockOpname::class,
                            referensiId: $stockOpname->id,
                            userId: $cancellerId,
                            tanggal: Carbon::now()
                        );
                    }
                }
            }

            $stockOpname->update([
                'status' => StatusStockOpname::Dibatalkan,
                'dibatalkan_at' => Carbon::now(),
                'dibatalkan_by' => $cancellerId,
                'alasan_batal' => $alasan,
                'updated_by' => $cancellerId,
            ]);

            app(ActivityLogService::class)->log(
                action: 'cancel',
                description: "Membatalkan stok opname {$stockOpname->no_stock_opname} (Alasan: {$alasan})",
                subject: $stockOpname,
                newValues: [
                    'status' => StatusStockOpname::Dibatalkan->value,
                    'alasan_batal' => $alasan,
                ],
                userId: $cancellerId
            );

            return $stockOpname->fresh(['details.produk', 'gudang', 'user', 'dibatalkanBy']);
        });
    }
}
