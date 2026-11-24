<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Kategori;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateKategoriAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama_kategori: string, parent_id?: ?int}  $data
     */
    public function execute(Kategori $kategori, array $data): Kategori
    {
        $nama = trim($data['nama_kategori']);
        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($parentId && $parentId === $kategori->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Kategori tidak dapat menjadi induk bagi dirinya sendiri.',
            ]);
        }

        if ($parentId && $kategori->children()->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Kategori yang sudah memiliki sub-kategori tidak dapat dijadikan sub-kategori.',
            ]);
        }

        if ($parentId) {
            $parent = Kategori::find($parentId);
            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Kategori induk yang dipilih tidak valid.',
                ]);
            }

            if ($parent->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Sub-kategori tidak dapat dijadikan sebagai kategori induk.',
                ]);
            }
        }

        $query = Kategori::where('nama_kategori', $nama)->where('id', '!=', $kategori->id);
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'nama_kategori' => "Kategori dengan nama '{$nama}' sudah digunakan oleh data lain.",
            ]);
        }

        $oldValues = $kategori->only(['nama_kategori', 'parent_id']);

        $kategori->update([
            'parent_id' => $parentId,
            'nama_kategori' => $nama,
            'updated_by' => Auth::id(),
        ]);

        $fresh = $kategori->fresh();
        $newValues = $fresh->only(['nama_kategori', 'parent_id']);

        $this->activityLog->log(
            action: 'update',
            description: "Memperbarui kategori '{$fresh->nama_kategori}'",
            subject: $fresh,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return $fresh;
    }
}
