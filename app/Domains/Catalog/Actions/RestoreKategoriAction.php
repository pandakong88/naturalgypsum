<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Kategori;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RestoreKategoriAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Kategori $kategori): bool
    {
        // If it is a sub-category, ensure its parent category is currently active
        if ($kategori->parent_id) {
            $parent = Kategori::withTrashed()->find($kategori->parent_id);
            if (! $parent) {
                // If parent does not exist at all, convert to root category
                $kategori->parent_id = null;
            } elseif ($parent->trashed()) {
                throw ValidationException::withMessages([
                    'restore' => "Sub-kategori '{$kategori->nama_kategori}' tidak dapat dipulihkan karena kategori induknya '{$parent->nama_kategori}' masih berstatus nonaktif. Silakan pulihkan kategori induk terlebih dahulu.",
                ]);
            }
        }

        // Ensure that restoring won't clash with an existing active record of the same name at this level
        $query = Kategori::where('nama_kategori', $kategori->nama_kategori)
            ->where('id', '!=', $kategori->id);

        if ($kategori->parent_id) {
            $query->where('parent_id', $kategori->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'restore' => "Kategori '{$kategori->nama_kategori}' tidak dapat dipulihkan karena sudah ada kategori aktif dengan nama yang sama pada tingkatan ini.",
            ]);
        }

        $kategori->update(['updated_by' => Auth::id()]);
        $restored = (bool) $kategori->restore();

        if ($restored) {
            $this->activityLog->log(
                action: 'restore',
                description: "Memulihkan kategori '{$kategori->nama_kategori}'",
                subject: $kategori,
                newValues: $kategori->only(['nama_kategori', 'parent_id']),
            );
        }

        return $restored;
    }
}
