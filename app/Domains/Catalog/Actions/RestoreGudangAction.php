<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Gudang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RestoreGudangAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Gudang $gudang): bool
    {
        if (Gudang::where('nama_gudang', $gudang->nama_gudang)->where('id', '!=', $gudang->id)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'restore' => "Gudang '{$gudang->nama_gudang}' tidak dapat dipulihkan karena sudah ada gudang aktif dengan nama yang sama.",
            ]);
        }

        $gudang->update(['updated_by' => Auth::id()]);
        $restored = (bool) $gudang->restore();

        if ($restored) {
            $this->activityLog->log(
                action: 'restore',
                description: "Memulihkan gudang '{$gudang->nama_gudang}'",
                subject: $gudang,
                newValues: $gudang->only(['nama_gudang', 'lokasi', 'kontak']),
            );
        }

        return $restored;
    }
}
