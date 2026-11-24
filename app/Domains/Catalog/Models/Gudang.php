<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Inventory\Models\Stok;
use App\Models\User;
use Database\Factories\GudangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama_gudang', 'lokasi', 'kontak', 'created_by', 'updated_by'])]
class Gudang extends Model
{
    /** @use HasFactory<GudangFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'gudang';

    protected static function newFactory(): GudangFactory
    {
        return GudangFactory::new();
    }

    /**
     * Get stock records in this warehouse.
     */
    public function stoks(): HasMany
    {
        return $this->hasMany(Stok::class, 'gudang_id');
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
