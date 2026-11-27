<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Hub Laporan &amp; Analisis Bisnis
            </h1>
            <p class="text-muted small mb-0">Pusat kendali analitik, profitabilitas, perputaran stok, dan kesehatan arus kas Natural Gypsum &amp; PVC (Read-Only).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('piutang.index') }}" class="btn btn-outline-warning btn-sm text-dark">
                <i class="bi bi-wallet2 me-1"></i> Kelola Piutang
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard Utama
            </a>
        </div>
    </div>

    <!-- Executive Business Snapshot (Ringkasan Eksekutif Pemilik Toko Bulan Ini) -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-2.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-stars text-warning"></i>
                <span class="fw-bold small text-uppercase" style="letter-spacing: 0.06em;">Ringkasan Eksekutif &amp; Kesehatan Bisnis — {{ now()->translatedFormat('F Y') }}</span>
            </div>
            <span class="badge bg-secondary-subtle text-light border border-secondary small">
                Pembaruan Real-Time
            </span>
        </div>
        <div class="card-body p-3 bg-white">
            <div class="row g-3">
                <!-- 1. Omset Penjualan Bulan Ini -->
                <div class="col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="text-muted small fw-semibold text-uppercase">Omset Bulan Ini</span>
                            <span class="badge {{ $pertumbuhanOmset >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border">
                                <i class="bi bi-graph-{{ $pertumbuhanOmset >= 0 ? 'up' : 'down' }}-arrow me-1"></i>{{ $pertumbuhanOmset >= 0 ? '+' : '' }}{{ $pertumbuhanOmset }}%
                            </span>
                        </div>
                        <div class="h5 fw-bold text-dark mb-1">Rp {{ number_format($omsetBulanIni, 0, ',', '.') }}</div>
                        <div class="small text-muted">
                            {{ number_format($transaksiBulanIni) }} faktur aktif &bull; Bln lalu: Rp {{ number_format($omsetBulanLalu, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- 2. Estimasi Laba Kotor / Kas Masuk -->
                <div class="col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        @if ($canViewPurchasePrices)
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="text-muted small fw-semibold text-uppercase">Est. Laba Kotor (Margin)</span>
                                <span class="badge bg-primary-subtle text-primary border">
                                    Margin {{ $marginBulanIni }}%
                                </span>
                            </div>
                            <div class="h5 fw-bold text-success mb-1">Rp {{ number_format($labaKotorBulanIni, 0, ',', '.') }}</div>
                            <div class="small text-muted">
                                Kas diterima bln ini: <strong>Rp {{ number_format($kasMasukBulanIni, 0, ',', '.') }}</strong>
                            </div>
                        @else
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="text-muted small fw-semibold text-uppercase">Penerimaan Kas Masuk</span>
                                <span class="badge bg-success-subtle text-success border">Bulan Ini</span>
                            </div>
                            <div class="h5 fw-bold text-success mb-1">Rp {{ number_format($kasMasukBulanIni, 0, ',', '.') }}</div>
                            <div class="small text-muted">Tunai &amp; Transfer masuk bulan ini</div>
                        @endif
                    </div>
                </div>

                <!-- 3. Eksposur Piutang & Jatuh Tempo 14 Hari -->
                <div class="col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="text-muted small fw-semibold text-uppercase">Saldo Piutang Tertunggak</span>
                            <span class="badge bg-danger-subtle text-danger border">{{ $fakturBelumLunas }} Nota</span>
                        </div>
                        <div class="h5 fw-bold text-danger mb-1">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
                        <div class="small {{ $fakturLewatTempo > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                            @if ($fakturLewatTempo > 0)
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Lewat tempo (&gt;14 hr): Rp {{ number_format($piutangLewatTempo, 0, ',', '.') }} ({{ $fakturLewatTempo }} nota)
                            @else
                                <i class="bi bi-check-circle-fill me-1"></i>Seluruh piutang dalam tempo 14 hari
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 4. Modal Persediaan Gudang & Hutang Supplier -->
                <div class="col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="text-muted small fw-semibold text-uppercase">Valuasi Modal Stok</span>
                            @if ($stokKritisCount > 0)
                                <span class="badge bg-warning-subtle text-warning-emphasis border">
                                    {{ $stokKritisCount }} Stok Kritis
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border">Stok Aman</span>
                            @endif
                        </div>
                        @if ($canViewPurchasePrices)
                            <div class="h5 fw-bold text-primary mb-1">Rp {{ number_format($nilaiModalStok, 0, ',', '.') }}</div>
                            <div class="small text-muted">
                                Hutang Supplier: <strong class="{{ $hutangSupplier > 0 ? 'text-danger' : 'text-dark' }}">Rp {{ number_format($hutangSupplier, 0, ',', '.') }}</strong>
                            </div>
                        @else
                            <div class="h5 fw-bold text-primary mb-1">{{ number_format($totalSkuStok) }} Posisi SKU</div>
                            <div class="small text-muted">{{ $stokKritisCount }} item perlu restock segera</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Grid -->
    <div class="row g-4">
        <!-- Laporan Penjualan -->
        @can('view_sales_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-3 bg-success-subtle text-success me-3">
                                    <i class="bi bi-cart-check fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Laporan Penjualan</h5>
                                    <small class="text-muted">Omset, Profit &amp; Best-Seller</small>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success border">{{ $transaksiBulanIni }} Nota Bln Ini</span>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Analisis omset penjualan, estimasi laba kotor &amp; margin (%), produk material paling laris, serta peringkat pelanggan/pemborong terbaik.
                        </p>
                        <a href="{{ route('laporan.penjualan') }}" class="btn btn-outline-success btn-sm mt-3 w-100 fw-semibold">
                            Buka Analisis Penjualan <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Laporan Stok -->
        @can('view_stock_reports')
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-3 bg-primary-subtle text-primary me-3">
                                    <i class="bi bi-boxes fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Laporan Stok</h5>
                                    <small class="text-muted">Valuasi Modal &amp; Dead-Stock</small>
                                </div>
                            </div>
                            @if ($stokKritisCount > 0)
                                <span class="badge bg-warning-subtle text-warning-emphasis border">{{ $stokKritisCount }} Rendah/Habis</span>
                            @endif
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Sebaran nilai modal &amp; potensi jual per gudang, deteksi barang tidak bergerak (dead-stock 30 hari), konversi kemasan dus/ikatan, dan stok kritis.
                        </p>
                        <a href="{{ route('laporan.stok') }}" class="btn btn-outline-primary btn-sm mt-3 w-100 fw-semibold">
                            Buka Analisis Stok <i class="bi bi-arrow-right ms-1"></i>
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
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-3 bg-warning-subtle text-warning-emphasis me-3">
                                    <i class="bi bi-clock-history fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Laporan Piutang</h5>
                                    <small class="text-muted">Aging 14 Hari &amp; Kolektibilitas</small>
                                </div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border">{{ $fakturBelumLunas }} Belum Lunas</span>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Analisis umur piutang berstandar tempo 14 hari (Lancar, Jatuh Tempo, Menunggak, Macet), top debitur, dan akses cetak Surat Tagihan (SOA).
                        </p>
                        <a href="{{ route('laporan.piutang') }}" class="btn btn-outline-warning text-dark btn-sm mt-3 w-100 fw-semibold">
                            Buka Analisis Piutang <i class="bi bi-arrow-right ms-1"></i>
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
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-3 bg-danger-subtle text-danger me-3">
                                    <i class="bi bi-truck fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Laporan Pembelian</h5>
                                    <small class="text-muted">Pengadaan &amp; Hutang Supplier</small>
                                </div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border">{{ $totalPoBulanIni }} PO Bln Ini</span>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi belanja stok ke pabrik/supplier, pemantauan sisa hutang tempo pembelian, status penerimaan fisik, dan peringkat supplier terbesar.
                        </p>
                        <a href="{{ route('laporan.pembelian') }}" class="btn btn-outline-danger btn-sm mt-3 w-100 fw-semibold">
                            Buka Analisis Pembelian <i class="bi bi-arrow-right ms-1"></i>
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
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-3 bg-info-subtle text-info me-3">
                                    <i class="bi bi-cash-stack fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Laporan Pembayaran</h5>
                                    <small class="text-muted">Arus Kas Masuk &amp; Alokasi</small>
                                </div>
                            </div>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi arus kas masuk tunai vs transfer bank, rincian alokasi pelunasan faktur, dan pemantauan dana deposit/titipan pelanggan.
                        </p>
                        <a href="{{ route('laporan.pembayaran') }}" class="btn btn-outline-info btn-sm mt-3 w-100 fw-semibold">
                            Buka Analisis Pembayaran <i class="bi bi-arrow-right ms-1"></i>
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
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-3 bg-secondary-subtle text-secondary me-3">
                                    <i class="bi bi-gear-wide-connected fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Laporan Produksi</h5>
                                    <small class="text-muted">Output Barang Jadi</small>
                                </div>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border">{{ $batchProduksiBulanIni }} Batch Bln Ini</span>
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            Rekapitulasi batch produksi barang jadi (output-only tracking) per gudang dan varian produk.
                        </p>
                        <a href="{{ route('laporan.produksi') }}" class="btn btn-outline-secondary btn-sm mt-3 w-100 fw-semibold">
                            Buka Laporan Produksi <i class="bi bi-arrow-right ms-1"></i>
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
                                <small class="text-muted">Supplier &amp; Pelanggan</small>
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
