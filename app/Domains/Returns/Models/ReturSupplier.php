<?php

namespace App\Domains\Returns\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Models\Pembelian;
use App\Models\User;
use Database\Factories\ReturSupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'no_retur',
    'tanggal',
    'supplier_id',
    'produk_id',
    'gudang_id',
    'user_id',
    'pembelian_id',
    'jumlah',
    'alasan',
    'created_by',
    'updated_by',
])]
class ReturSupplier extends Model
{
    /** @use HasFactory<ReturSupplierFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'retur_supplier';

    protected static function newFactory(): ReturSupplierFactory
    {
        return ReturSupplierFactory::new();
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
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
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

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id')->withTrashed();
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
