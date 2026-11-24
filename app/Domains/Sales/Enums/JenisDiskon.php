<?php

namespace App\Domains\Sales\Enums;

enum JenisDiskon: string
{
    case Nominal = 'nominal';
    case Persentase = 'persentase';

    public function label(): string
    {
        return match ($this) {
            self::Nominal => 'Nominal (Rp)',
            self::Persentase => 'Persentase (%)',
        };
    }
}
