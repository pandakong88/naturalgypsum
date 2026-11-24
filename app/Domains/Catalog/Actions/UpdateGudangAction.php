<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateGudangAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama_gudang: string, lokasi?: ?string, kontak?: ?string}  $data
     */
    public function execute(Gudang $gudang, array $data): Gudang
    {
        $nama = trim($data['nama_gudang']);

        if (Gudang::where('nama_gudang', $nama)->where('id', '!=', $gudang->id)->exists()) {
            throw ValidationException::withMessages([
                'nama_gudang' => "Gudang dengan nama '{$nama}' sudah digunakan oleh data lain.",
            ]);
        }

        $oldValues = $gudang->only(['nama_gudang', 'lokasi', 'kontak']);

        $gudang->update([
            'nama_gudang' => $nama,
            'lokasi' => isset($data['lokasi']) && filled($data['lokasi']) ? trim($data['lokasi']) : null,
            'kontak' => isset($data['kontak']) && filled($data['kontak']) ? trim($data['kontak']) : null,
            'updated_by' => Auth::id(),
        ]);

        $fresh = $gudang->fresh();
        $newValues = $fresh->only(['nama_gudang', 'lokasi', 'kontak']);

        $this->activityLog->log(
            action: 'update',
            description: "Memperbarui gudang '{$fresh->nama_gudang}'",
            subject: $fresh,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return $fresh;
    }
}
