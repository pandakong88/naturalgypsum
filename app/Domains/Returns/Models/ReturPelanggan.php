<?php

namespace App\Domains\Returns\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Sales\Models\Penjualan;
use App\Models\User;
use Database\Factories\ReturPelangganFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'no_retur',
    'tanggal',
    'pelanggan_id',
    'penjualan_id',
    'produk_id',
    'gudang_id',
    'user_id',
    'jumlah',
    'harga_retur',
    'alasan',
    'created_by',
    'updated_by',
])]
class ReturPelanggan extends Model
{
    /** @use HasFactory<ReturPelangganFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'retur_pelanggan';

    protected static function newFactory(): ReturPelangganFactory
    {
        return ReturPelangganFactory::new();
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
            'jumlah' => 'decimal:3',
            'harga_retur' => 'decimal:2',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id')->withTrashed();
    }

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id')->withTrashed();
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id')->withTrashed();
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
