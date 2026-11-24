<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Kategori;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeleteKategoriAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Kategori $kategori): bool
    {
        if ($kategori->children()->exists()) {
            throw ValidationException::withMessages([
                'delete' => "Kategori '{$kategori->nama_kategori}' tidak dapat dinonaktifkan karena masih memiliki sub-kategori aktif.",
            ]);
        }

        if ($kategori->produks()->exists()) {
            throw ValidationException::withMessages([
                'delete' => "Kategori '{$kategori->nama_kategori}' tidak dapat dinonaktifkan karena masih digunakan oleh produk aktif.",
            ]);
        }

        $oldValues = $kategori->only(['nama_kategori', 'parent_id']);

        $kategori->update(['updated_by' => Auth::id()]);
        $deleted = (bool) $kategori->delete();

        if ($deleted) {
            $this->activityLog->log(
                action: 'delete',
                description: "Menonaktifkan kategori '{$kategori->nama_kategori}'",
                subject: $kategori,
                oldValues: $oldValues,
            );
        }

        return $deleted;
    }
}
