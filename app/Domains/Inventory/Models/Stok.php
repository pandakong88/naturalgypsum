<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\StatusStok;
use Database\Factories\StokFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['produk_id', 'gudang_id', 'jumlah'])]
class Stok extends Model
{
    /** @use HasFactory<StokFactory> */
    use HasFactory;

    protected $table = 'stok';

    protected static function newFactory(): StokFactory
    {
        return StokFactory::new();
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

    /**
     * Get the product that this stock record belongs to.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Get the warehouse that this stock record belongs to.
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    /**
     * Determine the status of this stock record based on product minimum threshold.
     */
    public function getStatusAttribute(): StatusStok
    {
        $min = $this->produk ? (float) $this->produk->stok_minimum : 0.0;

        return StatusStok::determine((float) $this->jumlah, $min);
    }
}
