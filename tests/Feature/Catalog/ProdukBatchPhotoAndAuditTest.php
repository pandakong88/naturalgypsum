<?php

use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Services\ProdukBatchPhotoService;
use App\Domains\Catalog\Services\ProdukExcelService;
use App\Livewire\Catalog\ProdukIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural PVC']);

    Storage::fake('public');
});

/**
 * Helper to generate a genuine small PNG image.
 */
function createDummyImageFile(string $path, int $width = 50, int $height = 50): void
{
    $img = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($img, 30, 58, 138);
    imagefill($img, 0, 0, $bg);
    imagepng($img, $path);
    imagedestroy($img);
}

test('batch photo service can match product photos by sku from zip archive', function () {
    $produk1 = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'PVC-PUTIH-01',
        'foto' => null,
    ]);

    $produk2 = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'GYP-JBN-09',
        'foto' => null,
    ]);

    // Create temporary images
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_batch_'.uniqid();
    mkdir($tempDir, 0755, true);

    $img1 = $tempDir.DIRECTORY_SEPARATOR.'pvc-putih-01.png'; // lowercase matching
    $img2 = $tempDir.DIRECTORY_SEPARATOR.'GYP-JBN-09.png'; // uppercase
    $imgUnknown = $tempDir.DIRECTORY_SEPARATOR.'NOT-EXIST-01.png'; // unmatched
    $junkFile = $tempDir.DIRECTORY_SEPARATOR.'Thumbs.db'; // ignored

    createDummyImageFile($img1);
    createDummyImageFile($img2);
    createDummyImageFile($imgUnknown);
    file_put_contents($junkFile, 'junk data');

    // Create ZIP
    $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'batch_photos_'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFile($img1, 'pvc-putih-01.png');
    $zip->addFile($img2, 'GYP-JBN-09.png');
    $zip->addFile($imgUnknown, 'NOT-EXIST-01.png');
    $zip->addFile($junkFile, 'Thumbs.db');
    $zip->close();

    $service = app(ProdukBatchPhotoService::class);
    $result = $service->syncFromZip($zipPath, $this->owner->id);

    expect($result['matched'])->toBe(2)
        ->and($result['unmatched'])->toBe(1)
        ->and($result['ignored'])->toBe(1)
        ->and($result['unmatched_files'])->toContain('NOT-EXIST-01.png');

    $produk1->refresh();
    $produk2->refresh();

    expect($produk1->foto)->not->toBeNull()
        ->and($produk1->foto)->toEndWith('.webp')
        ->and($produk2->foto)->not->toBeNull()
        ->and($produk2->foto)->toEndWith('.webp');

    // Verify activity log was recorded
    $log = ActivityLog::where('action', 'batch_photo_sync')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->owner->id)
        ->and($log->description)->toContain('2 berhasil ditautkan');

    // Clean up
    @unlink($img1);
    @unlink($img2);
    @unlink($imgUnknown);
    @unlink($junkFile);
    @rmdir($tempDir);
    @unlink($zipPath);
});

test('batch photo service can sync from local directory directly', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'LST-SUDUT-01',
        'foto' => null,
    ]);

    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_dir_'.uniqid();
    mkdir($tempDir, 0755, true);

    $img = $tempDir.DIRECTORY_SEPARATOR.'LST-SUDUT-01.png';
    createDummyImageFile($img);

    $service = app(ProdukBatchPhotoService::class);
    $result = $service->syncFromDirectory($tempDir, $this->owner->id);

    expect($result['matched'])->toBe(1)
        ->and($result['unmatched'])->toBe(0);

    $produk->refresh();
    expect($produk->foto)->not->toBeNull();

    @unlink($img);
    @rmdir($tempDir);
});

test('artisan catalog:sync-photos command executes successfully', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'CMD-SKU-99',
        'foto' => null,
    ]);

    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_cmd_'.uniqid();
    mkdir($tempDir, 0755, true);
    $img = $tempDir.DIRECTORY_SEPARATOR.'CMD-SKU-99.png';
    createDummyImageFile($img);

    $this->artisan('catalog:sync-photos', ['path' => $tempDir])
        ->expectsOutputToContain('Foto Berhasil Ditautkan')
        ->expectsOutputToContain('Sinkronisasi foto selesai')
        ->assertSuccessful();

    $produk->refresh();
    expect($produk->foto)->not->toBeNull();

    @unlink($img);
    @rmdir($tempDir);
});

