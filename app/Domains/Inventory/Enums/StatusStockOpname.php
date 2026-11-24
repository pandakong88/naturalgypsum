<?php

namespace App\Domains\Inventory\Enums;

enum StatusStockOpname: string
{
    case Draft = 'draft';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Selesai => 'success',
            self::Dibatalkan => 'danger',
        };
    }
}
