<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProdukCsvService
{
    /**
     * Standard CSV header for export and template.
     *
     * @var array<int, string>
     */
    public const CSV_HEADERS = [
        'kode_katalog',
        'nama',
        'kategori',
        'merek',
        'ukuran',
        'satuan',
        'harga_beli',
        'harga_jual',
        'harga_aplikator',
        'harga_grosir',
        'stok_minimum',
        'panjang_per_roll',
    ];

    /**
     * Export products query to streamed CSV with UTF-8 BOM for Excel compatibility.
     */
    public function export(Builder|Collection $query, string $filename = 'katalog_produk.csv'): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM so Excel opens with correct encoding
            fwrite($handle, "\xEF\xBB\xBF");

            // Write headers
            fputcsv($handle, self::CSV_HEADERS);

            $processBatch = function ($products) use ($handle) {
                foreach ($products as $p) {
                    fputcsv($handle, [
                        $p->kode_katalog,
                        $p->nama,
                        $p->kategori?->nama_kategori ?? '',
                        $p->merek?->nama_merek ?? '',
                        $p->ukuran ?? '',
                        $p->satuan instanceof SatuanProduk ? $p->satuan->value : (string) $p->satuan,
                        (float) $p->harga_beli,
                        (float) $p->harga_jual,
                        $p->harga_aplikator !== null ? (float) $p->harga_aplikator : '',
                        $p->harga_grosir !== null ? (float) $p->harga_grosir : '',
                        (float) $p->stok_minimum,
                        $p->panjang_per_roll !== null ? (float) $p->panjang_per_roll : '',
                    ]);
                }
            };

            if ($query instanceof Builder) {
                $query->with(['kategori', 'merek'])->chunk(200, $processBatch);
            } else {
                $processBatch($query);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate template CSV content with sample guidance rows.
     */
    public function generateTemplateCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_produk.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, self::CSV_HEADERS);

            // Sample rows for different gypsum & PVC products
            $samples = [
                [
                    'PVC-VF-001',
                    'Plafon PVC Vifon Putih Glossy Motif Salur',
                    'Plafon PVC',
                    'Vifon',
                    '4m x 20cm x 8mm',
                    'meter',
                    25000,
                    36000,
                    33000,
                    31000,
                    100,
                    25.00,
                ],
                [
                    'GYP-JB-001',
                    'Papan Gypsum Jayaboard Standard 9mm',
                    'Papan Gypsum',
                    'Jayaboard',
                    '1200 x 2400 x 9mm',
                    'lembar',
                    65000,
                    78000,
                    75000,
                    72000,
                    50,
                    '',
                ],
                [
                    'CMP-AP-001',
                    'Semen Kompon Aplus Cornice Adhesive 20kg',
                    'Kompon & Finishing',
                    'Aplus',
                    'Zak 20kg',
                    'zak',
                    55000,
                    68000,
                    65000,
                    62000,
                    20,
                    '',
                ],
                [
                    'SKR-BW-001',
                    'Sekrup Gypsum Hitam BWS 6x1 inch',
                    'Sekrup & Aksesoris',
                    'BWS',
                    'Dus 1.000 pcs',
                    'dus',
                    32000,
                    44000,
                    40000,
                    38000,
                    10,
                    '',
                ],
            ];

            foreach ($samples as $sample) {
                fputcsv($handle, $sample);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Import products from CSV file.
     *
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function import(string|UploadedFile $file, bool $updateExisting = true, ?int $userId = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File tidak dapat dibaca atau tidak ditemukan.'],
            ];
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Gagal membuka file CSV.'],
            ];
        }

        // Read first row & strip BOM if present
        $headerLine = fgets($handle);
        if ($headerLine === false) {
            fclose($handle);

            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File CSV kosong.'],
            ];
        }

        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);
        $delimiter = str_contains($headerLine, ';') ? ';' : ',';
        $headers = str_getcsv(trim($headerLine), $delimiter);
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $rowNumber = 1;

        // Cache categories and brands to minimize queries
        $categoriesMap = Kategori::all()->pluck('id', 'nama_kategori')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim((string) $name)) => $id];
        });

        $brandsMap = Merek::all()->pluck('id', 'nama_merek')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim((string) $name)) => $id];
        });

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                // Skip completely empty rows
                if (empty(array_filter($row, fn ($v) => filled(trim((string) $v))))) {
                    continue;
                }

                $data = [];
                foreach ($headers as $index => $headerName) {
                    $data[$headerName] = isset($row[$index]) ? trim((string) $row[$index]) : '';
                }

                $kodeKatalog = $data['kode_katalog'] ?? '';
                $nama = $data['nama'] ?? '';
                $kategoriName = $data['kategori'] ?? '';
                $satuanRaw = strtolower($data['satuan'] ?? '');

                if (blank($kodeKatalog) || blank($nama)) {
                    $errors[] = "Baris #{$rowNumber}: Kode Katalog dan Nama Produk wajib diisi.";

                    continue;
                }

                // Resolve or create category
                $kategoriId = null;
                if (filled($kategoriName)) {
                    $kategoriKey = strtolower($kategoriName);
                    if ($categoriesMap->has($kategoriKey)) {
                        $kategoriId = $categoriesMap->get($kategoriKey);
                    } else {
                        $newKategori = Kategori::create([
                            'nama_kategori' => $kategoriName,
                            'slug' => Str::slug($kategoriName),
                        ]);
                        $kategoriId = $newKategori->id;
                        $categoriesMap->put($kategoriKey, $kategoriId);
                    }
                }

                if (! $kategoriId) {
                    $errors[] = "Baris #{$rowNumber} ({$kodeKatalog}): Kategori wajib diisi.";

                    continue;
                }

                // Resolve or create brand if provided
                $merekId = null;
                $merekName = $data['merek'] ?? '';
                if (filled($merekName)) {
                    $merekKey = strtolower($merekName);
                    if ($brandsMap->has($merekKey)) {
                        $merekId = $brandsMap->get($merekKey);
                    } else {
                        $newMerek = Merek::create([
                            'nama_merek' => $merekName,
                            'slug' => Str::slug($merekName),
                        ]);
                        $merekId = $newMerek->id;
                        $brandsMap->put($merekKey, $merekId);
                    }
                }

                // Validate Satuan
                $satuan = SatuanProduk::tryFrom($satuanRaw) ?? SatuanProduk::Meter;

                $hargaBeli = is_numeric($data['harga_beli'] ?? '') ? (float) $data['harga_beli'] : 0.0;
                $hargaJual = is_numeric($data['harga_jual'] ?? '') ? (float) $data['harga_jual'] : 0.0;
                $hargaAplikator = is_numeric($data['harga_aplikator'] ?? '') && filled($data['harga_aplikator']) ? (float) $data['harga_aplikator'] : null;
                $hargaGrosir = is_numeric($data['harga_grosir'] ?? '') && filled($data['harga_grosir']) ? (float) $data['harga_grosir'] : null;
                $stokMinimum = is_numeric($data['stok_minimum'] ?? '') ? (float) $data['stok_minimum'] : 0.0;
                $panjangRoll = is_numeric($data['panjang_per_roll'] ?? '') && filled($data['panjang_per_roll']) ? (float) $data['panjang_per_roll'] : null;

                $productPayload = [
                    'kategori_id' => $kategoriId,
                    'merek_id' => $merekId,
                    'kode_katalog' => $kodeKatalog,
                    'nama' => $nama,
                    'ukuran' => filled($data['ukuran'] ?? '') ? $data['ukuran'] : null,
                    'satuan' => $satuan->value,
                    'harga_beli' => $hargaBeli,
                    'harga_jual' => $hargaJual,
                    'harga_aplikator' => $hargaAplikator,
                    'harga_grosir' => $hargaGrosir,
                    'stok_minimum' => $stokMinimum,
                    'panjang_per_roll' => $satuan === SatuanProduk::Meter ? $panjangRoll : null,
                ];

                $existingProduct = Produk::withTrashed()->where('kode_katalog', $kodeKatalog)->first();

                if ($existingProduct) {
                    if ($updateExisting) {
                        $productPayload['updated_by'] = $userId;
                        if ($existingProduct->trashed()) {
                            $existingProduct->restore();
                        }
                        $existingProduct->update($productPayload);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    $productPayload['created_by'] = $userId;
                    $productPayload['updated_by'] = $userId;
                    Produk::create($productPayload);
                    $createdCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = 'Terjadi kesalahan sistem saat proses import: '.$e->getMessage();
        } finally {
            fclose($handle);
        }

        return [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
        ];
    }
}
