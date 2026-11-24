<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Services\ProdukExcelService;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Livewire\Catalog\ProdukIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->unauthorizedUser = User::factory()->create();

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural PVC']);
});

test('guest cannot access master produk index or detail', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
    ]);

    $this->get(route('master.produk.index'))->assertRedirect(route('login'));
    $this->get(route('master.produk.show', $produk->id))->assertRedirect(route('login'));
});

test('authorized user can view master produk list and detail', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'PVC Doff Putih 4m',
    ]);

    $this->actingAs($this->owner)
        ->get(route('master.produk.index'))
        ->assertOk()
        ->assertSee('Master Produk');

    $this->actingAs($this->owner)
        ->get(route('master.produk.show', $produk->id))
        ->assertOk()
        ->assertSee('PVC Doff Putih 4m');
});

test('user without view_purchase_prices cannot see harga beli on index or detail', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'PVC Motif Kayu',
        'harga_beli' => 77500,
        'harga_jual' => 95000,
    ]);

    // Staff Penjualan does NOT have view_purchase_prices permission
    $this->actingAs($this->staffPenjualan)
        ->get(route('master.produk.index'))
        ->assertOk()
        ->assertDontSee('77.500');

    $this->actingAs($this->staffPenjualan)
        ->get(route('master.produk.show', $produk->id))
        ->assertOk()
        ->assertDontSee('77.500')
        ->assertSee('Akses Terbatas');
});

test('authorized user can create a product with meter unit and panjang per roll', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProdukIndex::class)
        ->set('kategori_id', (string) $this->kategori->id)
        ->set('merek_id', (string) $this->merek->id)
        ->set('kode_katalog', 'PRD-PVC-001')
        ->set('nama', 'PVC Glossy Marble 4m')
        ->set('satuan', 'meter')
        ->set('ukuran', 'Lebar 20cm x Tebal 8mm')
        ->set('panjang_per_roll', 50.00)
        ->set('harga_beli', '15000')
        ->set('harga_jual', '22000')
        ->set('stok_minimum', '100')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('produk', [
        'kode_katalog' => 'PRD-PVC-001',
        'nama' => 'PVC Glossy Marble 4m',
        'satuan' => SatuanProduk::Meter->value,
        'panjang_per_roll' => 50.00,
        'harga_beli' => 15000.00,
        'harga_jual' => 22000.00,
        'stok_minimum' => 100.00,
        'created_by' => $this->owner->id,
    ]);
});

test('product creation with non-meter unit forces panjang_per_roll to null', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProdukIndex::class)
        ->set('kategori_id', (string) $this->kategori->id)
        ->set('merek_id', (string) $this->merek->id)
        ->set('kode_katalog', 'PRD-GYP-9MM')
        ->set('nama', 'Gypsum Board 9mm Standard')
        ->set('satuan', 'lembar')
        ->set('ukuran', '1200 x 2400 x 9mm')
        ->set('panjang_per_roll', 50.00) // should be stripped by action for non-meter
        ->set('harga_beli', '60000')
        ->set('harga_jual', '75000')
        ->set('stok_minimum', '20')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('produk', [
        'kode_katalog' => 'PRD-GYP-9MM',
        'satuan' => SatuanProduk::Lembar->value,
        'panjang_per_roll' => null,
    ]);
});

test('product creation validates required relationships and positive prices', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProdukIndex::class)
        ->set('kode_katalog', '')
        ->set('nama', '')
        ->set('kategori_id', '')
        ->set('merek_id', '')
        ->set('satuan', '')
        ->set('harga_beli', '-500')
        ->set('harga_jual', '-1000')
        ->set('stok_minimum', '-5')
        ->call('save')
        ->assertHasErrors(['kode_katalog', 'nama', 'kategori_id', 'satuan', 'harga_beli', 'harga_jual', 'stok_minimum']);
});

