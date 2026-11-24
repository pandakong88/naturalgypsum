<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdatePelangganAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama: string, kontak?: ?string, tipe: TipePelanggan|string, limit_piutang?: float|int|string}  $data
     */
    public function execute(Pelanggan $pelanggan, array $data): Pelanggan
    {
        $limit = isset($data['limit_piutang']) ? (float) $data['limit_piutang'] : (float) $pelanggan->limit_piutang;

        if ($limit < 0) {
            throw ValidationException::withMessages([
                'limit_piutang' => 'Limit piutang tidak boleh bernilai negatif.',
            ]);
        }

        $tipe = $data['tipe'] instanceof TipePelanggan
            ? $data['tipe']
            : TipePelanggan::tryFrom((string) $data['tipe']) ?? throw ValidationException::withMessages([
                'tipe' => 'Tipe pelanggan tidak valid.',
            ]);

        $nama = trim($data['nama']);
        $kontak = isset($data['kontak']) && filled($data['kontak']) ? trim($data['kontak']) : null;

        if (Pelanggan::whereRaw('LOWER(nama) = ?', [strtolower($nama)])->where('id', '!=', $pelanggan->id)->exists()) {
            throw ValidationException::withMessages([
                'nama' => 'Nama pelanggan ini sudah terdaftar di sistem.',
            ]);
        }

        if ($kontak !== null && Pelanggan::where('kontak', $kontak)->where('id', '!=', $pelanggan->id)->exists()) {
            throw ValidationException::withMessages([
                'kontak' => 'Nomor telepon/kontak ini sudah digunakan oleh pelanggan lain.',
            ]);
        }

        $oldValues = [
            'nama' => $pelanggan->nama,
            'kontak' => $pelanggan->kontak,
            'tipe' => $pelanggan->tipe->value,
            'limit_piutang' => (float) $pelanggan->limit_piutang,
        ];

        $pelanggan->update([
            'nama' => $nama,
            'kontak' => $kontak,
            'tipe' => $tipe,
            'limit_piutang' => $limit,
            'updated_by' => Auth::id(),
        ]);

        $fresh = $pelanggan->fresh();

        $this->activityLog->log(
            action: 'update',
            description: "Memperbarui pelanggan '{$fresh->nama}' (Tipe: {$fresh->tipe->label()}, Limit Piutang: Rp ".number_format((float) $fresh->limit_piutang, 0, ',', '.').')',
            subject: $fresh,
            oldValues: $oldValues,
            newValues: [
                'nama' => $fresh->nama,
                'kontak' => $fresh->kontak,
                'tipe' => $fresh->tipe->value,
                'limit_piutang' => (float) $fresh->limit_piutang,
            ],
        );

        return $fresh;
    }
}
