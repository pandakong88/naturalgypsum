<?php

use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Sales\Models\AturanDiskon;
use App\Domains\Sales\Services\DiscountCalculationService;
use App\Livewire\Sales\AturanDiskonIndex;
use App\Livewire\Sales\PenjualanCreate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->kategoriPvc = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->kategoriGypsum = Kategori::factory()->create(['nama_kategori' => 'Papan Gypsum']);

    $this->produkPvc = Produk::factory()->create([
        'kategori_id' => $this->kategoriPvc->id,
        'nama' => 'Plafon PVC Vifon 4m',
        'satuan' => SatuanProduk::Meter,
        'harga_beli' => 20000,
        'harga_jual' => 30000,
        'harga_aplikator' => 28000,
        'harga_grosir' => 26000,
    ]);

    $this->pelangganPemborong = Pelanggan::factory()->create([
        'nama' => 'Pak Joko Tukang',
        'tipe' => TipePelanggan::Pemborong,
    ]);

    $this->pelangganUmum = Pelanggan::factory()->create([
        'nama' => 'Ibu Siti',
        'tipe' => TipePelanggan::Umum,
    ]);

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
});

test('user can view, create, edit, toggle, and delete discount rules via Livewire', function () {
    $this->actingAs($this->owner);

    // Create rule
    Livewire::test(AturanDiskonIndex::class)
        ->set('nama', 'Diskon Pemborong Kategori PVC 5%')
        ->set('target_pelanggan', 'pemborong')
        ->set('target_produk', 'kategori')
        ->set('kategori_id', (string) $this->kategoriPvc->id)
        ->set('jenis_diskon', 'persentase')
        ->set('nilai_diskon', 5.0)
        ->set('min_kuantiti', 10)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('aturan_diskon', [
        'nama' => 'Diskon Pemborong Kategori PVC 5%',
        'target_pelanggan' => 'pemborong',
        'kategori_id' => $this->kategoriPvc->id,
        'jenis_diskon' => 'persentase',
        'nilai_diskon' => 5.00,
        'min_kuantiti' => 10.00,
        'is_active' => true,
    ]);

    $rule = AturanDiskon::first();

    // Toggle active
    Livewire::test(AturanDiskonIndex::class)
        ->call('toggleActive', $rule->id);

    expect($rule->fresh()->is_active)->toBeFalse();

    // Delete
    Livewire::test(AturanDiskonIndex::class)
        ->call('confirmDelete', $rule->id)
        ->call('delete');

    expect($rule->fresh()->trashed())->toBeTrue();
});

test('DiscountCalculationService calculates correct discount for target customer and category', function () {
    $service = new DiscountCalculationService;

    // Create active rule: 5% discount for Pemborong on PVC category with min qty 10
    AturanDiskon::create([
        'nama' => 'Promo Aplikator PVC 5%',
        'target_pelanggan' => 'pemborong',
        'target_produk' => 'kategori',
        'kategori_id' => $this->kategoriPvc->id,
        'jenis_diskon' => 'persentase',
        'nilai_diskon' => 5.0,
        'min_kuantiti' => 10,
        'is_active' => true,
    ]);

    // Scenario 1: Pemborong buying 15 meters PVC -> eligible!
    // Base price for Pemborong is harga_aplikator = 28.000. 5% of 28.000 = 1.400 discount per unit.
    $res1 = $service->calculate($this->produkPvc, $this->pelangganPemborong, 15);
    expect($res1['has_discount'])->toBeTrue()
        ->and($res1['diskon_per_unit'])->toBe(1400.0)
        ->and($res1['harga_setelah_diskon'])->toBe(26600.0);

    // Scenario 2: Pemborong buying 5 meters (less than min qty 10) -> not eligible
    $res2 = $service->calculate($this->produkPvc, $this->pelangganPemborong, 5);
    expect($res2['has_discount'])->toBeFalse()
        ->and($res2['diskon_per_unit'])->toBe(0.0);

    // Scenario 3: Umum customer buying 20 meters -> not eligible (rule is for pemborong)
    $res3 = $service->calculate($this->produkPvc, $this->pelangganUmum, 20);
    expect($res3['has_discount'])->toBeFalse();
});