test('authorized user can edit product', function () {
    $this->actingAs($this->owner);
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
    ]);

    Livewire::test(ProdukIndex::class)
        ->call('openEditModal', $produk->id)
        ->set('nama', 'Nama Produk Diubah')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('produk', [
        'id' => $produk->id,
        'nama' => 'Nama Produk Diubah',
        'updated_by' => $this->owner->id,
    ]);
});

test('product can be soft deleted and restored', function () {
    $this->actingAs($this->owner);
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
    ]);

    Livewire::test(ProdukIndex::class)
        ->call('confirmDelete', $produk->id)
        ->call('delete');

    $this->assertSoftDeleted('produk', [
        'id' => $produk->id,
    ]);

    Livewire::test(ProdukIndex::class)
        ->call('restore', $produk->id);

    $this->assertDatabaseHas('produk', [
        'id' => $produk->id,
        'deleted_at' => null,
    ]);
});

test('creating or editing product does NOT directly create stock balances or mutations', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProdukIndex::class)
        ->set('kategori_id', (string) $this->kategori->id)
        ->set('merek_id', (string) $this->merek->id)
        ->set('kode_katalog', 'PRD-TEST-BOUND')
        ->set('nama', 'Produk Batas Fase')
        ->set('satuan', 'batang')
        ->set('harga_beli', '30000')
        ->set('harga_jual', '45000')
        ->set('stok_minimum', '10')
        ->call('save')
        ->assertHasNoErrors();

    $produk = Produk::where('kode_katalog', 'PRD-TEST-BOUND')->firstOrFail();

    // Verify product creation never directly writes to stock or mutation tables
    expect(Stok::where('produk_id', $produk->id)->count())->toEqual(0)
        ->and(MutasiStok::where('produk_id', $produk->id)->count())->toEqual(0);
});

test('authorized user can create a product with new units zak, dus, pcs and multi-tier pricing', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProdukIndex::class)
        ->set('kategori_id', (string) $this->kategori->id)
        ->set('merek_id', (string) $this->merek->id)
        ->set('kode_katalog', 'CMP-TEST-001')
        ->set('nama', 'Kompon Gypsum Bagus')
        ->set('satuan', 'zak')
        ->set('harga_beli', '50000')
        ->set('harga_jual', '65000')
        ->set('harga_aplikator', '60000')
        ->set('harga_grosir', '58000')
        ->set('stok_minimum', '20')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('produk', [
        'kode_katalog' => 'CMP-TEST-001',
        'satuan' => SatuanProduk::Zak->value,
        'harga_beli' => 50000.00,
        'harga_jual' => 65000.00,
        'harga_aplikator' => 60000.00,
        'harga_grosir' => 58000.00,
    ]);
});

test('generateKodeKatalog produces clean unique code based on category and brand', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(ProdukIndex::class)
        ->set('kategori_id', (string) $this->kategori->id)
        ->set('merek_id', (string) $this->merek->id)
        ->call('generateKodeKatalog');

    $generated = $component->get('kode_katalog');
    expect($generated)->toMatch('/^[A-Z0-9]+-[A-Z0-9]+-\d{3}$/');
});

test('produk resolves correct selling price for different customer types', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_jual' => 100000.00,
        'harga_aplikator' => 90000.00,
        'harga_grosir' => 85000.00,
    ]);

    expect($produk->getHargaUntukPelanggan(TipePelanggan::Umum))->toBe(100000.0)
        ->and($produk->getHargaUntukPelanggan(TipePelanggan::Pemborong))->toBe(90000.0)
        ->and($produk->getHargaUntukPelanggan(TipePelanggan::Langganan))->toBe(85000.0)
        ->and($produk->getHargaUntukPelanggan(null))->toBe(100000.0);

    // Fallback when tier price is null
    $produkTanpaTier = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_jual' => 50000.00,
        'harga_aplikator' => null,
        'harga_grosir' => null,
    ]);

    expect($produkTanpaTier->getHargaUntukPelanggan(TipePelanggan::Pemborong))->toBe(50000.0)
        ->and($produkTanpaTier->getHargaUntukPelanggan(TipePelanggan::Langganan))->toBe(50000.0);
});

