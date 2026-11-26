<?php

namespace App\Domains\Administration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProfilPerusahaan extends Model
{
    protected $table = 'profil_perusahaan';

    protected $guarded = ['id'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'rekening_bank' => 'array',
    ];

    protected static ?self $cachedInstance = null;

    protected static function booted(): void
    {
        static::saved(function () {
            static::$cachedInstance = null;
            try {
                Cache::forget('profil_perusahaan_settings');
            } catch (\Throwable) {
                // Ignore cache store failure
            }
        });

        static::deleted(function () {
            static::$cachedInstance = null;
            try {
                Cache::forget('profil_perusahaan_settings');
            } catch (\Throwable) {
                // Ignore cache store failure
            }
        });
    }

    /**
     * Get the active company profile settings.
     * Uses in-memory request-level cache and fallback safely.
     */
    public static function getSettings(): self
    {
        if (static::$cachedInstance instanceof self) {
            return static::$cachedInstance;
        }

        try {
            $record = self::first();
            if ($record) {
                $needsSave = false;

                if (filled($record->logo_path) && ! str_starts_with($record->logo_path, 'images/') && ! Storage::disk('public')->exists($record->logo_path)) {
                    $record->logo_path = null;
                    $needsSave = true;
                }

                if ($record->alamat === 'Jl. Raya Utama Toko Natural Gypsum') {
                    $record->slogan = 'Aplikator dan Penyedia Bahan Gypsum dan PVC';
                    $record->alamat = 'Jln. Piyungan Prambanan KM 2 Pucung';
                    $record->telepon = '082136702637';
                    $needsSave = true;
                }

                if ($needsSave) {
                    $record->save();
                }
            }
        } catch (\Throwable) {
            $record = null;
        }

        return static::$cachedInstance = ($record ?? self::makeDefault());
    }

    /**
     * Clear in-memory instance and any legacy cache.
     */
    public static function clearSettingsCache(): void
    {
        static::$cachedInstance = null;
        try {
            Cache::forget('profil_perusahaan_settings');
        } catch (\Throwable) {
            // Ignore
        }
    }

    /**
     * Get the active logo URL for document headers (Kop Faktur, Surat Jalan, Kuitansi, etc.).
     * Falls back to the default modern architectural 'N' monogram mark when no custom logo is uploaded.
     */
    public function getLogoUrl(): string
    {
        if (filled($this->logo_path)) {
            if (str_starts_with($this->logo_path, 'images/')) {
                return asset($this->logo_path);
            }

            if (Storage::disk('public')->exists($this->logo_path)) {
                return asset('storage/'.$this->logo_path);
            }
        }

        return asset('images/logo-natural-modern-kop.svg');
    }

    /**
     * Make an unsaved default instance with fallback values.
     */
    public static function makeDefault(): self
    {
        $instance = new self;
        $instance->nama_toko = 'NATURAL GYPSUM & PVC';
        $instance->slogan = 'Aplikator dan Penyedia Bahan Gypsum dan PVC';
        $instance->alamat = 'Jln. Piyungan Prambanan KM 2 Pucung';
        $instance->telepon = '082136702637';
        $instance->email = 'info@naturalgypsum.com';
        $instance->website = 'naturalgypsum.com';
        $instance->catatan_kaki_faktur = 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa perjanjian tertulis.';
        $instance->rekening_bank = [
            [
                'nama_bank' => 'BCA',
                'nomor_rekening' => '123-456-7890',
                'atas_nama' => 'Natural Gypsum',
            ],
            [
                'nama_bank' => 'MANDIRI',
                'nomor_rekening' => '987-654-3210',
                'atas_nama' => 'Natural Gypsum',
            ],
        ];

        return $instance;
    }
}
