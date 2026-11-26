<aside id="sidebar" :class="{ 'show': sidebarOpen }">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <img src="{{ asset('images/logo-natural-modern-icon.svg') }}" alt="Natural Gypsum & PVC" width="38" height="38" class="rounded-2 flex-shrink-0">
        <div class="d-flex flex-column lh-1">
            <span class="fw-bolder text-white" style="letter-spacing: 0.14em; font-size: 1.05rem;">NATURAL</span>
            <span class="fw-bold mt-1" style="color: #C28550; letter-spacing: 0.18em; font-size: 0.64rem;">GYPSUM &amp; PVC</span>
            <span class="visually-hidden">Natural Gypsum</span>
        </div>
    </a>

    <div class="sidebar-nav">
        <!-- Dashboard -->
        <div class="nav-header">Utama</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <!-- Master Data -->
        <div class="nav-header">Master Data</div>
        @can('view_products')
            <a href="{{ route('master.produk.index') }}" class="nav-link {{ request()->routeIs('master.produk.*') ? 'active' : '' }}">
                <i class="bi bi-boxes"></i>
                <span>Produk</span>
            </a>
            <a href="{{ route('master.diskon.index') }}" class="nav-link {{ request()->routeIs('master.diskon.*') ? 'active' : '' }}">
                <i class="bi bi-percent"></i>
                <span>Aturan Diskon</span>
            </a>
        @endcan
        @can('view_categories')
            <a href="{{ route('master.kategori.index') }}" class="nav-link {{ request()->routeIs('master.kategori.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i>
                <span>Kategori</span>
            </a>
        @endcan
        @can('view_brands')
            <a href="{{ route('master.merek.index') }}" class="nav-link {{ request()->routeIs('master.merek.*') ? 'active' : '' }}">
                <i class="bi bi-patch-check"></i>
                <span>Merek</span>
            </a>
        @endcan
        @can('view_warehouses')
            <a href="{{ route('master.gudang.index') }}" class="nav-link {{ request()->routeIs('master.gudang.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                <span>Gudang</span>
            </a>
        @endcan
        @can('view_customers')
            <a href="{{ route('master.pelanggan.index') }}" class="nav-link {{ request()->routeIs('master.pelanggan.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>Pelanggan</span>
            </a>
        @endcan
        @can('view_suppliers')
            <a href="{{ route('master.supplier.index') }}" class="nav-link {{ request()->routeIs('master.supplier.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i>
                <span>Supplier</span>
            </a>
        @endcan

        <!-- Inventory / Stok -->
        <div class="nav-header">Stok</div>
        @can('view_stock')
            <a href="{{ route('stok.index') }}" class="nav-link {{ request()->routeIs('stok.index') ? 'active' : '' }}">
                <i class="bi bi-layers"></i>
                <span>Stok Saat Ini</span>
            </a>
        @endcan
        @can('view_stock_mutations')
            <a href="{{ route('stok.mutasi.index') }}" class="nav-link {{ request()->routeIs('stok.mutasi.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i>
                <span>Mutasi Stok</span>
            </a>
        @endcan
        @can('view_transfers')
            <a href="{{ route('transfer.index') }}" class="nav-link {{ request()->routeIs('transfer.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-repeat"></i>
                <span>Transfer Gudang</span>
            </a>
        @endcan
        @can('view_stock_opnames')
            <a href="{{ route('inventory.stock-opname.index') }}" class="nav-link {{ request()->routeIs('inventory.stock-opname.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i>
                <span>Stok Opname</span>
            </a>
        @endcan

        <!-- Transactions -->
        <div class="nav-header">Transaksi</div>
        @can('view_purchases')
            <a href="{{ route('pembelian.index') }}" class="nav-link {{ request()->routeIs('pembelian.*') ? 'active' : '' }}">
                <i class="bi bi-cart-plus"></i>
                <span>Pembelian</span>
            </a>
        @endcan
        @can('view_sales')
            <a href="{{ route('penjualan.index') }}" class="nav-link {{ request()->routeIs('penjualan.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i>
                <span>Penjualan</span>
            </a>
        @endcan
        @can('view_payments')
            <a href="{{ route('pembayaran.index') }}" class="nav-link {{ request()->routeIs('pembayaran.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>Pembayaran</span>
            </a>
        @endcan
        @can('view_receivables')
            <a href="{{ route('piutang.index') }}" class="nav-link {{ request()->routeIs('piutang.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i>
                <span>Piutang</span>
            </a>
        @endcan
        @can('view_production')
            <a href="{{ route('produksi.index') }}" class="nav-link {{ request()->routeIs('produksi.*') ? 'active' : '' }}">
                <i class="bi bi-gear-wide-connected"></i>
                <span>Produksi</span>
            </a>
        @endcan

        <!-- Returns -->
        <div class="nav-header">Retur</div>
        @can('view_supplier_returns')
            <a href="{{ route('retur-supplier.index') }}" class="nav-link {{ request()->routeIs('retur-supplier.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i>
                <span>Retur Supplier</span>
            </a>
        @endcan
        @can('view_customer_returns')
            <a href="{{ route('retur-pelanggan.index') }}" class="nav-link {{ request()->routeIs('retur-pelanggan.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-right"></i>
                <span>Retur Pelanggan</span>
            </a>
        @endcan

        <!-- Reports -->
        @can('view_reports')
            <div class="nav-header">Laporan</div>
            <a href="{{ route('laporan.hub') }}" class="nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Laporan & Analisis</span>
            </a>
        @endcan

        <!-- Administration -->
        <div class="nav-header">Administrasi</div>
        @can('view_users')
            <a href="{{ route('admin.profil-perusahaan.index') }}" class="nav-link {{ request()->routeIs('admin.profil-perusahaan.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i>
                <span>Profil Perusahaan</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i>
                <span>User</span>
            </a>
        @endcan
        @can('view_roles')
            <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i>
                <span>Role & Hak Akses</span>
            </a>
        @endcan
        <a href="#" class="nav-link disabled" title="Modul Riwayat Harga">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Harga</span>
        </a>
        @can('view_activity_logs')
            <a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Activity Log</span>
            </a>
        @endcan
    </div>

    <div class="sidebar-footer d-flex align-items-center justify-content-between">
        <div>
            <span class="d-block fw-semibold text-light">v0.10.0 (Phase 9)</span>
            <small class="text-secondary">Reports & Dashboard</small>
        </div>
    </div>
</aside>

