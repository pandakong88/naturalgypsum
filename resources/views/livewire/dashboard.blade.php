<div class="container-fluid py-4">
    <!-- Page Header & Period Filter -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-gray-800">
                <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Operasional
            </h4>
            <p class="text-muted small mb-0">Ringkasan performa stok, transaksi, dan piutang sistem Natural Gypsum & PVC.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" wire:click="$set('period', 'today')" class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Hari Ini</button>
                <button type="button" wire:click="$set('period', 'last_7_days')" class="btn {{ $period === 'last_7_days' ? 'btn-primary' : 'btn-outline-secondary' }}">7 Hari</button>
                <button type="button" wire:click="$set('period', 'this_month')" class="btn {{ $period === 'this_month' ? 'btn-primary' : 'btn-outline-secondary' }}">Bulan Ini</button>
                <button type="button" wire:click="$set('period', 'last_month')" class="btn {{ $period === 'last_month' ? 'btn-primary' : 'btn-outline-secondary' }}">Bulan Lalu</button>
                <button type="button" wire:click="$set('period', 'custom')" class="btn {{ $period === 'custom' ? 'btn-primary' : 'btn-outline-secondary' }}">Kustom</button>
            </div>
            @if($period === 'custom')
                <div class="d-flex align-items-center gap-1">
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm" style="width: 135px;">
                    <span class="text-muted small">-</span>
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm" style="width: 135px;">
                </div>
            @endif
        </div>
    </div>

    <!-- Inventory / Stock Overview Cards -->
    <h6 class="fw-bold text-muted text-uppercase mb-3 small"><i class="bi bi-boxes me-1"></i> Ringkasan Stok & Inventori</h6>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Total Item Produk</span>
                        <div class="p-2 bg-primary-subtle text-primary rounded"><i class="bi bi-box-seam fs-5"></i></div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($stockSummary['total_items'] ?? 0, 0, ',', '.') }}</h4>
                    <small class="text-muted">Total Stok Fisik: <span class="fw-semibold text-dark">{{ number_format($stockSummary['total_physical_stock'] ?? 0, 3, ',', '.') }}</span></small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Stok Menipis / Rendah</span>
                        <div class="p-2 bg-warning-subtle text-warning rounded"><i class="bi bi-exclamation-triangle fs-5"></i></div>
                    </div>
                    <h4 class="fw-bold mb-0 text-warning">{{ number_format($stockSummary['low_stock_count'] ?? 0, 0, ',', '.') }}</h4>
                    <small class="text-muted">Stok &le; Stok Minimum</small>
                </div>
            </div>
        </div>

        @can('view_purchase_prices')
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0 border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">Estimasi Nilai Stok</span>
                            <div class="p-2 bg-success-subtle text-success rounded"><i class="bi bi-cash-stack fs-5"></i></div>
                        </div>
                        <h4 class="fw-bold mb-0 text-success">Rp {{ number_format($stockSummary['estimated_stock_value'] ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">Estimasi: Stok &times; Harga Beli Terkini</small>
                    </div>
                </div>
            </div>
        @endcan

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0 border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Total Sisa Piutang</span>
                        <div class="p-2 bg-danger-subtle text-danger rounded"><i class="bi bi-credit-card-2-front fs-5"></i></div>
                    </div>
                    <h4 class="fw-bold mb-0 text-danger">Rp {{ number_format($receivablesSummary['total_outstanding'] ?? 0, 0, ',', '.') }}</h4>
                    <small class="text-muted">{{ $receivablesSummary['unpaid_invoice_count'] ?? 0 }} Faktur Belum Lunas ({{ $receivablesSummary['customer_count'] ?? 0 }} Pelanggan)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Summary for Selected Period -->
    <h6 class="fw-bold text-muted text-uppercase mb-3 small"><i class="bi bi-graph-up me-1"></i> Ringkasan Penjualan & Pembelian (Periode Terpilih)</h6>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Total Penjualan</span>
                        <div class="p-2 bg-success-subtle text-success rounded"><i class="bi bi-cart-check fs-5"></i></div>
                    </div>
                    <h5 class="fw-bold mb-0 text-success">Rp {{ number_format($salesSummary['total_sales'] ?? 0, 0, ',', '.') }}</h5>
                    <small class="text-muted">{{ $salesSummary['transaction_count'] ?? 0 }} Transaksi Penjualan</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Penerimaan Pembayaran</span>
                        <div class="p-2 bg-info-subtle text-info rounded"><i class="bi bi-cash-coin fs-5"></i></div>
                    </div>
                    <h5 class="fw-bold mb-0 text-info">Rp {{ number_format($paymentSummary['total_collected'] ?? 0, 0, ',', '.') }}</h5>
                    <small class="text-muted">{{ $paymentSummary['payment_count'] ?? 0 }} Pembayaran Diterima</small>
                </div>
            </div>
        </div>

        @can('view_purchase_prices')
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">Total Pembelian</span>
                            <div class="p-2 bg-secondary-subtle text-secondary rounded"><i class="bi bi-bag-plus fs-5"></i></div>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($purchaseSummary['total_purchases'] ?? 0, 0, ',', '.') }}</h5>
                        <small class="text-muted">{{ $purchaseSummary['transaction_count'] ?? 0 }} Transaksi Pembelian</small>
                    </div>
                </div>
            </div>
        @endcan

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Produksi & Retur</span>
                        <div class="p-2 bg-primary-subtle text-primary rounded"><i class="bi bi-arrow-repeat fs-5"></i></div>
                    </div>
                    <h6 class="fw-bold mb-1 text-dark">{{ number_format($productionSummary['total_qty_produced'] ?? 0, 3, ',', '.') }} Hasil Produksi</h6>
                    <small class="text-muted">{{ $productionSummary['total_batches'] ?? 0 }} Batch | Retur Sup: {{ $returnsSummary['supplier_returns_count'] ?? 0 }} | Retur Pel: {{ $returnsSummary['customer_returns_count'] ?? 0 }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links to Reports -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0"><i class="bi bi-grid-fill text-primary me-2"></i>Pusat Laporan & Navigasi Cepat</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                @can('view_stock_reports')
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route('laporan.stok') }}" class="btn btn-outline-primary btn-sm w-100 text-start p-2">
                            <i class="bi bi-layers me-2"></i>Laporan Stok & Nilai
                        </a>
                    </div>
                @endcan
                @can('view_sales_reports')
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route('laporan.penjualan') }}" class="btn btn-outline-success btn-sm w-100 text-start p-2">
                            <i class="bi bi-cart-check me-2"></i>Laporan Penjualan
                        </a>
                    </div>
                @endcan
                @can('view_purchase_reports')
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route('laporan.pembelian') }}" class="btn btn-outline-secondary btn-sm w-100 text-start p-2">
                            <i class="bi bi-bag-plus me-2"></i>Laporan Pembelian
                        </a>
                    </div>
                @endcan
                @can('view_receivable_reports')
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route('laporan.piutang') }}" class="btn btn-outline-danger btn-sm w-100 text-start p-2">
                            <i class="bi bi-cash-stack me-2"></i>Laporan Piutang
                        </a>
                    </div>
                @endcan
                @can('view_payment_reports')
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route('laporan.pembayaran') }}" class="btn btn-outline-info btn-sm w-100 text-start p-2">
                            <i class="bi bi-credit-card me-2"></i>Laporan Pembayaran
                        </a>
                    </div>
                @endcan
                @can('view_production_reports')
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route('laporan.produksi') }}" class="btn btn-outline-primary btn-sm w-100 text-start p-2">
                            <i class="bi bi-gear-wide-connected me-2"></i>Laporan Produksi
                        </a>
                    </div>
                @endcan
                @can('view_return_reports')
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route('laporan.retur') }}" class="btn btn-outline-warning btn-sm w-100 text-start p-2">
                            <i class="bi bi-arrow-return-left me-2"></i>Laporan Retur
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    <!-- Recent Sales Activity -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-muted me-2"></i>Aktivitas Penjualan Terbaru</h6>
            <a href="{{ route('laporan.penjualan') }}" class="btn btn-sm btn-link text-decoration-none">Lihat Semua Laporan Penjualan &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>No Faktur</th>
                        <th>Pelanggan</th>
                        <th class="text-end">Total Akhir</th>
                        <th class="text-center pe-3">Status Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $tx)
                        <tr>
                            <td class="ps-3 text-muted">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/Y') }}</td>
                            <td class="fw-bold text-primary">{{ $tx['doc_no'] }}</td>
                            <td>{{ $tx['party'] }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($tx['amount'], 0, ',', '.') }}</td>
                            <td class="text-center pe-3">
                                @if($tx['status'] === 'lunas')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Lunas</span>
                                @elseif($tx['status'] === 'sebagian')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Sebagian</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Belum Bayar</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi penjualan terbaru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
