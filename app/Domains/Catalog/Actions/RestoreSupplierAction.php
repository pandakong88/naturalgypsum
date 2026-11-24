<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RestoreSupplierAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Supplier $supplier): bool
    {
        if (Supplier::where('nama_supplier', $supplier->nama_supplier)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'restore' => "Tidak dapat memulihkan: sudah ada supplier aktif dengan nama yang sama '{$supplier->nama_supplier}'.",
            ]);
        }

        $supplier->update(['updated_by' => Auth::id()]);
        $restored = (bool) $supplier->restore();

        if ($restored) {
            $this->activityLog->log(
                action: 'restore',
                description: "Memulihkan supplier '{$supplier->nama_supplier}'",
                subject: $supplier,
                newValues: $supplier->only(['nama_supplier', 'kontak', 'alamat', 'email', 'nama_bank', 'no_rekening', 'atas_nama_rekening']),
            );
        }

        return $restored;
    }
}