test('livewire master produk supports batch photo sync tab and records audit log', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'LW-SYNC-01',
        'foto' => null,
    ]);

    // Create a real ZIP archive containing LW-SYNC-01.png
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lw_test_'.uniqid();
    mkdir($tempDir, 0755, true);
    $img = $tempDir.DIRECTORY_SEPARATOR.'LW-SYNC-01.png';
    createDummyImageFile($img);

    $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lw_batch_'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFile($img, 'LW-SYNC-01.png');
    $zip->close();

    $uploadedZip = UploadedFile::fake()->createWithContent('lw_batch.zip', (string) file_get_contents($zipPath));

    Livewire::actingAs($this->owner)
        ->test(ProdukIndex::class)
        ->call('openImportModal')
        ->assertSet('isImportModalOpen', true)
        ->assertSet('importTab', 'excel')
        ->call('setImportTab', 'photos')
        ->assertSet('importTab', 'photos')
        ->set('zipFile', $uploadedZip)
        ->call('syncPhotos')
        ->assertHasNoErrors()
        ->assertSet('photoSyncSummary.matched', 1);

    $produk->refresh();
    expect($produk->foto)->not->toBeNull();

    @unlink($img);
    @rmdir($tempDir);
    @unlink($zipPath);
});

test('master produk actions are fully recorded in activity_logs', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Papan Semen Board 6mm',
        'kode_katalog' => 'BRD-SEM-06',
    ]);

    // 1. Duplicate action
    Livewire::actingAs($this->owner)
        ->test(ProdukIndex::class)
        ->call('duplicate', $produk->id);

    $dupLog = ActivityLog::where('action', 'duplicate')->latest('id')->first();
    expect($dupLog)->not->toBeNull()
        ->and($dupLog->description)->toContain('Papan Semen Board 6mm');

    // 2. Barcode modal view
    Livewire::actingAs($this->owner)
        ->test(ProdukIndex::class)
        ->call('openBarcodeModal', $produk->id);

    $barcodeLog = ActivityLog::where('action', 'barcode_view')->latest('id')->first();
    expect($barcodeLog)->not->toBeNull()
        ->and($barcodeLog->description)->toContain('Papan Semen Board 6mm');

    // 3. Export Excel
    Livewire::actingAs($this->owner)
        ->test(ProdukIndex::class)
        ->call('exportExcel');

    $exportLog = ActivityLog::where('action', 'export')->latest('id')->first();
    expect($exportLog)->not->toBeNull()
        ->and($exportLog->description)->toContain('Mengekspor data katalog produk ke format Excel');
});

test('excel service import records activity log with summary', function () {
    $excelService = app(ProdukExcelService::class);

    // Create a temporary CSV
    $csvContent = "KODE BARANG,NAMA PRODUK,KATEGORI,MEREK,SATUAN,HARGA JUAL UMUM,STOK AWAL,STOK MINIMUM\n";
    $csvContent .= "CSV-LOG-01,Sekrup Gypsum 1in,Plafon PVC,Natural PVC,dus,45000,10,2\n";

    $csvPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_import_log_'.uniqid().'.csv';
    file_put_contents($csvPath, $csvContent);

    $summary = $excelService->import(
        file: $csvPath,
        updateExisting: true,
        userId: $this->owner->id
    );

    expect($summary['created'])->toBe(1);

    $importLog = ActivityLog::where('action', 'import')->latest('id')->first();
    expect($importLog)->not->toBeNull()
        ->and($importLog->user_id)->toBe($this->owner->id)
        ->and($importLog->description)->toContain('Import massal produk dari Excel/CSV')
        ->and($importLog->properties['created_count'])->toBe(1);

    @unlink($csvPath);
});

test('user can download sku photo cheat sheet excel file with photo status', function () {
    Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'SKU-CHEAT-01',
        'nama' => 'Plafon PVC Motif Teak Wood',
        'foto' => null,
    ]);

    Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'SKU-CHEAT-02',
        'nama' => 'Papan Semen 4mm',
        'foto' => 'produk/sample.webp',
    ]);

    Livewire::actingAs($this->owner)
        ->test(ProdukIndex::class)
        ->call('downloadSkuPhotoCheatSheet')
        ->assertFileDownloaded();

    $log = ActivityLog::where('action', 'export')
        ->where('description', 'like', '%Cheat-sheet%')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
});