test('product photo can be uploaded and is optimized to public disk', function () {
    Storage::fake('public');
    $this->actingAs($this->owner);

    $fakeImage = UploadedFile::fake()->image('motif_marmer.jpg', 1200, 800);

    Livewire::test(ProdukIndex::class)
        ->set('kategori_id', (string) $this->kategori->id)
        ->set('kode_katalog', 'PVC-IMG-001')
        ->set('nama', 'PVC Motif Marmer')
        ->set('satuan', 'meter')
        ->set('harga_beli', '15000')
        ->set('harga_jual', '22000')
        ->set('stok_minimum', '10')
        ->set('foto', $fakeImage)
        ->call('save')
        ->assertHasNoErrors();

    $produk = Produk::where('kode_katalog', 'PVC-IMG-001')->firstOrFail();
    expect($produk->foto)->not->toBeNull();
    Storage::disk('public')->assertExists($produk->foto);
});

test('authorized user can duplicate a product with cloned specs and newly generated code', function () {
    $this->actingAs($this->owner);

    $original = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Motif Bunga Emas',
        'ukuran' => '4m x 20cm x 8mm',
        'satuan' => SatuanProduk::Meter,
        'harga_beli' => 20000,
        'harga_jual' => 30000,
        'harga_aplikator' => 28000,
        'harga_grosir' => 26000,
        'stok_minimum' => 50,
        'panjang_per_roll' => 25.0,
    ]);

    $test = Livewire::test(ProdukIndex::class)
        ->call('duplicate', $original->id)
        ->assertSet('isFormOpen', true)
        ->assertSet('kategori_id', (string) $original->kategori_id)
        ->assertSet('merek_id', (string) $original->merek_id)
        ->assertSet('ukuran', '4m x 20cm x 8mm')
        ->assertSet('harga_jual', 30000.0)
        ->assertSet('harga_aplikator', 28000.0)
        ->assertSet('foto', null);

    expect($test->get('kode_katalog'))->not->toBe($original->kode_katalog);

    // Save duplicated product
    $test->call('save')->assertHasNoErrors();

    $this->assertDatabaseCount('produk', 2);
    $this->assertDatabaseHas('produk', [
        'nama' => 'Plafon PVC Motif Bunga Emas (Salinan)',
        'harga_jual' => 30000.00,
    ]);
});

test('user can switch tabs to view critical stock products and change perPage', function () {
    $this->actingAs($this->owner);

    $safeProduct = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'stok_minimum' => 10,
    ]);

    $criticalProduct = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'stok_minimum' => 50,
    ]);

    // Give safe product 100 stock in gudang
    $gudang = Gudang::factory()->create();
    Stok::create([
        'produk_id' => $safeProduct->id,
        'gudang_id' => $gudang->id,
        'jumlah' => 100,
    ]);

    // Critical product has 0 stock (< 50 minimum)

    Livewire::test(ProdukIndex::class)
        ->set('tabFilter', 'critical')
        ->assertSee($criticalProduct->nama)
        ->assertDontSee($safeProduct->nama)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});

