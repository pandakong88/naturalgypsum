<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateProdukAction
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{
     *     kategori_id: int|string,
     *     merek_id?: int|string|null,
     *     kode_katalog: string,
     *     nama: string,
     *     ukuran?: ?string,
     *     satuan: SatuanProduk|string,
     *     harga_beli?: float|int|string,
     *     harga_jual?: float|int|string,
     *     harga_aplikator?: float|int|string|null,
     *     harga_grosir?: float|int|string|null,
     *     stok_minimum?: float|int|string,
     *     panjang_per_roll?: float|int|string|null,
     *     panjang_meter?: float|int|string|null,
     *     kemasan?: ?string,
     *     isi_per_kemasan?: float|int|string|null,
     *     foto?: ?string,
     * }  $data
     */
    public function execute(Produk $produk, array $data): Produk
    {
        $kodeKatalog = trim($data['kode_katalog']);
        if (Produk::where('kode_katalog', $kodeKatalog)->where('id', '!=', $produk->id)->exists()) {
            throw ValidationException::withMessages([
                'kode_katalog' => "Kode katalog '{$kodeKatalog}' sudah digunakan oleh produk lain.",
            ]);
        }

        // Validate active category
        $kategori = Kategori::find($data['kategori_id']);
        if (! $kategori) {
            throw ValidationException::withMessages([
                'kategori_id' => 'Kategori yang dipilih tidak valid atau tidak aktif.',
            ]);
        }

        // Validate active brand if provided
        if (isset($data['merek_id']) && filled($data['merek_id'])) {
            $merek = Merek::find($data['merek_id']);
            if (! $merek) {
                throw ValidationException::withMessages([
                    'merek_id' => 'Merek yang dipilih tidak valid atau tidak aktif.',
                ]);
            }
        }

        $satuan = $data['satuan'] instanceof SatuanProduk
            ? $data['satuan']
            : SatuanProduk::tryFrom((string) $data['satuan']) ?? throw ValidationException::withMessages([
                'satuan' => 'Satuan produk tidak valid.',
            ]);

        $hargaBeli = array_key_exists('harga_beli', $data) && $data['harga_beli'] !== null
            ? (float) $data['harga_beli']
            : (float) $produk->harga_beli;
        $hargaJual = isset($data['harga_jual']) ? (float) $data['harga_jual'] : 0.00;
        $stokMin = isset($data['stok_minimum']) ? (float) $data['stok_minimum'] : 0.000;

        if ($hargaBeli < 0) {
            throw ValidationException::withMessages([
                'harga_beli' => 'Harga beli tidak boleh bernilai negatif.',
            ]);
        }

        if ($hargaJual < 0) {
            throw ValidationException::withMessages([
                'harga_jual' => 'Harga jual tidak boleh bernilai negatif.',
            ]);
        }

        $hargaAplikator = isset($data['harga_aplikator']) && filled($data['harga_aplikator'])
            ? (float) $data['harga_aplikator']
            : null;
        if ($hargaAplikator !== null && $hargaAplikator < 0) {
            throw ValidationException::withMessages([
                'harga_aplikator' => 'Harga aplikator tidak boleh bernilai negatif.',
            ]);
        }

        $hargaGrosir = isset($data['harga_grosir']) && filled($data['harga_grosir'])
            ? (float) $data['harga_grosir']
            : null;
        if ($hargaGrosir !== null && $hargaGrosir < 0) {
            throw ValidationException::withMessages([
                'harga_grosir' => 'Harga grosir tidak boleh bernilai negatif.',
            ]);
        }

        if ($stokMin < 0) {
            throw ValidationException::withMessages([
                'stok_minimum' => 'Stok minimum tidak boleh bernilai negatif.',
            ]);
        }

        $panjangPerRoll = null;
        if ($satuan === SatuanProduk::Meter && isset($data['panjang_per_roll']) && filled($data['panjang_per_roll'])) {
            $panjang = (float) $data['panjang_per_roll'];
            if ($panjang <= 0) {
                throw ValidationException::withMessages([
                    'panjang_per_roll' => 'Panjang per roll harus bernilai lebih dari 0.',
                ]);
            }
            $panjangPerRoll = $panjang;
        }

        $panjangMeter = isset($data['panjang_meter']) && filled($data['panjang_meter']) ? (float) $data['panjang_meter'] : null;
        if ($panjangMeter !== null && $panjangMeter <= 0) {
            throw ValidationException::withMessages([
                'panjang_meter' => 'Panjang fisik lembar/batang harus bernilai lebih dari 0.',
            ]);
        }

        $isiPerKemasan = isset($data['isi_per_kemasan']) && filled($data['isi_per_kemasan']) ? (float) $data['isi_per_kemasan'] : null;
        if ($isiPerKemasan !== null && $isiPerKemasan <= 0) {
            throw ValidationException::withMessages([
                'isi_per_kemasan' => 'Isi per kemasan harus bernilai lebih dari 0.',
            ]);
        }

        $kemasan = isset($data['kemasan']) && filled($data['kemasan']) ? trim((string) $data['kemasan']) : null;

        $fieldsToTrack = [
            'kode_katalog',
            'nama',
            'kategori_id',
            'merek_id',
            'ukuran',
            'satuan',
            'harga_beli',
            'harga_jual',
            'harga_aplikator',
            'harga_grosir',
            'stok_minimum',
            'panjang_per_roll',
            'panjang_meter',
            'kemasan',
            'isi_per_kemasan',
        ];

        $oldValues = $produk->only($fieldsToTrack);

        $produk->update([
            'kategori_id' => $kategori->id,
            'merek_id' => isset($data['merek_id']) && filled($data['merek_id']) ? (int) $data['merek_id'] : null,
            'kode_katalog' => $kodeKatalog,
            'nama' => trim($data['nama']),
            'ukuran' => isset($data['ukuran']) && filled($data['ukuran']) ? trim($data['ukuran']) : null,
            'satuan' => $satuan,
            'harga_beli' => $hargaBeli,
            'harga_jual' => $hargaJual,
            'harga_aplikator' => $hargaAplikator,
            'harga_grosir' => $hargaGrosir,
            'stok_minimum' => $stokMin,
            'panjang_meter' => $panjangMeter,
            'kemasan' => $kemasan,
            'isi_per_kemasan' => $isiPerKemasan,
            'panjang_per_roll' => $panjangPerRoll,
            'foto' => array_key_exists('foto', $data) ? $data['foto'] : $produk->foto,
            'updated_by' => Auth::id(),
        ]);

        $fresh = $produk->fresh();

        $this->activityLog->log(
            action: 'update',
            description: "Memperbarui produk '{$fresh->nama}' ({$fresh->kode_katalog})",
            subject: $fresh,
            oldValues: $oldValues,
            newValues: $fresh->only($fieldsToTrack),
        );

        return $fresh;
    }
}
