<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Produk;
use Illuminate\Support\Facades\Auth;

class DeleteProdukAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Produk $produk): bool
    {
        $oldValues = $produk->only(['kode_katalog', 'nama', 'kategori_id', 'merek_id', 'harga_jual', 'harga_beli']);

        $produk->update(['updated_by' => Auth::id()]);
        $deleted = (bool) $produk->delete();

        if ($deleted) {
            $this->activityLog->log(
                action: 'delete',
                description: "Menonaktifkan produk '{$produk->nama}' ({$produk->kode_katalog})",
                subject: $produk,
                oldValues: $oldValues,
            );
        }

        return $deleted;
    }
}
