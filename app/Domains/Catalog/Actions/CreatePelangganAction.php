<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatePelangganAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{nama: string, kontak?: ?string, tipe: TipePelanggan|string, limit_piutang?: float|int|string}  $data
     */
    public function execute(array $data): Pelanggan
    {
        $limit = isset($data['limit_piutang']) ? (float) $data['limit_piutang'] : 0.00;

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

        if (Pelanggan::whereRaw('LOWER(nama) = ?', [strtolower($nama)])->exists()) {
            throw ValidationException::withMessages([
                'nama' => 'Nama pelanggan ini sudah terdaftar di sistem.',
            ]);
        }

        if ($kontak !== null && Pelanggan::where('kontak', $kontak)->exists()) {
            throw ValidationException::withMessages([
                'kontak' => 'Nomor telepon/kontak ini sudah digunakan oleh pelanggan lain.',
            ]);
        }

        $pelanggan = Pelanggan::create([
            'nama' => $nama,
            'kontak' => $kontak,
            'tipe' => $tipe,
            'limit_piutang' => $limit,
            'created_by' => Auth::id(),
            'updated_by' => null,
        ]);

        $this->activityLog->log(
            action: 'create',
            description: "Menambahkan pelanggan '{$pelanggan->nama}' (Tipe: {$pelanggan->tipe->label()}, Limit Piutang: Rp ".number_format((float) $pelanggan->limit_piutang, 0, ',', '.').')',
            subject: $pelanggan,
            newValues: [
                'nama' => $pelanggan->nama,
                'kontak' => $pelanggan->kontak,
                'tipe' => $pelanggan->tipe->value,
                'limit_piutang' => (float) $pelanggan->limit_piutang,
            ],
        );

        return $pelanggan;
    }
}
