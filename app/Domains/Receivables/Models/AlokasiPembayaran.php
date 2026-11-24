<?php

namespace App\Domains\Receivables\Models;

use App\Domains\Sales\Models\Penjualan;
use Database\Factories\AlokasiPembayaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pembayaran_id',
    'penjualan_id',
    'jumlah_dialokasikan',
])]
class AlokasiPembayaran extends Model
{
    /** @use HasFactory<AlokasiPembayaranFactory> */
    use HasFactory;

    protected $table = 'alokasi_pembayaran';

    protected static function newFactory(): AlokasiPembayaranFactory
    {
        return AlokasiPembayaranFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_dialokasikan' => 'decimal:2',
        ];
    }

    /**
     * Get the parent payment.
     */
    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id')->withTrashed();
    }

    /**
     * Get the allocated sales invoice.
     */
    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id')->withTrashed();
    }
}
