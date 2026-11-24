<?php

namespace App\Domains\Purchasing\Enums;

enum StatusPembelian: string
{
    case Ordered = 'ordered';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Ordered => 'Dipesan (PO)',
            self::Received => 'Diterima di Gudang',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Ordered => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::Received => 'bg-success-subtle text-success border border-success-subtle',
            self::Cancelled => 'bg-danger-subtle text-danger border border-danger-subtle',
        };
    }
}
