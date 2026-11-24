<?php

namespace App\Domains\Production\Models;

use App\Domains\Catalog\Models\Produk;
use Database\Factories\DetailProduksiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'produksi_id',
    'produk_id',
    'jumlah',
])]
class DetailProduksi extends Model
{
    /** @use HasFactory<DetailProduksiFactory> */
    use HasFactory;

    protected $table = 'detail_produksi';

    protected static function newFactory(): DetailProduksiFactory
    {
        return DetailProduksiFactory::new();
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

    public function produksi(): BelongsTo
    {
        return $this->belongsTo(Produksi::class, 'produksi_id')->withTrashed();
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id')->withTrashed();
    }
}
