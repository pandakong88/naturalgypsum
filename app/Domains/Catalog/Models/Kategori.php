<?php

namespace App\Domains\Catalog\Models;

use App\Models\User;
use Database\Factories\KategoriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['parent_id', 'nama_kategori', 'created_by', 'updated_by'])]
class Kategori extends Model
{
    /** @use HasFactory<KategoriFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'kategori';

    protected static function newFactory(): KategoriFactory
    {
        return KategoriFactory::new();
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    /**
     * Get the child sub-categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope query to only root/parent categories.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope query to only sub-categories.
     */
    public function scopeChild($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Get the full category name including parent name if available.
     */
    public function getNamaLengkapAttribute(): string
    {
        return $this->parent
            ? "{$this->parent->nama_kategori} > {$this->nama_kategori}"
            : $this->nama_kategori;
    }

    /**
     * Get the products associated with the category.
     */
    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    /**
     * Get the total count of products for this category.
     * For parent categories, this aggregates direct products plus products in all child sub-categories.
     */
    public function getTotalProdukCountAttribute(): int
    {
        $directCount = (int) ($this->produks_count ?? $this->produks()->count());

        if ($this->relationLoaded('children')) {
            $childrenCount = (int) $this->children->sum(function ($child) {
                return $child->produks_count ?? $child->produks()->count();
            });

            return $directCount + $childrenCount;
        }

        if ($this->parent_id === null && $this->children()->exists()) {
            $childIds = $this->children()->pluck('id');
            $childrenCount = (int) Produk::whereIn('kategori_id', $childIds)->count();

            return $directCount + $childrenCount;
        }

        return $directCount;
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