test('PenjualanCreate automatically applies tier price and discount rule on item selection', function () {
    $this->actingAs($this->owner);

    // Create 10% discount rule for Pemborong on all PVC
    AturanDiskon::create([
        'nama' => 'Diskon Akbar Aplikator 10%',
        'target_pelanggan' => 'pemborong',
        'target_produk' => 'kategori',
        'kategori_id' => $this->kategoriPvc->id,
        'jenis_diskon' => 'persentase',
        'nilai_diskon' => 10.0,
        'min_kuantiti' => 0,
        'is_active' => true,
    ]);

    $component = Livewire::test(PenjualanCreate::class)
        ->set('pelanggan_id', $this->pelangganPemborong->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('items.0.produk_id', (string) $this->produkPvc->id)
        ->set('items.0.jumlah', 10);

    // Verify auto price and auto discount:
    // Base price for Pemborong: 28.000
    // Discount 10% of 28.000 = 2.800 per unit -> Subtotal per unit: 25.200 -> Total 10 unit = 252.000
    $component->assertSet('items.0.harga_satuan', 28000.0)
        ->assertSet('items.0.jenis_diskon', 'persentase')
        ->assertSet('items.0.nilai_diskon', 10.0)
        ->assertSet('items.0.subtotal', 252000.0);
});

test('nominal round discount and multi-product discount work via Livewire and calculation service', function () {
    $this->actingAs($this->owner);

    $produkGypsum = Produk::factory()->create([
        'kategori_id' => $this->kategoriGypsum->id,
        'nama' => 'Papan Gypsum Jayaboard 9mm',
        'satuan' => SatuanProduk::Lembar,
        'harga_jual' => 85000,
        'harga_aplikator' => 80000,
    ]);

    // Create rule with nominal Rp 10.000 and multiple specific products
    Livewire::test(AturanDiskonIndex::class)
        ->set('nama', 'Promo Potongan Rp 10.000 Produk Tertentu')
        ->set('target_pelanggan', 'semua')
        ->set('target_produk', 'spesifik')
        ->call('addSelectedProduk', $this->produkPvc->id)
        ->call('addSelectedProduk', $produkGypsum->id)
        ->set('jenis_diskon', 'nominal')
        ->set('nilai_diskon', 10000)
        ->set('min_kuantiti', 1)
        ->call('save')
        ->assertHasNoErrors();

    $rule = AturanDiskon::where('nama', 'Promo Potongan Rp 10.000 Produk Tertentu')->first();
    expect($rule)->not->toBeNull()
        ->and($rule->produks->pluck('id')->all())->toContain($this->produkPvc->id, $produkGypsum->id);

    // Test service applies nominal discount to both products
    $service = new DiscountCalculationService;

    $res1 = $service->calculate($this->produkPvc, $this->pelangganUmum, 2);
    expect($res1['has_discount'])->toBeTrue()
        ->and($res1['diskon_per_unit'])->toBe(10000.0)
        ->and($res1['harga_setelah_diskon'])->toBe(20000.0); // 30.000 - 10.000 = 20.000

    $res2 = $service->calculate($produkGypsum, $this->pelangganUmum, 1);
    expect($res2['has_discount'])->toBeTrue()
        ->and($res2['diskon_per_unit'])->toBe(10000.0)
        ->and($res2['harga_setelah_diskon'])->toBe(75000.0); // 85.000 - 10.000 = 75.000
});

test('rule with target_pelanggan umum applies only to retail customers', function () {
    $this->actingAs($this->owner);

    // Create rule targeted specifically to Umum (retail)
    Livewire::test(AturanDiskonIndex::class)
        ->set('nama', 'Promo Khusus Retail Umum 2rb')
        ->set('target_pelanggan', 'umum')
        ->set('target_produk', 'semua')
        ->set('jenis_diskon', 'nominal')
        ->set('nilai_diskon', 2000)
        ->set('min_kuantiti', 1)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('aturan_diskon', [
        'nama' => 'Promo Khusus Retail Umum 2rb',
        'target_pelanggan' => 'umum',
    ]);

    $service = new DiscountCalculationService;

    // Pelanggan Umum -> Eligible!
    $resUmum = $service->calculate($this->produkPvc, $this->pelangganUmum, 1);
    expect($resUmum['has_discount'])->toBeTrue()
        ->and($resUmum['diskon_per_unit'])->toBe(2000.0);

    // Pelanggan Pemborong -> Not Eligible!
    $resPemborong = $service->calculate($this->produkPvc, $this->pelangganPemborong, 1);
    expect($resPemborong['has_discount'])->toBeFalse();
});

test('validation prevents percentage discount greater than 100', function () {
    $this->actingAs($this->owner);

    Livewire::test(AturanDiskonIndex::class)
        ->set('nama', 'Diskon Halu 150%')
        ->set('target_pelanggan', 'semua')
        ->set('target_produk', 'semua')
        ->set('jenis_diskon', 'persentase')
        ->set('nilai_diskon', 150)
        ->set('min_kuantiti', 0)
        ->call('save')
        ->assertHasErrors(['nilai_diskon']);
});

test('all discount rule actions log to ActivityLog (create, update, toggle, delete, restore, forceDelete)', function () {
    $this->actingAs($this->owner);

    // 1. Create
    Livewire::test(AturanDiskonIndex::class)
        ->set('nama', 'Promo Audit Test 10%')
        ->set('target_pelanggan', 'semua')
        ->set('target_produk', 'semua')
        ->set('jenis_diskon', 'persentase')
        ->set('nilai_diskon', 10)
        ->set('priority', 5)
        ->call('save')
        ->assertHasNoErrors();

    $rule = AturanDiskon::where('nama', 'Promo Audit Test 10%')->first();
    expect($rule)->not->toBeNull();

    $createLog = ActivityLog::where('action', 'create')
        ->where('subject_type', AturanDiskon::class)
        ->where('subject_id', $rule->id)
        ->first();
    expect($createLog)->not->toBeNull()
        ->and($createLog->description)->toContain('Promo Audit Test 10%');

    // 2. Update
    Livewire::test(AturanDiskonIndex::class)
        ->call('openEditModal', $rule->id)
        ->set('nama', 'Promo Audit Test Updated 15%')
        ->set('nilai_diskon', 15)
        ->call('save')
        ->assertHasNoErrors();

    $updateLog = ActivityLog::where('action', 'update')
        ->where('subject_type', AturanDiskon::class)
        ->where('subject_id', $rule->id)
        ->latest('id')
        ->first();
    expect($updateLog)->not->toBeNull()
        ->and($updateLog->description)->toContain('Promo Audit Test Updated 15%');

    // 3. Toggle Active
    Livewire::test(AturanDiskonIndex::class)
        ->call('toggleActive', $rule->id);

    $toggleLog = ActivityLog::where('action', 'update')
        ->where('subject_type', AturanDiskon::class)
        ->where('subject_id', $rule->id)
        ->latest('id')
        ->first();
    expect($toggleLog)->not->toBeNull()
        ->and($toggleLog->description)->toContain('menonaktifkan');

    // 4. Soft Delete
    Livewire::test(AturanDiskonIndex::class)
        ->call('confirmDelete', $rule->id)
        ->call('delete');

    expect($rule->fresh()->trashed())->toBeTrue();

    $deleteLog = ActivityLog::where('action', 'delete')
        ->where('subject_type', AturanDiskon::class)
        ->where('subject_id', $rule->id)
        ->first();
    expect($deleteLog)->not->toBeNull()
        ->and($deleteLog->description)->toContain('Promo Audit Test Updated 15%');

    // 5. Restore
    Livewire::test(AturanDiskonIndex::class)
        ->set('showTrashed', true)
        ->call('confirmRestore', $rule->id)
        ->call('restore');

    expect($rule->fresh()->trashed())->toBeFalse();

    $restoreLog = ActivityLog::where('action', 'restore')
        ->where('subject_type', AturanDiskon::class)
        ->where('subject_id', $rule->id)
        ->first();
    expect($restoreLog)->not->toBeNull()
        ->and($restoreLog->description)->toContain('Memulihkan');

    // 6. Force Delete
    // Soft delete again first
    $rule->delete();
    expect($rule->fresh()->trashed())->toBeTrue();

    Livewire::test(AturanDiskonIndex::class)
        ->set('showTrashed', true)
        ->call('confirmForceDelete', $rule->id)
        ->call('forceDelete');

    expect(AturanDiskon::withTrashed()->find($rule->id))->toBeNull();

    $forceDeleteLog = ActivityLog::where('action', 'force_delete')
        ->where('subject_type', AturanDiskon::class)
        ->where('subject_id', $rule->id)
        ->first();
    expect($forceDeleteLog)->not->toBeNull()
        ->and($forceDeleteLog->description)->toContain('Menghapus permanen');
});

test('switch on/off discount rule displays confirmation modal alert before toggling', function () {
    $this->actingAs($this->owner);

    $rule = AturanDiskon::create([
        'nama' => 'Promo Konfirmasi Alert Switch',
        'target_pelanggan' => 'semua',
        'target_produk' => 'semua',
        'jenis_diskon' => 'persentase',
        'nilai_diskon' => 5,
        'is_active' => true,
    ]);

    $test = Livewire::test(AturanDiskonIndex::class)
        // Click switch triggers confirmation alert modal
        ->call('confirmToggleActive', $rule->id)
        ->assertSet('isToggleModalOpen', true)
        ->assertSet('aturanToToggleId', $rule->id)
        ->assertSet('aturanToToggleName', 'Promo Konfirmasi Alert Switch')
        ->assertSet('aturanToToggleTargetActive', false)
        ->assertSee('Nonaktifkan Aturan Diskon')
        ->assertSee('Apakah Anda yakin ingin');

    // Rule is still active because user hasn't confirmed yet
    expect($rule->fresh()->is_active)->toBeTrue();

    // User confirms toggle in modal
    $test->call('toggleActive')
        ->assertSet('isToggleModalOpen', false);

    expect($rule->fresh()->is_active)->toBeFalse();

    // Now test turning it back on via modal
    $test->call('confirmToggleActive', $rule->id)
        ->assertSet('isToggleModalOpen', true)
        ->assertSet('aturanToToggleTargetActive', true)
        ->assertSee('Aktifkan Aturan Diskon')
        ->call('toggleActive')
        ->assertSet('isToggleModalOpen', false);

    expect($rule->fresh()->is_active)->toBeTrue();
});
