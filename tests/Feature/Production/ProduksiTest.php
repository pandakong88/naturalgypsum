<?php

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Production\Actions\CancelProduksiAction;
use App\Domains\Production\Actions\CreateProduksiAction;
use App\Domains\Production\Models\Produksi;
use App\Livewire\Production\ProduksiCreate;
use App\Livewire\Production\ProduksiDetail;
use App\Livewire\Production\ProduksiIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffProduksi = User::factory()->create();
    $this->staffProduksi->assignRole('Staff Produksi');

    $this->unauthorizedUser = User::factory()->create();

    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Plafon PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural PVC']);

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Produksi']);

    $this->produkHasilA = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Ornamen PVC 60x60',
        'panjang_per_roll' => 25.0,
    ]);

    $this->produkHasilB = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'List Figura PVC Gold',
    ]);

    $this->createAction = app(CreateProduksiAction::class);
    $this->cancelAction = app(CancelProduksiAction::class);
});

test('TEST 1: successful finished goods production increases destination warehouse stock (Output Only)', function () {
    $produksi = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'catatan' => 'Shift pagi - batch 1',
        'items' => [
            ['produk_id' => $this->produkHasilA->id, 'jumlah' => 50],
            ['produk_id' => $this->produkHasilB->id, 'jumlah' => 30],
        ],
        'user_id' => $this->owner->id,
    ]);

    expect($produksi)->toBeInstanceOf(Produksi::class)
        ->and($produksi->no_produksi)->toMatch('/^PRD-2026-\d{6}$/')
        ->and($produksi->details)->toHaveCount(2);

    // Verify stock increased
    $stockA = Stok::where('produk_id', $this->produkHasilA->id)->where('gudang_id', $this->gudang->id)->value('jumlah');
    $stockB = Stok::where('produk_id', $this->produkHasilB->id)->where('gudang_id', $this->gudang->id)->value('jumlah');
    expect((float) $stockA)->toBe(50.0)
        ->and((float) $stockB)->toBe(30.0);

    // Verify mutasi stok
    $mutations = MutasiStok::where('referensi_type', 'produksi')->where('referensi_id', $produksi->id)->get();
    expect($mutations)->toHaveCount(2)
        ->and($mutations->first()->jenis)->toBe(JenisMutasiStok::ProduksiMasuk)
        ->and((float) $mutations->first()->jumlah)->toBe(50.0);
});

test('TEST 2: production validation rejects empty items', function () {
    $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'items' => [],
    ]);
})->throws(InvalidArgumentException::class);

