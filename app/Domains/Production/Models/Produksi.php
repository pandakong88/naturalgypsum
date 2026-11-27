<?php

namespace App\Domains\Production\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Inventory\Models\MutasiStok;
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
     * Output line items (Finished goods).
     */
    public function details(): HasMany
    {
        return $this->hasMany(DetailProduksi::class, 'produksi_id');
    }

    /**
     * Raw materials consumed during this production batch (Pemakaian Internal).
     */
    public function bahanMutations(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'referensi_id')
            ->where('referensi_type', 'produksi_bahan')
            ->where('jumlah', '<', 0);
    }

    /**
     * Calculate estimated HPP value of finished goods output.
     */
    public function getEstimasiNilaiOutputAttribute(): float
    {
        $details = $this->relationLoaded('details') ? $this->details : $this->details()->with('produk')->get();

        $total = 0.0;
        foreach ($details as $detail) {
            $hpp = (float) ($detail->produk?->harga_beli ?: $detail->produk?->harga_jual ?: 0);
            $total += ((float) $detail->jumlah) * $hpp;
        }

        return round($total, 2);
    }

    /**
     * Calculate estimated cost of consumed raw materials.
     */
    public function getEstimasiBiayaBahanAttribute(): float
    {
        $mutations = $this->relationLoaded('bahanMutations')
            ? $this->bahanMutations
            : $this->bahanMutations()->with('produk')->get();

        $total = 0.0;
        foreach ($mutations as $mutasi) {
            $hpp = (float) ($mutasi->produk?->harga_beli ?: 0);
            $total += abs((float) $mutasi->jumlah) * $hpp;
        }

        return round($total, 2);
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
