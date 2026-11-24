<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\StockOpname;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockOpnameExcelService
{
    /**
     * Standard column headers for Stock Opname count sheet template.
     *
     * @var array<int, string>
     */
    public const HEADERS = [
        'NO',
        'KODE BARANG',
        'NAMA PRODUK',
        'KATEGORI',
        'MEREK',
        'SATUAN SISTEM',
        'KEMASAN PABRIK',
        'ISI PER KEMASAN',
        'PANJANG (M)',
        'STOK SISTEM',
        'STOK FISIK (ISI DI SINI)',
        'CATATAN ITEM',
    ];

    /**
     * Standard column headers for Completed Stock Opname reconciliation results.
     *
     * @var array<int, string>
     */
    public const RECONCILIATION_HEADERS = [
        'NO',
        'NO. DOKUMEN',
        'KODE BARANG',
        'NAMA PRODUK',
        'KATEGORI',
        'MEREK',
        'SATUAN',
        'KEMASAN PABRIK',
        'STOK SISTEM',
        'STOK FISIK',
        'SELISIH (+/-)',
        'STATUS SELISIH',
        'CATATAN PENYESUAIAN',
    ];

    /**
     * Generate and download an Excel count sheet template for physical stock opname.
     *
     * @param  array<int, array{
     *     produk_id: int,
     *     nama_produk: string,
     *     kode_produk: string,
     *     satuan: string,
     *     stok_sistem: float|int|string,
     *     stok_fisik: float|int|string,
     *     catatan?: string|null,
     *     kategori?: string|null,
     *     merek?: string|null,
     *     kemasan?: string|null,
     *     isi_per_kemasan?: float|int|null,
     *     panjang_meter?: float|int|null
     * }>  $items
     */
    public function exportCountSheetTemplate(string $gudangNama, array $items, bool $blindMode = false): BinaryFileResponse
    {
        $safeGudang = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $gudangNama);
        $dateStr = now()->format('Ymd_His');
        $modeStr = $blindMode ? 'Blind' : 'Guided';
        $filename = "Form_Opname_{$safeGudang}_{$modeStr}_{$dateStr}.xlsx";
        $tempPath = tempnam(sys_get_temp_dir(), 'so_count_').'.xlsx';

        $options = new Options;
        $writer = new XlsxWriter($options);
        $writer->openToFile($tempPath);

        // Sheet 1: Lembar Hitung Fisik
        $sheet1 = $writer->getCurrentSheet();
        $sheet1->setName('Lembar Hitung Fisik');
        $this->applyCountSheetColumnWidths($sheet1);

        // Header Style: Deep Navy with bold white text
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('1E3A8A')
            ->withFontSize(11);

        $writer->addRow(Row::fromValuesWithStyle(self::HEADERS, $headerStyle));

        $dataStyle = (new Style)->withFontSize(10);

        foreach ($items as $index => $item) {
            $stokSistemDisplay = $blindMode ? '(Hitung Buta)' : (float) ($item['stok_sistem'] ?? 0);
            $stokFisikDisplay = $blindMode ? '' : (float) ($item['stok_fisik'] ?? $item['stok_sistem'] ?? 0);

            $isiPerKemasan = ! empty($item['isi_per_kemasan']) ? (float) $item['isi_per_kemasan'] : '-';
            $panjangMeter = ! empty($item['panjang_meter']) ? (float) $item['panjang_meter'] : '-';

            $rowValues = [
                $index + 1,
                $item['kode_produk'] ?? '-',
                $item['nama_produk'] ?? '-',
                $item['kategori'] ?? '-',
                $item['merek'] ?? '-',
                $item['satuan'] ?? 'Pcs',
                $item['kemasan'] ?? '-',
                $isiPerKemasan,
                $panjangMeter,
                $stokSistemDisplay,
                $stokFisikDisplay,
                $item['catatan'] ?? '',
            ];

            $writer->addRow(Row::fromValuesWithStyle($rowValues, $dataStyle));
        }

        // Sheet 2: Petunjuk & Panduan Opname
        $this->appendCountSheetGuideSheet($writer);

        // Sheet 3: Informasi Dokumen
        $this->appendCountSheetInfoSheet($writer, $gudangNama, count($items), $blindMode);

        $writer->close();

        return response()->download(
            $tempPath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * Generate and download an Excel workbook containing the official Stock Opname reconciliation results.
     */
    public function exportHasilOpname(StockOpname $stockOpname): BinaryFileResponse
    {
        $safeNo = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $stockOpname->no_stock_opname);
        $dateStr = now()->format('Ymd_His');
        $filename = "Hasil_Opname_{$safeNo}_{$dateStr}.xlsx";
        $tempPath = tempnam(sys_get_temp_dir(), 'so_result_').'.xlsx';

        $options = new Options;
        $writer = new XlsxWriter($options);
        $writer->openToFile($tempPath);

        // Sheet 1: Hasil Rekonsiliasi Opname
        $sheet1 = $writer->getCurrentSheet();
        $sheet1->setName('Hasil Rekonsiliasi');
        $this->applyHasilOpnameColumnWidths($sheet1);

        // Header style: Deep Navy, bold white
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('1E3A8A')
            ->withFontSize(11);

        $writer->addRow(Row::fromValuesWithStyle(self::RECONCILIATION_HEADERS, $headerStyle));

        $dataStyle = (new Style)->withFontSize(10);

        foreach ($stockOpname->details as $index => $d) {
            $p = $d->produk;
            $satLabel = $p?->satuan ? (is_object($p->satuan) ? $p->satuan->label() : (string) $p->satuan) : 'Pcs';
            $sel = (float) $d->selisih;
            $sis = (float) $d->stok_sistem;
            $fis = (float) $d->stok_fisik;

            if ($sel > 0) {
                $statusSelisih = 'SELISIH LEBIH (+)';
                $selisihFormatted = "+{$sel}";
            } elseif ($sel < 0) {
                $statusSelisih = 'SELISIH KURANG (-)';
                $selisihFormatted = (string) $sel;
            } else {
                $statusSelisih = 'SESUAI (COCOK)';
                $selisihFormatted = '0';
            }

            $kemasanDesc = $p?->kemasan ?? '-';
            if (! empty($p?->isi_per_kemasan)) {
                $kemasanDesc .= ' (isi '.(float) $p->isi_per_kemasan.')';
            }

            $rowValues = [
                $index + 1,
                $stockOpname->no_stock_opname,
                $p?->kode_katalog ?? '-',
                $p?->nama ?? '-',
                $p?->kategori?->nama_kategori ?? '-',
                $p?->merek?->nama_merek ?? '-',
                $satLabel,
                $kemasanDesc,
                $sis,
                $fis,
                $selisihFormatted,
                $statusSelisih,
                $d->catatan ?? '-',
            ];

            $writer->addRow(Row::fromValuesWithStyle($rowValues, $dataStyle));
        }

        // Sheet 2: Ringkasan Berita Acara
        $this->appendHasilOpnameSummarySheet($writer, $stockOpname);

        $writer->close();

        return response()->download(
            $tempPath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * Parse an uploaded Excel count sheet and update items array in-memory.
     *
     * @param  array<int, array{
     *     produk_id: int,
     *     nama_produk: string,
     *     kode_produk: string,
     *     satuan: string,
     *     stok_sistem: float|int|string,
     *     stok_fisik: float|int|string,
     *     catatan?: string|null,
     *     kemasan?: string|null,
     *     isi_per_kemasan?: float|int|null,
     *     panjang_meter?: float|int|null
     * }>  $currentItems
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     updated_count: int,
     *     unmatched_codes: array<int, string>,
     *     errors: array<int, string>
     * }
     */
    public function parseImportCountSheet(string|UploadedFile $file, array $currentItems): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'items' => $currentItems,
                'updated_count' => 0,
                'unmatched_codes' => [],
                'errors' => ['File spreadsheet tidak ditemukan atau tidak dapat dibaca.'],
            ];
        }

        $rows = [];
        try {
            $reader = new XlsxReader;
            $reader->open($filePath);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
                break; // read first sheet
            }

            $reader->close();
        } catch (\Throwable $e) {
            return [
                'items' => $currentItems,
                'updated_count' => 0,
                'unmatched_codes' => [],
                'errors' => ['Gagal membaca file Excel: '.$e->getMessage()],
            ];
        }

        if (empty($rows)) {
            return [
                'items' => $currentItems,
                'updated_count' => 0,
                'unmatched_codes' => [],
                'errors' => ['File Excel kosong atau tidak memiliki baris data.'],
            ];
        }

        $rawHeaders = array_shift($rows);
        $headerMap = $this->resolveHeaderMap($rawHeaders);

        if (! isset($headerMap['kode_barang']) || ! isset($headerMap['stok_fisik'])) {
            return [
                'items' => $currentItems,
                'updated_count' => 0,
                'unmatched_codes' => [],
                'errors' => ['Kolom "KODE BARANG" dan "STOK FISIK" wajib ada pada baris pertama file Excel.'],
            ];
        }

        // Build code to index map for fast matching
        $codeIndexMap = [];
        foreach ($currentItems as $index => $item) {
            $code = strtolower(trim((string) ($item['kode_produk'] ?? '')));
            if ($code !== '' && $code !== '-') {
                $codeIndexMap[$code] = $index;
            }
        }

        $updatedCount = 0;
        $unmatchedCodes = [];
        $errors = [];

        foreach ($rows as $rowIndex => $cells) {
            $rowNum = $rowIndex + 2;

            if (empty(array_filter($cells, fn ($c) => $c !== null && $c !== ''))) {
                continue; // skip blank rows
            }

            $kode = isset($cells[$headerMap['kode_barang']]) ? trim((string) $cells[$headerMap['kode_barang']]) : '';
            if ($kode === '' || $kode === '-') {
                continue;
            }

            $rawStokFisik = isset($cells[$headerMap['stok_fisik']]) ? $cells[$headerMap['stok_fisik']] : null;
            if ($rawStokFisik === null || trim((string) $rawStokFisik) === '') {
                continue; // unchanged / blank physical count
            }

            $stokFisik = $this->cleanNumericValue($rawStokFisik);
            if ($stokFisik === null || $stokFisik < 0) {
                $errors[] = "Baris #{$rowNum} ({$kode}): Nilai stok fisik '{$rawStokFisik}' tidak valid atau bernilai negatif.";

                continue;
            }

            $lookupKey = strtolower($kode);
            if (! isset($codeIndexMap[$lookupKey])) {
                $unmatchedCodes[] = $kode;

                continue;
            }

            $targetIndex = $codeIndexMap[$lookupKey];
            $currentItems[$targetIndex]['stok_fisik'] = $stokFisik;

            if (isset($headerMap['catatan'])) {
                $catatanVal = isset($cells[$headerMap['catatan']]) ? trim((string) $cells[$headerMap['catatan']]) : '';
                if ($catatanVal !== '') {
                    $currentItems[$targetIndex]['catatan'] = $catatanVal;
                }
            }

            $updatedCount++;
        }

        return [
            'items' => $currentItems,
            'updated_count' => $updatedCount,
            'unmatched_codes' => array_values(array_unique($unmatchedCodes)),
            'errors' => $errors,
        ];
    }

    /**
     * Map column headers to canonical keys.
     *
     * @param  array<int, mixed>  $rawHeaders
     * @return array<string, int>
     */
    protected function resolveHeaderMap(array $rawHeaders): array
    {
        $map = [];
        foreach ($rawHeaders as $index => $header) {
            $h = strtolower(trim((string) $header));

            if (str_contains($h, 'kode') || str_contains($h, 'sku')) {
                $map['kode_barang'] = $index;
            } elseif (str_contains($h, 'stok fisik') || str_contains($h, 'fisik') || str_contains($h, 'actual')) {
                $map['stok_fisik'] = $index;
            } elseif (str_contains($h, 'catatan') || str_contains($h, 'keterangan') || str_contains($h, 'note')) {
                $map['catatan'] = $index;
            }
        }

        return $map;
    }

    /**
     * Clean and normalize numeric input from Excel cells.
     */
    protected function cleanNumericValue(mixed $val): ?float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }

        if (! is_string($val)) {
            return null;
        }

        $s = trim($val);
        if ($s === '') {
            return null;
        }

        // Remove thousand separator dots or spaces if comma is used as decimal
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', $s);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Set proportional column widths for the count sheet so no text is clipped in Excel.
     */
    protected function applyCountSheetColumnWidths($sheet): void
    {
        $sheet->setColumnWidth(6, 1);   // NO
        $sheet->setColumnWidth(18, 2);  // KODE BARANG
        $sheet->setColumnWidth(40, 3);  // NAMA PRODUK
        $sheet->setColumnWidth(22, 4);  // KATEGORI
        $sheet->setColumnWidth(16, 5);  // MEREK
        $sheet->setColumnWidth(15, 6);  // SATUAN SISTEM
        $sheet->setColumnWidth(16, 7);  // KEMASAN PABRIK
        $sheet->setColumnWidth(16, 8);  // ISI PER KEMASAN
        $sheet->setColumnWidth(14, 9);  // PANJANG (M)
        $sheet->setColumnWidth(16, 10); // STOK SISTEM
        $sheet->setColumnWidth(26, 11); // STOK FISIK (ISI DI SINI)
        $sheet->setColumnWidth(32, 12); // CATATAN ITEM
    }

    /**
     * Set proportional column widths for the official reconciliation results sheet.
     */
    protected function applyHasilOpnameColumnWidths($sheet): void
    {
        $sheet->setColumnWidth(6, 1);   // NO
        $sheet->setColumnWidth(18, 2);  // NO. DOKUMEN
        $sheet->setColumnWidth(18, 3);  // KODE BARANG
        $sheet->setColumnWidth(40, 4);  // NAMA PRODUK
        $sheet->setColumnWidth(22, 5);  // KATEGORI
        $sheet->setColumnWidth(16, 6);  // MEREK
        $sheet->setColumnWidth(12, 7);  // SATUAN
        $sheet->setColumnWidth(20, 8);  // KEMASAN PABRIK
        $sheet->setColumnWidth(14, 9);  // STOK SISTEM
        $sheet->setColumnWidth(14, 10); // STOK FISIK
        $sheet->setColumnWidth(14, 11); // SELISIH (+/-)
        $sheet->setColumnWidth(20, 12); // STATUS SELISIH
        $sheet->setColumnWidth(32, 13); // CATATAN PENYESUAIAN
    }

    /**
     * Append "Petunjuk Pengisian" guide sheet to the workbook.
     */
    protected function appendCountSheetGuideSheet(XlsxWriter $writer): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Petunjuk Pengisian');
        $sheet->setColumnWidth(25, 1);
        $sheet->setColumnWidth(18, 2);
        $sheet->setColumnWidth(72, 3);

        $titleStyle = (new Style)->withFontBold(true)->withFontSize(13)->withFontColor('1E3A8A');
        $sectionStyle = (new Style)->withFontBold(true)->withFontSize(11)->withBackgroundColor('E0E7FF');
        $headerTableStyle = (new Style)->withFontBold(true)->withFontSize(10)->withFontColor(Color::WHITE)->withBackgroundColor('3B82F6');
        $bodyStyle = (new Style)->withFontSize(10);
        $boldBodyStyle = (new Style)->withFontSize(10)->withFontBold(true);

        $writer->addRow(Row::fromValuesWithStyle(['PANDUAN PENGISIAN LEMBAR HITUNG FISIK (COUNT SHEET)'], $titleStyle));
        $writer->addRow(Row::fromValuesWithStyle(['Natural Gypsum & Plafon PVC by Pandakong - Standar Template Multi-Sheet'], $boldBodyStyle));
        $writer->addRow(Row::fromValues(['']));

        // Section 1
        $writer->addRow(Row::fromValuesWithStyle(['1. PANDUAN PENGISIAN KOLOM EXCEL', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['NAMA KOLOM', 'SIFAT', 'PENJELASAN & CONTOH PENGISIAN'], $headerTableStyle));

        $guideColumns = [
            ['KODE BARANG', 'JANGAN UBAH', 'Kode acuan SKU unik barang. Wajib tetap ada agar sistem dapat mencocokkan produk secara otomatis saat di-import.'],
            ['NAMA PRODUK', 'REFERENSI', 'Nama material lengkap beserta varian spesifikasi produk.'],
            ['KATEGORI & MEREK', 'REFERENSI', 'Kelompok kategori dan merek dagang produk.'],
            ['SATUAN SISTEM', 'REFERENSI', 'Satuan dasar persediaan di aplikasi (meter, batang, lembar, pcs, dus, zak, roll).'],
            ['STOK SISTEM', 'INFORMASI', 'Jumlah saldo stok yang tercatat di database saat dokumen dibuat. Pada mode Blind Count kolom ini disembunyikan.'],
            ['STOK FISIK (ISI DI SINI)', 'WAJIB DIISI', 'Ketik angka murni hasil hitungan fisik riil di gudang (contoh: 120 atau 6.08). Jangan ketik teks/huruf.'],
            ['CATATAN ITEM', 'OPSIONAL', 'Tulis catatan kondisi fisik barang jika ada kejanggalan (contoh: "Dus rusak", "Barang patah di sudut", "Sisa di rak B2").'],
        ];

        foreach ($guideColumns as $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $bodyStyle));
        }

        $writer->addRow(Row::fromValues(['']));

        // Section 2
        $writer->addRow(Row::fromValuesWithStyle(['2. PANDUAN KONVERSI FISIK MATERIAL DI LAPANGAN', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['JENIS MATERIAL', 'SATUAN SISTEM', 'CARA HITUNG & KONVERSI FISIK'], $headerTableStyle));

        $archetypes = [
            ['Plafon PVC (Meter Lari)', 'meter', 'Satuan sistem adalah meter. Jika menghitung dus utuh (1 dus = 15 lbr @ 3m = 45m), ketik total meter (misal: 10 dus = 450). Lembaran eceran dikalikan panjang meter.'],
            ['Besi Hollow / Rangka (Batang)', 'batang', 'Satuan sistem adalah batang. 1 ikat kemasan pabrik = 20 batang. Contoh: 5 ikat utuh + 4 batang eceran = 104 batang.'],
            ['Papan Gypsum / GRC (Lembar)', 'lembar', 'Satuan sistem adalah lembar. 1 pallet pabrik = 80 atau 100 lembar. Contoh: 2 pallet @ 80 + 15 lembar = 175 lembar.'],
            ['Semen Kompon / Perekat (Zak)', 'zak', 'Satuan sistem adalah zak. Dihitung jumlah zak utuh.'],
            ['Paku / Sekrup Dus (Dus Utuh)', 'dus', 'Satuan sistem adalah dus. Jika ada dus terbuka, gunakan pecahan desimal (contoh: 6 dus utuh + 8 pcs pada dus isi 100 = 6.08 dus).'],
        ];

        foreach ($archetypes as $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $bodyStyle));
        }

        $writer->addRow(Row::fromValues(['']));

        // Section 3
        $writer->addRow(Row::fromValuesWithStyle(['3. CARA IMPOR KEMBALI KE SISTEM APLIKASI WEB', '', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle([
            'LANGKAH-LANGKAH',
            'PRAKTIS',
            '1. Simpan file Excel ini setelah mengisi kolom "STOK FISIK (ISI DI SINI)".'."\n".
            '2. Buka web aplikasi toko -> menu Stok Opname -> buka dokumen draft terkait.'."\n".
            '3. Klik tombol hijau "Import Excel" di atas tabel.'."\n".
            '4. Pilih file Excel ini lalu klik "Terapkan ke Draft".'."\n".
            '5. Seluruh angka stok fisik langsung terisi seketika dan siap diselesaikan!',
        ], $bodyStyle));
    }

    /**
     * Append metadata information sheet to count sheet workbook.
     */
    protected function appendCountSheetInfoSheet(XlsxWriter $writer, string $gudangNama, int $totalItems, bool $blindMode): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Informasi Dokumen');
        $sheet->setColumnWidth(28, 1);
        $sheet->setColumnWidth(55, 2);

        $sectionStyle = (new Style)->withFontBold(true)->withFontSize(11)->withBackgroundColor('E0E7FF');
        $headerTableStyle = (new Style)->withFontBold(true)->withFontSize(10)->withFontColor(Color::WHITE)->withBackgroundColor('1E3A8A');
        $bodyStyle = (new Style)->withFontSize(10);

        $writer->addRow(Row::fromValuesWithStyle(['INFORMASI DOKUMEN STOK OPNAME', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['PARAMETER', 'NILAI / KETERANGAN'], $headerTableStyle));

        $infoRows = [
            ['Nama Gudang', $gudangNama],
            ['Tanggal Form Dibuat', now()->format('d/m/Y H:i:s').' WIB'],
            ['Metode Pemeriksaan', $blindMode ? 'Blind Count (Hitung Buta - Angka Sistem Disembunyikan)' : 'Guided Count (Dengan Acuan Angka Sistem)'],
            ['Total Item Produk', $totalItems.' Produk'],
            ['Status Form', 'Draft Lembar Kerja Lapangan'],
            ['Format Spreadsheet', 'Multi-Sheet XLSX (OpenSpout v4)'],
        ];

        foreach ($infoRows as $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $bodyStyle));
        }
    }

    /**
     * Append metadata and KPI summary sheet to reconciliation results workbook.
     */
    protected function appendHasilOpnameSummarySheet(XlsxWriter $writer, StockOpname $stockOpname): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Ringkasan Berita Acara');
        $sheet->setColumnWidth(28, 1);
        $sheet->setColumnWidth(55, 2);

        $sectionStyle = (new Style)->withFontBold(true)->withFontSize(11)->withBackgroundColor('E0E7FF');
        $headerTableStyle = (new Style)->withFontBold(true)->withFontSize(10)->withFontColor(Color::WHITE)->withBackgroundColor('1E3A8A');
        $bodyStyle = (new Style)->withFontSize(10);

        $totItem = $stockOpname->details->count();
        $totCocok = $stockOpname->details->where('selisih', 0)->count();
        $totLebih = $stockOpname->details->filter(fn ($d) => (float) $d->selisih > 0)->count();
        $totKurang = $stockOpname->details->filter(fn ($d) => (float) $d->selisih < 0)->count();
        $netSelisih = (float) $stockOpname->details->sum('selisih');

        $writer->addRow(Row::fromValuesWithStyle(['RINGKASAN HASIL BERITA ACARA STOK OPNAME', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['PARAMETER', 'NILAI / KETERANGAN'], $headerTableStyle));

        $summaryRows = [
            ['Nomor Dokumen', $stockOpname->no_stock_opname],
            ['Tanggal Pelaksanaan', $stockOpname->tanggal ? $stockOpname->tanggal->format('d/m/Y') : '-'],
            ['Lokasi Gudang', $stockOpname->gudang->nama_gudang ?? '-'],
            ['Petugas Pelaksana', $stockOpname->user->name ?? '-'],
            ['Status Dokumen', $stockOpname->status->label()],
            ['Total Produk Diperiksa', $totItem.' Produk'],
            ['Produk Sesuai (Cocok)', $totCocok.' Produk'],
            ['Produk Selisih Lebih (+)', $totLebih.' Produk'],
            ['Produk Selisih Kurang (-)', $totKurang.' Produk'],
            ['Net Selisih Kuantitas', $netSelisih],
            ['Catatan Dokumen', $stockOpname->catatan ?? '-'],
            ['Waktu Selesai / Update', $stockOpname->updated_at ? $stockOpname->updated_at->format('d/m/Y H:i:s') : '-'],
        ];

        foreach ($summaryRows as $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $bodyStyle));
        }
    }
}
