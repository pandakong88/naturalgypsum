<?php

namespace App\Domains\Purchasing\Enums;

enum StatusPembayaranPembelian: string
{
    case Lunas = 'lunas';
    case Tempo = 'tempo';
    case Sebagian = 'sebagian';

    public function label(): string
    {
        return match ($this) {
            self::Lunas => 'Lunas',
            self::Tempo => 'Tempo (Hutang)',
            self::Sebagian => 'Sebagian (DP)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Lunas => 'bg-success-subtle text-success border border-success-subtle',
            self::Tempo => 'bg-danger-subtle text-danger border border-danger-subtle',
            self::Sebagian => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        };
    }
}
