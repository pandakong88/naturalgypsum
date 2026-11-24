<?php

namespace App\Domains\Returns\Actions;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Returns\Models\ReturPelanggan;
use App\Domains\Sales\Models\Penjualan;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateReturPelangganAction
{
    public function __construct(
        protected StockService $stockService,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Execute return from customer.
     *
     * @param  array{
     *     pelanggan_id: int,
     *     produk_id: int,
     *     gudang_id: int,
     *     tanggal: string|Carbon,
     *     jumlah: float|int|string,
     *     harga_retur?: float|int|string|null,
     *     penjualan_id?: int|null,
     *     alasan?: string|null,
     *     user_id?: int|null
     * }  $data
     */
    public function execute(array $data): ReturPelanggan
    {
        $pelangganId = (int) ($data['pelanggan_id'] ?? 0);
        $produkId = (int) ($data['produk_id'] ?? 0);
        $gudangId = (int) ($data['gudang_id'] ?? 0);
        $qty = (float) ($data['jumlah'] ?? 0);
        $hargaRetur = isset($data['harga_retur']) ? (float) $data['harga_retur'] : 0.0;

        if ($pelangganId <= 0) {
            throw new InvalidArgumentException('Pelanggan wajib dipilih.');
        }
        if ($produkId <= 0) {
            throw new InvalidArgumentException('Produk yang diretur wajib dipilih.');
        }
        if ($gudangId <= 0) {
            throw new InvalidArgumentException('Gudang tujuan retur wajib dipilih.');
        }
        if ($qty <= 0) {
            throw new InvalidArgumentException('Jumlah retur harus lebih besar dari nol.');
        }
        if ($hargaRetur < 0) {
            throw new InvalidArgumentException('Harga retur tidak boleh negatif.');
        }

        $pelanggan = Pelanggan::whereNull('deleted_at')->findOrFail($pelangganId);
        $produk = Produk::whereNull('deleted_at')->findOrFail($produkId);
        $gudang = Gudang::whereNull('deleted_at')->findOrFail($gudangId);

        $penjualanId = ! empty($data['penjualan_id']) ? (int) $data['penjualan_id'] : null;
        if ($penjualanId) {
            Penjualan::whereNull('deleted_at')->findOrFail($penjualanId);
        }

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $userId = $data['user_id'] ?? Auth::id();
        $alasan = isset($data['alasan']) ? trim($data['alasan']) : null;

        return DB::transaction(function () use (
            $pelanggan,
            $produk,
            $gudang,
            $qty,
            $hargaRetur,
            $penjualanId,
            $tanggal,
            $alasan,
            $userId
        ) {
            $noRetur = $this->documentNumberService->generate('RP', (int) $tanggal->format('Y'));

            /** @var ReturPelanggan $retur */
            $retur = ReturPelanggan::create([
                'no_retur' => $noRetur,
                'tanggal' => $tanggal->toDateString(),
                'pelanggan_id' => $pelanggan->id,
                'produk_id' => $produk->id,
                'gudang_id' => $gudang->id,
                'user_id' => $userId,
                'penjualan_id' => $penjualanId,
                'jumlah' => $qty,
                'harga_retur' => $hargaRetur,
                'alasan' => $alasan ?: null,
                'created_by' => $userId,
            ]);

            // Increase stock in destination warehouse
            $this->stockService->increase(
                produk: $produk,
                gudang: $gudang,
                qty: $qty,
                jenis: JenisMutasiStok::ReturDariPelanggan,
                referensi: $retur->no_retur,
                referensiType: 'retur_pelanggan',
                referensiId: $retur->id,
                userId: $userId,
                tanggal: $tanggal
            );

            return $retur->load(['pelanggan', 'produk', 'gudang', 'penjualan', 'user']);
        });
    }
}
