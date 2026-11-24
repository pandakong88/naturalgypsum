<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;

class RestorePelangganAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Pelanggan $pelanggan): bool
    {
        $pelanggan->update(['updated_by' => Auth::id()]);
        $restored = (bool) $pelanggan->restore();

        if ($restored) {
            $this->activityLog->log(
                action: 'restore',
                description: "Memulihkan pelanggan '{$pelanggan->nama}'",
                subject: $pelanggan,
                newValues: [
                    'nama' => $pelanggan->nama,
                    'kontak' => $pelanggan->kontak,
                    'tipe' => $pelanggan->tipe->value,
                    'limit_piutang' => (float) $pelanggan->limit_piutang,
                ],
            );
        }

        return $restored;
    }
}
