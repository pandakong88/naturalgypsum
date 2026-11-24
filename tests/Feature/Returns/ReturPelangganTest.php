<?php

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Returns\Actions\CancelReturPelangganAction;
use App\Domains\Returns\Actions\CreateReturPelangganAction;
use App\Domains\Returns\Models\ReturPelanggan;
use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Returns\ReturPelangganCreate;
use App\Livewire\Returns\ReturPelangganIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->pelanggan = Pelanggan::factory()->create(['nama' => 'Toko Bangunan Abadi']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'PVC']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Natural']);
    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);

    $this->produk = Produk::factory()->create([
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'nama' => 'Plafon PVC Wood Brown 4m',
        'harga_jual' => 32000.00,
    ]);

    $this->createAction = app(CreateReturPelangganAction::class);
    $this->cancelAction = app(CancelReturPelangganAction::class);
});

test('TEST 1: successful customer return increases warehouse stock and stores snapshot price', function () {
    $retur = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'jumlah' => 12,
        'harga_retur' => 32000.00,
        'alasan' => 'Kelebihan beli saat renovasi',
        'user_id' => $this->owner->id,
    ]);

    expect($retur)->toBeInstanceOf(ReturPelanggan::class)
        ->and($retur->no_retur)->toMatch('/^RP-2026-\d{6}$/')
        ->and((float) $retur->jumlah)->toBe(12.0)
        ->and((float) $retur->harga_retur)->toBe(32000.00);

    // Verify stock increased
    $stock = Stok::where('produk_id', $this->produk->id)->where('gudang_id', $this->gudang->id)->value('jumlah');
    expect((float) $stock)->toBe(12.0);

    // Verify mutation
    $mutasi = MutasiStok::where('referensi_type', 'retur_pelanggan')->where('referensi_id', $retur->id)->first();
    expect($mutasi)->not->toBeNull()
        ->and($mutasi->jenis)->toBe(JenisMutasiStok::ReturDariPelanggan)
        ->and((float) $mutasi->jumlah)->toBe(12.0);
});

test('TEST 2: customer return with optional penjualan_id linkage does NOT alter financial invoice balances', function () {
    $penjualan = Penjualan::factory()->create([
        'pelanggan_id' => $this->pelanggan->id,
        'gudang_id' => $this->gudang->id,
        'user_id' => $this->owner->id,
        'total_akhir' => 100000.00,
        'sisa_piutang' => 100000.00,
    ]);

    $retur = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'penjualan_id' => $penjualan->id,
        'tanggal' => '2026-09-17',
        'jumlah' => 5,
        'harga_retur' => 20000.00,
        'user_id' => $this->owner->id,
    ]);

    expect($retur->penjualan_id)->toBe($penjualan->id);

    // Invariant: invoice financial balance and sisa_piutang MUST remain untouched in Phase 6
    $freshSale = $penjualan->fresh();
    expect((float) $freshSale->total_akhir)->toBe(100000.00)
        ->and((float) $freshSale->sisa_piutang)->toBe(100000.00);
});

test('TEST 3: customer return cancellation decreases warehouse stock back', function () {
    $retur = $this->createAction->execute([
        'pelanggan_id' => $this->pelanggan->id,
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'tanggal' => '2026-09-17',
        'jumlah' => 10,
        'user_id' => $this->owner->id,
    ]);

    expect((float) Stok::where('produk_id', $this->produk->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(10.0);

    $cancelled = $this->cancelAction->execute($retur, $this->owner->id);
    expect($cancelled->trashed())->toBeTrue();

    // Stock decreased back to 0.0
    expect((float) Stok::where('produk_id', $this->produk->id)->where('gudang_id', $this->gudang->id)->value('jumlah'))->toBe(0.0);
});

test('TEST 4: Livewire ReturPelanggan components work smoothly', function () {
    $this->actingAs($this->staffPenjualan);

    Livewire::test(ReturPelangganIndex::class)->assertOk();

    Livewire::test(ReturPelangganCreate::class)
        ->set('pelanggan_id', $this->pelanggan->id)
        ->set('produk_id', $this->produk->id)
        ->set('gudang_id', $this->gudang->id)
        ->set('tanggal', '2026-09-17')
        ->set('jumlah', 8)
        ->set('harga_retur', 32000.00)
        ->set('alasan', 'Salah ukuran')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();
});
