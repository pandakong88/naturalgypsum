<?php

namespace App\Domains\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    /**
     * Maximum width or height for saved product photos.
     */
    protected const MAX_DIMENSION = 800;

    /**
     * WebP compression quality (0-100).
     */
    protected const QUALITY = 80;

    /**
     * Optimize an uploaded image and store it on the public disk.
     */
    public function optimizeAndStore(UploadedFile $file, string $directory = 'produk'): string
    {
        return $this->optimizeAndStoreFromPath($file->getRealPath(), $directory, $file->getClientOriginalExtension());
    }

    /**
     * Optimize an image file from a local path and store it on the public disk.
     */
    public function optimizeAndStoreFromPath(string $sourcePath, string $directory = 'produk', ?string $fallbackExtension = null): string
    {
        if (! file_exists($sourcePath) || ! is_readable($sourcePath)) {
            throw new \InvalidArgumentException("File gambar tidak ditemukan atau tidak dapat dibaca: {$sourcePath}");
        }

        $mime = @mime_content_type($sourcePath) ?: '';

        // Create GD image resource based on mime type or extension
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default => null,
        };

        if (! $image) {
            $ext = strtolower($fallbackExtension ?: pathinfo($sourcePath, PATHINFO_EXTENSION));
            $image = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
                'png' => @imagecreatefrompng($sourcePath),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
                default => null,
            };
        }

        $filename = Str::random(32).'.webp';
        $targetDirectory = trim($directory, '/');
        $fullDirectory = Storage::disk('public')->path($targetDirectory);

        if (! is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }

        $targetPath = $fullDirectory.DIRECTORY_SEPARATOR.$filename;
        $relativePath = $targetDirectory.'/'.$filename;

        // If GD image couldn't be created or webp is unsupported, fallback to file copy
        if (! $image) {
            $ext = strtolower($fallbackExtension ?: pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'jpg';
            $fallbackFilename = Str::random(32).'.'.$ext;
            $fallbackTargetPath = $fullDirectory.DIRECTORY_SEPARATOR.$fallbackFilename;
            copy($sourcePath, $fallbackTargetPath);

            return $targetDirectory.'/'.$fallbackFilename;
        }

        // Calculate aspect-ratio preserved dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            if ($width >= $height) {
                $newWidth = self::MAX_DIMENSION;
                $newHeight = (int) round(($height / $width) * self::MAX_DIMENSION);
            } else {
                $newHeight = self::MAX_DIMENSION;
                $newWidth = (int) round(($width / $height) * self::MAX_DIMENSION);
            }

            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Handle transparency for PNG/WebP
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);

            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resizedImage;
        }

        // Output to WebP
        if (function_exists('imagewebp')) {
            imagewebp($image, $targetPath, self::QUALITY);
            imagedestroy($image);

            return $relativePath;
        }

        // Fallback to JPEG if WebP output not available
        $filename = Str::random(32).'.jpg';
        $targetPath = $fullDirectory.DIRECTORY_SEPARATOR.$filename;
        imagejpeg($image, $targetPath, self::QUALITY);
        imagedestroy($image);

        return $targetDirectory.'/'.$filename;
    }

    /**
     * Delete an existing image from the public disk.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
