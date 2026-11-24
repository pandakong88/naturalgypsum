<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;

class DeletePelangganAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Pelanggan $pelanggan): bool
    {
        $oldValues = [
            'nama' => $pelanggan->nama,
            'kontak' => $pelanggan->kontak,
            'tipe' => $pelanggan->tipe->value,
            'limit_piutang' => (float) $pelanggan->limit_piutang,
        ];

        $pelanggan->update(['updated_by' => Auth::id()]);
        $deleted = (bool) $pelanggan->delete();

        if ($deleted) {
            $this->activityLog->log(
                action: 'delete',
                description: "Menonaktifkan pelanggan '{$pelanggan->nama}'",
                subject: $pelanggan,
                oldValues: $oldValues,
            );
        }

        return $deleted;
    }
}
