<?php

namespace App\Domains\Returns\Actions;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Returns\Models\ReturSupplier;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateReturSupplierAction
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Execute return to supplier.
     *
     * @param  array{
     *     supplier_id: int,
     *     produk_id: int,
     *     gudang_id: int,
     *     tanggal: string|Carbon,
     *     jumlah: float|int|string,
     *     pembelian_id?: int|null,
     *     alasan?: string|null,
     *     user_id?: int|null
     * }  $data
     */
    public function execute(array $data): ReturSupplier
    {
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $produkId = (int) ($data['produk_id'] ?? 0);
        $gudangId = (int) ($data['gudang_id'] ?? 0);
        $qty = (float) ($data['jumlah'] ?? 0);

        if ($supplierId <= 0) {
            throw new InvalidArgumentException('Supplier wajib dipilih.');
        }
        if ($produkId <= 0) {
            throw new InvalidArgumentException('Produk yang diretur wajib dipilih.');
        }
        if ($gudangId <= 0) {
            throw new InvalidArgumentException('Gudang asal retur wajib dipilih.');
        }
        if ($qty <= 0) {
            throw new InvalidArgumentException('Jumlah retur harus lebih besar dari nol.');
        }

        $supplier = Supplier::whereNull('deleted_at')->findOrFail($supplierId);
        $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);
        $gudang = Gudang::whereNull('deleted_at')->findOrFail($gudangId);

        $pembelianId = ! empty($data['pembelian_id']) ? (int) $data['pembelian_id'] : null;
        if ($pembelianId) {
            Pembelian::whereNull('deleted_at')->findOrFail($pembelianId);
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();
        $alasan = isset($data['alasan']) ? trim($data['alasan']) : null;

        return DB::transaction(function () use (
            $supplier,
            $produk,
            $gudang,
            $qty,
            $pembelianId,
            $tanggal,
            $alasan,
            $userId
        ) {
            $noRetur = $this->documentNumberService->generate('RS', (int) $tanggal->format('Y'));

            /** @var ReturSupplier $retur */
            $retur = ReturSupplier::create([
                'no_retur' => $noRetur,
                'tanggal' => $tanggal->toDateString(),
                'supplier_id' => $supplier->id,
                'produk_id' => $produk->id,
                'gudang_id' => $gudang->id,
                'user_id' => $userId,
                'pembelian_id' => $pembelianId,
                'jumlah' => $qty,
                'alasan' => $alasan ?: null,
                'created_by' => $userId,
            ]);

            // Deduct stock from warehouse
            $this->stockService->decrease(
                produk: $produk,
                gudang: $gudang,
                qty: $qty,
                jenis: JenisMutasiStok::ReturKeSupplier,
                referensi: $retur->no_retur,
                referensiType: 'retur_supplier',
                referensiId: $retur->id,
                userId: $userId,
                tanggal: $tanggal
            );

            return $retur->load(['supplier', 'produk', 'gudang', 'pembelian', 'user']);
        });
    }
}
