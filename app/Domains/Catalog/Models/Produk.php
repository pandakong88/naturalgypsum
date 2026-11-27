<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Inventory\Models\Stok;
use App\Models\User;
use Database\Factories\ProdukFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'kategori_id',
    'merek_id',
    'kode_katalog',
    'nama',
    'ukuran',
    'satuan',
    'harga_beli',
    'harga_jual',
    'harga_aplikator',
    'harga_grosir',
    'stok_minimum',
    'panjang_meter',
    'kemasan',
    'isi_per_kemasan',
    'panjang_per_roll',
    'foto',
    'created_by',
    'updated_by',
])]
class Produk extends Model
{
    /** @use HasFactory<ProdukFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'produk';

    protected static function newFactory(): ProdukFactory
    {
        return ProdukFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'satuan' => SatuanProduk::class,
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'harga_aplikator' => 'decimal:2',
            'harga_grosir' => 'decimal:2',
            'stok_minimum' => 'decimal:3',
            'panjang_meter' => 'decimal:2',
            'isi_per_kemasan' => 'decimal:2',
            'panjang_per_roll' => 'decimal:2',
        ];
    }

    /**
     * Get the selling price for a specific customer type.
     */
    public function getHargaUntukPelanggan(?TipePelanggan $tipe): float
    {
        return match ($tipe) {
            TipePelanggan::Pemborong => (float) ($this->harga_aplikator ?? $this->harga_jual),
            TipePelanggan::Langganan => (float) ($this->harga_grosir ?? $this->harga_jual),
            default => (float) $this->harga_jual,
        };
    }

