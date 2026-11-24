<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Returns\Models\ReturSupplier;
use App\Models\User;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nama_supplier',
    'kontak',
    'alamat',
    'email',
    'nama_bank',
    'no_rekening',
    'atas_nama_rekening',
    'created_by',
    'updated_by',
])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'supplier';

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
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

    /**
     * Get the purchases from this supplier.
     */
    public function pembelians(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'supplier_id');
    }

    /**
     * Get returns made to this supplier.
     */
    public function returs(): HasMany
    {
        return $this->hasMany(ReturSupplier::class, 'supplier_id');
    }

    /**
     * Scope a query to search by name or contact number (flexible phone search with/without dashes, spaces, dots).
     *
     * @param  Builder  $query
     */
    public function scopeSearch($query, ?string $term): mixed
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim((string) $term);
        $digits = preg_replace('/[^0-9]/', '', $term);
        $cleanSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(kontak, '-', ''), ' ', ''), '.', ''), '+', ''), '(', ''), ')', '')";

        return $query->where(function ($q) use ($term, $digits, $cleanSql) {
            $q->where('nama_supplier', 'like', "%{$term}%")
                ->orWhere('kontak', 'like', "%{$term}%");

            if (strlen($digits) >= 2) {
                $q->orWhereRaw("{$cleanSql} LIKE ?", ["%{$digits}%"]);

                if (str_starts_with($digits, '628')) {
                    $local = '08'.substr($digits, 3);
                    $q->orWhereRaw("{$cleanSql} LIKE ?", ["%{$local}%"]);
                } elseif (str_starts_with($digits, '08')) {
                    $intl = '628'.substr($digits, 2);
                    $q->orWhereRaw("{$cleanSql} LIKE ?", ["%{$intl}%"]);
                }
            }
        });
    }
}