test('TEST 3: production cancellation decreases finished goods stock back', function () {
    $produksi = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'items' => [
            ['produk_id' => $this->produkHasilA->id, 'jumlah' => 40],
        ],
        'user_id' => $this->owner->id,
    ]);

    expect((float) Stok::where('produk_id', $this->produkHasilA->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(40.0);

    $cancelled = $this->cancelAction->execute($produksi, $this->owner->id);
    expect($cancelled->trashed())->toBeTrue();

    // Stock should be 0.0
    expect((float) Stok::where('produk_id', $this->produkHasilA->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(0.0);
});

test('TEST 4: production cancellation decreases stock even into negative when consumed', function () {
    $produksi = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'items' => [
            ['produk_id' => $this->produkHasilA->id, 'jumlah' => 40],
        ],
        'user_id' => $this->owner->id,
    ]);

    // Manually consume stock via StockService
    app(StockService::class)->decrease($this->produkHasilA, $this->gudang, 30, JenisMutasiStok::PenjualanKeluar, 'Sales', userId: $this->owner->id);

    // Cancel production (reverses 40, resulting in 40 - 30 - 40 = -30)
    $cancelled = $this->cancelAction->execute($produksi, $this->owner->id);
    expect($cancelled->trashed())->toBeTrue()
        ->and((float) Stok::where('produk_id', $this->produkHasilA->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(-30.0);
});

test('TEST 5: Livewire ProduksiIndex renders list with authorization', function () {
    $this->actingAs($this->staffProduksi);

    Livewire::test(ProduksiIndex::class)
        ->assertOk()
        ->assertSee('Catatan Produksi Barang Jadi');
});

test('TEST 6: Livewire ProduksiCreate with roll converter creates production record', function () {
    $this->actingAs($this->staffProduksi);

    Livewire::test(ProduksiCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('catatan', 'Produksi Roll PVC')
        ->set('items', [
            ['produk_id' => $this->produkHasilA->id, 'jumlah' => 0],
        ])
        ->call('openRollConverter', 0)
        ->set('rollCount', 4)
        ->set('rollLength', 25)
        ->call('applyRollConversion')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('detail_produksi', [
        'produk_id' => $this->produkHasilA->id,
        'jumlah' => 100.0, // 4 * 25
    ]);
});

test('TEST 7: production with optional raw materials deducts raw material stock (PemakaianInternal) and restores it upon cancellation', function () {
    $bahanBaku = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Tepung Casting Plaster 20kg',
        'harga_beli' => 50000,
    ]);

    // Seed 20 zak of raw material in warehouse
    app(StockService::class)->increase($bahanBaku, $this->gudang, 20, JenisMutasiStok::PembelianMasuk, 'INIT-BAHAN', userId: $this->owner->id);

    $produksi = $this->createAction->execute([
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-18',
        'catatan' => 'Cetak ornamen dengan bahan baku',
        'items' => [
            ['produk_id' => $this->produkHasilA->id, 'jumlah' => 25],
        ],
        'materials' => [
            ['produk_id' => $bahanBaku->id, 'jumlah' => 3],
        ],
        'user_id' => $this->owner->id,
    ]);

    expect((float) Stok::where('produk_id', $this->produkHasilA->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(25.0)
        ->and((float) Stok::where('produk_id', $bahanBaku->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(17.0)
        ->and($produksi->bahanMutations)->toHaveCount(1)
        ->and((float) $produksi->estimasi_biaya_bahan)->toBe(150000.0);

    // Cancel production -> finished goods back to 0, raw material restored back to 20
    $this->cancelAction->execute($produksi, $this->owner->id);

    expect((float) Stok::where('produk_id', $this->produkHasilA->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(0.0)
        ->and((float) Stok::where('produk_id', $bahanBaku->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(20.0);
});

test('TEST 8: Livewire ProduksiCreate with shift, penanggung_jawab, quickAdd, and raw materials', function () {
    $bahanBaku = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Kompon Gypsum Bahan Cetak',
        'harga_beli' => 60000,
    ]);
    app(StockService::class)->increase($bahanBaku, $this->gudang, 10, JenisMutasiStok::PembelianMasuk, 'INIT-KOMPON', userId: $this->owner->id);

    $this->actingAs($this->staffProduksi);

    Livewire::test(ProduksiCreate::class)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-19')
        ->set('shift', 'Shift Pagi')
        ->set('penanggung_jawab', 'Pak Budi')
        ->set('catatan', 'Cetakan halus')
        ->call('openCatalogModal', 'output')
        ->assertSet('isCatalogModalOpen', true)
        ->call('toggleCatalogProduct', $this->produkHasilA->id)
        ->call('addSelectedCatalogProducts')
        ->assertSet('isCatalogModalOpen', false)
        ->set('items.0.jumlah', 30)
        ->call('openCatalogModal', 'material')
        ->call('toggleCatalogProduct', $bahanBaku->id)
        ->call('addSelectedCatalogProducts')
        ->set('materials.0.jumlah', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $produksi = Produksi::latest('id')->first();
    expect($produksi)->not->toBeNull()
        ->and($produksi->catatan)->toContain('[Shift: Shift Pagi | Tim/Tukang: Pak Budi]')
        ->and($produksi->catatan)->toContain('Cetakan halus')
        ->and($produksi->bahanMutations)->toHaveCount(1);

    // Verify ProduksiDetail and ProduksiIndex display
    $this->actingAs($this->owner);

    Livewire::test(ProduksiDetail::class, ['id' => $produksi->id])
        ->assertSee($produksi->no_produksi)
        ->assertSee('Ornamen PVC 60x60')
        ->assertSee('Kompon Gypsum Bahan Cetak')
        ->call('openCancelModal')
        ->assertSet('isCancelModalOpen', true)
        ->call('cancel')
        ->assertRedirect(route('produksi.index'));

    Livewire::test(ProduksiIndex::class)
        ->assertSee('Belum ada catatan produksi yang ditemukan.')
        ->set('statusFilter', 'dibatalkan')
        ->assertSee($produksi->no_produksi)
        ->assertSee('DIBATALKAN');
});
