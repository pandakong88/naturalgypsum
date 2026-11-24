<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Merek;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateMerekAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama_merek: string}  $data
     */
    public function execute(array $data): Merek
    {
        $nama = trim($data['nama_merek']);

        if (Merek::where('nama_merek', $nama)->exists()) {
            throw ValidationException::withMessages([
                'nama_merek' => "Merek dengan nama '{$nama}' sudah ada.",
            ]);
        }

        $merek = Merek::create([
            'nama_merek' => $nama,
            'created_by' => Auth::id(),
            'updated_by' => null,
        ]);

        $this->activityLog->log(
            action: 'create',
            description: "Menambahkan merek '{$merek->nama_merek}'",
            subject: $merek,
            newValues: $merek->only(['nama_merek']),
        );

        return $merek;
    }
}
