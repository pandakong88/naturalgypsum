<?php

namespace App\Domains\Sales\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Sales\Enums\JenisDiskon;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Domains\Sales\Models\DetailPenjualan;
use App\Domains\Sales\Models\Penjualan;
use App\Domains\Sales\Services\DiscountCalculationService;
use App\Models\User;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreatePenjualanAction
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumberService,
        protected DiscountCalculationService $discountCalculationService,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute sales transaction creation with atomic stock updates.
     *
     * @param  array{
     *     pelanggan_id: int,
     *     gudang_id: int,
     *     tanggal: string|Carbon,
     *     biaya_ongkir?: float|int|string|null,
     *     keterangan?: string|null,
     *     nama_sopir?: string|null,
     *     plat_nomor?: string|null,
     *     alamat_pengiriman?: string|null,
     *     items: array<int, array{
     *         produk_id: int,
     *         gudang_id?: int|null,
     *         harga_satuan: float|int|string,
     *         jumlah: float|int|string,
     *         jenis_diskon?: string|JenisDiskon|null,
     *         nilai_diskon?: float|int|string|null
     *     }>,
     *     user_id?: int|null
     * }  $data
     */
    public function execute(array $data): Penjualan
    {
        $pelangganId = (int) ($data['pelanggan_id'] ?? 0);
        $pelanggan = Pelanggan::whereNull('deleted_at')->findOrFail($pelangganId);

        $gudangId = (int) ($data['gudang_id'] ?? 0);
        $gudang = Gudang::whereNull('deleted_at')->findOrFail($gudangId);

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new InvalidArgumentException('Penjualan must contain at least one line item.');
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();
        $user = $userId ? User::find($userId) : Auth::user();

        $biayaOngkir = (float) ($data['biaya_ongkir'] ?? 0.00);
        if ($biayaOngkir < 0) {
            throw new InvalidArgumentException('Biaya ongkir cannot be negative.');
        }

        $keterangan = isset($data['keterangan']) ? trim($data['keterangan']) : null;
        $namaSopir = isset($data['nama_sopir']) ? trim($data['nama_sopir']) : null;
        $platNomor = isset($data['plat_nomor']) ? trim($data['plat_nomor']) : null;
        $alamatPengiriman = isset($data['alamat_pengiriman']) ? trim($data['alamat_pengiriman']) : null;
        $metodePembayaran = isset($data['metode_pembayaran']) ? trim($data['metode_pembayaran']) : 'tunai';

        // Pre-validate all items and calculate line items
        $processedItems = [];
        $subtotalPenjualan = 0.00;

        foreach ($items as $index => $item) {
            $rowNum = $index + 1;
            $produkId = (int) ($item['produk_id'] ?? 0);
            $qty = (float) ($item['jumlah'] ?? 0);
            $price = (float) ($item['harga_satuan'] ?? 0);

            if ($qty <= 0) {
                throw new InvalidArgumentException("Item at row {$rowNum} has invalid quantity. Quantity must be greater than zero.");
            }

            if ($price < 0) {
                throw new InvalidArgumentException("Item at row {$rowNum} has invalid price. Price cannot be negative.");
            }

            $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);

            // Price Override Authorization Check (reference = customer-tier price)
            $hargaNormal = round($produk->getHargaUntukPelanggan($pelanggan->tipe), 2);
            if (abs($price - $hargaNormal) > 0.001) {
                if ($user && ! $user->can('override_sales_price')) {
                    throw new AuthorizationException("User is not authorized to override sales price on item at row {$rowNum}.");
                }
            }

            // Discount Processing & Authorization Check
            $gross = round($qty * $price, 2);
            $rawJenisDiskon = $item['jenis_diskon'] ?? null;
            if ($rawJenisDiskon instanceof JenisDiskon) {
                $rawJenisDiskon = $rawJenisDiskon->value;
            }
            $nilaiDiskon = (float) ($item['nilai_diskon'] ?? 0.00);
            $discountAmount = 0.00;
            $finalJenisDiskon = null;

            if ($nilaiDiskon < 0) {
                throw new InvalidArgumentException("Discount value cannot be negative on row {$rowNum}.");
            }

            if ($nilaiDiskon > 0) {
                if ($rawJenisDiskon === JenisDiskon::Persentase->value || $rawJenisDiskon === 'persentase') {
                    if ($nilaiDiskon > 100) {
                        throw new InvalidArgumentException("Percentage discount on row {$rowNum} must be between 0 and 100.");
                    }
                    $discountAmount = round($gross * ($nilaiDiskon / 100), 2);
                    $finalJenisDiskon = JenisDiskon::Persentase;
                } elseif ($rawJenisDiskon === JenisDiskon::Nominal->value || $rawJenisDiskon === 'nominal' || empty($rawJenisDiskon)) {
                    $discountAmount = round($nilaiDiskon, 2);
                    $finalJenisDiskon = JenisDiskon::Nominal;
                } else {
                    throw new InvalidArgumentException("Invalid discount type on row {$rowNum}.");
                }

                if ($discountAmount > $gross) {
                    throw new InvalidArgumentException("Discount amount on row {$rowNum} cannot exceed item gross total.");
                }

                if ($user && ! $user->can('apply_sales_discount')) {
                    $promo = $this->discountCalculationService->calculate($produk, $pelanggan, $qty, $price);
                    $promoAmount = $promo['has_discount'] ? (float) $promo['total_diskon'] : 0.00;

                    if ($discountAmount > $promoAmount + 0.01) {
                        throw new AuthorizationException("User is not authorized to apply sales discount on item at row {$rowNum}.");
                    }
                }
            } else {
                $nilaiDiskon = 0.00;
                $finalJenisDiskon = null;
            }

            $subtotal = max(0.00, round($gross - $discountAmount, 2));
            $subtotalPenjualan += $subtotal;

            $itemGudangId = (int) ($item['gudang_id'] ?? 0);
            $itemGudang = $itemGudangId > 0 && $itemGudangId !== $gudang->id
                ? Gudang::whereNull('deleted_at')->findOrFail($itemGudangId)
                : $gudang;

            $processedItems[] = [
                'produk' => $produk,
                'gudang' => $itemGudang,
                'harga_satuan' => $price,
                'harga_normal' => $hargaNormal,
                'jumlah' => $qty,
                'jenis_diskon' => $finalJenisDiskon,
                'nilai_diskon' => $nilaiDiskon,
                'subtotal' => $subtotal,
            ];
        }

        $totalAkhir = round($subtotalPenjualan + $biayaOngkir, 2);

        return DB::transaction(function () use (
            $pelanggan,
            $gudang,
            $userId,
            $tanggal,
            $biayaOngkir,
            $totalAkhir,
            $keterangan,
            $namaSopir,
            $platNomor,
            $alamatPengiriman,
            $metodePembayaran,
            $processedItems
        ) {
            // Generate unique sequential PJ number
            $noPenjualan = $this->documentNumberService->generate('PJ', (int) $tanggal->format('Y'));

            /** @var Penjualan $penjualan */
            $penjualan = Penjualan::create([
                'pelanggan_id' => $pelanggan->id,
                'gudang_id' => $gudang->id,
                'user_id' => $userId,
                'no_penjualan' => $noPenjualan,
                'tanggal' => $tanggal->toDateString(),
                'biaya_ongkir' => $biayaOngkir,
                'total_akhir' => $totalAkhir,
                'status_pembayaran' => StatusPembayaran::BelumBayar,
                'metode_pembayaran' => $metodePembayaran ?: 'tunai',
                'total_dibayar' => 0.00,
                'sisa_piutang' => $totalAkhir,
                'keterangan' => $keterangan ?: null,
                'nama_sopir' => $namaSopir ?: null,
                'plat_nomor' => $platNomor ?: null,
                'alamat_pengiriman' => $alamatPengiriman ?: null,
                'created_by' => $userId,
            ]);

            foreach ($processedItems as $item) {
                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'produk_id' => $item['produk']->id,
                    'gudang_id' => $item['gudang']->id,
                    'harga_satuan' => $item['harga_satuan'],
                    'harga_normal' => $item['harga_normal'],
                    'jenis_diskon' => $item['jenis_diskon']?->value,
                    'nilai_diskon' => $item['nilai_diskon'],
                    'jumlah' => $item['jumlah'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Decrease inventory stock using StockService gatekeeper
                $this->stockService->decrease(
                    produk: $item['produk'],
                    gudang: $item['gudang'],
                    qty: $item['jumlah'],
                    jenis: JenisMutasiStok::PenjualanKeluar,
                    referensi: $penjualan->no_penjualan,
                    referensiType: 'penjualan',
                    referensiId: $penjualan->id,
                    userId: $userId,
                    tanggal: $tanggal
                );
            }

            $this->activityLog->log(
                action: 'create',
                description: "Membuat transaksi penjualan {$penjualan->no_penjualan} untuk pelanggan {$pelanggan->nama} total Rp ".number_format((float) $totalAkhir, 0, ',', '.'),
                subject: $penjualan,
                newValues: [
                    'no_penjualan' => $penjualan->no_penjualan,
                    'pelanggan' => $pelanggan->nama,
                    'gudang' => $gudang->nama_gudang,
                    'tanggal' => $penjualan->tanggal->toDateString(),
                    'total' => (float) $totalAkhir,
                    'status_pembayaran' => $penjualan->status_pembayaran->value,
                    'total_items' => count($processedItems),
                ],
                userId: $userId
            );

            return $penjualan->load(['details.produk', 'details.gudang', 'pelanggan', 'gudang']);
        });
    }
}
