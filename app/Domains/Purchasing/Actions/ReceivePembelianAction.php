<?php

namespace App\Domains\Purchasing\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Services\PriceHistoryService;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReceivePembelianAction
{
    public function __construct(
        protected StockService $stockService,
        protected ActivityLogService $activityLog,
        protected ?PriceHistoryService $priceHistory = null
    ) {
        $this->priceHistory ??= app(PriceHistoryService::class);
    }

    /**
     * Mark an ordered purchase as received and increase warehouse stocks.
     */
    public function execute(
        Pembelian $pembelian,
        ?int $userId = null,
        string|Carbon|null $tanggalDiterima = null,
        ?string $noSuratJalan = null,
        ?string $noFakturSupplier = null,
        ?string $noPolisi = null,
        bool $updateMasterHargaBeli = false
    ): Pembelian {
        if ($pembelian->status === StatusPembelian::Received) {
            throw new InvalidArgumentException("Transaksi pembelian {$pembelian->no_pembelian} sudah berstatus Diterima sebelumnya.");
        }

        if ($pembelian->status === StatusPembelian::Cancelled) {
            throw new InvalidArgumentException("Transaksi pembelian {$pembelian->no_pembelian} sudah dibatalkan dan tidak dapat diterima.");
        }

        $userId = $userId ?? Auth::id();
        $tanggalTerima = $tanggalDiterima instanceof Carbon
            ? $tanggalDiterima
            : (filled($tanggalDiterima) ? Carbon::parse($tanggalDiterima) : Carbon::now());

        return DB::transaction(function () use ($pembelian, $userId, $tanggalTerima, $noSuratJalan, $noFakturSupplier, $noPolisi, $updateMasterHargaBeli) {
            $pembelian->loadMissing(['details.produk', 'details.gudang', 'supplier']);

            $updatePayload = [
                'status' => StatusPembelian::Received,
                'tanggal_diterima' => $tanggalTerima->toDateString(),
                'diterima_by' => $userId,
                'updated_by' => $userId,
            ];

            if (filled($noSuratJalan)) {
                $updatePayload['no_surat_jalan'] = trim($noSuratJalan);
            }

            if (filled($noFakturSupplier)) {
                $updatePayload['no_faktur_supplier'] = trim($noFakturSupplier);
            }

            if (filled($noPolisi)) {
                $updatePayload['no_polisi'] = trim($noPolisi);
            }

            $pembelian->update($updatePayload);

            foreach ($pembelian->details as $item) {
                $this->stockService->increase(
                    produk: $item->produk,
                    gudang: $item->gudang,
                    qty: (float) $item->jumlah,
                    jenis: JenisMutasiStok::PembelianMasuk,
                    referensi: $pembelian->no_pembelian,
                    referensiType: 'pembelian',
                    referensiId: $pembelian->id,
                    userId: $userId,
                    tanggal: $tanggalTerima
                );

                if ($updateMasterHargaBeli) {
                    $oldHargaBeli = (float) $item->produk->harga_beli;
                    $newHargaBeli = (float) $item->harga_beli_satuan;

                    $item->produk->update([
                        'harga_beli' => $newHargaBeli,
                        'updated_by' => $userId,
                    ]);

                    $this->priceHistory?->recordSingleChange(
                        produk: $item->produk,
                        jenisHarga: 'harga_beli',
                        hargaLama: $oldHargaBeli,
                        hargaBaru: $newHargaBeli,
                        sumber: 'pembelian',
                        referensi: $pembelian->no_pembelian,
                        referensiType: 'pembelian',
                        referensiId: $pembelian->id,
                        supplierId: $pembelian->supplier_id,
                        keterangan: "Pembaruan HPP otomatis saat Terima Barang PO {$pembelian->no_pembelian} ({$pembelian->supplier?->nama_supplier})",
                        userId: $userId
                    );
                }
            }

            $this->activityLog->log(
                action: 'receive',
                description: "Menerima barang masuk untuk pesanan {$pembelian->no_pembelian} dari supplier {$pembelian->supplier?->nama_supplier} (Total: {$pembelian->details->count()} item masuk gudang)",
                subject: $pembelian,
                newValues: [
                    'no_pembelian' => $pembelian->no_pembelian,
                    'status' => StatusPembelian::Received->value,
                    'tanggal_diterima' => $tanggalTerima->toDateString(),
                    'diterima_by' => $userId,
                    'no_surat_jalan' => $pembelian->no_surat_jalan,
                ],
                userId: $userId
            );

            return $pembelian->fresh(['details.produk', 'details.gudang', 'supplier', 'penerima']);
        });
    }
}
