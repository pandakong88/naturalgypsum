<?php

namespace App\Domains\Production\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Models\User;
use Database\Factories\ProduksiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'no_produksi',
    'tanggal',
    'gudang_id',
    'user_id',
    'catatan',
    'created_by',
    'updated_by',
])]
class Produksi extends Model
{
    /** @use HasFactory<ProduksiFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'produksi';

    protected static function newFactory(): ProduksiFactory
    {
        return ProduksiFactory::new();
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
        ];
    }

    /**
     * Target warehouse receiving output.
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id')->withTrashed();
    }

    /**
     * Staff handling production record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Output line items.
     */
    public function details(): HasMany
    {
        return $this->hasMany(DetailProduksi::class, 'produksi_id');
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
