<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Payments\PembayaranPrintController;
use App\Http\Controllers\Purchasing\PembelianPrintController;
use App\Http\Controllers\Sales\PenjualanPrintController;
use App\Livewire\Admin\ActivityLogIndex;
use App\Livewire\Admin\ProfilPerusahaanIndex;
use App\Livewire\Admin\RoleIndex;
use App\Livewire\Admin\UserIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Catalog\GudangIndex;
use App\Livewire\Catalog\KategoriIndex;
use App\Livewire\Catalog\MerekIndex;
use App\Livewire\Catalog\PelangganIndex;
use App\Livewire\Catalog\ProdukDetail;
use App\Livewire\Catalog\ProdukIndex;
use App\Livewire\Catalog\RiwayatHargaIndex;
use App\Livewire\Catalog\SupplierIndex;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\MutasiStokIndex;
use App\Livewire\Inventory\StockOpnameCreate;
use App\Livewire\Inventory\StockOpnameDetail;
use App\Livewire\Inventory\StockOpnameIndex;
use App\Livewire\Inventory\StokIndex;
use App\Livewire\Inventory\TransferCreate;
use App\Livewire\Inventory\TransferDetail;
use App\Livewire\Inventory\TransferIndex;
use App\Livewire\Payments\PembayaranCreate;
use App\Livewire\Payments\PembayaranDetail;
use App\Livewire\Payments\PembayaranIndex;
use App\Livewire\Production\ProduksiCreate;
use App\Livewire\Production\ProduksiDetail;
use App\Livewire\Production\ProduksiIndex;
use App\Livewire\Purchasing\PembelianCreate;
use App\Livewire\Purchasing\PembelianDetail;
use App\Livewire\Purchasing\PembelianEdit;
use App\Livewire\Purchasing\PembelianIndex;
use App\Livewire\Receivables\PiutangIndex;
use App\Livewire\Reports\PaymentReport;
use App\Livewire\Reports\ProductionReport;
use App\Livewire\Reports\PurchaseReport;
use App\Livewire\Reports\ReceivablesReport;
use App\Livewire\Reports\ReportHub;
use App\Livewire\Reports\ReturnsReport;
use App\Livewire\Reports\SalesReport;
use App\Livewire\Reports\StockReport;
use App\Livewire\Returns\ReturPelangganCreate;
use App\Livewire\Returns\ReturPelangganDetail;
use App\Livewire\Returns\ReturPelangganIndex;
use App\Livewire\Returns\ReturSupplierCreate;
use App\Livewire\Returns\ReturSupplierDetail;
use App\Livewire\Returns\ReturSupplierIndex;
use App\Livewire\Sales\AturanDiskonIndex;
use App\Livewire\Sales\PenjualanCreate;
use App\Livewire\Sales\PenjualanDetail;
use App\Livewire\Sales\PenjualanIndex;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard (if authenticated) or login (if guest)
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::post('/logout', LogoutController::class)->name('logout');

    // Master Data (Phase 1)
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/kategori', KategoriIndex::class)->name('kategori.index');
        Route::get('/merek', MerekIndex::class)->name('merek.index');
        Route::get('/gudang', GudangIndex::class)->name('gudang.index');
        Route::get('/supplier', SupplierIndex::class)->name('supplier.index');
        Route::get('/pelanggan', PelangganIndex::class)->name('pelanggan.index');
        Route::get('/produk', ProdukIndex::class)->name('produk.index');
        Route::get('/produk/{id}', ProdukDetail::class)->name('produk.show');
        Route::get('/produk/{id}/detail', ProdukDetail::class)->name('produk.detail');
        Route::get('/riwayat-harga', RiwayatHargaIndex::class)->name('riwayat-harga.index');
        Route::get('/diskon', AturanDiskonIndex::class)->name('diskon.index');
    });

    // Inventory Core (Phase 2)
    Route::prefix('stok')->name('stok.')->group(function () {
        Route::get('/', StokIndex::class)->name('index');
        Route::get('/mutasi', MutasiStokIndex::class)->name('mutasi.index');
    });

    // Transfer Gudang (Phase 6)
    Route::prefix('transfer')->name('transfer.')->group(function () {
        Route::get('/', TransferIndex::class)->name('index');
        Route::get('/create', TransferCreate::class)->name('create');
        Route::get('/{id}', TransferDetail::class)->name('show');
    });

    // Stock Opname (Phase 7)
    Route::prefix('stok-opname')->name('inventory.stock-opname.')->group(function () {
        Route::get('/', StockOpnameIndex::class)->name('index');
        Route::get('/create', StockOpnameCreate::class)->name('create');
        Route::get('/{id}', StockOpnameDetail::class)->name('detail');
    });

    // Purchasing (Phase 3)
    Route::prefix('pembelian')->name('pembelian.')->group(function () {
        Route::get('/', PembelianIndex::class)->name('index');
        Route::get('/create', PembelianCreate::class)->name('create');
        Route::get('/{id}', PembelianDetail::class)->name('show');
        Route::get('/{id}/edit', PembelianEdit::class)->name('edit');
        Route::get('/{pembelian}/cetak/faktur', [PembelianPrintController::class, 'faktur'])->name('print.faktur');
        Route::get('/{pembelian}/cetak/po', [PembelianPrintController::class, 'po'])->name('print.po');
    });

    // Sales (Phase 4)
    Route::prefix('penjualan')->name('penjualan.')->group(function () {
        Route::get('/', PenjualanIndex::class)->name('index');
        Route::get('/create', PenjualanCreate::class)->name('create');
        Route::get('/{id}', PenjualanDetail::class)->name('show');
        Route::get('/{penjualan}/cetak/faktur', [PenjualanPrintController::class, 'faktur'])->name('print.faktur');
        Route::get('/{penjualan}/cetak/surat-jalan', [PenjualanPrintController::class, 'suratJalan'])->name('print.surat-jalan');
        Route::get('/{penjualan}/cetak/thermal', [PenjualanPrintController::class, 'thermal'])->name('print.thermal');
    });

    // Payments & Receivables (Phase 5)
    Route::prefix('pembayaran')->name('pembayaran.')->group(function () {
        Route::get('/', PembayaranIndex::class)->name('index');
        Route::get('/create', PembayaranCreate::class)->name('create');
        Route::get('/{id}', PembayaranDetail::class)->name('show');
        Route::get('/{pembayaran}/cetak/kuitansi', [PembayaranPrintController::class, 'kuitansi'])->name('print.kuitansi');
        Route::get('/{pembayaran}/cetak/thermal', [PembayaranPrintController::class, 'thermal'])->name('print.thermal');
    });

    Route::prefix('piutang')->name('piutang.')->group(function () {
        Route::get('/', PiutangIndex::class)->name('index');
        Route::get('/{pelanggan}/cetak-tagihan', [PembayaranPrintController::class, 'tagihanPelanggan'])->name('print.tagihan');
    });

    // Produksi (Phase 6)
    Route::prefix('produksi')->name('produksi.')->group(function () {
        Route::get('/', ProduksiIndex::class)->name('index');
        Route::get('/create', ProduksiCreate::class)->name('create');
        Route::get('/{id}', ProduksiDetail::class)->name('show');
    });

    // Returns (Phase 6)
    Route::prefix('retur/supplier')->name('retur-supplier.')->group(function () {
        Route::get('/', ReturSupplierIndex::class)->name('index');
        Route::get('/create', ReturSupplierCreate::class)->name('create');
        Route::get('/{id}', ReturSupplierDetail::class)->name('show');
    });

    Route::prefix('retur/pelanggan')->name('retur-pelanggan.')->group(function () {
        Route::get('/', ReturPelangganIndex::class)->name('index');
        Route::get('/create', ReturPelangganCreate::class)->name('create');
        Route::get('/{id}', ReturPelangganDetail::class)->name('show');
    });

    // Administration & Audit (Phase 8)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/profil-perusahaan', ProfilPerusahaanIndex::class)->name('profil-perusahaan.index');
        Route::get('/users', UserIndex::class)->name('users.index');
        Route::get('/roles', RoleIndex::class)->name('roles.index');
        Route::get('/activity-logs', ActivityLogIndex::class)->name('activity-logs.index');
    });

    // Reports & Analytics (Phase 9)
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', ReportHub::class)->name('hub');
        Route::get('/stok', StockReport::class)->name('stok');
        Route::get('/penjualan', SalesReport::class)->name('penjualan');
        Route::get('/pembelian', PurchaseReport::class)->name('pembelian');
        Route::get('/piutang', ReceivablesReport::class)->name('piutang');
        Route::get('/pembayaran', PaymentReport::class)->name('pembayaran');
        Route::get('/produksi', ProductionReport::class)->name('produksi');
        Route::get('/retur', ReturnsReport::class)->name('retur');
    });
});
