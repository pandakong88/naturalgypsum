<?php

namespace App\Domains\Sales\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Receivables\Actions\CancelPembayaranAction;
use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Returns\Models\ReturPelanggan;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelPenjualanAction
{
    public function __construct(
        protected StockService $stockService,
        protected CancelPembayaranAction $cancelPembayaranAction,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Cancel/void a sales transaction, return inventory, reverse payment allocations, and soft delete.
     *
     * @throws InvalidArgumentException
     */
    public function execute(int|Penjualan $penjualan, string $alasanBatal, ?int $userId = null): Penjualan
    {
        $penjualanId = $penjualan instanceof Penjualan ? $penjualan->id : (int) $penjualan;
        $userId = $userId ?? Auth::id();
        $alasan = trim($alasanBatal);

        if (empty($alasan)) {
            throw new InvalidArgumentException('Alasan pembatalan nota penjualan wajib diisi.');
        }

        return DB::transaction(function () use ($penjualanId, $alasan, $userId) {
            /** @var Penjualan $record */
            $record = Penjualan::withTrashed()
                ->where('id', $penjualanId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record->trashed() || ! is_null($record->dibatalkan_at)) {
                throw new InvalidArgumentException("Faktur penjualan {$record->no_penjualan} sudah dalam status dibatalkan sebelumnya.");
            }

            // Safety guard: Check for active customer returns linked to this sale
            $hasActiveRetur = ReturPelanggan::where('penjualan_id', $record->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasActiveRetur) {
                throw new InvalidArgumentException(
                    "Faktur penjualan {$record->no_penjualan} tidak dapat dibatalkan karena memiliki riwayat retur barang pelanggan. Batalkan transaksi retur terlebih dahulu."
                );
            }

            $record->loadMissing(['details.produk', 'details.gudang', 'gudang']);
            $now = Carbon::now();

            // 1. Revert stock: Return physical inventory to the warehouse
            foreach ($record->details as $detail) {
                $targetGudang = $detail->gudang ?? $record->gudang;

                $this->stockService->increase(
                    produk: $detail->produk,
                    gudang: $targetGudang,
                    qty: $detail->jumlah,
                    jenis: JenisMutasiStok::PenjualanKeluar,
                    referensi: 'Batal '.$record->no_penjualan,
                    referensiType: 'penjualan',
                    referensiId: $record->id,
                    userId: $userId,
                    tanggal: $now
                );
            }

            // 2. Revert payments & allocations
            $allocations = AlokasiPembayaran::where('penjualan_id', $record->id)
                ->with('pembayaran')
                ->get();

            foreach ($allocations as $allocation) {
                $pembayaran = $allocation->pembayaran;

                if (! $pembayaran) {
                    $allocation->delete();

                    continue;
                }

                $totalAllocationsForPayment = AlokasiPembayaran::where('pembayaran_id', $pembayaran->id)->count();

                if ($totalAllocationsForPayment <= 1) {
                    // Payment was solely for this invoice: cancel the payment entirely
                    $this->cancelPembayaranAction->execute($pembayaran, $userId);
                } else {
                    // Payment covered multiple invoices: remove this allocation and adjust payment total
                    $allocatedAmount = (float) $allocation->jumlah_dialokasikan;
                    $allocation->delete();

                    $newBayar = max(0.00, round((float) $pembayaran->jumlah_bayar - $allocatedAmount, 2));
                    $pembayaran->update([
                        'jumlah_bayar' => $newBayar,
                        'updated_by' => $userId,
                    ]);
                }
            }

            // 3. Reset receivable / payment amounts and record cancellation details
            $record->update([
                'status_pembayaran' => StatusPembayaran::BelumBayar,
                'total_dibayar' => 0.00,
                'sisa_piutang' => 0.00,
                'alasan_batal' => $alasan,
                'dibatalkan_by' => $userId,
                'dibatalkan_at' => $now,
                'updated_by' => $userId,
            ]);

            // 4. Soft delete the sales record
            $record->delete();

            // 5. System audit trail
            $this->activityLogService->log(
                action: 'cancel',
                description: "Membatalkan nota penjualan {$record->no_penjualan} (Alasan: {$alasan})",
                subject: $record,
                newValues: [
                    'status' => 'dibatalkan',
                    'alasan_batal' => $alasan,
                    'dibatalkan_at' => $now->toDateTimeString(),
                ],
                userId: $userId
            );

            return $record->fresh(['details.produk', 'details.gudang', 'pelanggan', 'gudang', 'dibatalkanBy']);
        });
    }
}
