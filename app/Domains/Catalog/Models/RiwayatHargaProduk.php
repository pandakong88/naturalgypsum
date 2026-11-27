<?php

namespace App\Domains\Catalog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'produk_id',
    'jenis_harga',
    'harga_lama',
    'harga_baru',
    'selisih_nominal',
    'selisih_persen',
    'sumber',
    'referensi',
    'referensi_type',
    'referensi_id',
    'supplier_id',
    'keterangan',
    'user_id',
])]
class RiwayatHargaProduk extends Model
{
    protected $table = 'riwayat_harga_produk';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_lama' => 'decimal:2',
            'harga_baru' => 'decimal:2',
            'selisih_nominal' => 'decimal:2',
            'selisih_persen' => 'decimal:2',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id')->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function isPurchasePrice(): bool
    {
        return $this->jenis_harga === 'harga_beli';
    }

    public function jenisHargaLabel(): string
    {
        return match ($this->jenis_harga) {
            'harga_beli' => 'Harga Beli (HPP)',
            'harga_jual' => 'Harga Jual Umum',
            'harga_aplikator' => 'Harga Aplikator',
            'harga_grosir' => 'Harga Grosir',
            default => ucwords(str_replace('_', ' ', (string) $this->jenis_harga)),
        };
    }

    public function jenisHargaBadgeClass(): string
    {
        return match ($this->jenis_harga) {
            'harga_beli' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'harga_jual' => 'bg-success-subtle text-success border border-success-subtle',
            'harga_aplikator' => 'bg-info-subtle text-info border border-info-subtle',
            'harga_grosir' => 'bg-primary-subtle text-primary border border-primary-subtle',
            default => 'bg-light text-secondary border',
        };
    }

    public function sumberLabel(): string
    {
        return match ($this->sumber) {
            'pembelian' => 'Faktur Pembelian / PO',
            'master_update' => 'Edit Manual Katalog',
            'master_create' => 'Produk Baru',
            'import_excel' => 'Import Excel / CSV',
            default => ucwords(str_replace('_', ' ', (string) $this->sumber)),
        };
    }

    public function sumberBadgeClass(): string
    {
        return match ($this->sumber) {
            'pembelian' => 'bg-primary-subtle text-primary border border-primary-subtle',
            'master_update' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            'master_create' => 'bg-success-subtle text-success border border-success-subtle',
            'import_excel' => 'bg-info-subtle text-info border border-info-subtle',
            default => 'bg-light text-dark border',
        };
    }
}
