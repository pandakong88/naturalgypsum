<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Produk;
use Database\Factories\DetailTransferGudangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transfer_gudang_id',
    'produk_id',
    'jumlah',
])]
class DetailTransferGudang extends Model
{
    /** @use HasFactory<DetailTransferGudangFactory> */
    use HasFactory;

    protected $table = 'detail_transfer_gudang';

    protected static function newFactory(): DetailTransferGudangFactory
    {
        return DetailTransferGudangFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:3',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(TransferGudang::class, 'transfer_gudang_id')->withTrashed();
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id')->withTrashed();
    }
}
