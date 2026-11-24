<?php

namespace App\Providers;

use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Audit\Policies\ActivityLogPolicy;
use App\Domains\Auth\Policies\UserPolicy;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Catalog\Policies\GudangPolicy;
use App\Domains\Catalog\Policies\KategoriPolicy;
use App\Domains\Catalog\Policies\MerekPolicy;
use App\Domains\Catalog\Policies\PelangganPolicy;
use App\Domains\Catalog\Policies\ProdukPolicy;
use App\Domains\Catalog\Policies\SupplierPolicy;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Models\TransferGudang;
use App\Domains\Inventory\Policies\MutasiStokPolicy;
use App\Domains\Inventory\Policies\StockOpnamePolicy;
use App\Domains\Inventory\Policies\StokPolicy;
use App\Domains\Inventory\Policies\TransferGudangPolicy;
use App\Domains\Production\Models\Produksi;
use App\Domains\Production\Policies\ProduksiPolicy;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Purchasing\Policies\PembelianPolicy;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Receivables\Policies\PembayaranPolicy;
use App\Domains\Returns\Models\ReturPelanggan;
use App\Domains\Returns\Models\ReturSupplier;
use App\Domains\Returns\Policies\ReturPelangganPolicy;
use App\Domains\Returns\Policies\ReturSupplierPolicy;
use App\Domains\Sales\Models\Penjualan;
use App\Domains\Sales\Policies\PenjualanPolicy;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(Kategori::class, KategoriPolicy::class);
        Gate::policy(Merek::class, MerekPolicy::class);
        Gate::policy(Gudang::class, GudangPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Pelanggan::class, PelangganPolicy::class);
        Gate::policy(Produk::class, ProdukPolicy::class);
        Gate::policy(Stok::class, StokPolicy::class);
        Gate::policy(MutasiStok::class, MutasiStokPolicy::class);
        Gate::policy(TransferGudang::class, TransferGudangPolicy::class);
        Gate::policy(StockOpname::class, StockOpnamePolicy::class);
        Gate::policy(Pembelian::class, PembelianPolicy::class);
        Gate::policy(Penjualan::class, PenjualanPolicy::class);
        Gate::policy(Pembayaran::class, PembayaranPolicy::class);
        Gate::policy(Produksi::class, ProduksiPolicy::class);
        Gate::policy(ReturSupplier::class, ReturSupplierPolicy::class);
        Gate::policy(ReturPelanggan::class, ReturPelangganPolicy::class);
    }
}
