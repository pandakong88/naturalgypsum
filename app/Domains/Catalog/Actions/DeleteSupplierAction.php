<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeleteSupplierAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Supplier $supplier): bool
    {
        // 1. Prevent deleting if supplier has purchase transactions
        $pembelianCount = $supplier->pembelians()->count();
        if ($pembelianCount > 0) {
            throw ValidationException::withMessages([
                'delete' => "Supplier '{$supplier->nama_supplier}' tidak dapat dinonaktifkan karena masih memiliki {$pembelianCount} data riwayat pembelian.",
            ]);
        }

        // 2. Prevent deleting if supplier has returns
        $returCount = $supplier->returs()->count();
        if ($returCount > 0) {
            throw ValidationException::withMessages([
                'delete' => "Supplier '{$supplier->nama_supplier}' tidak dapat dinonaktifkan karena masih memiliki {$returCount} data retur pembelian.",
            ]);
        }

        $oldValues = $supplier->only(['nama_supplier', 'kontak', 'alamat', 'email', 'nama_bank', 'no_rekening', 'atas_nama_rekening']);

        $supplier->update(['updated_by' => Auth::id()]);
        $deleted = (bool) $supplier->delete();

        if ($deleted) {
            $this->activityLog->log(
                action: 'delete',
                description: "Menonaktifkan supplier '{$supplier->nama_supplier}'",
                subject: $supplier,
                oldValues: $oldValues,
            );
        }

        return $deleted;
    }
}
