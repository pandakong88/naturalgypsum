<?php

namespace App\Domains\Receivables\Services;

use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Domains\Sales\Models\Penjualan;

class PaymentAllocationService
{
    /**
     * Recalculate total_dibayar, sisa_piutang, and status_pembayaran for a sales invoice.
     * Must be called within a database transaction with appropriate row locking.
     */
    public function recalculateInvoice(int|Penjualan $penjualan): Penjualan
    {
        $penjualanId = $penjualan instanceof Penjualan ? $penjualan->id : (int) $penjualan;

        /** @var Penjualan $invoice */
        $invoice = Penjualan::where('id', $penjualanId)->lockForUpdate()->firstOrFail();

        $totalDibayar = (float) AlokasiPembayaran::where('penjualan_id', $invoice->id)
            ->whereHas('pembayaran', fn ($q) => $q->whereNull('deleted_at'))
            ->sum('jumlah_dialokasikan');

        $totalDibayar = round($totalDibayar, 2);
        $totalAkhir = (float) $invoice->total_akhir;
        $sisaPiutang = max(0.00, round($totalAkhir - $totalDibayar, 2));

        if ($totalDibayar <= 0.00) {
            $status = $totalAkhir == 0.00 ? StatusPembayaran::Lunas : StatusPembayaran::BelumBayar;
        } elseif ($totalDibayar >= $totalAkhir) {
            $status = StatusPembayaran::Lunas;
        } else {
            $status = StatusPembayaran::Sebagian;
        }

        $invoice->update([
            'total_dibayar' => $totalDibayar,
            'sisa_piutang' => $sisaPiutang,
            'status_pembayaran' => $status,
        ]);

        return $invoice->fresh();
    }
}
