<?php

namespace App\Domains\Purchasing\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\DetailPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeletePembelianPOAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Delete an un-received purchase order (PO).
     */
    public function execute(Pembelian $pembelian, ?int $userId = null): bool
    {
        if ($pembelian->status === StatusPembelian::Received) {
            throw new InvalidArgumentException("Pembelian {$pembelian->no_pembelian} yang sudah diterima di gudang tidak dapat dihapus. Silakan gunakan fitur Batalkan jika ingin menarik stok kembali.");
        }

        if ($pembelian->pembayaran()->count() > 0 || (float) $pembelian->nominal_bayar > 0) {
            throw new InvalidArgumentException("Pesanan pembelian (PO) {$pembelian->no_pembelian} yang sudah memiliki catatan pembayaran tidak dapat dihapus.");
        }

        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($pembelian, $userId) {
            $this->activityLog->log(
                action: 'delete',
                description: "Menghapus data pesanan pembelian (PO) {$pembelian->no_pembelian} ke supplier {$pembelian->supplier?->nama_supplier} (Total: Rp ".number_format((float) $pembelian->total, 0, ',', '.').')',
                subject: $pembelian,
                oldValues: [
                    'no_pembelian' => $pembelian->no_pembelian,
                    'supplier' => $pembelian->supplier?->nama_supplier,
                    'total' => (float) $pembelian->total,
                ],
                userId: $userId
            );

            DetailPembelian::where('pembelian_id', $pembelian->id)->delete();
            $pembelian->delete();

            return true;
        });
    }
}
