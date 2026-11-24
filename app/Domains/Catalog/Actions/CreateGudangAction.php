<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateGudangAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama_gudang: string, lokasi?: ?string, kontak?: ?string}  $data
     */
    public function execute(array $data): Gudang
    {
        $nama = trim($data['nama_gudang']);

        if (Gudang::where('nama_gudang', $nama)->exists()) {
            throw ValidationException::withMessages([
                'nama_gudang' => "Gudang dengan nama '{$nama}' sudah ada.",
            ]);
        }

        $gudang = Gudang::create([
            'nama_gudang' => $nama,
            'lokasi' => isset($data['lokasi']) && filled($data['lokasi']) ? trim($data['lokasi']) : null,
            'kontak' => isset($data['kontak']) && filled($data['kontak']) ? trim($data['kontak']) : null,
            'created_by' => Auth::id(),
            'updated_by' => null,
        ]);

        $this->activityLog->log(
            action: 'create',
            description: "Menambahkan gudang '{$gudang->nama_gudang}'",
            subject: $gudang,
            newValues: $gudang->only(['nama_gudang', 'lokasi', 'kontak']),
        );

        return $gudang;
    }
}
