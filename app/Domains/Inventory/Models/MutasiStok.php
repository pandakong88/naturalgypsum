<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Models\User;
use Database\Factories\MutasiStokFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'produk_id',
    'gudang_id',
    'user_id',
    'jenis',
    'jumlah',
    'referensi',
    'referensi_type',
    'referensi_id',
    'tanggal',
])]
class MutasiStok extends Model
{
    /** @use HasFactory<MutasiStokFactory> */
    use HasFactory;

    protected $table = 'mutasi_stok';

    protected static function newFactory(): MutasiStokFactory
    {
        return MutasiStokFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisMutasiStok::class,
            'jumlah' => 'decimal:3',
            'tanggal' => 'datetime',
        ];
    }

    /**
     * Get the product associated with this stock mutation.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id')->withTrashed();
    }

    /**
     * Get the warehouse associated with this stock mutation.
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id')->withTrashed();
    }

    /**
     * Get the user who triggered this stock mutation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