test('user can export products and download template excel xlsx', function () {
    $this->actingAs($this->owner);

    Produk::factory()->count(3)->create([
        'kategori_id' => $this->kategori->id,
    ]);

    Livewire::test(ProdukIndex::class)
        ->call('exportExcel')
        ->assertFileDownloaded();

    Livewire::test(ProdukIndex::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('template_import_katalog_produk.xlsx');
});

test('user can import products with friendly indonesian headers and currency symbols', function () {
    $this->actingAs($this->owner);

    $existing = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'kode_katalog' => 'XLS-OLD-001',
        'nama' => 'Produk Lama',
        'harga_jual' => 50000,
    ]);

    // Test with friendly Indonesian headers and user-formatted numbers (Rp, dots, uppercase unit)
    $csvContent = implode("\n", [
        'KODE BARANG;NAMA PRODUK;KATEGORI;MEREK;UKURAN / SPESIFIKASI;SATUAN;HARGA BELI (HPP);HARGA JUAL UMUM;HARGA TUKANG (APLIKATOR);HARGA GROSIR (TOKO);STOK MINIMUM;PANJANG ROLL (METER)',
        'XLS-OLD-001;Plafon PVC Vifon Putih Update;Plafon PVC;Vifon;4m;meter;Rp 30.000;Rp 60.000;55000;52000;20;25.00',
        'XLS-NEW-002;Semen Kompon Aplus 20kg;Kompon & Finishing;Aplus;Zak 20kg;ZAK;45.000;58.000;55.000;;15;',
    ]);

    $file = UploadedFile::fake()->createWithContent('import_indonesia.csv', $csvContent);

    Livewire::test(ProdukIndex::class)
        ->set('excelFile', $file)
        ->set('updateExistingOnImport', true)
        ->call('importExcel')
        ->assertHasNoErrors();

    // Verify existing updated
    $existing->refresh();
    expect($existing->nama)->toBe('Plafon PVC Vifon Putih Update')
        ->and((float) $existing->harga_jual)->toBe(60000.0);

    // Verify new created with cleaned numbers and normalized Satuan::Zak
    $newProduct = Produk::where('kode_katalog', 'XLS-NEW-002')->firstOrFail();
    expect($newProduct->nama)->toBe('Semen Kompon Aplus 20kg')
        ->and((float) $newProduct->harga_jual)->toBe(58000.0)
        ->and((float) $newProduct->harga_beli)->toBe(45000.0)
        ->and($newProduct->satuan)->toBe(SatuanProduk::Zak);
});

test('user can preview excel or csv data before confirming import into database', function () {
    $this->actingAs($this->owner);

    Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'kode_katalog' => 'PREV-001',
        'nama' => 'Barang Lama',
    ]);

    $csvContent = implode("\n", [
        'KODE BARANG;NAMA PRODUK;KATEGORI;MEREK;UKURAN / SPESIFIKASI;SATUAN;HARGA BELI (HPP);HARGA JUAL UMUM;HARGA TUKANG (APLIKATOR);HARGA GROSIR (TOKO);STOK MINIMUM;PANJANG ROLL (METER)',
        'PREV-001;Barang Lama Mau Diupdate;Plafon PVC;Vifon;4m;meter;12000;16000;14500;13000;50;25.0',
        'PREV-002;Barang Baru Nyata;Plafon PVC;Vifon;4m;meter;15000;20000;18000;17000;40;25.0',
        ';Produk Tanpa Kode;;;;;;;;;;', // Baris error (kode kosong)
    ]);

    $file = UploadedFile::fake()->createWithContent('preview_test.csv', $csvContent);

    // Initial test with update existing = true
    $test = Livewire::test(ProdukIndex::class)
        ->call('openImportModal')
        ->assertSet('previewData', null)
        ->set('excelFile', $file);

    $preview = $test->get('previewData');
    expect($preview)->not->toBeNull()
        ->and($preview['total_rows'])->toBe(3)
        ->and($preview['new_count'])->toBe(1)
        ->and($preview['update_count'])->toBe(1)
        ->and($preview['error_count'])->toBe(1)
        ->and(count($preview['preview_rows']))->toBe(3);

    // Toggle update existing to false -> update_count becomes 0, skip_count becomes 1
    $test->set('updateExistingOnImport', false);
    $previewAfterToggle = $test->get('previewData');
    expect($previewAfterToggle['update_count'])->toBe(0)
        ->and($previewAfterToggle['skip_count'])->toBe(1);

    // Test reset file
    $test->call('resetImportFile')
        ->assertSet('previewData', null)
        ->assertSet('excelFile', null);

    // Re-set file and confirm import
    $test->set('excelFile', $file)
        ->set('updateExistingOnImport', true)
        ->call('importExcel')
        ->assertHasNoErrors();

    expect($test->get('importSummary')['created'])->toBe(1)
        ->and($test->get('importSummary')['updated'])->toBe(1)
        ->and(count($test->get('importSummary')['errors']))->toBe(1);

    $test->call('closeImportModal')
        ->assertSet('isImportModalOpen', false);

    expect(Produk::where('kode_katalog', 'PREV-002')->exists())->toBeTrue()
        ->and(Produk::where('kode_katalog', 'PREV-001')->first()->nama)->toBe('Barang Lama Mau Diupdate');
});

