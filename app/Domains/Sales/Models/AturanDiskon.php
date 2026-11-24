<?php

namespace App\Domains\Sales\Models;

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AturanDiskon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aturan_diskon';

    protected $fillable = [
        'nama',
        'keterangan',
        'target_pelanggan',
        'pelanggan_id',
        'target_produk',
        'kategori_id',
        'merek_id',
        'produk_id',
        'jenis_diskon',
        'nilai_diskon',
        'min_kuantiti',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
        'priority',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai_diskon' => 'decimal:2',
            'min_kuantiti' => 'decimal:2',
            'is_active' => 'boolean',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'priority' => 'integer',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class, 'aturan_diskon_kategori', 'aturan_diskon_id', 'kategori_id');
    }

    public function merek(): BelongsTo
    {
        return $this->belongsTo(Merek::class, 'merek_id');
    }

    public function mereks(): BelongsToMany
    {
        return $this->belongsToMany(Merek::class, 'aturan_diskon_merek', 'aturan_diskon_id', 'merek_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function produks(): BelongsToMany
    {
        return $this->belongsToMany(Produk::class, 'aturan_diskon_produk', 'aturan_diskon_id', 'produk_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeValidToday(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where(function ($q) use ($today) {
            $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today);
        });
    }

    /**
     * Determine if this rule applies to given product, customer, and quantity.
     */
    public function appliesTo(Produk $produk, ?Pelanggan $pelanggan, float $qty): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = Carbon::today();
        if ($this->tanggal_mulai && $today->lt($this->tanggal_mulai)) {
            return false;
        }
        if ($this->tanggal_selesai && $today->gt($this->tanggal_selesai)) {
            return false;
        }

        // Check minimum quantity
        if ($this->min_kuantiti > 0 && $qty < (float) $this->min_kuantiti) {
            return false;
        }

        // Check customer target
        $targetPelanggan = strtolower((string) $this->target_pelanggan);
        if ($targetPelanggan === 'spesifik') {
            if (! $pelanggan || $pelanggan->id !== $this->pelanggan_id) {
                return false;
            }
        } elseif ($targetPelanggan === 'umum') {
            $tipe = $pelanggan?->tipe instanceof TipePelanggan
                ? $pelanggan->tipe->value
                : (string) $pelanggan?->tipe;
            if ($tipe !== 'umum') {
                return false;
            }
        } elseif ($targetPelanggan === 'pemborong') {
            $tipe = $pelanggan?->tipe instanceof TipePelanggan
                ? $pelanggan->tipe->value
                : (string) $pelanggan?->tipe;
            if ($tipe !== 'pemborong') {
                return false;
            }
        } elseif ($targetPelanggan === 'langganan') {
            $tipe = $pelanggan?->tipe instanceof TipePelanggan
                ? $pelanggan->tipe->value
                : (string) $pelanggan?->tipe;
            if ($tipe !== 'langganan') {
                return false;
            }
        }

        // Check product target
        $targetProduk = strtolower((string) $this->target_produk);
        if ($targetProduk === 'spesifik') {
            // Support pivot multi-products & legacy single product_id
            $matchingProductIds = $this->relationLoaded('produks')
                ? $this->produks->pluck('id')->all()
                : $this->produks()->pluck('produk.id')->all();

            if (! empty($matchingProductIds)) {
                if (! in_array($produk->id, $matchingProductIds, true)) {
                    return false;
                }
            } elseif ($produk->id !== $this->produk_id) {
                return false;
            }
        } elseif ($targetProduk === 'kategori') {
            // Support pivot multi-categories & legacy single kategori_id
            $matchingCategoryIds = $this->relationLoaded('kategoris')
                ? $this->kategoris->pluck('id')->all()
                : $this->kategoris()->pluck('kategori.id')->all();

            $prodCatIds = array_filter([
                $produk->kategori_id,
                $produk->kategori?->parent_id,
            ]);

            if (! empty($matchingCategoryIds)) {
                if (empty(array_intersect($prodCatIds, $matchingCategoryIds))) {
                    return false;
                }
            } elseif (! in_array($this->kategori_id, $prodCatIds, true)) {
                return false;
            }
        } elseif ($targetProduk === 'merek') {
            // Support pivot multi-brands & legacy single merek_id
            $matchingMerekIds = $this->relationLoaded('mereks')
                ? $this->mereks->pluck('id')->all()
                : $this->mereks()->pluck('merek.id')->all();

            if (! empty($matchingMerekIds)) {
                if (! $produk->merek_id || ! in_array($produk->merek_id, $matchingMerekIds, true)) {
                    return false;
                }
            } elseif (! $produk->merek_id || $produk->merek_id !== $this->merek_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate unit discount amount based on product price.
     */
    public function calculateUnitDiscount(float $hargaSatuan): float
    {
        $jenis = strtolower((string) $this->jenis_diskon);
        $nilai = (float) $this->nilai_diskon;

        if ($jenis === 'persentase') {
            return round(($hargaSatuan * $nilai) / 100, 2);
        }

        // Nominal discount per unit, capped at unit price
        return min($nilai, $hargaSatuan);
    }
}
