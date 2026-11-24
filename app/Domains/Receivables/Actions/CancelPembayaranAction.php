<?php

namespace App\Domains\Receivables\Actions;

use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Receivables\Services\PaymentAllocationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelPembayaranAction
{
    public function __construct(
        protected PaymentAllocationService $allocationService
    ) {}

    /**
     * Cancel/reverse a payment and recalculate all affected sales invoices.
     */
    public function execute(int|Pembayaran $pembayaran, ?int $userId = null): Pembayaran
    {
        $pembayaranId = $pembayaran instanceof Pembayaran ? $pembayaran->id : (int) $pembayaran;
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($pembayaranId, $userId) {
            /** @var Pembayaran $payment */
            $payment = Pembayaran::where('id', $pembayaranId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            // Find all affected sales invoices
            $allocations = AlokasiPembayaran::where('pembayaran_id', $payment->id)->get();
            $affectedInvoiceIds = $allocations->pluck('penjualan_id')->unique()->toArray();

            // Delete allocation records
            AlokasiPembayaran::where('pembayaran_id', $payment->id)->delete();

            // Mark updated_by and soft delete payment
            $payment->update(['updated_by' => $userId]);
            $payment->delete();

            // Recalculate each affected invoice
            foreach ($affectedInvoiceIds as $invoiceId) {
                $this->allocationService->recalculateInvoice($invoiceId);
            }

            return $payment;
        });
    }
}
