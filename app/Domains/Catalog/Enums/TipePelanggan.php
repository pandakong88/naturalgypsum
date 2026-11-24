<?php

namespace App\Domains\Catalog\Enums;

enum TipePelanggan: string
{
    case Umum = 'umum';
    case Pemborong = 'pemborong';
    case Langganan = 'langganan';

    /**
     * Get user-friendly display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Umum => 'Umum',
            self::Pemborong => 'Aplikator',
            self::Langganan => 'Toko',
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
