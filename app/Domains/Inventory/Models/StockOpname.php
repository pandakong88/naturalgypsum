<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Models\User;
use Database\Factories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'stock_opnames';

    protected $fillable = [
        'no_stock_opname',
        'tanggal',
        'gudang_id',
        'user_id',
        'status',
        'catatan',
        'dibatalkan_at',
        'dibatalkan_by',
        'alasan_batal',
        'created_by',
        'updated_by',
    ];

    protected static function newFactory(): StockOpnameFactory
    {
        return StockOpnameFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'status' => StatusStockOpname::class,
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dibatalkanBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailStockOpname::class, 'stock_opname_id');
    }
}
