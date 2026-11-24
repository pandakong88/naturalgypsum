<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeleteGudangAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Gudang $gudang): bool
    {
        // 1. Prevent deleting if it is the only active warehouse in the system
        if (Gudang::whereNull('deleted_at')->count() <= 1) {
            throw ValidationException::withMessages([
                'delete' => "Gudang '{$gudang->nama_gudang}' tidak dapat dinonaktifkan karena merupakan satu-satunya gudang aktif yang tersisa.",
            ]);
        }

        // 2. Prevent deleting if warehouse still holds physical stock
        $activeStockCount = $gudang->stoks()->where('jumlah', '>', 0)->count();
        if ($activeStockCount > 0) {
            $totalQty = (float) $gudang->stoks()->where('jumlah', '>', 0)->sum('jumlah');
            $formattedQty = number_format($totalQty, 0, ',', '.');
            throw ValidationException::withMessages([
                'delete' => "Gudang '{$gudang->nama_gudang}' tidak dapat dinonaktifkan karena masih menyimpan {$activeStockCount} item produk (total {$formattedQty} unit fisik). Silakan kosongkan atau transfer stok ke gudang lain terlebih dahulu.",
            ]);
        }

        $oldValues = $gudang->only(['nama_gudang', 'lokasi', 'kontak']);

        $gudang->update(['updated_by' => Auth::id()]);
        $deleted = (bool) $gudang->delete();

        if ($deleted) {
            $this->activityLog->log(
                action: 'delete',
                description: "Menonaktifkan gudang '{$gudang->nama_gudang}'",
                subject: $gudang,
                oldValues: $oldValues,
            );
        }

        return $deleted;
    }
}
