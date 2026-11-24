<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Models\User;
use Database\Factories\TransferGudangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'no_transfer',
    'tanggal',
    'gudang_asal_id',
    'gudang_tujuan_id',
    'user_id',
    'keterangan',
    'created_by',
    'updated_by',
])]
class TransferGudang extends Model
{
    /** @use HasFactory<TransferGudangFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'transfer_gudang';

    protected static function newFactory(): TransferGudangFactory
    {
        return TransferGudangFactory::new();
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
        ];
    }

    /**
     * Source warehouse.
     */
    public function gudangAsal(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id')->withTrashed();
    }

    /**
     * Destination warehouse.
     */
    public function gudangTujuan(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id')->withTrashed();
    }

    /**
     * Staff handling the transfer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Line item details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(DetailTransferGudang::class, 'transfer_gudang_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
