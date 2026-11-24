<?php

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Enums\StatusStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Services\StockService;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->produk = Produk::factory()->create(['stok_minimum' => 10.000]);
    $this->gudang = Gudang::factory()->create();
    $this->stockService = app(StockService::class);
});

test('TEST 1: increase creates a new stok record if none exists for the warehouse', function () {
    expect(Stok::where('produk_id', $this->produk->id)->where('gudang_id', $this->gudang->id)->exists())->toBeFalse();

    $stok = $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 50.000,
        jenis: JenisMutasiStok::PembelianMasuk,
        referensi: 'PB-2026-000001',
        userId: $this->user->id
    );

    expect($stok)->not->toBeNull()
        ->and((float) $stok->jumlah)->toEqual(50.000);

    $this->assertDatabaseHas('stok', [
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => 50.000,
    ]);
});

test('TEST 2: increase stock adds quantity correctly from initial state', function () {
    $stok = $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 100.000,
        jenis: JenisMutasiStok::PembelianMasuk,
        referensi: 'PB-2026-000002',
        userId: $this->user->id
    );

    expect((float) $stok->jumlah)->toEqual(100.000);

    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jenis' => JenisMutasiStok::PembelianMasuk->value,
        'jumlah' => 100.000,
        'referensi' => 'PB-2026-000002',
    ]);
});

test('TEST 3: decrease stock deducts quantity and records negative mutation', function () {
    // Initial 100
    $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 100.000,
        jenis: JenisMutasiStok::PembelianMasuk,
        referensi: 'PB-2026-000003',
        userId: $this->user->id
    );

    // Decrease 20
    $stok = $this->stockService->decrease(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 20.000,
        jenis: JenisMutasiStok::PenjualanKeluar,
        referensi: 'PJ-2026-000001',
        userId: $this->user->id
    );

    expect((float) $stok->jumlah)->toEqual(80.000);

    $this->assertDatabaseHas('stok', [
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => 80.000,
    ]);

    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jenis' => JenisMutasiStok::PenjualanKeluar->value,
        'jumlah' => -20.000,
        'referensi' => 'PJ-2026-000001',
    ]);
});

test('TEST 4: negative stock is allowed per business rule', function () {
    // Initial 10
    $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 10.000,
        jenis: JenisMutasiStok::PembelianMasuk,
        referensi: 'PB-2026-000004',
        userId: $this->user->id
    );

    // Decrease 15 (exceeds current stock)
    $stok = $this->stockService->decrease(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 15.000,
        jenis: JenisMutasiStok::PenjualanKeluar,
        referensi: 'PJ-2026-000002',
        userId: $this->user->id
    );

    expect((float) $stok->jumlah)->toEqual(-5.000);

    $this->assertDatabaseHas('stok', [
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => -5.000,
    ]);

    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jenis' => JenisMutasiStok::PenjualanKeluar->value,
        'jumlah' => -15.000,
        'referensi' => 'PJ-2026-000002',
    ]);
});

test('TEST 5: every stock modification creates a corresponding mutation record', function () {
    expect(MutasiStok::count())->toEqual(0);

    $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 30.000,
        jenis: JenisMutasiStok::PembelianMasuk,
        referensi: 'PB-2026-000005',
        userId: $this->user->id
    );

    expect(MutasiStok::count())->toEqual(1);

    $this->stockService->decrease(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 10.000,
        jenis: JenisMutasiStok::PemakaianInternal,
        referensi: 'PI-2026-000001',
        userId: $this->user->id
    );

    expect(MutasiStok::count())->toEqual(2);
});

