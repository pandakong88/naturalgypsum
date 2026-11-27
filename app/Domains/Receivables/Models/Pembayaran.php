<?php

namespace App\Domains\Receivables\Models;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Models\User;
use Database\Factories\PembayaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'pelanggan_id',
    'user_id',
    'no_pembayaran',
    'tanggal',
    'jumlah_bayar',
    'metode',
    'catatan',
    'created_by',
    'updated_by',
])]
class Pembayaran extends Model
{
    /** @use HasFactory<PembayaranFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'pembayaran';

    protected static function newFactory(): PembayaranFactory
    {
        return PembayaranFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah_bayar' => 'decimal:2',
            'metode' => MetodePembayaran::class,
        ];
    }

    /**
     * Get the customer for this payment.
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id')->withTrashed();
    }

    /**
     * Get the staff/user who received the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the payment allocations to invoices.
     */
    public function alokasi(): HasMany
    {
        return $this->hasMany(AlokasiPembayaran::class, 'pembayaran_id');
    }

    /**
     * Alias for alokasi relationship.
     */
    public function alokasiPembayaran(): HasMany
    {
        return $this->hasMany(AlokasiPembayaran::class, 'pembayaran_id');
    }

    /**
     * Get total allocated amount across invoices.
     */
    public function getTotalDialokasikanAttribute(): float
    {
        if (array_key_exists('alokasi_sum_jumlah_dialokasikan', $this->attributes)) {
            return (float) ($this->attributes['alokasi_sum_jumlah_dialokasikan'] ?? 0);
        }

        if ($this->relationLoaded('alokasi')) {
            return (float) $this->alokasi->sum('jumlah_dialokasikan');
        }

        return (float) $this->alokasi()->sum('jumlah_dialokasikan');
    }

    /**
     * Determine whether this payment was automatically created from direct POS checkout.
     */
    public function getIsKasirLangsungAttribute(): bool
    {
        return str_starts_with((string) $this->catatan, 'Pembayaran kasir langsung untuk faktur');
    }

    /**
     * Get remaining unallocated amount.
     */
    public function getSisaBelumDialokasikanAttribute(): float
    {
        $allocated = (float) $this->total_dialokasikan;
        $total = (float) $this->jumlah_bayar;

        return max(0.00, round($total - $allocated, 2));
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
}
