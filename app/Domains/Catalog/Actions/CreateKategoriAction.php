<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Kategori;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateKategoriAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama_kategori: string, parent_id?: ?int}  $data
     */
    public function execute(array $data): Kategori
    {
        $nama = trim($data['nama_kategori']);
        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;

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

        $query = Kategori::where('nama_kategori', $nama);
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'nama_kategori' => "Kategori dengan nama '{$nama}' sudah ada.",
            ]);
        }

        $kategori = Kategori::create([
            'parent_id' => $parentId,
            'nama_kategori' => $nama,
            'created_by' => Auth::id(),
            'updated_by' => null,
        ]);

        $label = $parentId ? 'sub-kategori' : 'kategori';
        $this->activityLog->log(
            action: 'create',
            description: "Menambahkan {$label} '{$kategori->nama_kategori}'",
            subject: $kategori,
            newValues: $kategori->only(['nama_kategori', 'parent_id']),
        );

        return $kategori;
    }
}
