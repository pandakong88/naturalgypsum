<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Penjualan</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-cart-check text-success me-2"></i>Laporan &amp; Analisis Penjualan
            </h1>
            <p class="text-muted small mb-0">Rekapitulasi omset faktur aktif, estimasi laba kotor &amp; margin, produk terlaris, serta pelanggan terbaik.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Cetak
            </button>
            <a href="{{ route('laporan.hub') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Hub
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Penjualan</div>
                    <div class="h4 fw-bold text-success mb-1">Rp {{ number_format($summary['total_penjualan'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">{{ $summary['total_transaksi'] ?? 0 }} transaksi faktur aktif</div>
                </div>
            </div>
        </div>
        @if($canViewPurchasePrices)
            <div class="col-sm-6 col-xl">
                <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small text-uppercase fw-semibold">Est. Laba Kotor</div>
                            <span class="badge bg-warning-subtle text-warning-emphasis border">Margin {{ $summary['margin_persen'] ?? 0 }}%</span>
                        </div>
                        <div class="h4 fw-bold text-dark mb-1">Rp {{ number_format($summary['estimasi_laba_kotor'] ?? 0, 0, ',', '.') }}</div>
                        <div class="small text-muted">Modal HPP: Rp {{ number_format($summary['total_hpp_estimasi'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endif
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Terbayar</div>
                    <div class="h4 fw-bold text-primary mb-1">Rp {{ number_format($summary['total_terbayar'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Kas &amp; transfer diterima</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Sisa Piutang</div>
                    <div class="h4 fw-bold text-danger mb-1">Rp {{ number_format($summary['total_sisa_piutang'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Tagihan belum tertagih</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Rata-rata Transaksi</div>
                    @php
                        $avg = ($summary['total_transaksi'] ?? 0) > 0 ? ($summary['total_penjualan'] ?? 0) / $summary['total_transaksi'] : 0;
                    @endphp
                    <div class="h4 fw-bold text-info mb-1">Rp {{ number_format($avg, 0, ',', '.') }}</div>
                    <div class="small text-muted">{{ number_format($summary['total_unit_terjual'] ?? 0, 0, ',', '.') }} total unit terjual</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari No Faktur / Pelanggan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik no faktur / nama...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Pelanggan</label>
                    <select wire:model.live="pelanggan_id" class="form-select form-select-sm">
                        <option value="">Semua Pelanggan</option>
                        @foreach($pelangganList as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Gudang</label>
                    <select wire:model.live="gudang_id" class="form-select form-select-sm">
                        <option value="">Semua Gudang</option>
                        @foreach($gudangList as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status Pembayaran</label>
                    <select wire:model.live="status_pembayaran" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="lunas">Lunas</option>
                        <option value="sebagian">Sebagian</option>
                        <option value="belum_bayar">Belum Bayar</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Periode Cepat</label>
                    <select wire:model.live="period" class="form-select form-select-sm">
                        <option value="all">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="last_7_days">7 Hari Terakhir</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="custom">Kustom Tanggal</option>
                    </select>
                </div>
                @if($period === 'custom')
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Rentang Tanggal</label>
                        <div class="input-group input-group-sm">
                            <input type="date" wire:model.live="startDate" class="form-control">
                            <span class="input-group-text">s/d</span>
                            <input type="date" wire:model.live="endDate" class="form-control">
                        </div>
                    </div>
                @endif
                <div class="col-md-auto d-flex align-items-end">
                    <button wire:click="resetFilter" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytical Insights Grid: Top 5 Products & Top 5 Customers -->
    @if($topProducts->isNotEmpty() || $topCustomers->isNotEmpty())
        <div class="row g-4 mb-4">
            <!-- Left: Top 5 Best-Selling Products -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-trophy-fill text-warning me-2"></i>Top 5 Produk / Material Terlaris
                        </h6>
                        <span class="badge bg-light text-secondary border small">Berdasarkan Omset</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Produk / Material</th>
                                        <th class="text-center">Qty Terjual</th>
                                        <th class="text-end">Total Omset</th>
                                        @if($canViewPurchasePrices)
                                            <th class="text-end pe-3">Est. Laba</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topProducts as $idx => $prod)
                                        <tr>
                                            <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark small">{{ $prod['nama'] }}</div>
                                                <small class="text-muted font-monospace" style="font-size: 0.7rem;">{{ $prod['kode_katalog'] }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary border">
                                                    {{ number_format($prod['total_qty'], 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td class="text-end fw-semibold small">
                                                Rp {{ number_format($prod['total_revenue'], 0, ',', '.') }}
                                            </td>
                                            @if($canViewPurchasePrices)
                                                <td class="text-end pe-3 fw-bold text-success small">
                                                    +Rp {{ number_format($prod['total_profit'], 0, ',', '.') }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Top 5 Customers / Contractors -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-people-fill text-primary me-2"></i>Top 5 Pelanggan / Pemborong Terbaik
                        </h6>
                        <span class="badge bg-light text-secondary border small">Volume Belanja</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Pelanggan</th>
                                        <th class="text-center">Faktur</th>
                                        <th class="text-end">Total Belanja</th>
                                        <th class="text-end pe-3">Sisa Piutang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topCustomers as $idx => $cust)
                                        <tr>
                                            <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark small">{{ $cust['nama'] }}</div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">{{ strtoupper($cust['tipe']) }}</span>
                                            </td>
                                            <td class="text-center small">{{ $cust['total_faktur'] }} nota</td>
                                            <td class="text-end fw-bold text-dark small">
                                                Rp {{ number_format($cust['total_belanja'], 0, ',', '.') }}
                                            </td>
                                            <td class="text-end pe-3 small {{ $cust['sisa_piutang'] > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                                {{ $cust['sisa_piutang'] > 0 ? 'Rp ' . number_format($cust['sisa_piutang'], 0, ',', '.') : 'Lunas' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-gray-800">
                <i class="bi bi-receipt me-2"></i>Daftar Faktur Penjualan
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Per halaman:</span>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>No Faktur</th>
                        <th>Pelanggan</th>
                        <th>Gudang</th>
                        <th class="text-end">Total Akhir</th>
                        @if($canViewPurchasePrices)
                            <th class="text-end">Est. Laba Kotor</th>
                        @endif
                        <th class="text-end">Terbayar</th>
                        <th class="text-end">Sisa Piutang</th>
                        <th class="text-center">Status</th>
                        <th class="pe-3 text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $index => $row)
                        @php
                            $rowCogs = 0.0;
                            $rowRev = 0.0;
                            if ($canViewPurchasePrices) {
                                foreach ($row->details as $det) {
                                    $rowRev += (float) $det->subtotal;
                                    $rowCogs += (float) $det->jumlah * (float) ($det->produk->harga_beli ?? 0);
                                }
                            }
                            $rowProfit = $rowRev - $rowCogs;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $sales->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                            <td class="fw-bold text-primary">{{ $row->no_penjualan }}</td>
                            <td>{{ $row->pelanggan->nama ?? '-' }}</td>
                            <td>{{ $row->gudang->nama_gudang ?? '-' }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($row->total_akhir, 0, ',', '.') }}</td>
                            @if($canViewPurchasePrices)
                                <td class="text-end fw-semibold {{ $rowProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $rowProfit >= 0 ? '+' : '' }}Rp {{ number_format($rowProfit, 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="text-end text-success">Rp {{ number_format($row->total_dibayar, 0, ',', '.') }}</td>
                            <td class="text-end {{ $row->sisa_piutang > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                Rp {{ number_format($row->sisa_piutang, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($row->status_pembayaran->value === 'lunas')
                                    <span class="badge bg-success">Lunas</span>
                                @elseif($row->status_pembayaran->value === 'sebagian')
                                    <span class="badge bg-warning text-dark">Sebagian</span>
                                @else
                                    <span class="badge bg-danger">Belum Bayar</span>
                                @endif
                            </td>
                            <td class="pe-3 text-center">
                                <a href="{{ route('penjualan.show', $row->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canViewPurchasePrices ? 11 : 10 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data penjualan yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>
