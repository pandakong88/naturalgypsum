<?php

namespace App\Domains\Purchasing\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Catalog\Services\PriceHistoryService;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Purchasing\Enums\StatusPembayaranPembelian;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\DetailPembelian;
use App\Domains\Purchasing\Models\PembayaranPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreatePembelianAction
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumberService,
        protected ActivityLogService $activityLog,
        protected ?PriceHistoryService $priceHistory = null
    ) {
        $this->priceHistory ??= app(PriceHistoryService::class);
    }

    /**
     * Execute purchase transaction creation with atomic stock updates.
     *
     * @param  array{
     *     supplier_id: int,
     *     tanggal: string|Carbon,
     *     no_faktur_supplier?: string|null,
     *     no_surat_jalan?: string|null,
     *     no_kontainer?: string|null,
     *     no_polisi?: string|null,
     *     status?: StatusPembelian|string|null,
     *     status_pembayaran?: StatusPembayaranPembelian|string|null,
     *     tanggal_jatuh_tempo?: string|Carbon|null,
     *     nominal_bayar_awal?: float|int|string|null,
     *     metode_pembayaran_awal?: string|null,
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
     *     user_id?: int|null,
     *     update_master_harga_beli?: bool|null
     * }  $data
     */
    public function execute(array $data): Pembelian
    {
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $supplier = Supplier::whereNull('deleted_at')->findOrFail($supplierId);

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new InvalidArgumentException('Pembelian must contain at least one line item.');
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();
        $noFakturSupplier = filled($data['no_faktur_supplier'] ?? null) ? trim((string) $data['no_faktur_supplier']) : null;
        $noSuratJalan = filled($data['no_surat_jalan'] ?? null) ? trim((string) $data['no_surat_jalan']) : null;
        $noKontainer = filled($data['no_kontainer'] ?? null) ? trim((string) $data['no_kontainer']) : null;
        $noPolisi = filled($data['no_polisi'] ?? null) ? trim((string) $data['no_polisi']) : null;
        $keterangan = filled($data['keterangan'] ?? null) ? trim((string) $data['keterangan']) : null;
        $updateMasterHargaBeli = ! empty($data['update_master_harga_beli']);

        $statusRaw = $data['status'] ?? StatusPembelian::Received;
        $status = $statusRaw instanceof StatusPembelian
            ? $statusRaw
            : (StatusPembelian::tryFrom((string) $statusRaw) ?? StatusPembelian::Received);

        $defaultStatusBayar = ($status === StatusPembelian::Ordered)
            ? StatusPembayaranPembelian::Tempo
            : StatusPembayaranPembelian::Lunas;

        $statusBayarRaw = $data['status_pembayaran'] ?? $defaultStatusBayar;
        $statusPembayaran = $statusBayarRaw instanceof StatusPembayaranPembelian
            ? $statusBayarRaw
            : (StatusPembayaranPembelian::tryFrom((string) $statusBayarRaw) ?? $defaultStatusBayar);

        $tanggalJatuhTempo = filled($data['tanggal_jatuh_tempo'] ?? null)
            ? Carbon::parse($data['tanggal_jatuh_tempo'])->toDateString()
            : null;

        // Pre-validate all items before entering transaction
        $processedItems = [];
        $total = 0.00;

        foreach ($items as $index => $item) {
            $produkId = (int) ($item['produk_id'] ?? 0);
            $gudangId = (int) ($item['gudang_id'] ?? 0);
            $qty = (float) ($item['jumlah'] ?? 0);
            $price = (float) ($item['harga_beli_satuan'] ?? 0);

            if ($qty <= 0) {
                throw new InvalidArgumentException('Item at row '.($index + 1).' has invalid quantity. Quantity must be greater than zero.');
            }

            if ($price < 0) {
                throw new InvalidArgumentException('Item at row '.($index + 1).' has invalid price. Price cannot be negative.');
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

        // Determine nominal bayar and sisa hutang
        $nominalBayarAwal = isset($data['nominal_bayar_awal'])
            ? (float) $data['nominal_bayar_awal']
            : (isset($data['nominal_bayar']) ? (float) $data['nominal_bayar'] : null);
        if ($statusPembayaran === StatusPembayaranPembelian::Lunas) {
            $nominalBayar = $total;
            $sisaHutang = 0.00;
        } elseif ($statusPembayaran === StatusPembayaranPembelian::Tempo) {
            $nominalBayar = $nominalBayarAwal !== null && $nominalBayarAwal > 0 ? min($nominalBayarAwal, $total) : 0.00;
            $sisaHutang = max(0.00, round($total - $nominalBayar, 2));
            if ($nominalBayar > 0 && $sisaHutang > 0) {
                $statusPembayaran = StatusPembayaranPembelian::Sebagian;
            } elseif ($nominalBayar >= $total) {
                $statusPembayaran = StatusPembayaranPembelian::Lunas;
            }
        } else {
            // Sebagian
            $nominalBayar = $nominalBayarAwal !== null ? min(max(0.00, $nominalBayarAwal), $total) : 0.00;
            $sisaHutang = max(0.00, round($total - $nominalBayar, 2));
            if ($sisaHutang == 0.00) {
                $statusPembayaran = StatusPembayaranPembelian::Lunas;
            }
        }

        return DB::transaction(function () use (
            $supplier,
            $userId,
            $tanggal,
            $total,
            $noFakturSupplier,
            $noSuratJalan,
            $noKontainer,
            $noPolisi,
            $status,
            $statusPembayaran,
            $tanggalJatuhTempo,
            $nominalBayar,
            $sisaHutang,
            $keterangan,
            $processedItems,
            $updateMasterHargaBeli,
            $data
        ) {
            // Generate unique sequential PB number
            $noPembelian = $this->documentNumberService->generate('PB', (int) $tanggal->format('Y'));

            $isReceived = $status === StatusPembelian::Received;

            /** @var Pembelian $pembelian */
            $pembelian = Pembelian::create([
                'supplier_id' => $supplier->id,
                'user_id' => $userId,
                'no_pembelian' => $noPembelian,
                'no_faktur_supplier' => $noFakturSupplier,
                'no_surat_jalan' => $noSuratJalan,
                'no_kontainer' => $noKontainer,
                'no_polisi' => $noPolisi,
                'tanggal' => $tanggal->toDateString(),
                'total' => $total,
                'status' => $status,
                'status_pembayaran' => $statusPembayaran,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'nominal_bayar' => $nominalBayar,
                'sisa_hutang' => $sisaHutang,
                'tanggal_diterima' => $isReceived ? $tanggal->toDateString() : null,
                'diterima_by' => $isReceived ? $userId : null,
                'keterangan' => $keterangan,
                'created_by' => $userId,
            ]);

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

                // Increase inventory stock ONLY if status is Received (goods arrived at warehouse)
                if ($isReceived) {
                    $this->stockService->increase(
                        produk: $item['produk'],
                        gudang: $item['gudang'],
                        qty: $item['jumlah'],
                        jenis: JenisMutasiStok::PembelianMasuk,
                        referensi: $pembelian->no_pembelian,
                        referensiType: 'pembelian',
                        referensiId: $pembelian->id,
                        userId: $userId,
                        tanggal: $tanggal
                    );

                    // Optionally update master purchase price on product catalog
                    if ($updateMasterHargaBeli) {
                        $oldHargaBeli = (float) $item['produk']->harga_beli;
                        $newHargaBeli = (float) $item['harga_beli_satuan'];

                        $item['produk']->update([
                            'harga_beli' => $newHargaBeli,
                            'updated_by' => $userId,
                        ]);

                        $this->priceHistory?->recordSingleChange(
                            produk: $item['produk'],
                            jenisHarga: 'harga_beli',
                            hargaLama: $oldHargaBeli,
                            hargaBaru: $newHargaBeli,
                            sumber: 'pembelian',
                            referensi: $pembelian->no_pembelian,
                            referensiType: 'pembelian',
                            referensiId: $pembelian->id,
                            supplierId: $supplier->id,
                            keterangan: "Pembaruan HPP otomatis dari Faktur Pembelian {$pembelian->no_pembelian} ({$supplier->nama_supplier})",
                            userId: $userId
                        );
                    }
                }
            }

            // Record initial payment record if any payment was made
            if ($nominalBayar > 0) {
                PembayaranPembelian::create([
                    'pembelian_id' => $pembelian->id,
                    'no_pembayaran' => $this->documentNumberService->generate('BYR-PB', (int) $tanggal->format('Y')),
                    'tanggal_bayar' => $tanggal->toDateString(),
                    'nominal' => $nominalBayar,
                    'metode_pembayaran' => $data['metode_pembayaran_awal'] ?? 'transfer',
                    'keterangan' => $statusPembayaran === StatusPembayaranPembelian::Lunas
                        ? 'Pembayaran Lunas saat transaksi'
                        : 'Uang Muka / Pembayaran Awal Transaksi',
                    'created_by' => $userId,
                ]);
            }

            $this->activityLog->log(
                action: 'create',
                description: "Membuat transaksi pembelian {$pembelian->no_pembelian} (".($isReceived ? 'Langsung Diterima' : 'Status PO Dipesan').") dari supplier {$supplier->nama_supplier} total Rp ".number_format((float) $pembelian->total, 0, ',', '.')." (Status Bayar: {$statusPembayaran->label()})",
                subject: $pembelian,
                newValues: [
                    'no_pembelian' => $pembelian->no_pembelian,
                    'supplier' => $supplier->nama_supplier,
                    'status' => $status->value,
                    'status_pembayaran' => $statusPembayaran->value,
                    'no_faktur_supplier' => $noFakturSupplier,
                    'no_surat_jalan' => $noSuratJalan,
                    'tanggal' => $pembelian->tanggal->toDateString(),
                    'total' => (float) $pembelian->total,
                    'nominal_bayar' => $nominalBayar,
                    'sisa_hutang' => $sisaHutang,
                    'total_items' => count($processedItems),
                    'no_kontainer' => $noKontainer,
                ],
                userId: $userId
            );

            return $pembelian->load(['details.produk', 'details.gudang', 'supplier', 'pembayaran']);
        });
    }
}