test('import initializes opening stock and records mutasi stok safely', function () {
    $this->actingAs($this->owner);

    $gudang = Gudang::firstOrCreate(
        ['nama_gudang' => 'Gudang Utama'],
        ['lokasi' => 'Pusat Toko', 'created_by' => $this->owner->id]
    );

    $csvContent = implode("\n", [
        'KODE BARANG;NAMA PRODUK;KATEGORI;MEREK;UKURAN / SPESIFIKASI;SATUAN;HARGA BELI (HPP);HARGA JUAL UMUM;HARGA TUKANG (APLIKATOR);HARGA GROSIR (TOKO);STOK AWAL;STOK MINIMUM;PANJANG ROLL (METER)',
        'STK-INIT-001;Gypsum Jayaboard Standard 9mm;Papan Gypsum;Jayaboard;1200x2400;lembar;68000;82000;76000;73000;120;50;',
        'STK-INIT-002;Hollow Kencana 2x4;Rangka & Hollow;Kencana;4m;batang;16500;23000;20000;18500;250;100;',
    ]);

    $file = UploadedFile::fake()->createWithContent('setup_awal.csv', $csvContent);

    Livewire::test(ProdukIndex::class)
        ->set('excelFile', $file)
        ->call('importExcel')
        ->assertHasNoErrors();

    $produk1 = Produk::where('kode_katalog', 'STK-INIT-001')->firstOrFail();
    $produk2 = Produk::where('kode_katalog', 'STK-INIT-002')->firstOrFail();

    // Verify stock is initialized in Gudang Utama
    $stok1 = Stok::where('produk_id', $produk1->id)->where('gudang_id', $gudang->id)->firstOrFail();
    $stok2 = Stok::where('produk_id', $produk2->id)->where('gudang_id', $gudang->id)->firstOrFail();

    expect((float) $stok1->jumlah)->toBe(120.0)
        ->and((float) $stok2->jumlah)->toBe(250.0);

    // Verify mutasi stok audit trail
    $mutasi1 = MutasiStok::where('produk_id', $produk1->id)->firstOrFail();
    expect($mutasi1->referensi)->toBe('Saldo Awal Setup Excel')
        ->and((float) $mutasi1->jumlah)->toBe(120.0)
        ->and($mutasi1->jenis)->toBe(JenisMutasiStok::PenyesuaianOpname);
});

test('produk index correctly filters products when filterKategori url parameter is provided', function () {
    $this->actingAs($this->owner);

    $parentCategory = Kategori::factory()->create(['nama_kategori' => 'PVC Parent Induk']);
    $subCategory1 = Kategori::factory()->create(['nama_kategori' => 'Sub Seri 1', 'parent_id' => $parentCategory->id]);
    $subCategory2 = Kategori::factory()->create(['nama_kategori' => 'Sub Seri 2', 'parent_id' => $parentCategory->id]);
    $otherCategory = Kategori::factory()->create(['nama_kategori' => 'Other Category']);

    $prod1 = Produk::factory()->create(['nama' => 'Produk Sub 1', 'kategori_id' => $subCategory1->id, 'merek_id' => $this->merek->id]);
    $prod2 = Produk::factory()->create(['nama' => 'Produk Sub 2', 'kategori_id' => $subCategory2->id, 'merek_id' => $this->merek->id]);
    $prodOther = Produk::factory()->create(['nama' => 'Produk Other', 'kategori_id' => $otherCategory->id, 'merek_id' => $this->merek->id]);

    // Test with parent category ID: should see both prod1 and prod2, but not prodOther
    Livewire::withQueryParams(['filterKategori' => (string) $parentCategory->id])
        ->test(ProdukIndex::class)
        ->assertSee('Produk Sub 1')
        ->assertSee('Produk Sub 2')
        ->assertDontSee('Produk Other');

    // Test with child category ID: should see only prod1
    Livewire::withQueryParams(['filterKategori' => (string) $subCategory1->id])
        ->test(ProdukIndex::class)
        ->assertSee('Produk Sub 1')
        ->assertDontSee('Produk Sub 2')
        ->assertDontSee('Produk Other');
});