test('TEST 6: atomic rollback ensures stock and mutation revert on failure', function () {
    // Initial 50
    $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 50.000,
        jenis: JenisMutasiStok::PembelianMasuk,
        referensi: 'PB-2026-000006',
        userId: $this->user->id
    );

    expect((float) $this->stockService->getStock($this->produk, $this->gudang))->toEqual(50.000)
        ->and(MutasiStok::count())->toEqual(1);

    try {
        DB::transaction(function () {
            $this->stockService->decrease(
                produk: $this->produk,
                gudang: $this->gudang,
                qty: 20.000,
                jenis: JenisMutasiStok::PenjualanKeluar,
                referensi: 'PJ-FAIL-001',
                userId: $this->user->id
            );

            // Trigger forced exception to simulate failure in outer business transaction
            throw new Exception('Simulated transaction failure');
        });
    } catch (Exception $e) {
        // Exception caught
    }

    // Stock must remain 50, and mutation count must remain 1
    expect((float) $this->stockService->getStock($this->produk, $this->gudang))->toEqual(50.000)
        ->and(MutasiStok::count())->toEqual(1)
        ->and(MutasiStok::where('referensi', 'PJ-FAIL-001')->exists())->toBeFalse();
});

test('TEST 7: product and warehouse unique constraint prevents duplicate rows in stok table', function () {
    Stok::create([
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jumlah' => 10.000,
    ]);

    expect(function () {
        Stok::create([
            'produk_id' => $this->produk->id,
            'gudang_id' => $this->gudang->id,
            'jumlah' => 20.000,
        ]);
    })->toThrow(QueryException::class);
});

test('TEST 8: mutation references are accurately saved', function () {
    $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 45.000,
        jenis: JenisMutasiStok::ProduksiMasuk,
        referensi: 'PRD-2026-000001',
        referensiType: 'produksi',
        referensiId: 99,
        userId: $this->user->id
    );

    $mutation = MutasiStok::where('referensi', 'PRD-2026-000001')->first();

    expect($mutation)->not->toBeNull()
        ->and($mutation->referensi_type)->toEqual('produksi')
        ->and($mutation->referensi_id)->toEqual(99)
        ->and($mutation->user_id)->toEqual($this->user->id);
});

test('TEST 9: stock status is accurately determined', function () {
    // stok_minimum = 10
    expect(StatusStok::determine(25.000, 10.000))->toEqual(StatusStok::Aman)
        ->and(StatusStok::determine(10.000, 10.000))->toEqual(StatusStok::Menipis)
        ->and(StatusStok::determine(5.000, 10.000))->toEqual(StatusStok::Menipis)
        ->and(StatusStok::determine(0.000, 10.000))->toEqual(StatusStok::Habis)
        ->and(StatusStok::determine(-5.000, 10.000))->toEqual(StatusStok::Habis);
});

test('TEST 15: stock adjust sets physical count and records difference mutation', function () {
    // Initial stock = 100
    $this->stockService->increase(
        produk: $this->produk,
        gudang: $this->gudang,
        qty: 100.000,
        jenis: JenisMutasiStok::PembelianMasuk,
        referensi: 'PB-2026-000010',
        userId: $this->user->id
    );

    // Stock opname physical count = 95 (difference = -5)
    $stok = $this->stockService->adjust(
        produk: $this->produk,
        gudang: $this->gudang,
        physicalQty: 95.000,
        referensi: 'SO-2026-000001',
        referensiType: 'stock_opname',
        referensiId: 1,
        userId: $this->user->id
    );

    expect((float) $stok->jumlah)->toEqual(95.000);

    $this->assertDatabaseHas('mutasi_stok', [
        'produk_id' => $this->produk->id,
        'gudang_id' => $this->gudang->id,
        'jenis' => JenisMutasiStok::PenyesuaianOpname->value,
        'jumlah' => -5.000,
        'referensi' => 'SO-2026-000001',
    ]);
});

test('TEST 16: aggregated multi-warehouse stock calculation works dynamically', function () {
    $gudang2 = Gudang::factory()->create();

    $this->stockService->increase($this->produk, $this->gudang, 40.000, JenisMutasiStok::PembelianMasuk, 'PB-01', userId: $this->user->id);
    $this->stockService->increase($this->produk, $gudang2, 25.000, JenisMutasiStok::PembelianMasuk, 'PB-02', userId: $this->user->id);

    expect($this->stockService->getTotalStock($this->produk))->toEqual(65.000);
});
