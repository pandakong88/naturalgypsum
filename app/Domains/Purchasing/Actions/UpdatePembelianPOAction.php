<?php

namespace App\Domains\Purchasing\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Enums\StatusPembayaranPembelian;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\DetailPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdatePembelianPOAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Update an ordered purchase order (PO) items and terms before physical receipt.
     *
     * @param  array{
     *     supplier_id: int,
     *     tanggal: string|Carbon,
     *     tanggal_jatuh_tempo?: string|Carbon|null,
     *     keterangan?: string|null,
     *     items: array<int, array{
     *         produk_id: int,
     *         gudang_id: int,
     *         jumlah: float|int|string,
     *         harga_beli_satuan: float|int|string,
     *         qty_lembar?: float|int|string|null,
     *         panjang_meter?: float|int|string|null,
     *         harga_bruto?: float|int|string|null,
     *         diskon_satuan?: float|int|string|null
     *     }>,
     *     user_id?: int|null
     * }  $data
     */
    public function execute(Pembelian $pembelian, array $data): Pembelian
    {
        if ($pembelian->status !== StatusPembelian::Ordered) {
            throw new InvalidArgumentException("Hanya pesanan pembelian (PO) berstatus 'Dipesan' yang dapat diedit langsung. Transaksi yang sudah diterima di gudang tidak dapat diedit demi integritas stok.");
        }

        $supplierId = (int) ($data['supplier_id'] ?? $pembelian->supplier_id);
        $supplier = Supplier::whereNull('deleted_at')->findOrFail($supplierId);

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new InvalidArgumentException('PO pembelian harus memiliki minimal satu baris barang.');
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : $pembelian->tanggal;
        $userId = $data['user_id'] ?? Auth::id();
        $keterangan = isset($data['keterangan']) ? (filled($data['keterangan']) ? trim((string) $data['keterangan']) : null) : $pembelian->keterangan;
        $tanggalJatuhTempo = filled($data['tanggal_jatuh_tempo'] ?? null)
            ? Carbon::parse($data['tanggal_jatuh_tempo'])->toDateString()
            : null;

        // Pre-validate items and calculate new total
        $processedItems = [];
        $total = 0.00;

        foreach ($items as $index => $item) {
            $produkId = (int) ($item['produk_id'] ?? 0);
            $gudangId = (int) ($item['gudang_id'] ?? 0);
            $qty = (float) ($item['jumlah'] ?? 0);
            $price = (float) ($item['harga_beli_satuan'] ?? 0);

            if ($qty <= 0) {
                throw new InvalidArgumentException('Baris ke-'.($index + 1).' memiliki jumlah yang tidak valid (harus lebih besar dari 0).');
            }

            if ($price < 0) {
                throw new InvalidArgumentException('Baris ke-'.($index + 1).' memiliki harga beli yang tidak valid (tidak boleh negatif).');
            }

            $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);
            $gudang = Gudang::whereNull('deleted_at')->findOrFail($gudangId);

            $subtotal = round($qty * $price, 2);
            $total += $subtotal;

            $qtyLembar = filled($item['qty_lembar'] ?? null) ? (float) $item['qty_lembar'] : null;
            $panjangMeter = filled($item['panjang_meter'] ?? null) ? (float) $item['panjang_meter'] : null;
            $hargaBruto = filled($item['harga_bruto'] ?? null) ? (float) $item['harga_bruto'] : null;
            $diskonSatuan = filled($item['diskon_satuan'] ?? null) ? (float) $item['diskon_satuan'] : 0.00;

            $processedItems[] = [
                'produk' => $produk,
                'gudang' => $gudang,
                'qty_lembar' => $qtyLembar,
                'panjang_meter' => $panjangMeter,
                'jumlah' => $qty,
                'harga_bruto' => $hargaBruto,
                'diskon_satuan' => $diskonSatuan,
                'harga_beli_satuan' => $price,
                'subtotal' => $subtotal,
            ];
        }

        $currentPaid = (float) $pembelian->nominal_bayar;
        if ($total < $currentPaid) {
            throw new InvalidArgumentException('Total PO baru (Rp '.number_format($total, 0, ',', '.').') tidak boleh lebih kecil dari uang muka / pembayaran yang sudah dicatat (Rp '.number_format($currentPaid, 0, ',', '.').').');
        }

        $sisaHutang = max(0.00, round($total - $currentPaid, 2));

        if ($sisaHutang <= 0.00) {
            $statusPembayaran = StatusPembayaranPembelian::Lunas;
        } elseif ($currentPaid > 0.00) {
            $statusPembayaran = StatusPembayaranPembelian::Sebagian;
        } else {
            $statusPembayaran = StatusPembayaranPembelian::Tempo;
        }

        return DB::transaction(function () use (
            $pembelian,
            $supplier,
            $userId,
            $tanggal,
            $total,
            $statusPembayaran,
            $tanggalJatuhTempo,
            $sisaHutang,
            $keterangan,
            $processedItems
        ) {
            $oldTotal = (float) $pembelian->total;
            $oldSupplier = $pembelian->supplier?->nama_supplier;

            // Delete old details
            DetailPembelian::where('pembelian_id', $pembelian->id)->delete();

            // Insert new details
            foreach ($processedItems as $item) {
                DetailPembelian::create([
                    'pembelian_id' => $pembelian->id,
                    'produk_id' => $item['produk']->id,
                    'gudang_id' => $item['gudang']->id,
                    'qty_lembar' => $item['qty_lembar'],
                    'panjang_meter' => $item['panjang_meter'],
                    'jumlah' => $item['jumlah'],
                    'harga_bruto' => $item['harga_bruto'],
                    'diskon_satuan' => $item['diskon_satuan'],
                    'harga_beli_satuan' => $item['harga_beli_satuan'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $pembelian->update([
                'supplier_id' => $supplier->id,
                'tanggal' => $tanggal->toDateString(),
                'total' => $total,
                'status_pembayaran' => $statusPembayaran,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'sisa_hutang' => $sisaHutang,
                'keterangan' => $keterangan,
                'updated_by' => $userId,
            ]);

            $this->activityLog->log(
                action: 'update',
                description: "Memperbarui pesanan pembelian (PO) {$pembelian->no_pembelian} supplier {$supplier->nama_supplier} (Total: Rp ".number_format($total, 0, ',', '.').')',
                subject: $pembelian,
                oldValues: [
                    'supplier' => $oldSupplier,
                    'total' => $oldTotal,
                ],
                newValues: [
                    'supplier' => $supplier->nama_supplier,
                    'total' => $total,
                    'items_count' => count($processedItems),
                    'sisa_hutang' => $sisaHutang,
                    'status_pembayaran' => $statusPembayaran->value,
                ],
                userId: $userId
            );

            return $pembelian->fresh(['details.produk', 'details.gudang', 'supplier', 'pembayaran']);
        });
    }
}