test('excel export masks purchase price if user does not have view_purchase_prices permission', function () {
    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Rahasia',
        'harga_beli' => 88000,
        'harga_jual' => 120000,
    ]);

    $service = app(ProdukExcelService::class);

    // 1. Staff Penjualan (no view_purchase_prices)
    $this->actingAs($this->staffPenjualan);
    $responseStaff = $service->export(Produk::where('id', $produk->id));
    $fileStaff = $responseStaff->getFile()->getPathname();

    $reader = new Reader;
    $reader->open($fileStaff);
    $rowsStaff = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rowsStaff[] = $row->toArray();
        }
    }
    $reader->close();

    expect($rowsStaff[1][7])->toBe('-');

    // 2. Owner (has view_purchase_prices)
    $this->actingAs($this->owner);
    $responseOwner = $service->export(Produk::where('id', $produk->id));
    $fileOwner = $responseOwner->getFile()->getPathname();

    $reader = new Reader;
    $reader->open($fileOwner);
    $rowsOwner = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rowsOwner[] = $row->toArray();
        }
    }
    $reader->close();

    expect((float) $rowsOwner[1][7])->toBe(88000.0);
});

test('user without view_purchase_prices permission retains original harga_beli on update', function () {
    $catalogEditor = User::factory()->create();
    $catalogEditor->givePermissionTo(['view_products', 'update_products']);
    $this->actingAs($catalogEditor);

    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'PRD-STAFF-01',
        'nama' => 'Nama Awal',
        'harga_beli' => 75000,
        'harga_jual' => 90000,
        'stok_minimum' => 10,
    ]);

    Livewire::test(ProdukIndex::class)
        ->call('openEditModal', $produk->id)
        ->set('nama', 'Nama Diperbarui Oleh Staff')
        ->set('harga_jual', 92000)
        ->call('save')
        ->assertHasNoErrors();

    $produk->refresh();
    expect($produk->nama)->toBe('Nama Diperbarui Oleh Staff')
        ->and((float) $produk->harga_jual)->toBe(92000.0)
        ->and((float) $produk->harga_beli)->toBe(75000.0);
});

test('user can open and close barcode print modal', function () {
    $this->actingAs($this->owner);

    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'BC-TEST-001',
        'nama' => 'Papan Gypsum 9mm',
        'harga_jual' => 65000,
    ]);

    Livewire::test(ProdukIndex::class)
        ->assertSet('isBarcodeModalOpen', false)
        ->call('openBarcodeModal', $produk->id)
        ->assertSet('isBarcodeModalOpen', true)
        ->assertSet('barcodeQty', 1)
        ->assertSee('Cetak Label Rak')
        ->assertSee('BC-TEST-001')
        ->call('closeBarcodeModal')
        ->assertSet('isBarcodeModalOpen', false);
});

test('user can reset all catalog filters with resetFilters', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProdukIndex::class)
        ->set('search', 'pvc doff')
        ->set('filterKategori', (string) $this->kategori->id)
        ->set('filterMerek', (string) $this->merek->id)
        ->set('filterSatuan', 'meter')
        ->set('tabFilter', 'critical')
        ->assertSet('search', 'pvc doff')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('filterKategori', '')
        ->assertSet('filterMerek', '')
        ->assertSet('filterSatuan', '')
        ->assertSet('tabFilter', 'all');
});

