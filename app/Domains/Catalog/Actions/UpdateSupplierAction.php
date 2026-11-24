<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateSupplierAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama_supplier: string, kontak?: ?string, alamat?: ?string, email?: ?string, nama_bank?: ?string, no_rekening?: ?string, atas_nama_rekening?: ?string}  $data
     */
    public function execute(Supplier $supplier, array $data): Supplier
    {
        $nama = trim($data['nama_supplier']);

        if (Supplier::where('nama_supplier', $nama)->where('id', '!=', $supplier->id)->exists()) {
            throw ValidationException::withMessages([
                'nama_supplier' => "Supplier dengan nama '{$nama}' sudah ada.",
            ]);
        }

        $oldValues = $supplier->only(['nama_supplier', 'kontak', 'alamat', 'email', 'nama_bank', 'no_rekening', 'atas_nama_rekening']);

        $supplier->update([
            'nama_supplier' => $nama,
            'kontak' => isset($data['kontak']) && filled($data['kontak']) ? trim($data['kontak']) : null,
            'alamat' => isset($data['alamat']) && filled($data['alamat']) ? trim($data['alamat']) : null,
            'email' => isset($data['email']) && filled($data['email']) ? trim($data['email']) : null,
            'nama_bank' => isset($data['nama_bank']) && filled($data['nama_bank']) ? trim($data['nama_bank']) : null,
            'no_rekening' => isset($data['no_rekening']) && filled($data['no_rekening']) ? trim($data['no_rekening']) : null,
            'atas_nama_rekening' => isset($data['atas_nama_rekening']) && filled($data['atas_nama_rekening']) ? trim($data['atas_nama_rekening']) : null,
            'updated_by' => Auth::id(),
        ]);

        $this->activityLog->log(
            action: 'update',
            description: "Memperbarui supplier '{$supplier->nama_supplier}'",
            subject: $supplier,
            oldValues: $oldValues,
            newValues: $supplier->only(['nama_supplier', 'kontak', 'alamat', 'email', 'nama_bank', 'no_rekening', 'atas_nama_rekening']),
        );

        return $supplier->fresh();
    }
}
