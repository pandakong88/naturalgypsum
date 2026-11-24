<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Merek;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateMerekAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama_merek: string}  $data
     */
    public function execute(Merek $merek, array $data): Merek
    {
        $nama = trim($data['nama_merek']);

        if (Merek::where('nama_merek', $nama)->where('id', '!=', $merek->id)->exists()) {
            throw ValidationException::withMessages([
                'nama_merek' => "Merek dengan nama '{$nama}' sudah digunakan oleh data lain.",
            ]);
        }

        $oldValues = $merek->only(['nama_merek']);

        $merek->update([
            'nama_merek' => $nama,
            'updated_by' => Auth::id(),
        ]);

        $fresh = $merek->fresh();
        $newValues = $fresh->only(['nama_merek']);

        $this->activityLog->log(
            action: 'update',
            description: "Memperbarui merek '{$fresh->nama_merek}'",
            subject: $fresh,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return $fresh;
    }
}
