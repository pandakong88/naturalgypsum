<?php

namespace App\Domains\Inventory\Enums;

enum StatusStok: string
{
    case Aman = 'aman';
    case Menipis = 'menipis';
    case Habis = 'habis';

    /**
     * Get user-friendly display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Aman => 'Aman',
            self::Menipis => 'Menipis',
            self::Habis => 'Habis',
        };
    }

    /**
     * Get Bootstrap badge CSS class for display.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Aman => 'bg-success-subtle text-success border border-success-subtle',
            self::Menipis => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::Habis => 'bg-danger-subtle text-danger border border-danger-subtle',
        };
    }

    /**
     * Determine stock status from current quantity and minimum stock threshold.
     */
    public static function determine(float|int|string $jumlah, float|int|string $stokMinimum): self
    {
        $qty = (float) $jumlah;
        $min = (float) $stokMinimum;

        if ($qty <= 0) {
            return self::Habis;
        }

        if ($qty <= $min) {
            return self::Menipis;
        }

        return self::Aman;
    }
}
