<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Merek;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeleteMerekAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Merek $merek): bool
    {
        if ($merek->produks()->exists()) {
            throw ValidationException::withMessages([
                'delete' => "Merek '{$merek->nama_merek}' tidak dapat dinonaktifkan karena masih digunakan oleh produk aktif.",
            ]);
        }

        $oldValues = $merek->only(['nama_merek']);

        $merek->update(['updated_by' => Auth::id()]);
        $deleted = (bool) $merek->delete();

        if ($deleted) {
            $this->activityLog->log(
                action: 'delete',
                description: "Menonaktifkan merek '{$merek->nama_merek}'",
                subject: $merek,
                oldValues: $oldValues,
            );
        }

        return $deleted;
    }
}
