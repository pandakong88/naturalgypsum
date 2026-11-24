<?php

namespace App\Domains\Receivables\Enums;

enum MetodePembayaran: string
{
    case Tunai = 'tunai';
    case Transfer = 'transfer';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai (Cash)',
            self::Transfer => 'Transfer Bank',
            self::Lainnya => 'Lainnya',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Tunai => 'bg-success-subtle text-success border border-success-subtle',
            self::Transfer => 'bg-primary-subtle text-primary border border-primary-subtle',
            self::Lainnya => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        };
    }
}
