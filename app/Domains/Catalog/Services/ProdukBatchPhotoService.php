<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Produk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ProdukBatchPhotoService
{
    /**
     * Allowed image extensions for product photos.
     *
     * @var array<int, string>
     */
    protected const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        protected ImageOptimizationService $imageService,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Synchronize product photos from an uploaded or local ZIP file.
     *
     * @return array{
     *     matched: int,
     *     unmatched: int,
     *     ignored: int,
     *     unmatched_files: array<int, string>,
     *     errors: array<int, string>
     * }
     */
    public function syncFromZip(UploadedFile|string $zipFile, ?int $userId = null): array
    {
        $filePath = $zipFile instanceof UploadedFile ? $zipFile->getRealPath() : $zipFile;
        $originalName = $zipFile instanceof UploadedFile ? $zipFile->getClientOriginalName() : basename($zipFile);

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'matched' => 0,
                'unmatched' => 0,
                'ignored' => 0,
                'unmatched_files' => [],
                'errors' => ["File ZIP tidak dapat ditemukan atau dibaca: {$originalName}"],
            ];
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($filePath);

        if ($openResult !== true) {
            return [
                'matched' => 0,
                'unmatched' => 0,
                'ignored' => 0,
                'unmatched_files' => [],
                'errors' => ["Gagal membuka file arsip ZIP (Kode Error: {$openResult}). Pastikan file berupa .zip yang valid."],
            ];
        }

        // Temporary unique directory
        $tempDir = storage_path('app'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'zip_batch_'.uniqid());
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip->extractTo($tempDir);
        $zip->close();

        try {
            $result = $this->processDirectory($tempDir, $userId, $originalName);
        } finally {
            // Clean up temporary extracted files
            $this->deleteDirectory($tempDir);
        }

        return $result;
    }

    /**
     * Synchronize product photos from a local folder path.
     *
     * @return array{
     *     matched: int,
     *     unmatched: int,
     *     ignored: int,
     *     unmatched_files: array<int, string>,
     *     errors: array<int, string>
     * }
     */
    public function syncFromDirectory(string $directoryPath, ?int $userId = null): array
    {
        if (! is_dir($directoryPath) || ! is_readable($directoryPath)) {
            return [
                'matched' => 0,
                'unmatched' => 0,
                'ignored' => 0,
                'unmatched_files' => [],
                'errors' => ["Direktori tidak ditemukan atau tidak dapat dibaca: {$directoryPath}"],
            ];
        }

        return $this->processDirectory($directoryPath, $userId, basename($directoryPath));
    }

    /**
     * Recursively scan directory and match photo files against product catalog codes (SKU).
     *
     * @return array{
     *     matched: int,
     *     unmatched: int,
     *     ignored: int,
     *     unmatched_files: array<int, string>,
     *     errors: array<int, string>
     * }
     */
    protected function processDirectory(string $dir, ?int $userId, string $sourceName): array
    {
        $matched = 0;
        $unmatched = 0;
        $ignored = 0;
        $unmatchedFiles = [];
        $errors = [];

        // Preload active products index into lookup map by lowercase SKU
        $produkMap = Produk::withTrashed()
            ->get(['id', 'kode_katalog', 'foto'])
            ->keyBy(fn (Produk $p) => strtolower(trim($p->kode_katalog)));

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $realPath = $item->getRealPath();
            $filename = $item->getFilename();

            // Ignore system/junk artifacts (macOS, Windows, hidden files)
            if (
                str_starts_with($filename, '.')
                || str_contains($realPath, '__MACOSX')
                || strcasecmp($filename, 'Thumbs.db') === 0
                || strcasecmp($filename, 'desktop.ini') === 0
            ) {
                $ignored++;

                continue;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $ignored++;

                continue;
            }

            // Extract SKU from base filename without extension
            $sku = trim(pathinfo($filename, PATHINFO_FILENAME));
            $skuKey = strtolower($sku);

            if (! $produkMap->has($skuKey)) {
                $unmatched++;
                $unmatchedFiles[] = $filename;

                continue;
            }

            /** @var Produk $targetProduk */
            $targetProduk = $produkMap->get($skuKey);

            try {
                $newFotoPath = $this->imageService->optimizeAndStoreFromPath($realPath, 'produk', $extension);

                // If previous photo exists and is different, remove old file from disk
                if ($targetProduk->foto && $targetProduk->foto !== $newFotoPath) {
                    $this->imageService->delete($targetProduk->foto);
                }

                $targetProduk->update([
                    'foto' => $newFotoPath,
                    'updated_by' => $userId,
                ]);

                // Update in-memory map to reflect new photo
                $targetProduk->foto = $newFotoPath;
                $matched++;
            } catch (\Throwable $e) {
                $errors[] = "Gagal memproses file '{$filename}': ".$e->getMessage();
            }
        }

        // Record audit activity log
        $this->activityLog->log(
            action: 'batch_photo_sync',
            description: "Sinkronisasi massal foto produk: {$matched} berhasil ditautkan, {$unmatched} SKU tidak ditemukan.",
            subject: null,
            oldValues: null,
            newValues: null,
            properties: [
                'source' => $sourceName,
                'matched_count' => $matched,
                'unmatched_count' => $unmatched,
                'ignored_count' => $ignored,
                'unmatched_files' => array_slice($unmatchedFiles, 0, 50),
                'errors_count' => count($errors),
            ],
            userId: $userId
        );

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
            'ignored' => $ignored,
            'unmatched_files' => $unmatchedFiles,
            'errors' => $errors,
        ];
    }

    /**
     * Recursively delete a directory and its contents.
     */
    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        if (class_exists(File::class)) {
            File::deleteDirectory($dir);

            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