    /**
     * Get public URL for product photo.
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('storage/'.$this->foto) : null;
    }

    /**
     * Get the category that the product belongs to.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    /**
     * Get the brand that the product belongs to.
     */
    public function merek(): BelongsTo
    {
        return $this->belongsTo(Merek::class, 'merek_id');
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
     * Get stock records for this product across warehouses.
     */
    public function stok(): HasMany
    {
        return $this->hasMany(Stok::class, 'produk_id');
    }

    /**
     * Get price change history records for this product.
     */
    public function riwayatHarga(): HasMany
    {
        return $this->hasMany(RiwayatHargaProduk::class, 'produk_id');
    }

    public function isRollProduct(): bool
    {
        $kemasan = strtolower(trim((string) $this->kemasan));
        $satuanVal = strtolower((string) ($this->satuan?->value ?? ''));
        $panjangRoll = (float) ($this->panjang_per_roll ?? 0);

        if ($satuanVal === 'roll' || $kemasan === 'roll' || $panjangRoll > 0) {
            return true;
        }

        $nama = strtolower($this->nama ?? '');

        return ($satuanVal === 'meter') && (
            str_contains($nama, 'foil') ||
            str_contains($nama, 'lakban') ||
            str_contains($nama, 'kasa')
        );
    }

    /**
     * Hitung konversi fisik untuk estimasi stok gudang & nota.
     *
     * @return array{
     *     has_conversion: bool,
     *     panjang_meter: float|null,
     *     qty_lembar: float|null,
     *     kemasan: string|null,
     *     isi_per_kemasan: float|null,
     *     jumlah_kemasan_utuh: int|null,
     *     sisa_eceran: float|null,
     *     badge_text: string|null
     * }
     */
    public function getKonversiFisik(float $qty): array
    {
        $panjangMeter = $this->panjang_meter ? (float) $this->panjang_meter : null;
        $kemasan = filled($this->kemasan) ? trim($this->kemasan) : null;
        $isiPerKemasan = $this->isi_per_kemasan ? (float) $this->isi_per_kemasan : null;
        $panjangRoll = (float) ($this->panjang_per_roll ?: (strcasecmp((string) $kemasan, 'roll') === 0 ? $isiPerKemasan : 0));

        // 1. Kasus Barang Roll (Bubble Foil, Woven Foil, Lakban, Kasa Gypsum)
        if ($this->isRollProduct() && $qty != 0) {
            $effectiveRollLength = $panjangRoll > 0 ? $panjangRoll : ($isiPerKemasan ?: 25.0);
            $kemasanUtuh = (int) floor($qty / $effectiveRollLength);
            $sisaMeter = (float) fmod($qty, $effectiveRollLength);

            if ($kemasanUtuh > 0) {
                $detail = $sisaMeter > 0
                    ? ' + '.number_format($sisaMeter, 0, ',', '.').'m eceran'
                    : ' pas (@'.number_format($effectiveRollLength, 0, ',', '.').'m)';
                $badge = "{$kemasanUtuh} Roll{$detail}";
            } else {
                $badge = number_format($qty, 0, ',', '.').'m eceran';
            }

            return [
                'has_conversion' => true,
                'panjang_meter' => null,
                'qty_lembar' => null,
                'kemasan' => 'Roll',
                'isi_per_kemasan' => $effectiveRollLength,
                'jumlah_kemasan_utuh' => $kemasanUtuh,
                'sisa_eceran' => $sisaMeter,
                'badge_text' => $badge,
            ];
        }

        // Fallback untuk Plafon PVC jika panjang_meter belum diset di database, deteksi dari nama/ukuran
        if ($panjangMeter === null && $this->satuan === SatuanProduk::Meter) {
            $text = ($this->ukuran ?? '').' '.$this->nama.' '.($this->kode_katalog ?? '');
            if (preg_match('/(\d+(?:\.\d+)?)\s*m\b/i', $text, $matches)) {
                $val = (float) $matches[1];
                if ($val >= 1 && $val <= 12) {
                    $panjangMeter = $val;
                }
            }
        }

        // Fallback default kemasan dus Plafon PVC (1 dus = 15 lembar standar pabrik)
        if ($isiPerKemasan === null && $this->satuan === SatuanProduk::Meter && str_contains(strtolower($this->nama), 'pvc')) {
            $isiPerKemasan = 15.0;
        }

        // 2. Kasus Barang Meter Lari (Plafon PVC, Lis, dll)
        if ($this->satuan === SatuanProduk::Meter && $panjangMeter && $panjangMeter > 0 && $qty != 0) {
            $qtyLembar = round($qty / $panjangMeter, 4);
            $satuanFisik = 'lembar';

            $kemasanUtuh = null;
            $sisaEceran = null;
            $kemasanLabel = $kemasan ?: 'Dus';

            // Hitung lembar utuh dan sisa potongan meter jika ada
            $wholeSheets = (int) floor($qty / $panjangMeter);
            $sisaMeterPotongan = round($qty - ($wholeSheets * $panjangMeter), 2);
            if (abs($sisaMeterPotongan) < 0.001) {
                $sisaMeterPotongan = 0.0;
            }

            if ($isiPerKemasan && $isiPerKemasan > 0) {
                $kemasanUtuh = (int) floor($wholeSheets / $isiPerKemasan);
                $sisaLembarUtuh = $wholeSheets - ($kemasanUtuh * (int) $isiPerKemasan);
                $sisaEceran = $sisaLembarUtuh;

                $parts = [];
                if ($kemasanUtuh > 0) {
                    $parts[] = "{$kemasanUtuh} {$kemasanLabel}";
                }
                if ($sisaLembarUtuh > 0) {
                    $parts[] = "{$sisaLembarUtuh} lbr";
                }
                if ($sisaMeterPotongan > 0) {
                    $parts[] = (fmod($sisaMeterPotongan, 1) == 0 ? (int) $sisaMeterPotongan : number_format($sisaMeterPotongan, 1, ',', '.')).'m potongan';
                }

                if (empty($parts)) {
                    $badge = '0 lbr';
                } elseif ($kemasanUtuh > 0) {
                    if ($sisaLembarUtuh > 0 || $sisaMeterPotongan > 0) {
                        $badge = implode(' + ', $parts).' (total '.number_format($qtyLembar, (fmod($qtyLembar, 1) == 0 ? 0 : 1), ',', '.').' lbr)';
                    } else {
                        $badge = "{$kemasanUtuh} {$kemasanLabel} (".number_format($qtyLembar, 0, ',', '.').' lbr)';
                    }
                } else {
                    if ($sisaLembarUtuh > 0 && $sisaMeterPotongan > 0) {
                        $badge = "{$sisaLembarUtuh} lbr + ".(fmod($sisaMeterPotongan, 1) == 0 ? (int) $sisaMeterPotongan : number_format($sisaMeterPotongan, 1, ',', '.'))."m potongan ({$qty}m)";
                    } elseif ($sisaMeterPotongan > 0) {
                        $badge = 'Potongan '.(fmod($sisaMeterPotongan, 1) == 0 ? (int) $sisaMeterPotongan : number_format($sisaMeterPotongan, 1, ',', '.')).'m eceran';
                    } else {
                        $badge = number_format($qtyLembar, 0, ',', '.')." lbr eceran ({$panjangMeter}m)";
                    }
                }
            } else {
                if ($sisaMeterPotongan > 0 && $wholeSheets > 0) {
                    $badge = "{$wholeSheets} lbr + ".(fmod($sisaMeterPotongan, 1) == 0 ? (int) $sisaMeterPotongan : number_format($sisaMeterPotongan, 1, ',', '.')).'m potongan';
                } elseif ($sisaMeterPotongan > 0) {
                    $badge = 'Potongan '.(fmod($sisaMeterPotongan, 1) == 0 ? (int) $sisaMeterPotongan : number_format($sisaMeterPotongan, 1, ',', '.')).'m';
                } else {
                    $badge = '± '.number_format($qtyLembar, 1, ',', '.')." {$satuanFisik} ({$panjangMeter}m)";
                }
            }

            return [
                'has_conversion' => true,
                'panjang_meter' => $panjangMeter,
                'qty_lembar' => $qtyLembar,
                'kemasan' => $kemasanLabel,
                'isi_per_kemasan' => $isiPerKemasan,
                'jumlah_kemasan_utuh' => $kemasanUtuh,
                'sisa_eceran' => $sisaEceran,
                'sisa_meter_potongan' => $sisaMeterPotongan,
                'badge_text' => $badge,
            ];
        }

        // 3. Kasus Barang Non-Meter dengan Kemasan Pabrik (Hollow per ikat, Sekrup per dus, dll)
        if ($isiPerKemasan && $isiPerKemasan > 0 && $qty != 0) {
            $kemasanName = $kemasan ?: ($this->satuan === SatuanProduk::Dus ? 'Dus' : 'Dus');

            // 3a. Jika satuan dasar adalah kemasan (misal Satuan = Dus / Pack / Box) dan isi_per_kemasan terisi
            if (strcasecmp($this->satuan->value, $kemasanName) === 0 || $this->satuan === SatuanProduk::Dus || in_array(strtolower($this->satuan->value), ['dus', 'pack', 'box'], true)) {
                $kemasanUtuh = (int) floor($qty);
                $sisaPcs = (int) round(($qty - $kemasanUtuh) * $isiPerKemasan);
                $totalPcs = (int) round($qty * $isiPerKemasan);

                if ($kemasanUtuh > 0) {
                    if ($sisaPcs > 0) {
                        $badge = "{$kemasanUtuh} {$kemasanName} + {$sisaPcs} pcs";
                    } else {
                        $badge = "{$kemasanUtuh} {$kemasanName} (@".number_format($isiPerKemasan, 0, ',', '.').' pcs)';
                    }
                } else {
                    $badge = "{$sisaPcs} pcs eceran";
                }

                return [
                    'has_conversion' => true,
                    'panjang_meter' => $panjangMeter,
                    'qty_lembar' => null,
                    'kemasan' => $kemasanName,
                    'isi_per_kemasan' => $isiPerKemasan,
                    'jumlah_kemasan_utuh' => $kemasanUtuh,
                    'sisa_eceran' => $sisaPcs,
                    'badge_text' => $badge,
                ];
            }

            // 3b. Jika satuan dasar adalah eceran (misal batang / lembar / pcs) dan kemasan adalah wadah (misal Ikat / Dus)
            $kemasanUtuh = (int) floor($qty / $isiPerKemasan);
            $sisaEceran = (int) round(fmod($qty, $isiPerKemasan));
            $satuanSingkat = match (strtolower($this->satuan->value)) {
                'batang' => 'btg',
                'lembar' => 'lbr',
                'meter' => 'm',
                default => $this->satuan->label(),
            };

            if ($kemasanUtuh > 0) {
                if ($sisaEceran > 0) {
                    $badge = "{$kemasanUtuh} {$kemasanName} + {$sisaEceran} {$satuanSingkat} (total ".number_format($qty, 0, ',', '.')." {$satuanSingkat})";
                } else {
                    $badge = "{$kemasanUtuh} {$kemasanName} pas (@".number_format($isiPerKemasan, 0, ',', '.')." {$satuanSingkat})";
                }
            } else {
                $badge = number_format($qty, 0, ',', '.')." {$satuanSingkat} eceran";
            }

            return [
                'has_conversion' => true,
                'panjang_meter' => $panjangMeter,
                'qty_lembar' => null,
                'kemasan' => $kemasanName,
                'isi_per_kemasan' => $isiPerKemasan,
                'jumlah_kemasan_utuh' => $kemasanUtuh,
                'sisa_eceran' => $sisaEceran,
                'badge_text' => $badge,
            ];
        }

        return [
            'has_conversion' => false,
            'panjang_meter' => null,
            'qty_lembar' => null,
            'kemasan' => null,
            'isi_per_kemasan' => null,
            'jumlah_kemasan_utuh' => null,
            'sisa_eceran' => null,
            'badge_text' => null,
        ];
    }
}
