<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Reports\Services\StockReportService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->gudangA = Gudang::factory()->create(['nama_gudang' => 'Gudang 1']);
    $this->gudangB = Gudang::factory()->create(['nama_gudang' => 'Gudang 2']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Gypsum Board']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);

    $this->produk1 = Produk::factory()->create([
        'nama' => 'Gypsum A',
        'satuan' => SatuanProduk::Lembar,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 60000,
        'harga_jual' => 75000,
        'stok_minimum' => 10,
    ]);

    $this->produk2 = Produk::factory()->create([
        'nama' => 'Gypsum B',
        'satuan' => SatuanProduk::Lembar,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 80000,
        'harga_jual' => 95000,
        'stok_minimum' => 10,
    ]);

    // Stok:
    // P1 in GDG-A = 50 unit -> value = 50 * 60,000 = 3,000,000
    // P1 in GDG-B = 20 unit -> value = 20 * 60,000 = 1,200,000
    // P2 in GDG-A = 30 unit -> value = 30 * 80,000 = 2,400,000
    // Total All = 3,000,000 + 1,200,000 + 2,400,000 = 6,600,000
    Stok::create(['produk_id' => $this->produk1->id, 'gudang_id' => $this->gudangA->id, 'jumlah' => 50]);
    Stok::create(['produk_id' => $this->produk1->id, 'gudang_id' => $this->gudangB->id, 'jumlah' => 20]);
    Stok::create(['produk_id' => $this->produk2->id, 'gudang_id' => $this->gudangA->id, 'jumlah' => 30]);
});

test('TEST 1 — StockReportService correctly calculates estimated stock value formula (jumlah * harga_beli)', function () {
    $service = app(StockReportService::class);

    $summaryAll = $service->getSummary([]);
    expect((float) $summaryAll['total_nilai_stok'])->toBe(6600000.0);
    expect((float) $summaryAll['total_stok'])->toBe(100.0);

    // Filter by GDG-A: P1 (50*60k = 3M) + P2 (30*80k = 2.4M) = 5.4M
    $summaryGdgA = $service->getSummary(['gudang_id' => $this->gudangA->id]);
    expect((float) $summaryGdgA['total_nilai_stok'])->toBe(5400000.0);
    expect((float) $summaryGdgA['total_stok'])->toBe(80.0);

    // Filter by GDG-B: P1 (20*60k = 1.2M) = 1.2M
    $summaryGdgB = $service->getSummary(['gudang_id' => $this->gudangB->id]);
    expect((float) $summaryGdgB['total_nilai_stok'])->toBe(1200000.0);
    expect((float) $summaryGdgB['total_stok'])->toBe(20.0);
});
