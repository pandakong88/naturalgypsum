<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Sales\Models\Penjualan;
use App\Models\User;
use Database\Factories\PelangganFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama', 'kontak', 'tipe', 'limit_piutang', 'created_by', 'updated_by'])]
class Pelanggan extends Model
{
    /** @use HasFactory<PelangganFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'pelanggan';

    protected static function newFactory(): PelangganFactory
    {
        return PelangganFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe' => TipePelanggan::class,
            'limit_piutang' => 'decimal:2',
        ];
    }

    /**
     * Get the sales invoices for this customer.
     */
    public function penjualans(): HasMany
    {
        return $this->hasMany(Penjualan::class, 'pelanggan_id');
    }

    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class, 'pelanggan_id');
    }

    /**
     * Get the payments made by this customer.
     */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'pelanggan_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'pelanggan_id');
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to search by name or contact number (flexible phone search with/without dashes, spaces, dots).
     *
     * @param  Builder  $query
     */
    public function scopeSearch($query, ?string $term): mixed
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim((string) $term);
        $digits = preg_replace('/[^0-9]/', '', $term);
        $cleanSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(kontak, '-', ''), ' ', ''), '.', ''), '+', ''), '(', ''), ')', '')";

        return $query->where(function ($q) use ($term, $digits, $cleanSql) {
            $q->where('nama', 'like', "%{$term}%")
                ->orWhere('kontak', 'like', "%{$term}%");

            if (strlen($digits) >= 2) {
                $q->orWhereRaw("{$cleanSql} LIKE ?", ["%{$digits}%"]);

                if (str_starts_with($digits, '628')) {
                    $local = '08'.substr($digits, 3);
                    $q->orWhereRaw("{$cleanSql} LIKE ?", ["%{$local}%"]);
                } elseif (str_starts_with($digits, '08')) {
                    $intl = '628'.substr($digits, 2);
                    $q->orWhereRaw("{$cleanSql} LIKE ?", ["%{$intl}%"]);
                }
            }
        });
    }
}
