<?php

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Reports\Services\DashboardService;
use App\Domains\Reports\Services\PaymentReportService;
use App\Domains\Reports\Services\ProductionReportService;
use App\Domains\Reports\Services\PurchaseReportService;
use App\Domains\Reports\Services\ReceivablesReportService;
use App\Domains\Reports\Services\ReturnsReportService;
use App\Domains\Reports\Services\SalesReportService;
use App\Domains\Reports\Services\StockReportService;
use App\Domains\Sales\Models\Penjualan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->owner->assignRole('Owner');

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $this->kategori = Kategori::factory()->create(['nama_kategori' => 'Gypsum']);
    $this->merek = Merek::factory()->create(['nama_merek' => 'Jayaboard']);
    $this->produk = Produk::factory()->create([
        'nama' => 'Produk A',
        'satuan' => SatuanProduk::Batang,
        'kategori_id' => $this->kategori->id,
        'merek_id' => $this->merek->id,
        'harga_beli' => 50000,
        'harga_jual' => 70000,
        'stok_minimum' => 10,
    ]);

    Stok::create(['produk_id' => $this->produk->id, 'gudang_id' => $this->gudang->id, 'jumlah' => 100]);
});

test('TEST 1 — READ-ONLY GUARANTEE: executing all report and dashboard services causes ZERO mutations', function () {
    $initialCounts = [
        'produks' => Produk::count(),
        'stoks' => Stok::count(),
        'mutasi_stoks' => MutasiStok::count(),
        'pembelians' => Pembelian::count(),
        'penjualans' => Penjualan::count(),
        'pembayarans' => Pembayaran::count(),
    ];

    $initialStockQty = (float) Stok::where('produk_id', $this->produk->id)->value('jumlah');

    // Execute all report services
    app(DashboardService::class)->getStockSummary();
    app(DashboardService::class)->getSalesSummary();
    app(DashboardService::class)->getPurchaseSummary();
    app(DashboardService::class)->getReceivablesSummary();
    app(DashboardService::class)->getPaymentSummary();
    app(DashboardService::class)->getProductionSummary();
    app(DashboardService::class)->getReturnsSummary();
    app(DashboardService::class)->getRecentTransactions();

    app(StockReportService::class)->getSummary([]);
    app(StockReportService::class)->getFilteredQuery([])->get();

    app(SalesReportService::class)->getSummary([]);
    app(SalesReportService::class)->getFilteredQuery([])->get();

    app(PurchaseReportService::class)->getSummary([]);
    app(PurchaseReportService::class)->getFilteredQuery([])->get();

    app(ReceivablesReportService::class)->getSummary([]);
    app(ReceivablesReportService::class)->getFilteredQuery([])->get();

    app(PaymentReportService::class)->getSummary([]);
    app(PaymentReportService::class)->getFilteredQuery([])->get();

    app(ProductionReportService::class)->getSummary([]);
    app(ProductionReportService::class)->getFilteredQuery([])->get();

    app(ReturnsReportService::class)->getSummary([]);
    app(ReturnsReportService::class)->getFilteredSupplierQuery([])->get();
    app(ReturnsReportService::class)->getFilteredCustomerQuery([])->get();

    // Verify all table row counts remain strictly identical
    expect(Produk::count())->toBe($initialCounts['produks'])
        ->and(Stok::count())->toBe($initialCounts['stoks'])
        ->and(MutasiStok::count())->toBe($initialCounts['mutasi_stoks'])
        ->and(Pembelian::count())->toBe($initialCounts['pembelians'])
        ->and(Penjualan::count())->toBe($initialCounts['penjualans'])
        ->and(Pembayaran::count())->toBe($initialCounts['pembayarans']);

    // Verify stock quantity did not change
    $finalStockQty = (float) Stok::where('produk_id', $this->produk->id)->value('jumlah');
    expect($finalStockQty)->toBe($initialStockQty);
});
