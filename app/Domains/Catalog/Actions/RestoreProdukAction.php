<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Produk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RestoreProdukAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Produk $produk): bool
    {
        if (Produk::where('kode_katalog', $produk->kode_katalog)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'restore' => "Produk dengan kode katalog '{$produk->kode_katalog}' tidak dapat dipulihkan karena sudah ada produk aktif dengan kode yang sama.",
            ]);
        }

        $produk->update(['updated_by' => Auth::id()]);
        $restored = (bool) $produk->restore();

        if ($restored) {
            $this->activityLog->log(
                action: 'restore',
                description: "Memulihkan produk '{$produk->nama}' ({$produk->kode_katalog})",
                subject: $produk,
                newValues: $produk->only(['kode_katalog', 'nama', 'kategori_id', 'merek_id', 'harga_jual', 'harga_beli']),
            );
        }

        return $restored;
    }
}
