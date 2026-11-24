<?php

namespace App\Domains\Sales\Enums;

enum StatusPembayaran: string
{
    case BelumBayar = 'belum_bayar';
    case Sebagian = 'sebagian';
    case Lunas = 'lunas';

    public function label(): string
    {
        return match ($this) {
            self::BelumBayar => 'Belum Bayar',
            self::Sebagian => 'Sebagian',
            self::Lunas => 'Lunas',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BelumBayar => 'bg-danger-subtle text-danger border border-danger-subtle',
            self::Sebagian => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::Lunas => 'bg-success-subtle text-success border border-success-subtle',
        };
    }
}
