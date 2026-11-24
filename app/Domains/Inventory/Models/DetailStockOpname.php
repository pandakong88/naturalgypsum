<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Produk;
use Database\Factories\DetailStockOpnameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailStockOpname extends Model
{
    /** @use HasFactory<DetailStockOpnameFactory> */
    use HasFactory;

    protected $table = 'detail_stock_opnames';

    protected $fillable = [
        'stock_opname_id',
        'produk_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'catatan',
    ];

    protected static function newFactory(): DetailStockOpnameFactory
    {
        return DetailStockOpnameFactory::new();
    }

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'decimal:3',
            'stok_fisik' => 'decimal:3',
            'selisih' => 'decimal:3',
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
