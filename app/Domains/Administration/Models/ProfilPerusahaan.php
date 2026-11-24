<?php

namespace App\Domains\Administration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
     * Make an unsaved default instance with fallback values.
     */
    public static function makeDefault(): self
    {
        $instance = new self;
        $instance->nama_toko = 'NATURAL GYPSUM & PVC';
        $instance->slogan = 'Distributor & Toko Material Plafon, Gypsum, Rangka Hollow & PVC';
        $instance->alamat = 'Jl. Raya Utama Toko Natural Gypsum';
        $instance->telepon = '0812-3456-7890';
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
