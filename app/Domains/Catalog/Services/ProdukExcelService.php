<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProdukExcelService
{
    public function __construct(
        protected ?ActivityLogService $activityLog = null
    ) {
        $this->activityLog ??= app(ActivityLogService::class);
    }

    /**
     * Friendly Indonesian header titles for non-technical users.
     *
     * @var array<int, string>
     */
    public const DISPLAY_HEADERS = [
        'KODE BARANG',
        'NAMA PRODUK',
        'KATEGORI INDUK',
        'SUB KATEGORI',
        'MEREK',
        'UKURAN / SPESIFIKASI',
        'SATUAN',
        'HARGA BELI (HPP)',
        'HARGA JUAL UMUM',
        'STOK AWAL',
        'STOK MINIMUM',
        'PANJANG LEMBAR (M)',
        'KEMASAN PABRIK',
        'ISI PER KEMASAN',
        'PANJANG ROLL (METER)',
    ];

    /**
     * Map of canonical keys to acceptable user-friendly header names (case-insensitive).
     */
    protected const HEADER_ALIASES = [
        'kode_katalog' => ['kode barang', 'kode_katalog', 'kode katalog', 'kode', 'sku', 'kode sku', 'kode_barang'],
        'nama' => ['nama produk', 'nama', 'nama_produk', 'produk', 'deskripsi', 'nama material'],
        'kategori_induk' => ['kategori induk', 'kategori_induk', 'kategori utama', 'induk kategori', 'parent kategori', 'kategori besar'],
        'sub_kategori' => ['sub kategori', 'sub_kategori', 'sub-kategori', 'subkategori', 'anak kategori', 'jenis', 'tipe'],
        'kategori' => ['kategori', 'kategori produk', 'nama_kategori'],
        'merek' => ['merek', 'brand', 'nama_merek', 'merk'],
        'ukuran' => ['ukuran / spesifikasi', 'ukuran', 'spesifikasi', 'dimensi', 'tebal'],
        'satuan' => ['satuan', 'unit', 'satuan dasar', 'satuan produk'],
        'harga_beli' => ['harga beli (hpp)', 'harga beli', 'harga_beli', 'hpp', 'modal', 'harga modal', 'beli'],
        'harga_jual' => ['harga jual umum', 'harga jual', 'harga_jual', 'harga retail', 'harga umum', 'eceran', 'jual'],
        'harga_aplikator' => ['harga tukang (aplikator)', 'harga aplikator', 'harga_aplikator', 'harga tukang', 'pemborong', 'aplikator'],
        'harga_grosir' => ['harga grosir (toko)', 'harga grosir', 'harga_grosir', 'toko', 'harga partai', 'grosir'],
        'stok_awal' => ['stok awal', 'saldo awal', 'stok_awal', 'qty awal', 'stok fisik', 'saldo awal stok', 'stok gudang', 'stok'],
        'stok_minimum' => ['stok minimum', 'stok_minimum', 'min stok', 'minimum stok', 'ambang stok minimum', 'stok min'],
        'panjang_meter' => ['panjang lembar (m)', 'panjang lembar', 'panjang fisik', 'panjang_meter', 'panjang fisik (meter)', 'panjang meter', 'panjang m', 'panjang (m)', 'panjang'],
        'kemasan' => ['kemasan pabrik', 'satuan kemasan', 'kemasan', 'packaging', 'kemasan pabrik (dus/ikat/roll)'],
        'isi_per_kemasan' => ['isi per kemasan', 'isi kemasan', 'isi per dus', 'isi dus', 'isi ikat', 'isi_per_kemasan', 'isi per pack'],
        'panjang_per_roll' => ['panjang roll (meter)', 'panjang per roll', 'panjang_per_roll', 'roll', 'panjang roll', 'isi per roll'],
    ];

    /**
     * Cache for resolved category IDs during import.
     *
     * @var array<string, int>
     */
    protected array $resolvedCategoryCache = [];

    /**
     * Export products query to genuine, beautifully styled .xlsx Excel file with multi-sheets.
     */
    public function export(Builder|Collection $query, string $filename = 'katalog_produk.xlsx'): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'export_prd_').'.xlsx';

        $options = new Options;
        $writer = new XlsxWriter($options);
        $writer->openToFile($tempPath);

        // Sheet 1: Data Produk
        $sheet1 = $writer->getCurrentSheet();
        $sheet1->setName('Data Produk');
        $this->applyDataSheetColumnWidths($sheet1);

        // Header style: Navy background, White bold text, clean font
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('1E3A8A')
            ->withFontSize(11);

        $writer->addRow(Row::fromValuesWithStyle(self::DISPLAY_HEADERS, $headerStyle));

        // Data row style: clean readable text
        $dataStyle = (new Style)->withFontSize(10);
        $canViewPurchasePrices = Auth::check() && Auth::user()->can('view_purchase_prices');

        $processBatch = function ($products) use ($writer, $dataStyle, $canViewPurchasePrices) {
            foreach ($products as $p) {
                $satuanLabel = $p->satuan instanceof SatuanProduk ? $p->satuan->value : (string) $p->satuan;
                $stokSekarang = (float) ($p->total_stok ?? ($p->relationLoaded('stok') ? $p->stok->sum('jumlah') : 0));
                $hargaBeliVal = $canViewPurchasePrices ? (float) $p->harga_beli : '-';

                $kategoriInduk = '';
                $subKategori = '';
                if ($p->kategori) {
                    if ($p->kategori->parent) {
                        $kategoriInduk = $p->kategori->parent->nama_kategori;
                        $subKategori = $p->kategori->nama_kategori;
                    } else {
                        $kategoriInduk = $p->kategori->nama_kategori;
                        $subKategori = '';
                    }
                }

                $rowValues = [
                    $p->kode_katalog,
                    $p->nama,
                    $kategoriInduk,
                    $subKategori,
                    $p->merek?->nama_merek ?? '',
                    $p->ukuran ?? '',
                    $satuanLabel,
                    $hargaBeliVal,
                    (float) $p->harga_jual,
                    $stokSekarang,
                    (float) $p->stok_minimum,
                    $p->panjang_meter !== null ? (float) $p->panjang_meter : '',
                    $p->kemasan ?? '',
                    $p->isi_per_kemasan !== null ? (float) $p->isi_per_kemasan : '',
                    $p->panjang_per_roll !== null ? (float) $p->panjang_per_roll : '',
                ];

                $writer->addRow(Row::fromValuesWithStyle($rowValues, $dataStyle));
            }
        };

        if ($query instanceof Builder) {
            $query->with(['kategori.parent', 'merek', 'stok'])->chunk(200, $processBatch);
        } else {
            $processBatch($query);
        }

        // Sheet 2: Petunjuk Pengisian
        $this->appendPetunjukSheet($writer);

        // Sheet 3: Daftar Referensi
        $this->appendReferensiSheet($writer);

        $writer->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate sample Excel template (.xlsx) with styled header and practical gypsum & PVC examples.
     */
    public function generateTemplateExcel(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template_prd_').'.xlsx';

        $options = new Options;
        $writer = new XlsxWriter($options);
        $writer->openToFile($tempPath);

        // Sheet 1: Data Produk
        $sheet1 = $writer->getCurrentSheet();
        $sheet1->setName('Data Produk');
        $this->applyDataSheetColumnWidths($sheet1);

        // Header style: Deep Emerald Green, White bold font
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('0F766E')
            ->withFontSize(11);

        $writer->addRow(Row::fromValuesWithStyle(self::DISPLAY_HEADERS, $headerStyle));

        $sampleStyle = (new Style)->withFontSize(10);

        // Practical real-world examples with 2-level categories and no manual wholesale prices
        $samples = [
            [
                'PVC-VF-001',
                'Plafon PVC Vifon Putih Glossy Motif Salur 4m',
                'PVC Plafon & Dinding',
                'Seri Glossy',
                'VIFON',
                '4m x 20cm x 8mm',
                'meter',
                25000,
                36000,
                600,
                100,
                4.0,
                'Dus',
                15,
                '',
            ],
            [
                'GYP-JB-001',
                'Papan Gypsum Jayaboard Standard 9mm',
                'Gypsum & Moulding',
                'Papan Gypsum (Board)',
                'Jayaboard',
                '1200 x 2400 x 9mm',
                'lembar',
                65000,
                78000,
                80,
                50,
                '',
                'Pallet',
                80,
                '',
            ],
            [
                'CMP-AP-001',
                'Semen Kompon Aplus Cornice Adhesive 20kg',
                'Gypsum & Moulding',
                'Kompon & Finishing',
                'Aplus',
                'Zak 20kg',
                'zak',
                55000,
                68000,
                30,
                20,
                '',
                'Sak',
                1,
                '',
            ],
            [
                'HLW-KN-001',
                'Hollow Galvalum Kencana 2x4 Tebal 0.30mm',
                'Rangka & Hollow',
                'Hollow Galvanis',
                'Kencana',
                'Panjang 4 meter',
                'batang',
                18000,
                26000,
                300,
                200,
                4.0,
                'Ikat',
                20,
                '',
            ],
            [
                'SKR-BW-001',
                'Sekrup Gypsum Hitam BWS 6x1 inch',
                'Aksesoris & Ricikan',
                'Baut & Sekrup',
                'BWS',
                'Dus 1.000 pcs',
                'dus',
                32000,
                44000,
                20,
                10,
                '',
                'Dus',
                1000,
                '',
            ],
            [
                'INS-BF-001',
                'Peredam Panas Bubble Foil Single 25m',
                'Aksesoris & Ricikan',
                'Ricikan Lainnya',
                'Zelltech',
                '1.2m x 25m',
                'meter',
                350000,
                480000,
                10,
                5,
                '',
                'Roll',
                1,
                25.0,
            ],
        ];

        foreach ($samples as $sample) {
            $writer->addRow(Row::fromValuesWithStyle($sample, $sampleStyle));
        }

        // Sheet 2: Petunjuk Pengisian
        $this->appendPetunjukSheet($writer);

        // Sheet 3: Daftar Referensi
        $this->appendReferensiSheet($writer);

        $writer->close();

        return response()->download($tempPath, 'template_import_katalog_produk.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate Excel cheat-sheet listing all existing products with their SKU, Name, Category, Photo status,
     * and recommended photo file name for easy renaming/copy-pasting.
     */
    public function generateSkuPhotoCheatSheet(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'sku_photo_list_').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($tempPath);

        // Header style: Deep Navy Blue, White bold font
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('1E40AF')
            ->withFontSize(11);

        $headers = [
            'KODE BARANG (SKU) - TINGGAL COPAS',
            'NAMA PRODUK',
            'KATEGORI',
            'MEREK',
            'STATUS FOTO SAAT INI',
            'CONTOH NAMA FILE FOTO',
        ];

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        $produks = Produk::with(['kategori', 'merek'])
            ->orderBy('kategori_id')
            ->orderBy('kode_katalog')
            ->get();

        $rowStyle = (new Style)->withFontSize(10);

        foreach ($produks as $p) {
            $hasPhoto = filled($p->foto);
            $photoStatus = $hasPhoto ? 'SUDAH ADA FOTO' : 'BELUM ADA FOTO (PRIORITAS)';
            $recommendedFileName = "{$p->kode_katalog}.jpg";

            $writer->addRow(Row::fromValuesWithStyle([
                $p->kode_katalog,
                $p->nama,
                $p->kategori?->nama_kategori ?? '-',
                $p->merek?->nama_merek ?? '-',
                $photoStatus,
                $recommendedFileName,
            ], $rowStyle));
        }

        $writer->close();

        return response()->download(
            $tempPath,
            'daftar_sku_foto_produk_'.now()->format('Ymd_His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * Preview products from an uploaded file (.xlsx or .csv) without modifying the database.
     *
     * @return array{
     *     total_rows: int,
     *     new_count: int,
     *     update_count: int,
     *     skip_count: int,
     *     error_count: int,
     *     errors: array<int, string>,
     *     preview_rows: array<int, array<string, mixed>>,
     *     has_more: bool
     * }
     */
    public function preview(string|UploadedFile $file, bool $updateExisting = true): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'total_rows' => 0,
                'new_count' => 0,
                'update_count' => 0,
                'skip_count' => 0,
                'error_count' => 1,
                'errors' => ['File tidak dapat dibaca atau tidak ditemukan.'],
                'preview_rows' => [],
                'has_more' => false,
            ];
        }

        // Read rows from XLSX or CSV
        if ($extension === 'csv' || $extension === 'txt') {
            $rows = $this->readCsvRows($filePath);
        } else {
            $rows = $this->readXlsxRows($filePath);
        }

        if (empty($rows)) {
            return [
                'total_rows' => 0,
                'new_count' => 0,
                'update_count' => 0,
                'skip_count' => 0,
                'error_count' => 1,
                'errors' => ['File Excel/CSV kosong atau tidak memiliki baris data.'],
                'preview_rows' => [],
                'has_more' => false,
            ];
        }

        // Detect header row
        $rawHeaders = array_shift($rows);
        $headerMap = $this->resolveHeaderMap($rawHeaders);

        if (! isset($headerMap['kode_katalog']) || ! isset($headerMap['nama'])) {
            return [
                'total_rows' => 0,
                'new_count' => 0,
                'update_count' => 0,
                'skip_count' => 0,
                'error_count' => 1,
                'errors' => ['Kolom "KODE BARANG" dan "NAMA PRODUK" wajib ada di baris pertama lembar kerja Excel.'],
                'preview_rows' => [],
                'has_more' => false,
            ];
        }

        $existingCatalogCodes = Produk::pluck('id', 'kode_katalog')->all();
        $canViewPurchasePrices = Auth::check() && Auth::user()->can('view_purchase_prices');

        $newCount = 0;
        $updateCount = 0;
        $skipCount = 0;
        $errors = [];
        $previewRows = [];
        $rowNumber = 1;

        foreach ($rows as $cells) {
            $rowNumber++;

            // Skip completely empty rows
            if (empty(array_filter($cells, fn ($v) => filled(trim((string) $v))))) {
                continue;
            }

            $data = [];
            foreach ($headerMap as $canonicalKey => $colIndex) {
                $data[$canonicalKey] = isset($cells[$colIndex]) ? trim((string) $cells[$colIndex]) : '';
            }

            $kodeKatalog = $data['kode_katalog'] ?? '';
            $nama = $data['nama'] ?? '';
            $kategoriIndukName = trim($data['kategori_induk'] ?? '');
            $subKategoriName = trim($data['sub_kategori'] ?? '');
            $legacyKategori = trim($data['kategori'] ?? '');
            if (filled($kategoriIndukName)) {
                $kategoriName = (filled($subKategoriName) && $subKategoriName !== '-') ? "{$kategoriIndukName} > {$subKategoriName}" : $kategoriIndukName;
            } else {
                $kategoriName = $legacyKategori;
            }
            $merekName = $data['merek'] ?? '';
            $ukuran = $data['ukuran'] ?? '';
            $satuanRaw = $data['satuan'] ?? '';
            $satuan = $this->normalizeSatuan($satuanRaw)->value;

            $hargaBeli = $this->cleanNumericValue($data['harga_beli'] ?? 0);
            $hargaJual = $this->cleanNumericValue($data['harga_jual'] ?? 0);
            $hargaAplikator = filled($data['harga_aplikator'] ?? null) ? $this->cleanNumericValue($data['harga_aplikator']) : null;
            $hargaGrosir = filled($data['harga_grosir'] ?? null) ? $this->cleanNumericValue($data['harga_grosir']) : null;
            $stokAwal = filled($data['stok_awal'] ?? null) ? $this->cleanNumericValue($data['stok_awal']) : 0.0;
            $stokMinimum = $this->cleanNumericValue($data['stok_minimum'] ?? 0);
            $panjangMeter = filled($data['panjang_meter'] ?? null) ? $this->cleanNumericValue($data['panjang_meter']) : null;
            $kemasan = filled($data['kemasan'] ?? null) ? trim((string) $data['kemasan']) : null;
            $isiPerKemasan = filled($data['isi_per_kemasan'] ?? null) ? $this->cleanNumericValue($data['isi_per_kemasan']) : null;
            $panjangPerRoll = filled($data['panjang_per_roll'] ?? null) ? $this->cleanNumericValue($data['panjang_per_roll']) : null;

            if (blank($kodeKatalog) || blank($nama)) {
                $errMsg = "Baris #{$rowNumber}: Kode Barang dan Nama Produk tidak boleh kosong.";
                $errors[] = $errMsg;
                $status = 'ERROR';
                $statusLabel = 'Data Tidak Valid';
                $statusBadge = 'danger';
                $statusNote = 'Kode atau Nama kosong';
            } elseif (isset($existingCatalogCodes[$kodeKatalog])) {
                if ($updateExisting) {
                    $updateCount++;
                    $status = 'UPDATE';
                    $statusLabel = 'Akan Diperbarui';
                    $statusBadge = 'primary';
                    $statusNote = 'Data produk lama akan diperbarui';
                } else {
                    $skipCount++;
                    $status = 'SKIP';
                    $statusLabel = 'Dilewati';
                    $statusBadge = 'secondary';
                    $statusNote = 'Sudah ada (update dimatikan)';
                }
            } else {
                $newCount++;
                $status = 'NEW';
                $statusLabel = 'Baru';
                $statusBadge = 'success';
                $statusNote = 'Produk baru akan ditambahkan';
                $existingCatalogCodes[$kodeKatalog] = true;
            }

            $previewRows[] = [
                'row_number' => $rowNumber,
                'kode_katalog' => $kodeKatalog,
                'nama' => $nama,
                'kategori' => $kategoriName,
                'merek' => $merekName,
                'ukuran' => $ukuran,
                'satuan' => $satuan,
                'harga_beli' => $canViewPurchasePrices ? $hargaBeli : 0.0,
                'harga_jual' => $hargaJual,
                'harga_aplikator' => $hargaAplikator,
                'harga_grosir' => $hargaGrosir,
                'stok_awal' => $stokAwal,
                'stok_minimum' => $stokMinimum,
                'panjang_meter' => $panjangMeter,
                'kemasan' => $kemasan,
                'isi_per_kemasan' => $isiPerKemasan,
                'panjang_per_roll' => $panjangPerRoll,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
                'status_note' => $statusNote,
            ];
        }

        $totalRows = count($previewRows);
        $hasMore = $totalRows > 50;

        return [
            'total_rows' => $totalRows,
            'new_count' => $newCount,
            'update_count' => $updateCount,
            'skip_count' => $skipCount,
            'error_count' => count($errors),
            'errors' => $errors,
            'preview_rows' => array_slice($previewRows, 0, 50),
            'has_more' => $hasMore,
        ];
    }

    /**
     * Import products from an uploaded file (.xlsx or .csv) with forgiving, smart normalization.
     *
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function import(string|UploadedFile $file, bool $updateExisting = true, ?int $userId = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File tidak dapat dibaca atau tidak ditemukan.'],
            ];
        }

        // Read rows from XLSX or CSV
        if ($extension === 'csv' || $extension === 'txt') {
            $rows = $this->readCsvRows($filePath);
        } else {
            $rows = $this->readXlsxRows($filePath);
        }

        if (empty($rows)) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File Excel/CSV kosong atau tidak memiliki baris data.'],
            ];
        }

        // Detect header row
        $rawHeaders = array_shift($rows);
        $headerMap = $this->resolveHeaderMap($rawHeaders);

        if (! isset($headerMap['kode_katalog']) || ! isset($headerMap['nama'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Kolom "KODE BARANG" dan "NAMA PRODUK" wajib ada di baris pertama lembar kerja Excel.'],
            ];
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $rowNumber = 1;

        // Cache categories and brands
        $categoriesMap = Kategori::all()->pluck('id', 'nama_kategori')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim((string) $name)) => $id];
        });

        $brandsMap = Merek::all()->pluck('id', 'nama_merek')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim((string) $name)) => $id];
        });

        // Resolve default warehouse for opening stock
        $defaultGudang = Gudang::where('id', 1)->first() ?? Gudang::first();
        if (! $defaultGudang) {
            $defaultGudang = Gudang::create([
                'nama_gudang' => 'Gudang Utama',
                'lokasi' => 'Pusat Toko',
                'created_by' => $userId ?? 1,
            ]);
        }
        $stockService = app(StockService::class);
        $canViewPurchasePrices = Auth::check() && Auth::user()->can('view_purchase_prices');

        DB::beginTransaction();

        try {
            foreach ($rows as $cells) {
                $rowNumber++;

                // Skip blank rows
                if (empty(array_filter($cells, fn ($v) => filled(trim((string) $v))))) {
                    continue;
                }

                $data = [];
                foreach ($headerMap as $canonicalKey => $colIndex) {
                    $data[$canonicalKey] = isset($cells[$colIndex]) ? trim((string) $cells[$colIndex]) : '';
                }

                $kodeKatalog = $data['kode_katalog'] ?? '';
                $nama = $data['nama'] ?? '';
                $kategoriIndukName = trim($data['kategori_induk'] ?? '');
                $subKategoriName = trim($data['sub_kategori'] ?? '');
                $legacyKategoriName = trim($data['kategori'] ?? '');
                $satuanRaw = $data['satuan'] ?? '';

                if (blank($kodeKatalog) || blank($nama)) {
                    $errors[] = "Baris #{$rowNumber}: Kode Barang dan Nama Produk tidak boleh kosong.";

                    continue;
                }

                // Resolve legacy single column "Induk > Sub" or fallback
                if (blank($kategoriIndukName) && filled($legacyKategoriName)) {
                    if (str_contains($legacyKategoriName, '>')) {
                        $parts = array_map('trim', explode('>', $legacyKategoriName, 2));
                        $kategoriIndukName = $parts[0] ?? '';
                        $subKategoriName = $parts[1] ?? '';
                    } else {
                        $kategoriIndukName = $legacyKategoriName;
                    }
                }

                $kategoriId = null;
                if (filled($kategoriIndukName)) {
                    $kategoriId = $this->resolveOrCreateCategoryHierarchical($kategoriIndukName, $subKategoriName, $userId);
                }

                if (! $kategoriId) {
                    $errors[] = "Baris #{$rowNumber} ({$kodeKatalog}): Kategori Induk wajib diisi.";

                    continue;
                }

                // Resolve or create brand
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

                // Smart unit normalization
                $satuan = $this->normalizeSatuan($satuanRaw);

                // Smart numeric cleaning (handles "Rp 35.000", "35.000", "35000", etc.)
                $hargaBeli = $this->cleanNumericValue($data['harga_beli'] ?? 0);
                $hargaJual = $this->cleanNumericValue($data['harga_jual'] ?? 0);
                $hargaAplikator = filled($data['harga_aplikator'] ?? '') ? $this->cleanNumericValue($data['harga_aplikator']) : null;
                $hargaGrosir = filled($data['harga_grosir'] ?? '') ? $this->cleanNumericValue($data['harga_grosir']) : null;
                $stokAwal = filled($data['stok_awal'] ?? '') ? $this->cleanNumericValue($data['stok_awal']) : 0.0;
                $stokMinimum = filled($data['stok_minimum'] ?? '') ? $this->cleanNumericValue($data['stok_minimum']) : 0.0;
                $panjangMeter = filled($data['panjang_meter'] ?? '') ? $this->cleanNumericValue($data['panjang_meter']) : null;
                $panjangRoll = filled($data['panjang_per_roll'] ?? '') ? $this->cleanNumericValue($data['panjang_per_roll']) : null;

                $isRollItem = ($panjangRoll && $panjangRoll > 0) ||
                    str_contains(strtolower($nama), 'foil') ||
                    str_contains(strtolower($nama), 'lakban') ||
                    str_contains(strtolower($nama), 'kasa') ||
                    strcasecmp(trim((string) ($data['kemasan'] ?? '')), 'roll') === 0;

                // Auto-detect length if not provided and product is in meters (only for Plafon PVC, NOT for roll items):
                if ($panjangMeter === null && $satuan === SatuanProduk::Meter && ! $isRollItem) {
                    $text = ($data['ukuran'] ?? '').' '.$nama.' '.$kodeKatalog;
                    if (preg_match('/(\d+(?:\.\d+)?)\s*m\b/i', $text, $matches)) {
                        $val = (float) $matches[1];
                        if ($val >= 1 && $val <= 12) {
                            $panjangMeter = $val;
                        }
                    }
                }

                $kemasan = filled($data['kemasan'] ?? '') ? trim((string) $data['kemasan']) : null;
                if ($kemasan === null) {
                    if ($isRollItem) {
                        $kemasan = 'Roll';
                    } else {
                        $kemasan = match ($satuan) {
                            SatuanProduk::Meter => 'Dus',
                            SatuanProduk::Batang => 'Ikat',
                            SatuanProduk::Dus => 'Dus',
                            SatuanProduk::Zak => 'Sak',
                            default => null,
                        };
                    }
                }

                $isiPerKemasan = filled($data['isi_per_kemasan'] ?? '') ? $this->cleanNumericValue($data['isi_per_kemasan']) : null;
                if ($isiPerKemasan === null) {
                    if ($isRollItem && $panjangRoll && $panjangRoll > 0) {
                        $isiPerKemasan = $panjangRoll;
                    } else {
                        $isiPerKemasan = match ($satuan) {
                            SatuanProduk::Meter => $isRollItem ? null : 15.00,
                            SatuanProduk::Batang => 20.00,
                            SatuanProduk::Dus => 1000.00,
                            default => null,
                        };
                    }
                }

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
                    'panjang_meter' => $panjangMeter,
                    'kemasan' => $kemasan,
                    'isi_per_kemasan' => $isiPerKemasan,
                    'panjang_per_roll' => $panjangRoll,
                ];

                $existing = Produk::withTrashed()->where('kode_katalog', $kodeKatalog)->first();
                $targetProduct = null;
                $isNewProduct = false;

                if ($existing) {
                    if ($updateExisting) {
                        $productPayload['updated_by'] = $userId;
                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        // Protect existing HPP: If unauthorized, or if Excel cell is '-', blank, or 0 while DB has value
                        $rawHargaBeli = trim((string) ($data['harga_beli'] ?? ''));
                        if (! $canViewPurchasePrices || $rawHargaBeli === '-' || $rawHargaBeli === '' || ($hargaBeli <= 0 && (float) $existing->harga_beli > 0)) {
                            $productPayload['harga_beli'] = (float) $existing->harga_beli;
                        }

                        $oldPrices = $existing->only(PriceHistoryService::PRICE_FIELDS);
                        $existing->update($productPayload);

                        app(PriceHistoryService::class)->recordProductPriceChanges(
                            produk: $existing,
                            oldValues: $oldPrices,
                            newValues: $existing->only(PriceHistoryService::PRICE_FIELDS),
                            sumber: 'import_excel',
                            referensi: $originalName ?: 'Import Excel/CSV',
                            referensiType: 'produk',
                            referensiId: $existing->id,
                            keterangan: 'Pembaruan harga massal via Import Excel/CSV',
                            userId: $userId
                        );

                        $targetProduct = $existing;
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    if (! $canViewPurchasePrices) {
                        $productPayload['harga_beli'] = 0;
                    }
                    $productPayload['created_by'] = $userId;
                    $productPayload['updated_by'] = $userId;
                    $targetProduct = Produk::create($productPayload);
                    $isNewProduct = true;
                    $createdCount++;
                }

                // Initialize opening stock safely via StockService with audit mutation
                if ($targetProduct && $stokAwal > 0 && $defaultGudang) {
                    $currentStock = $stockService->getStock($targetProduct, $defaultGudang);
                    if ($isNewProduct || $currentStock == 0) {
                        $stockService->apply(
                            produk: $targetProduct,
                            gudang: $defaultGudang,
                            mutationQty: $stokAwal,
                            jenis: JenisMutasiStok::PenyesuaianOpname,
                            referensi: 'Saldo Awal Setup Excel',
                            referensiType: null,
                            referensiId: null,
                            userId: $userId
                        );
                    }
                }
            }

            DB::commit();

            // Record audit activity log for catalog import
            $this->activityLog->log(
                action: 'import',
                description: "Import massal produk dari Excel/CSV: {$createdCount} produk baru, {$updatedCount} diperbarui, {$skippedCount} dilewati.",
                subject: null,
                oldValues: null,
                newValues: null,
                properties: [
                    'file_name' => $originalName,
                    'created_count' => $createdCount,
                    'updated_count' => $updatedCount,
                    'skipped_count' => $skippedCount,
                    'errors_count' => count($errors),
                ],
                userId: $userId
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = 'Terjadi kendala saat import: '.$e->getMessage();
        }

        return [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Read all rows from XLSX file targeting the "Data Produk" sheet if multi-sheet.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function readXlsxRows(string $filePath): array
    {
        $reader = new XlsxReader;
        $reader->open($filePath);

        $rows = [];
        $targetSheet = null;

        // Search for sheet named "Data Produk", "Produk", or "Sheet1"
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetName = strtolower(trim($sheet->getName()));
            if ($sheetName === 'data produk' || $sheetName === 'produk' || $sheetName === 'sheet1') {
                $targetSheet = $sheet;
                break;
            }
        }

        // Fallback: Use the very first sheet
        if (! $targetSheet) {
            foreach ($reader->getSheetIterator() as $sheet) {
                $targetSheet = $sheet;
                break;
            }
        }

        if ($targetSheet) {
            foreach ($targetSheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }

        $reader->close();

        return $rows;
    }

    /**
     * Read all rows from CSV file with auto delimiter detection.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function readCsvRows(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return [];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        // Strip BOM and detect delimiter
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Match raw user header row against alias dictionary.
     *
     * @param  array<int, mixed>  $rawHeaders
     * @return array<string, int> Map of canonicalKey => columnIndex
     */
    protected function resolveHeaderMap(array $rawHeaders): array
    {
        $map = [];

        foreach ($rawHeaders as $index => $raw) {
            $clean = strtolower(trim((string) $raw));
            $clean = preg_replace('/[^\w\s\(\)\/\-]/', '', $clean);

            foreach (self::HEADER_ALIASES as $canonicalKey => $aliases) {
                if (in_array($clean, $aliases, true)) {
                    $map[$canonicalKey] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Tolerant currency and numeric parser.
     * Handles "Rp 35.000", "Rp 35.000,50", "35000", "35.000", etc.
     */
    protected function cleanNumericValue(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if (blank($str)) {
            return 0.0;
        }

        // Remove "Rp", currency symbols, spaces
        $str = preg_replace('/[^\d.,\-]/', '', $str);

        // If string contains both dot and comma (e.g. 1.500,50 or 1,500.50)
        if (str_contains($str, '.') && str_contains($str, ',')) {
            if (strrpos($str, ',') > strrpos($str, '.')) {
                // Indonesian format: 1.500,50 -> 1500.50
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // US format: 1,500.50 -> 1500.50
                $str = str_replace(',', '', $str);
            }
        } elseif (str_contains($str, '.')) {
            // Check if dot is thousand separator (e.g. 35.000) or decimal (35.5)
            $parts = explode('.', $str);
            $lastPart = end($parts);
            if (strlen($lastPart) === 3 && count($parts) > 1) {
                // Likely thousand separator
                $str = str_replace('.', '', $str);
            }
        } elseif (str_contains($str, ',')) {
            // Could be comma decimal (e.g. 35,5) or thousand separator (35,000)
            $parts = explode(',', $str);
            $lastPart = end($parts);
            if (strlen($lastPart) === 3 && count($parts) > 1) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace(',', '.', $str);
            }
        }

        return is_numeric($str) ? (float) $str : 0.0;
    }

    /**
     * Map various user input variations to SatuanProduk enum.
     */
    protected function normalizeSatuan(string $raw): SatuanProduk
    {
        $val = strtolower(trim($raw));

        return match ($val) {
            'zak', 'sak', 'zk' => SatuanProduk::Zak,
            'dus', 'box', 'kotak', 'bx' => SatuanProduk::Dus,
            'pcs', 'buah', 'biji', 'pc', 'biji/pcs', 'roll', 'rol' => SatuanProduk::Pcs,
            'lembar', 'lbr', 'sheet' => SatuanProduk::Lembar,
            'batang', 'btg', 'lonjor' => SatuanProduk::Batang,
            'meter', 'm', 'mtr', 'm1', 'm2' => SatuanProduk::Meter,
            default => SatuanProduk::Pcs,
        };
    }

    /**
     * Set clean column widths for the Data sheet so columns are well-proportioned in Excel.
     */
    protected function applyDataSheetColumnWidths($sheet): void
    {
        $sheet->setColumnWidth(16, 1);  // KODE BARANG
        $sheet->setColumnWidth(40, 2);  // NAMA PRODUK
        $sheet->setColumnWidth(24, 3);  // KATEGORI INDUK
        $sheet->setColumnWidth(24, 4);  // SUB KATEGORI
        $sheet->setColumnWidth(16, 5);  // MEREK
        $sheet->setColumnWidth(22, 6);  // UKURAN / SPESIFIKASI
        $sheet->setColumnWidth(12, 7);  // SATUAN
        $sheet->setColumnWidth(18, 8);  // HARGA BELI (HPP)
        $sheet->setColumnWidth(18, 9);  // HARGA JUAL UMUM
        $sheet->setColumnWidth(12, 10); // STOK AWAL
        $sheet->setColumnWidth(14, 11); // STOK MINIMUM
        $sheet->setColumnWidth(18, 12); // PANJANG LEMBAR (M)
        $sheet->setColumnWidth(16, 13); // KEMASAN PABRIK
        $sheet->setColumnWidth(16, 14); // ISI PER KEMASAN
        $sheet->setColumnWidth(18, 15); // PANJANG ROLL (METER)
    }

    /**
     * Append "Petunjuk Pengisian" guide sheet to the workbook.
     */
    protected function appendPetunjukSheet(XlsxWriter $writer): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Petunjuk Pengisian');
        $sheet->setColumnWidth(25, 1);
        $sheet->setColumnWidth(16, 2);
        $sheet->setColumnWidth(70, 3);

        $titleStyle = (new Style)->withFontBold(true)->withFontSize(13)->withFontColor('1E3A8A');
        $sectionStyle = (new Style)->withFontBold(true)->withFontSize(11)->withBackgroundColor('E0E7FF');
        $headerTableStyle = (new Style)->withFontBold(true)->withFontSize(10)->withFontColor(Color::WHITE)->withBackgroundColor('3B82F6');
        $bodyStyle = (new Style)->withFontSize(10);
        $boldBodyStyle = (new Style)->withFontSize(10)->withFontBold(true);

        $writer->addRow(Row::fromValuesWithStyle(['PETUNJUK PENGISIAN IMPORT KATALOG PRODUK'], $titleStyle));
        $writer->addRow(Row::fromValuesWithStyle(['Natural Gypsum & PVC by Pandakong - Standar Template Multi-Sheet'], $boldBodyStyle));
        $writer->addRow(Row::fromValues(['']));

        // Section 1
        $writer->addRow(Row::fromValuesWithStyle(['1. PANDUAN KOLOM PADA SHEET "DATA PRODUK"', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['NAMA KOLOM', 'SIFAT', 'PENJELASAN & CONTOH PENGISIAN'], $headerTableStyle));

        $petunjukColumns = [
            ['KODE BARANG', 'WAJIB', 'Kode SKU unik barang (misal: PVC-VF-001, HLW-KN-001). Tidak boleh ada yang kembar/duplikat.'],
            ['NAMA PRODUK', 'WAJIB', 'Nama lengkap material beserta spesifikasi motif atau tipe (misal: Plafon PVC Vifon Putih Glossy 4m).'],
            ['KATEGORI INDUK', 'WAJIB', 'Kategori utama / induk toko (misal: PVC Plafon & Dinding, Gypsum & Moulding, Rangka & Hollow, Aksesoris & Ricikan).'],
            ['SUB KATEGORI', 'OPSIONAL', 'Sub-kategori produk (misal: Seri Glossy, Papan Gypsum (Board), Hollow Galvanis). Bisa dicek di sheet "Daftar Referensi".'],
            ['MEREK', 'OPSIONAL', 'Merek produk (misal: VIFON, Jayaboard, Kencana, Aplus, BWS). Jika baru, otomatis didaftarkan ke sistem.'],
            ['UKURAN / SPESIFIKASI', 'OPSIONAL', 'Dimensi/ukuran fisik (misal: 4m x 20cm x 8mm, 2x4 Tebal 0.30mm, Dus 1000 pcs).'],
            ['SATUAN', 'WAJIB', 'Satuan dasar stok toko: meter, batang, lembar, pcs, dus, zak, roll. Wajib huruf kecil/standar.'],
            ['HARGA BELI (HPP)', 'WAJIB', 'Harga modal beli pokok dari pabrik/supplier per satuan dasar (hanya angka murni tanpa Rp/titik).'],
            ['HARGA JUAL UMUM', 'WAJIB', 'Harga jual standar untuk konsumen retail/umum per satuan dasar.'],
            ['STOK AWAL', 'OPSIONAL', 'Saldo stok fisik di Gudang Utama saat pertama kali input katalog (default 0).'],
            ['STOK MINIMUM', 'OPSIONAL', 'Ambang batas peringatan stok menipis pada sistem (default 10).'],
            ['PANJANG LEMBAR (M)', 'KHUSUS PVC', 'Panjang fisik lembaran dalam meter (misal: 3, 4, 5, 6). Wajib diisi untuk Plafon PVC agar kalkulator kasir presisi.'],
            ['KEMASAN PABRIK', 'OPSIONAL', 'Nama wadah dari pabrik: Dus, Ikat, Pallet, Sak, Roll.'],
            ['ISI PER KEMASAN', 'OPSIONAL', 'Isi fisik per kemasan (PVC: 15 lbr/dus; Hollow: 20 btg/ikat; Sekrup: 1000 pcs/dus; Gypsum: 80 lbr/pallet).'],
            ['PANJANG ROLL (METER)', 'KHUSUS ROLL', 'Panjang per gulungan dalam meter (Bubble Foil: 25, Lakban: 45). Hanya untuk barang gulungan.'],
        ];

        foreach ($petunjukColumns as $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $bodyStyle));
        }

        $writer->addRow(Row::fromValues(['']));

        // Section 2
        $writer->addRow(Row::fromValuesWithStyle(['2. PANDUAN PENGISIAN 5 JENIS BARANG MATERIAL UTAMA', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['JENIS BARANG', 'SATUAN DASAR', 'ATURAN KONVERSI FISIK & KEMASAN PABRIK'], $headerTableStyle));

        $petunjukArchetypes = [
            ['Plafon PVC (Meter Lari)', 'meter', 'Isi Panjang Lembar = 4 (atau sesuai ukuran). Kemasan Pabrik = Dus. Isi Kemasan = 15. Kasir otomatis mengalikan lembar x panjang.'],
            ['Besi Hollow / Rangka (Batang)', 'batang', 'Satuan = batang. Panjang Lembar = 4 (opsional info). Kemasan Pabrik = Ikat. Isi Kemasan = 20. Pembelian PO otomatis hitung ikat.'],
            ['Papan Gypsum / GRC (Lembar)', 'lembar', 'Satuan = lembar. Kemasan Pabrik = Pallet. Isi Kemasan = 80 atau 100.'],
            ['Semen Kompon / Perekat (Zak)', 'zak', 'Satuan = zak. Kemasan Pabrik = Sak. Isi Kemasan = 1.'],
            ['Sekrup & Baut Dus (Dus Utuh)', 'dus', 'Satuan = dus. Kemasan Pabrik = Dus. Isi Kemasan = 1000 (atau 100 untuk Angle Clip/Fischer). Beli/jual dihitung per dus utuh.'],
            ['Insulasi / Bubble Foil (Roll)', 'meter atau roll', 'Satuan = meter (atau roll). Kemasan = Roll. Panjang Roll = 25 atau 50.'],
        ];

        foreach ($petunjukArchetypes as $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $bodyStyle));
        }

        $writer->addRow(Row::fromValues(['']));

        // Section 3
        $writer->addRow(Row::fromValuesWithStyle(['3. CARA MENGATUR HARGA TUKANG (APLIKATOR) & TOKO (GROSIR)', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle([
            'INFO DISKON TERPUSAT',
            'PENTING',
            'Harga khusus Tukang/Pemborong dan Toko/Langganan tidak perlu diketik satu per satu per produk. Cukup atur persentase diskon di menu "Penjualan > Pengaturan Diskon" di web. Kasir otomatis menghitung harga khusus saat memilih nama pelanggan!',
        ], $bodyStyle));
    }

    /**
     * Append "Daftar Referensi" lookup sheet to the workbook.
     */
    protected function appendReferensiSheet(XlsxWriter $writer): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Daftar Referensi');
        $sheet->setColumnWidth(26, 1);
        $sheet->setColumnWidth(26, 2);
        $sheet->setColumnWidth(35, 3);

        $sectionStyle = (new Style)->withFontBold(true)->withFontSize(11)->withBackgroundColor('FEF3C7');
        $headerTableStyle = (new Style)->withFontBold(true)->withFontSize(10)->withFontColor(Color::WHITE)->withBackgroundColor('D97706');
        $bodyStyle = (new Style)->withFontSize(10);

        // Section 1: Kategori
        $writer->addRow(Row::fromValuesWithStyle(['DAFTAR KATEGORI INDUK & SUB-KATEGORI RESMI (BISA DI-COPAS)', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['KATEGORI INDUK', 'SUB KATEGORI', 'STATUS DI DATABASE'], $headerTableStyle));

        $rootKategoris = Kategori::root()->with('children')->orderBy('nama_kategori')->get();
        if ($rootKategoris->isNotEmpty()) {
            foreach ($rootKategoris as $root) {
                if ($root->children->isNotEmpty()) {
                    foreach ($root->children as $child) {
                        $writer->addRow(Row::fromValuesWithStyle([$root->nama_kategori, $child->nama_kategori, 'Sub-Kategori Aktif'], $bodyStyle));
                    }
                } else {
                    $writer->addRow(Row::fromValuesWithStyle([$root->nama_kategori, '-', 'Kategori Utama Tanpa Sub'], $bodyStyle));
                }
            }
        }

        $writer->addRow(Row::fromValues(['']));

        // Section 2: Merek
        $writer->addRow(Row::fromValuesWithStyle(['DAFTAR MEREK TERDAFTAR (BISA DI-COPAS)', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['NAMA MEREK', 'KETERANGAN', 'STATUS'], $headerTableStyle));

        $mereks = Merek::orderBy('nama_merek')->get();
        if ($mereks->isNotEmpty()) {
            foreach ($mereks as $m) {
                $writer->addRow(Row::fromValuesWithStyle([$m->nama_merek, 'Merek Terdaftar', 'Aktif'], $bodyStyle));
            }
        }

        $writer->addRow(Row::fromValues(['']));

        // Section 3: Satuan
        $writer->addRow(Row::fromValuesWithStyle(['DAFTAR SATUAN RESMI YANG DIDUKUNG', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['KODE SATUAN', 'LABEL SATUAN', 'CONTOH BARANG'], $headerTableStyle));

        foreach (SatuanProduk::cases() as $satuan) {
            $writer->addRow(Row::fromValuesWithStyle([$satuan->value, $satuan->label(), $satuan->description()], $bodyStyle));
        }
    }

    /**
     * Resolve or create category hierarchy (Parent Root & Child Sub).
     */
    protected function resolveOrCreateCategoryHierarchical(string $indukName, ?string $subName, ?int $userId): int
    {
        $indukName = trim($indukName);
        $subName = filled($subName) && $subName !== '-' ? trim($subName) : null;
        $cacheKey = strtolower($indukName).'|'.strtolower($subName ?? '');

        if (isset($this->resolvedCategoryCache[$cacheKey])) {
            return $this->resolvedCategoryCache[$cacheKey];
        }

        // 1. Resolve or create root category
        $indukKey = strtolower($indukName);
        $rootKategori = Kategori::whereNull('parent_id')
            ->whereRaw('LOWER(nama_kategori) = ?', [$indukKey])
            ->first();

        if (! $rootKategori) {
            $rootKategori = Kategori::create([
                'parent_id' => null,
                'nama_kategori' => $indukName,
                'created_by' => $userId ?? 1,
            ]);
        }

        // 2. Resolve or create sub-category if provided
        if ($subName) {
            $subKey = strtolower($subName);
            $childKategori = Kategori::where('parent_id', $rootKategori->id)
                ->whereRaw('LOWER(nama_kategori) = ?', [$subKey])
                ->first();

            if (! $childKategori) {
                $childKategori = Kategori::create([
                    'parent_id' => $rootKategori->id,
                    'nama_kategori' => $subName,
                    'created_by' => $userId ?? 1,
                ]);
            }

            $resultId = $childKategori->id;
        } else {
            $resultId = $rootKategori->id;
        }

        $this->resolvedCategoryCache[$cacheKey] = $resultId;

        return $resultId;
    }
}