test('user can open and close image preview lightbox modal', function () {
    $this->actingAs($this->owner);

    $produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'IMG-TEST-001',
        'nama' => 'Plafon PVC Motif Marmer',
        'foto' => 'produk/sample-image.webp',
        'harga_jual' => 85000,
    ]);

    Livewire::test(ProdukIndex::class)
        ->assertSet('isImageModalOpen', false)
        ->call('openImageModal', $produk->id)
        ->assertSet('isImageModalOpen', true)
        ->assertSee('IMG-TEST-001')
        ->assertSee('Plafon PVC Motif Marmer')
        ->assertSee('Ukuran Penuh')
        ->call('closeImageModal')
        ->assertSet('isImageModalOpen', false)
        ->assertSet('imageModalProduk', null);
});

test('user without create_products permission cannot open import modal', function () {
    $this->actingAs($this->staffPenjualan);

    Livewire::test(ProdukIndex::class)
        ->call('openImportModal')
        ->assertForbidden();
});

test('excel preview masks harga_beli when user cannot view purchase prices', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view_products', 'create_products']);
    $this->actingAs($user);

    $csvContent = implode("\n", [
        'KODE BARANG;NAMA PRODUK;KATEGORI;MEREK;UKURAN / SPESIFIKASI;SATUAN;HARGA BELI (HPP);HARGA JUAL UMUM',
        'TEST-PREV-01;Gypsum Confidential;Papan Gypsum;Jayaboard;1200x2400;lembar;75000;90000',
    ]);
    $file = UploadedFile::fake()->createWithContent('test_confidential.csv', $csvContent);

    $service = app(ProdukExcelService::class);
    $preview = $service->preview($file);

    expect((float) $preview['preview_rows'][0]['harga_beli'])->toBe(0.0)
        ->and((float) $preview['preview_rows'][0]['harga_jual'])->toBe(90000.0);
});

test('excel import preserves existing database harga_beli when updated by user without view_purchase_prices', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view_products', 'create_products', 'update_products']);
    $this->actingAs($user);

    $existing = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'kode_katalog' => 'CONF-001',
        'nama' => 'Gypsum Rahasia Toko',
        'harga_beli' => 85000.00,
        'harga_jual' => 110000.00,
    ]);

    $csvContent = implode("\n", [
        'KODE BARANG;NAMA PRODUK;KATEGORI;MEREK;UKURAN / SPESIFIKASI;SATUAN;HARGA BELI (HPP);HARGA JUAL UMUM',
        'CONF-001;Gypsum Rahasia Toko Update;Plafon PVC;Natural PVC;1200x2400;lembar;-;115000',
    ]);
    $file = UploadedFile::fake()->createWithContent('import_update.csv', $csvContent);

    $service = app(ProdukExcelService::class);
    $service->import($file, updateExisting: true, userId: $user->id);

    $existing->refresh();
    expect($existing->nama)->toBe('Gypsum Rahasia Toko Update')
        ->and((float) $existing->harga_jual)->toBe(115000.0)
        ->and((float) $existing->harga_beli)->toBe(85000.0);
});

test('produk model correctly computes physical and packaging conversion', function () {
    // 1. Plafon PVC 4m (15 lembar/dus)
    $pvc = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'satuan' => SatuanProduk::Meter,
        'panjang_meter' => 4.00,
        'kemasan' => 'Dus',
        'isi_per_kemasan' => 15.00,
    ]);

    $konvPvc = $pvc->getKonversiFisik(460.0); // 460 meter = 115 lembar (7 dus 10 lbr)
    expect($konvPvc['has_conversion'])->toBeTrue()
        ->and($konvPvc['qty_lembar'])->toBe(115.0)
        ->and($konvPvc['jumlah_kemasan_utuh'])->toBe(7)
        ->and($konvPvc['sisa_eceran'])->toBe(10)
        ->and($konvPvc['badge_text'])->toContain('7 Dus + 10 lbr');

    // Test kasus sisa potongan: 6 meter (1 lbr utuh 4m + 2m potongan)
    $konvPotongan = $pvc->getKonversiFisik(6.0);
    expect($konvPotongan['badge_text'])->toBe('1 lbr + 2m potongan (6m)')
        ->and($konvPotongan['qty_lembar'])->toBe(1.5)
        ->and($konvPotongan['sisa_meter_potongan'])->toBe(2.0);

    // Test kasus hanya beli potongan: 2 meter
    $konvPotonganOnly = $pvc->getKonversiFisik(2.0);
    expect($konvPotonganOnly['badge_text'])->toBe('Potongan 2m eceran')
        ->and($konvPotonganOnly['sisa_meter_potongan'])->toBe(2.0);

    // 2. Hollow 4m (20 batang/ikat)
    $hollow = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'satuan' => SatuanProduk::Batang,
        'kemasan' => 'Ikat',
        'isi_per_kemasan' => 20.00,
    ]);

    $konvHollow = $hollow->getKonversiFisik(100.0);
    expect($konvHollow['has_conversion'])->toBeTrue()
        ->and($konvHollow['jumlah_kemasan_utuh'])->toBe(5)
        ->and($konvHollow['badge_text'])->toContain('5 Ikat pas');
});

