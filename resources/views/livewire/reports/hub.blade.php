<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Hub Laporan & Analisis
            </h1>
            <p class="text-muted small mb-0">Pusat pelaporan dan analisis operasional Natural Gypsum & PVC (Read-Only).</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard Utama
            </a>
        </div>
    </div>

    <!-- Reports Grid -->
    <div class="row g-4">
        <!-- Laporan Stok -->
        @can('view_stock_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-3 bg-primary-subtle text-primary me-3">
                                <i class="bi bi-boxes fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Laporan Stok</h5>
                                <small class="text-muted">Inventori & Nilai Stok</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Informasi stok fisik per gudang, filter stok minimum, dan estimasi nilai persediaan.
                        </p>
                        <a href="{{ route('laporan.stok') }}" class="btn btn-outline-primary btn-sm mt-3 w-100">
                            Buka Laporan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Laporan Penjualan -->
        @can('view_sales_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-3 bg-success-subtle text-success me-3">
                                <i class="bi bi-cart-check fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Laporan Penjualan</h5>
                                <small class="text-muted">Transaksi & Omset</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi faktur penjualan, omset penjualan, status pembayaran, dan rincian item per pelanggan.
                        </p>
                        <a href="{{ route('laporan.penjualan') }}" class="btn btn-outline-success btn-sm mt-3 w-100">
                            Buka Laporan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Laporan Pembelian -->
        @can('view_purchase_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-3 bg-danger-subtle text-danger me-3">
                                <i class="bi bi-truck fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Laporan Pembelian</h5>
                                <small class="text-muted">Pengadaan & Supplier</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi purchase order per supplier, rincian barang masuk, dan total belanja pengadaan.
                        </p>
                        <a href="{{ route('laporan.pembelian') }}" class="btn btn-outline-danger btn-sm mt-3 w-100">
                            Buka Laporan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Laporan Piutang -->
        @can('view_receivable_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-3 bg-warning-subtle text-warning me-3">
                                <i class="bi bi-clock-history fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Laporan Piutang</h5>
                                <small class="text-muted">Outstanding & Tagihan</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Daftar faktur belum lunas, sisa piutang berjalan, dan daftar debitur dengan saldo tagihan terbesar.
                        </p>
                        <a href="{{ route('laporan.piutang') }}" class="btn btn-outline-warning btn-sm mt-3 w-100">
                            Buka Laporan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Laporan Pembayaran -->
        @can('view_payment_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-3 bg-info-subtle text-info me-3">
                                <i class="bi bi-cash-stack fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Laporan Pembayaran</h5>
                                <small class="text-muted">Kas Masuk & Metode Bayar</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi penerimaan pembayaran kas/bank, breakdown tunai/transfer, dan alokasi pelunasan.
                        </p>
                        <a href="{{ route('laporan.pembayaran') }}" class="btn btn-outline-info btn-sm mt-3 w-100">
                            Buka Laporan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Laporan Produksi -->
        @can('view_production_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-3 bg-secondary-subtle text-secondary me-3">
                                <i class="bi bi-gear-wide-connected fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Laporan Produksi</h5>
                                <small class="text-muted">Output Barang Jadi</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi batch produksi barang jadi (output-only tracking) per gudang dan varian produk.
                        </p>
                        <a href="{{ route('laporan.produksi') }}" class="btn btn-outline-secondary btn-sm mt-3 w-100">
                            Buka Laporan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Laporan Retur -->
        @can('view_return_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-3 bg-dark-subtle text-dark me-3">
                                <i class="bi bi-arrow-return-left fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Laporan Retur</h5>
                                <small class="text-muted">Supplier & Pelanggan</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi pengembalian barang ke supplier serta penerimaan retur barang rusak dari pelanggan.
                        </p>
                        <a href="{{ route('laporan.retur') }}" class="btn btn-outline-dark btn-sm mt-3 w-100">
                            Buka Laporan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
