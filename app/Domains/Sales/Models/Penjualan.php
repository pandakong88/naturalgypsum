<?php

namespace App\Domains\Sales\Models;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Models\AlokasiPembayaran;
use App\Domains\Sales\Enums\JenisDiskon;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Models\User;
use Database\Factories\PenjualanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable([
    'pelanggan_id',
    'gudang_id',
    'user_id',
    'no_penjualan',
    'tanggal',
    'biaya_ongkir',
    'total_akhir',
    'status_pembayaran',
    'metode_pembayaran',
    'total_dibayar',
    'sisa_piutang',
    'keterangan',
    'nama_sopir',
    'plat_nomor',
    'alamat_pengiriman',
    'alasan_batal',
    'dibatalkan_by',
    'dibatalkan_at',
    'created_by',
    'updated_by',
])]
class Penjualan extends Model
{
    /** @use HasFactory<PenjualanFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'penjualan';

    protected static function newFactory(): PenjualanFactory
    {
        return PenjualanFactory::new();
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
            'biaya_ongkir' => 'decimal:2',
            'total_akhir' => 'decimal:2',
            'total_dibayar' => 'decimal:2',
            'sisa_piutang' => 'decimal:2',
            'status_pembayaran' => StatusPembayaran::class,
            'dibatalkan_at' => 'datetime',
        ];
    }

    /**
     * Get the customer for this sale.
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id')->withTrashed();
    }

    /**
     * Get the warehouse source for this sale.
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id')->withTrashed();
    }

    /**
     * Get the sales staff / user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the line items for this sale.
     */
    public function details(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class, 'penjualan_id');
    }

    /**
     * Alias for details relationship.
     */
    public function detailPenjualan(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class, 'penjualan_id');
    }

    /**
     * Get payment allocations for this invoice.
     */
    public function alokasiPembayarans(): HasMany
    {
        return $this->hasMany(AlokasiPembayaran::class, 'penjualan_id');
    }

    /**
     * Get user-friendly payment method label.
     */
    public function getMetodeLabel(): string
    {
        if ($this->metode_pembayaran === 'transfer') {
            return 'Transfer Bank';
        }

        if ($this->metode_pembayaran === 'belum_bayar' || $this->metode_pembayaran === 'tempo') {
            return 'Tempo (Piutang)';
        }

        if ($this->metode_pembayaran === 'tunai') {
            return 'Tunai (Cash POS)';
        }

        // Fallback based on payment records or status
        $firstAlloc = $this->alokasiPembayarans()->with('pembayaran')->first();
        if ($firstAlloc && $firstAlloc->pembayaran?->metode) {
            return $firstAlloc->pembayaran->metode->label();
        }

        return $this->status_pembayaran === StatusPembayaran::Lunas ? 'Tunai (Cash)' : 'Tempo (Piutang)';
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
     * Get the user who cancelled this record.
     */
    public function dibatalkanBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_by');
    }

    /**
     * Determine if this transaction has been cancelled.
     */
    public function isDibatalkan(): bool
    {
        return $this->trashed() || ! is_null($this->dibatalkan_at);
    }

    /**
     * Get line items aggregated by product and unit price for clean commercial invoice/receipt.
     * When items were split across multiple warehouses, this merges them back into single lines.
     *
     * @return Collection<int, object>
     */
    public function getGroupedDetailsForInvoice(): Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, DetailPenjualan> $details */
        $details = $this->relationLoaded('details')
            ? $this->details
            : $this->details()->with(['produk', 'gudang'])->get();

        $grouped = [];

        foreach ($details as $detail) {
            $produkId = (int) $detail->produk_id;
            $priceKey = number_format((float) $detail->harga_satuan, 2, '.', '');
            $groupKey = "{$produkId}_{$priceKey}";

            $gudangName = $detail->gudang?->nama_gudang ?? ($this->gudang?->nama_gudang ?? '-');
            $qty = (float) $detail->jumlah;
            $price = (float) $detail->harga_satuan;
            $subtotal = (float) $detail->subtotal;
            $gross = $qty * $price;
            $diskonNominal = max(0.0, $gross - $subtotal);

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'produk' => $detail->produk,
                    'produk_id' => $produkId,
                    'gudang_names' => [$gudangName],
                    'jumlah' => $qty,
                    'harga_satuan' => $price,
                    'harga_normal' => (float) ($detail->harga_normal ?? $price),
                    'total_gross' => $gross,
                    'total_diskon' => $diskonNominal,
                    'subtotal' => $subtotal,
                    'jenis_diskon' => $detail->jenis_diskon,
                    'nilai_diskon' => (float) ($detail->nilai_diskon ?? 0),
                    'is_consistent_percentage' => $detail->jenis_diskon === JenisDiskon::Persentase,
                ];
            } else {
                $grouped[$groupKey]['jumlah'] += $qty;
                $grouped[$groupKey]['total_gross'] += $gross;
                $grouped[$groupKey]['total_diskon'] += $diskonNominal;
                $grouped[$groupKey]['subtotal'] += $subtotal;

                if (! in_array($gudangName, $grouped[$groupKey]['gudang_names'], true)) {
                    $grouped[$groupKey]['gudang_names'][] = $gudangName;
                }

                if ($detail->jenis_diskon !== JenisDiskon::Persentase || abs((float) $detail->nilai_diskon - (float) $grouped[$groupKey]['nilai_diskon']) > 0.001) {
                    $grouped[$groupKey]['is_consistent_percentage'] = false;
                }
            }
        }

        return collect(array_values($grouped))->map(function (array $item) {
            $gudangLabel = implode(' & ', $item['gudang_names']);

            $finalJenisDiskon = null;
            $finalNilaiDiskon = 0.0;

            if ($item['total_diskon'] > 0.001) {
                if ($item['is_consistent_percentage']) {
                    $finalJenisDiskon = JenisDiskon::Persentase;
                    $finalNilaiDiskon = $item['nilai_diskon'];
                } else {
                    $finalJenisDiskon = JenisDiskon::Nominal;
                    $finalNilaiDiskon = $item['total_diskon'];
                }
            }

            return (object) [
                'produk' => $item['produk'],
                'produk_id' => $item['produk_id'],
                'gudang' => (object) ['nama_gudang' => $gudangLabel],
                'gudang_label' => $gudangLabel,
                'jumlah' => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan'],
                'harga_normal' => $item['harga_normal'],
                'jenis_diskon' => $finalJenisDiskon,
                'nilai_diskon' => $finalNilaiDiskon,
                'subtotal' => $item['subtotal'],
            ];
        });
    }
}
