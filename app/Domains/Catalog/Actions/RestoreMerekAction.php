<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Merek;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RestoreMerekAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Merek $merek): bool
    {
        if (Merek::where('nama_merek', $merek->nama_merek)->where('id', '!=', $merek->id)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'restore' => "Merek '{$merek->nama_merek}' tidak dapat dipulihkan karena sudah ada merek aktif dengan nama yang sama.",
            ]);
        }

        $merek->update(['updated_by' => Auth::id()]);
        $restored = (bool) $merek->restore();

        if ($restored) {
            $this->activityLog->log(
                action: 'restore',
                description: "Memulihkan merek '{$merek->nama_merek}'",
                subject: $merek,
                newValues: $merek->only(['nama_merek']),
            );
        }

        return $restored;
    }
}
