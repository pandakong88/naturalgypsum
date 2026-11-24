<?php

namespace App\Domains\Catalog\Models;

use App\Models\User;
use Database\Factories\MerekFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama_merek', 'created_by', 'updated_by'])]
class Merek extends Model
{
    /** @use HasFactory<MerekFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'merek';

    protected static function newFactory(): MerekFactory
    {
        return MerekFactory::new();
    }

    /**
     * Get the products associated with the brand.
     */
    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class, 'merek_id');
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
