<?php

namespace App\Domains\Sales\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Sales\Enums\JenisDiskon;
use Database\Factories\DetailPenjualanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'penjualan_id',
    'produk_id',
    'gudang_id',
    'harga_satuan',
    'harga_normal',
    'jenis_diskon',
    'nilai_diskon',
    'jumlah',
    'subtotal',
])]
class DetailPenjualan extends Model
{
    /** @use HasFactory<DetailPenjualanFactory> */
    use HasFactory;

    protected $table = 'detail_penjualan';

    protected static function newFactory(): DetailPenjualanFactory
    {
        return DetailPenjualanFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'harga_normal' => 'decimal:2',
            'jenis_diskon' => JenisDiskon::class,
            'nilai_diskon' => 'decimal:2',
            'jumlah' => 'decimal:3',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Determine whether the sold unit price differs from the system reference price.
     */
    public function isHargaDiubah(): bool
    {
        if ($this->harga_normal === null) {
            return false;
        }

        return abs((float) $this->harga_satuan - (float) $this->harga_normal) > 0.001;
    }

    /**
     * Get the parent sale.
     */
    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    /**
     * Get the warehouse for this line item (falls back to sale warehouse if null).
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id')->withTrashed();
    }

    /**
     * Get the product for this line item.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id')->withTrashed();
    }

    /**
     * Get the physical packaging breakdown description for invoice & delivery note printing.
     */
    public function getDeskripsiKemasan(): ?string
    {
        if (! $this->produk) {
            return null;
        }

        $konv = $this->produk->getKonversiFisik((float) $this->jumlah);

        return $konv['has_conversion'] ? $konv['badge_text'] : null;
    }
}
