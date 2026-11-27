<div class="py-1 py-md-3">
    <!-- Page Header & Period Filter -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center flex-wrap gap-2 gap-md-3 mb-3 mb-md-4">
        <div>
            <h4 class="fw-bold mb-1 text-gray-800">
                <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Operasional
            </h4>
            <p class="text-muted small mb-0">Ringkasan performa stok, transaksi, dan piutang sistem Natural Gypsum & PVC.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap w-100 w-md-auto">
            <div class="btn-group btn-group-sm w-100 w-sm-auto overflow-x-auto" role="group">
                <button type="button" wire:click="$set('period', 'today')" class="btn text-nowrap px-2 px-sm-2.5 {{ $period === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Hari Ini</button>
                <button type="button" wire:click="$set('period', 'last_7_days')" class="btn text-nowrap px-2 px-sm-2.5 {{ $period === 'last_7_days' ? 'btn-primary' : 'btn-outline-secondary' }}">7 Hari</button>
                <button type="button" wire:click="$set('period', 'this_month')" class="btn text-nowrap px-2 px-sm-2.5 {{ $period === 'this_month' ? 'btn-primary' : 'btn-outline-secondary' }}">Bulan Ini</button>
                <button type="button" wire:click="$set('period', 'last_month')" class="btn text-nowrap px-2 px-sm-2.5 {{ $period === 'last_month' ? 'btn-primary' : 'btn-outline-secondary' }}">Bulan Lalu</button>
                <button type="button" wire:click="$set('period', 'custom')" class="btn text-nowrap px-2 px-sm-2.5 {{ $period === 'custom' ? 'btn-primary' : 'btn-outline-secondary' }}">Kustom</button>
            </div>
            @if($period === 'custom')
                <div class="d-flex align-items-center gap-1 w-100 w-sm-auto">
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm flex-fill" style="min-width: 120px;">
                    <span class="text-muted small">-</span>
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm flex-fill" style="min-width: 120px;">
                </div>
            @endif
        </div>
    </div>

    <!-- Inventory / Stock Overview Cards (2x2 Grid on Mobile) -->
    <h6 class="fw-bold text-muted text-uppercase mb-2 mb-md-3 small"><i class="bi bi-boxes me-1"></i> Ringkasan Stok & Inventori</h6>
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Total Item Produk</span>
                        <div class="p-1.5 p-sm-2 bg-primary-subtle text-primary rounded d-none d-sm-flex"><i class="bi bi-box-seam fs-5"></i></div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark fs-5 fs-sm-4">{{ number_format($stockSummary['total_items'] ?? 0, 0, ',', '.') }}</h4>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">Stok Fisik: <span class="fw-semibold text-dark">{{ number_format($stockSummary['total_physical_stock'] ?? 0, 1, ',', '.') }}</span></small>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Stok Menipis</span>
                        <div class="p-1.5 p-sm-2 bg-warning-subtle text-warning rounded d-none d-sm-flex"><i class="bi bi-exclamation-triangle fs-5"></i></div>
                    </div>
                    <h4 class="fw-bold mb-0 text-warning fs-5 fs-sm-4">{{ number_format($stockSummary['low_stock_count'] ?? 0, 0, ',', '.') }}</h4>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">Stok &le; Stok Minimum</small>
                </div>
            </div>
        </div>

        @can('view_purchase_prices')
            <div class="col-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0 border-start border-success border-4">
                    <div class="card-body p-2.5 p-sm-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Estimasi Nilai Stok</span>
                            <div class="p-1.5 p-sm-2 bg-success-subtle text-success rounded d-none d-sm-flex"><i class="bi bi-cash-stack fs-5"></i></div>
                        </div>
                        <h4 class="fw-bold mb-0 text-success fs-6 fs-sm-4 font-monospace">Rp {{ number_format($stockSummary['estimated_stock_value'] ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">Stok &times; Harga Beli</small>
                    </div>
                </div>
            </div>
        @endcan

        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0 border-start border-danger border-4">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Total Sisa Piutang</span>
                        <div class="p-1.5 p-sm-2 bg-danger-subtle text-danger rounded d-none d-sm-flex"><i class="bi bi-credit-card-2-front fs-5"></i></div>
                    </div>
                    <h4 class="fw-bold mb-0 text-danger fs-6 fs-sm-4 font-monospace">Rp {{ number_format($receivablesSummary['total_outstanding'] ?? 0, 0, ',', '.') }}</h4>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">{{ $receivablesSummary['unpaid_invoice_count'] ?? 0 }} Faktur ({{ $receivablesSummary['customer_count'] ?? 0 }} Plg)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Summary for Selected Period (2x2 Grid on Mobile) -->
    <h6 class="fw-bold text-muted text-uppercase mb-2 mb-md-3 small"><i class="bi bi-graph-up me-1"></i> Ringkasan Penjualan & Pembelian (Periode Terpilih)</h6>
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Total Penjualan</span>
                        <div class="p-1.5 p-sm-2 bg-success-subtle text-success rounded d-none d-sm-flex"><i class="bi bi-cart-check fs-5"></i></div>
                    </div>
                    <h5 class="fw-bold mb-0 text-success fs-6 fs-sm-5 font-monospace">Rp {{ number_format($salesSummary['total_sales'] ?? 0, 0, ',', '.') }}</h5>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">{{ $salesSummary['transaction_count'] ?? 0 }} Transaksi Penjualan</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Pembayaran Masuk</span>
                        <div class="p-1.5 p-sm-2 bg-info-subtle text-info rounded d-none d-sm-flex"><i class="bi bi-cash-coin fs-5"></i></div>
                    </div>
                    <h5 class="fw-bold mb-0 text-info fs-6 fs-sm-5 font-monospace">Rp {{ number_format($paymentSummary['total_collected'] ?? 0, 0, ',', '.') }}</h5>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">{{ $paymentSummary['payment_count'] ?? 0 }} Pembayaran Diterima</small>
                </div>
            </div>
        </div>

        @can('view_purchase_prices')
            <div class="col-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-2.5 p-sm-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Total Pembelian</span>
                            <div class="p-1.5 p-sm-2 bg-secondary-subtle text-secondary rounded d-none d-sm-flex"><i class="bi bi-bag-plus fs-5"></i></div>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark fs-6 fs-sm-5 font-monospace">Rp {{ number_format($purchaseSummary['total_purchases'] ?? 0, 0, ',', '.') }}</h5>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">{{ $purchaseSummary['transaction_count'] ?? 0 }} Transaksi Pembelian</small>
                    </div>
                </div>
            </div>
        @endcan

        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">Produksi & Retur</span>
                        <div class="p-1.5 p-sm-2 bg-primary-subtle text-primary rounded d-none d-sm-flex"><i class="bi bi-arrow-repeat fs-5"></i></div>
                    </div>
                    <h6 class="fw-bold mb-0.5 text-dark" style="font-size: 0.88rem;">{{ number_format($productionSummary['total_qty_produced'] ?? 0, 1, ',', '.') }} Produksi</h6>
                    <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $productionSummary['total_batches'] ?? 0 }} Batch &bull; Retur: {{ ($returnsSummary['supplier_returns_count'] ?? 0) + ($returnsSummary['customer_returns_count'] ?? 0) }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links to Reports (2-Column Grid on Mobile) -->
    <div class="card shadow-sm border-0 mb-3 mb-md-4">
        <div class="card-header bg-white py-2.5 py-md-3 border-0">
            <h6 class="fw-bold mb-0"><i class="bi bi-grid-fill text-primary me-2"></i>Pusat Laporan & Navigasi Cepat</h6>
        </div>
        <div class="card-body p-2.5 p-md-3 pt-0 pt-md-2">
            <div class="row g-2">
                @can('view_stock_reports')
                    <div class="col-6 col-md-3">
                        <a href="{{ route('laporan.stok') }}" class="btn btn-outline-primary btn-sm w-100 text-start p-2 text-truncate">
                            <i class="bi bi-layers me-1.5"></i>Laporan Stok & Nilai
                        </a>
                    </div>
                @endcan
                @can('view_sales_reports')
                    <div class="col-6 col-md-3">
                        <a href="{{ route('laporan.penjualan') }}" class="btn btn-outline-success btn-sm w-100 text-start p-2 text-truncate">
                            <i class="bi bi-cart-check me-1.5"></i>Laporan Penjualan
                        </a>
                    </div>
                @endcan
                @can('view_purchase_reports')
                    <div class="col-6 col-md-3">
                        <a href="{{ route('laporan.pembelian') }}" class="btn btn-outline-secondary btn-sm w-100 text-start p-2 text-truncate">
                            <i class="bi bi-bag-plus me-1.5"></i>Laporan Pembelian
                        </a>
                    </div>
                @endcan
                @can('view_receivable_reports')
                    <div class="col-6 col-md-3">
                        <a href="{{ route('laporan.piutang') }}" class="btn btn-outline-danger btn-sm w-100 text-start p-2 text-truncate">
                            <i class="bi bi-cash-stack me-1.5"></i>Laporan Piutang
                        </a>
                    </div>
                @endcan
                @can('view_payment_reports')
                    <div class="col-6 col-md-3">
                        <a href="{{ route('laporan.pembayaran') }}" class="btn btn-outline-info btn-sm w-100 text-start p-2 text-truncate">
                            <i class="bi bi-credit-card me-1.5"></i>Laporan Pembayaran
                        </a>
                    </div>
                @endcan
                @can('view_production_reports')
                    <div class="col-6 col-md-3">
                        <a href="{{ route('laporan.produksi') }}" class="btn btn-outline-primary btn-sm w-100 text-start p-2 text-truncate">
                            <i class="bi bi-gear-wide-connected me-1.5"></i>Laporan Produksi
                        </a>
                    </div>
                @endcan
                @can('view_return_reports')
                    <div class="col-6 col-md-3">
                        <a href="{{ route('laporan.retur') }}" class="btn btn-outline-warning btn-sm w-100 text-start p-2 text-truncate">
                            <i class="bi bi-arrow-return-left me-1.5"></i>Laporan Retur
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    <!-- Recent Sales Activity -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-white py-2.5 py-md-3 border-bottom d-flex justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-muted me-2"></i>Aktivitas Penjualan Terbaru</h6>
            <a href="{{ route('laporan.penjualan') }}" class="btn btn-sm btn-link text-decoration-none p-0 text-nowrap">
                <span class="d-none d-sm-inline">Lihat Semua </span>Laporan &rarr;
            </a>
        </div>

        <!-- Mobile Card List View (< 768px) -->
        <div class="d-md-none">
            @forelse($recentTransactions as $tx)
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-2">
                    <div class="min-w-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-primary font-monospace">{{ $tx['doc_no'] }}</span>
                            <small class="text-muted" style="font-size: 0.74rem;">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/Y') }}</small>
                        </div>
                        <div class="fw-semibold text-dark text-truncate mt-0.5" style="font-size: 0.86rem;">
                            {{ $tx['party'] }}
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="fw-bold text-dark font-monospace" style="font-size: 0.88rem;">
                            Rp {{ number_format($tx['amount'], 0, ',', '.') }}
                        </div>
                        <div class="mt-0.5">
                            @if($tx['status'] === 'lunas')
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Lunas</span>
                            @elseif($tx['status'] === 'sebagian')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.68rem;">Sebagian</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">Belum Bayar</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 px-3 text-muted small">
                    Belum ada transaksi penjualan terbaru.
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (>= 768px) -->
        <div class="table-responsive d-none d-md-block">
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
