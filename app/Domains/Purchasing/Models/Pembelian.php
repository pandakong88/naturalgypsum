<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Enums\StatusPembayaranPembelian;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Models\User;
use Database\Factories\PembelianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'supplier_id',
    'user_id',
    'no_pembelian',
    'no_faktur_supplier',
    'no_surat_jalan',
    'no_kontainer',
    'no_polisi',
    'tanggal',
    'total',
    'status',
    'status_pembayaran',
    'tanggal_jatuh_tempo',
    'nominal_bayar',
    'sisa_hutang',
    'tanggal_diterima',
    'diterima_by',
    'keterangan',
    'created_by',
    'updated_by',
])]
class Pembelian extends Model
{
    /** @use HasFactory<PembelianFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'pembelian';

    protected static function newFactory(): PembelianFactory
    {
        return PembelianFactory::new();
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
            'tanggal_jatuh_tempo' => 'date',
            'tanggal_diterima' => 'date',
            'total' => 'decimal:2',
            'nominal_bayar' => 'decimal:2',
            'sisa_hutang' => 'decimal:2',
            'status' => StatusPembelian::class,
            'status_pembayaran' => StatusPembayaranPembelian::class,
        ];
    }

    /**
     * Get the supplier for this purchase.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the user assigned to this purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the purchase line items.
     */
    public function details(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'pembelian_id');
    }

    /**
     * Alias for details relationship.
     */
    public function detailPembelian(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'pembelian_id');
    }

    /**
     * Get payment history for this purchase.
     */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(PembayaranPembelian::class, 'pembelian_id');
    }

    /**
     * Get the user who received the goods at warehouse.
     */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_by');
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

    public function isReceived(): bool
    {
        return $this->status === StatusPembelian::Received;
    }

    public function isOrdered(): bool
    {
        return $this->status === StatusPembelian::Ordered;
    }

    public function isTempo(): bool
    {
        return $this->status_pembayaran === StatusPembayaranPembelian::Tempo || $this->status_pembayaran === StatusPembayaranPembelian::Sebagian;
    }

    public function isLunas(): bool
    {
        return $this->status_pembayaran === StatusPembayaranPembelian::Lunas;
    }

    public function isCancelled(): bool
    {
        return $this->status === StatusPembelian::Cancelled;
    }
}
