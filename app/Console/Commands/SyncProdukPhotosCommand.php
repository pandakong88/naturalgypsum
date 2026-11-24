<?php

namespace App\Console\Commands;

use App\Domains\Catalog\Services\ProdukBatchPhotoService;
use Illuminate\Console\Command;

class SyncProdukPhotosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:sync-photos
                            {path : Path absolut atau relatif ke file .zip atau direktori folder foto}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi massal foto produk berdasarkan kecocokan nama file dengan SKU';

    /**
     * Execute the console command.
     */
    public function handle(ProdukBatchPhotoService $batchService): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Path tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $this->info("Memulai sinkronisasi massal foto produk dari: {$path}");

        $isZip = is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip';

        if ($isZip) {
            $result = $batchService->syncFromZip($path);
        } elseif (is_dir($path)) {
            $result = $batchService->syncFromDirectory($path);
        } else {
            $this->error('Target harus berupa file .zip atau sebuah folder direktori.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Foto Berhasil Ditautkan', "<info>{$result['matched']}</info>"],
                ['SKU Tidak Ditemukan', "<comment>{$result['unmatched']}</comment>"],
                ['File Sistem/Non-Gambar Diabaikan', $result['ignored']],
                ['Total Kendala/Error', count($result['errors']) > 0 ? '<error>'.count($result['errors']).'</error>' : 0],
            ]
        );

        if (! empty($result['unmatched_files'])) {
            $this->newLine();
            $this->warn('Daftar file dengan SKU tidak ditemukan (Maks 20 sampel):');
            foreach (array_slice($result['unmatched_files'], 0, 20) as $file) {
                $this->line("  - {$file}");
            }
        }

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('Daftar kendala:');
            foreach ($result['errors'] as $err) {
                $this->line("  * {$err}");
            }
        }

        $this->newLine();
        $this->info('Sinkronisasi foto selesai!');

        return self::SUCCESS;
    }
}
