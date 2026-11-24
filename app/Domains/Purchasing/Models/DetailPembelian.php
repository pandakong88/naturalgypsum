<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use Database\Factories\DetailPembelianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pembelian_id',
    'produk_id',
    'gudang_id',
    'qty_lembar',
    'panjang_meter',
    'jumlah',
    'harga_bruto',
    'diskon_satuan',
    'harga_beli_satuan',
    'subtotal',
])]
class DetailPembelian extends Model
{
    /** @use HasFactory<DetailPembelianFactory> */
    use HasFactory;

    protected $table = 'detail_pembelian';

    protected static function newFactory(): DetailPembelianFactory
    {
        return DetailPembelianFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_lembar' => 'decimal:3',
            'panjang_meter' => 'decimal:2',
            'jumlah' => 'decimal:3',
            'harga_bruto' => 'decimal:2',
            'diskon_satuan' => 'decimal:2',
            'harga_beli_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Get the parent purchase transaction.
     */
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    /**
     * Get the product for this line item.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Get the warehouse where this item is stored.
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