test('owner can save product with multi-tier packaging attributes via livewire form', function () {
    Livewire::actingAs($this->owner)
        ->test(ProdukIndex::class)
        ->call('openCreateModal')
        ->set('kategori_id', (string) $this->kategori->id)
        ->set('merek_id', (string) $this->merek->id)
        ->set('kode_katalog', 'PVC-TEST-001')
        ->set('nama', 'PVC Motif Bintang 4m')
        ->set('satuan', 'meter')
        ->set('panjang_meter', 4.00)
        ->set('kemasan', 'Dus')
        ->set('isi_per_kemasan', 15.00)
        ->set('harga_beli', 10000)
        ->set('harga_jual', 14500)
        ->set('stok_minimum', 60)
        ->call('save')
        ->assertHasNoErrors();

    $saved = Produk::where('kode_katalog', 'PVC-TEST-001')->first();
    expect($saved)->not->toBeNull()
        ->and((float) $saved->panjang_meter)->toBe(4.00)
        ->and($saved->kemasan)->toBe('Dus')
        ->and((float) $saved->isi_per_kemasan)->toBe(15.00);
});

test('excel template generates 3 sheets: Data Produk, Petunjuk Pengisian, and Daftar Referensi', function () {
    $service = app(ProdukExcelService::class);
    $response = $service->generateTemplateExcel();
    $file = $response->getFile()->getPathname();

    $reader = new Reader;
    $reader->open($file);
    $sheetNames = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        $sheetNames[] = $sheet->getName();
    }
    $reader->close();

    expect($sheetNames)->toContain('Data Produk')
        ->and($sheetNames)->toContain('Petunjuk Pengisian')
        ->and($sheetNames)->toContain('Daftar Referensi');
});

test('excel import resolves and links parent category and sub-category with parent_id', function () {
    $service = app(ProdukExcelService::class);

    // Create a temporary XLSX file with 2-level category
    $tempPath = tempnam(sys_get_temp_dir(), 'import_hier_').'.xlsx';
    $options = new Options;
    $writer = new Writer($options);
    $writer->openToFile($tempPath);

    $s1 = $writer->getCurrentSheet();
    $s1->setName('Data Produk');
    $writer->addRow(Row::fromValues(ProdukExcelService::DISPLAY_HEADERS));
    $writer->addRow(Row::fromValues([
        'PVC-HIER-001',
        'Plafon PVC Hierarki Test 4m',
        'PVC Plafon & Dinding',
        'Seri Glossy',
        'VIFON',
        '4m x 20cm x 8mm',
        'meter',
        25000,
        36000,
        100,
        20,
        4.0,
        'Dus',
        15,
        '',
    ]));
    $writer->close();

    $result = $service->import($tempPath, true, $this->owner->id);
    @unlink($tempPath);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $imported = Produk::with('kategori.parent')->where('kode_katalog', 'PVC-HIER-001')->first();
    expect($imported)->not->toBeNull()
        ->and($imported->kategori)->not->toBeNull()
        ->and($imported->kategori->nama_kategori)->toBe('Seri Glossy')
        ->and($imported->kategori->parent)->not->toBeNull()
        ->and($imported->kategori->parent->nama_kategori)->toBe('PVC Plafon & Dinding');
});
