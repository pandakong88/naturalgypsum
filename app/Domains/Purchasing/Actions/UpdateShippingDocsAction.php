<?php

namespace App\Domains\Purchasing\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Purchasing\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateShippingDocsAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Update logistics, shipping documents and due date of a purchase.
     *
     * @param  array{
     *     no_faktur_supplier?: string|null,
     *     no_surat_jalan?: string|null,
     *     no_kontainer?: string|null,
     *     no_polisi?: string|null,
     *     tanggal_jatuh_tempo?: string|Carbon|null,
     *     keterangan?: string|null
     * }  $data
     */
    public function execute(Pembelian $pembelian, array $data, ?int $userId = null): Pembelian
    {
        $userId = $userId ?? Auth::id();

        $noFakturSupplier = filled($data['no_faktur_supplier'] ?? null) ? trim((string) $data['no_faktur_supplier']) : null;
        $noSuratJalan = filled($data['no_surat_jalan'] ?? null) ? trim((string) $data['no_surat_jalan']) : null;
        $noKontainer = filled($data['no_kontainer'] ?? null) ? trim((string) $data['no_kontainer']) : null;
        $noPolisi = filled($data['no_polisi'] ?? null) ? trim((string) $data['no_polisi']) : null;
        $keterangan = isset($data['keterangan']) ? (filled($data['keterangan']) ? trim((string) $data['keterangan']) : null) : $pembelian->keterangan;
        $tanggalJatuhTempo = filled($data['tanggal_jatuh_tempo'] ?? null)
            ? Carbon::parse($data['tanggal_jatuh_tempo'])->toDateString()
            : null;

        return DB::transaction(function () use (
            $pembelian,
            $noFakturSupplier,
            $noSuratJalan,
            $noKontainer,
            $noPolisi,
            $tanggalJatuhTempo,
            $keterangan,
            $userId
        ) {
            $oldValues = [
                'no_faktur_supplier' => $pembelian->no_faktur_supplier,
                'no_surat_jalan' => $pembelian->no_surat_jalan,
                'no_kontainer' => $pembelian->no_kontainer,
                'no_polisi' => $pembelian->no_polisi,
                'tanggal_jatuh_tempo' => $pembelian->tanggal_jatuh_tempo?->toDateString(),
            ];

            $pembelian->update([
                'no_faktur_supplier' => $noFakturSupplier,
                'no_surat_jalan' => $noSuratJalan,
                'no_kontainer' => $noKontainer,
                'no_polisi' => $noPolisi,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'keterangan' => $keterangan,
                'updated_by' => $userId,
            ]);

            $this->activityLog->log(
                action: 'update_shipping',
                description: "Memperbarui dokumen pengiriman / logistik pembelian {$pembelian->no_pembelian}",
                subject: $pembelian,
                oldValues: $oldValues,
                newValues: [
                    'no_faktur_supplier' => $noFakturSupplier,
                    'no_surat_jalan' => $noSuratJalan,
                    'no_kontainer' => $noKontainer,
                    'no_polisi' => $noPolisi,
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                ],
                userId: $userId
            );

            return $pembelian->fresh(['supplier', 'penerima', 'creator', 'updater']);
        });
    }
}
