<?php

namespace App\Domains\Catalog\Enums;

enum SatuanProduk: string
{
    case Meter = 'meter';
    case Lembar = 'lembar';
    case Batang = 'batang';
    case Zak = 'zak';
    case Dus = 'dus';
    case Pcs = 'pcs';

    /**
     * Get user-friendly display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Meter => 'Meter (m)',
            self::Lembar => 'Lembar (lbr)',
            self::Batang => 'Batang (btg)',
            self::Zak => 'Zak (zak)',
            self::Dus => 'Dus (dus/ktk)',
            self::Pcs => 'Pcs (buah)',
        };
    }

    /**
     * Get typical usage description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Meter => 'PVC Vinyl (pembelian roll, penjualan meter)',
            self::Lembar => 'Papan Plafon / Ceiling Board',
            self::Batang => 'Moulding / List Profil / Rangka',
            self::Zak => 'Kompon / Semen Gypsum / Casting Plaster',
            self::Dus => 'Sekrup / Paku Gypsum / Fischer',
            self::Pcs => 'Bracket / Klip Rangka / Aksesoris',
        };
    }

    /**
     * Get all values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
