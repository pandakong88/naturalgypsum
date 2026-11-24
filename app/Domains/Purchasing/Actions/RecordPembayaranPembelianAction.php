<?php

namespace App\Domains\Purchasing\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Purchasing\Enums\StatusPembayaranPembelian;
use App\Domains\Purchasing\Models\PembayaranPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordPembayaranPembelianAction
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Record a payment (down payment, installment, or settlement) for a purchase invoice.
     */
    public function execute(
        Pembelian $pembelian,
        float $nominal,
        string|Carbon $tanggalBayar,
        string $metodePembayaran = 'transfer',
        ?string $keterangan = null,
        ?int $userId = null
    ): PembayaranPembelian {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari nol.');
        }

        $sisaHutang = (float) $pembelian->sisa_hutang;
        if ($nominal > $sisaHutang + 0.01) {
            throw new InvalidArgumentException(
                'Nominal pembayaran (Rp '.number_format($nominal, 0, ',', '.').') melebihi sisa hutang (Rp '.number_format($sisaHutang, 0, ',', '.').').'
            );
        }

        $tanggal = $tanggalBayar instanceof Carbon ? $tanggalBayar : Carbon::parse($tanggalBayar);
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($pembelian, $nominal, $tanggal, $metodePembayaran, $keterangan, $userId) {
            $noPembayaran = $this->documentNumberService->generate('BYR-PB', (int) $tanggal->format('Y'));

            $pembayaran = PembayaranPembelian::create([
                'pembelian_id' => $pembelian->id,
                'no_pembayaran' => $noPembayaran,
                'tanggal_bayar' => $tanggal->toDateString(),
                'nominal' => $nominal,
                'metode_pembayaran' => $metodePembayaran,
                'keterangan' => $keterangan,
                'created_by' => $userId,
            ]);

            $newNominalBayar = round((float) $pembelian->nominal_bayar + $nominal, 2);
            $newSisaHutang = max(0.00, round((float) $pembelian->total - $newNominalBayar, 2));

            $newStatusPembayaran = $newSisaHutang <= 0.00
                ? StatusPembayaranPembelian::Lunas
                : StatusPembayaranPembelian::Sebagian;

            $pembelian->update([
                'nominal_bayar' => $newNominalBayar,
                'sisa_hutang' => $newSisaHutang,
                'status_pembayaran' => $newStatusPembayaran,
                'updated_by' => $userId,
            ]);

            $this->activityLog->log(
                action: 'payment',
                description: "Mencatat pembayaran {$noPembayaran} sebesar Rp ".number_format($nominal, 0, ',', '.')." untuk faktur pembelian {$pembelian->no_pembelian} (Sisa hutang: Rp ".number_format($newSisaHutang, 0, ',', '.').')',
                subject: $pembayaran,
                newValues: [
                    'no_pembayaran' => $noPembayaran,
                    'no_pembelian' => $pembelian->no_pembelian,
                    'nominal' => $nominal,
                    'metode_pembayaran' => $metodePembayaran,
                    'sisa_hutang' => $newSisaHutang,
                    'status_pembayaran' => $newStatusPembayaran->value,
                ],
                userId: $userId
            );

            return $pembayaran;
        });
    }
}
