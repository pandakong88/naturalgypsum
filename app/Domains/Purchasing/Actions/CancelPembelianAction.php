<?php

namespace App\Domains\Purchasing\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelPembelianAction
{
    public function __construct(
        protected StockService $stockService,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Cancel a purchase transaction.
     * If the purchase was already received in the warehouse, checks stock sufficiency and reverts inventory.
     */
    public function execute(Pembelian $pembelian, ?string $alasan = null, ?int $userId = null): Pembelian
    {
        if ($pembelian->status === StatusPembelian::Cancelled) {
            throw new InvalidArgumentException("Transaksi pembelian {$pembelian->no_pembelian} sudah dibatalkan sebelumnya.");
        }

        $userId = $userId ?? Auth::id();
        $isReceived = $pembelian->isReceived();
        $pembelian->loadMissing(['details.produk', 'details.gudang', 'supplier']);

        // If goods were received, verify that warehouse still has enough stock to return/revert
        if ($isReceived) {
            foreach ($pembelian->details as $item) {
                $stokTersedia = (float) (Stok::where('produk_id', $item->produk_id)
                    ->where('gudang_id', $item->gudang_id)
                    ->value('jumlah') ?? 0);

                $qtyDibutuhkan = (float) $item->jumlah;
                if ($stokTersedia < $qtyDibutuhkan) {
                    $namaProduk = $item->produk?->nama ?? "Produk #{$item->produk_id}";
                    $namaGudang = $item->gudang?->nama ?? "Gudang #{$item->gudang_id}";

                    throw new InvalidArgumentException(
                        "Tidak dapat membatalkan transaksi: Stok {$namaProduk} di {$namaGudang} tersisa {$stokTersedia}, tidak mencukupi untuk menarik kembali {$qtyDibutuhkan} unit (kemungkinan barang sudah terjual atau terpakai)."
                    );
                }
            }
        }

        return DB::transaction(function () use ($pembelian, $isReceived, $alasan, $userId) {
            $catatanBatal = filled($alasan) ? trim($alasan) : 'Dibatalkan oleh pengguna';

            // Revert warehouse stocks if previously received
            if ($isReceived) {
                foreach ($pembelian->details as $item) {
                    $this->stockService->decrease(
                        produk: $item->produk,
                        gudang: $item->gudang,
                        qty: (float) $item->jumlah,
                        jenis: JenisMutasiStok::ReturKeSupplier,
                        referensi: "BATAL-{$pembelian->no_pembelian}",
                        referensiType: 'pembelian',
                        referensiId: $pembelian->id,
                        userId: $userId,
                        tanggal: Carbon::now()
                    );
                }
            }

            $oldKeterangan = $pembelian->keterangan;
            $newKeterangan = trim(($oldKeterangan ? $oldKeterangan.' | ' : '')."[Dibatalkan: {$catatanBatal}]");

            $pembelian->update([
                'status' => StatusPembelian::Cancelled,
                'sisa_hutang' => 0.00,
                'keterangan' => $newKeterangan,
                'updated_by' => $userId,
            ]);

            $this->activityLog->log(
                action: 'cancel',
                description: "Membatalkan transaksi pembelian {$pembelian->no_pembelian} (".($isReceived ? 'Stok ditarik kembali dari gudang' : 'Status PO Dibatalkan')."). Alasan: {$catatanBatal}",
                subject: $pembelian,
                oldValues: [
                    'status' => $isReceived ? StatusPembelian::Received->value : StatusPembelian::Ordered->value,
                    'sisa_hutang' => (float) $pembelian->getOriginal('sisa_hutang'),
                ],
                newValues: [
                    'status' => StatusPembelian::Cancelled->value,
                    'sisa_hutang' => 0.00,
                    'alasan_batal' => $catatanBatal,
                ],
                userId: $userId
            );

            return $pembelian->fresh(['details.produk', 'details.gudang', 'supplier', 'pembayaran']);
        });
    }
}
