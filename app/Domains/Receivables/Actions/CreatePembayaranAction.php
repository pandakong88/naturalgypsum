<?php

namespace App\Domains\Receivables\Actions;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Receivables\Services\PaymentAllocationService;
use App\Domains\Sales\Models\Penjualan;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreatePembayaranAction
{
    public function __construct(
        protected PaymentAllocationService $allocationService,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Execute customer payment creation with manual allocation to sales invoices.
     *
     * @param  array{
     *     pelanggan_id: int,
     *     tanggal: string|Carbon,
     *     jumlah_bayar: float|int|string,
     *     metode: string|MetodePembayaran,
     *     catatan?: string|null,
     *     alokasi?: array<int, array{
     *         penjualan_id: int,
     *         jumlah_dialokasikan: float|int|string
     *     }>,
     *     user_id?: int|null
     * }  $data
     */
    public function execute(array $data): Pembayaran
    {
        $pelangganId = (int) ($data['pelanggan_id'] ?? 0);
        $pelanggan = Pelanggan::whereNull('deleted_at')->findOrFail($pelangganId);

        $jumlahBayar = round((float) ($data['jumlah_bayar'] ?? 0), 2);
        if ($jumlahBayar <= 0.00) {
            throw new InvalidArgumentException('Jumlah bayar must be greater than zero.');
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();

        $metode = $data['metode'] ?? MetodePembayaran::Tunai;
        if (! ($metode instanceof MetodePembayaran)) {
            $metode = MetodePembayaran::tryFrom((string) $metode)
                ?? throw new InvalidArgumentException("Invalid payment method: {$data['metode']}");
        }

        $catatan = isset($data['catatan']) ? trim($data['catatan']) : null;
        $rawAllocations = $data['alokasi'] ?? [];

        // Validate allocation totals and uniqueness
        $processedAllocations = [];
        $totalAllocated = 0.00;
        $seenInvoices = [];

        foreach ($rawAllocations as $alloc) {
            $penjualanId = (int) ($alloc['penjualan_id'] ?? 0);
            $allocatedAmount = round((float) ($alloc['jumlah_dialokasikan'] ?? 0), 2);

            if ($allocatedAmount <= 0.00) {
                continue; // Skip zero/negative allocations or ignore if empty
            }

            if (isset($seenInvoices[$penjualanId])) {
                throw new InvalidArgumentException("Duplicate invoice allocation detected for invoice ID {$penjualanId}.");
            }
            $seenInvoices[$penjualanId] = true;

            $totalAllocated = round($totalAllocated + $allocatedAmount, 2);
            $processedAllocations[] = [
                'penjualan_id' => $penjualanId,
                'jumlah_dialokasikan' => $allocatedAmount,
            ];
        }

        if ($totalAllocated > $jumlahBayar) {
            throw new InvalidArgumentException("Total allocated amount ({$totalAllocated}) cannot exceed payment amount ({$jumlahBayar}).");
        }

        return DB::transaction(function () use (
            $pelanggan,
            $userId,
            $tanggal,
            $jumlahBayar,
            $metode,
            $catatan,
            $processedAllocations
        ) {
            // Generate unique sequential payment number
            $noPembayaran = $this->documentNumberService->generate('BY', (int) $tanggal->format('Y'));

            /** @var Pembayaran $pembayaran */
            $pembayaran = Pembayaran::create([
                'pelanggan_id' => $pelanggan->id,
                'user_id' => $userId,
                'no_pembayaran' => $noPembayaran,
                'tanggal' => $tanggal->toDateString(),
                'jumlah_bayar' => $jumlahBayar,
                'metode' => $metode,
                'catatan' => $catatan ?: null,
                'created_by' => $userId,
            ]);

            foreach ($processedAllocations as $alloc) {
                $penjualanId = $alloc['penjualan_id'];
                $allocAmount = $alloc['jumlah_dialokasikan'];

                /** @var Penjualan $penjualan */
                $penjualan = Penjualan::where('id', $penjualanId)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->firstOrFail();

                // Invariant: payment customer must match invoice customer
                if ($penjualan->pelanggan_id !== $pelanggan->id) {
                    throw new InvalidArgumentException("Invoice {$penjualan->no_penjualan} does not belong to customer {$pelanggan->nama}.");
                }

                // Invariant: allocation amount cannot exceed current outstanding balance
                if ($allocAmount > (float) $penjualan->sisa_piutang) {
                    throw new InvalidArgumentException("Allocation amount ({$allocAmount}) exceeds outstanding balance ({$penjualan->sisa_piutang}) for invoice {$penjualan->no_penjualan}.");
                }

                AlokasiPembayaran::create([
                    'pembayaran_id' => $pembayaran->id,
                    'penjualan_id' => $penjualan->id,
                    'jumlah_dialokasikan' => $allocAmount,
                ]);

                // Recalculate invoice totals and status
                $this->allocationService->recalculateInvoice($penjualan);
            }

            return $pembayaran->load(['pelanggan', 'alokasi.penjualan', 'user']);
        });
    }
}
