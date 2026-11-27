<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Pembelian</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-truck text-danger me-2"></i>Laporan Pembelian &amp; Hutang Supplier
            </h1>
            <p class="text-muted small mb-0">Rekapitulasi belanja pengadaan stok, status penerimaan fisik, serta posisi hutang tempo ke supplier/pabrik.</p>
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
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Pembelian</div>
                    @if($canViewPurchasePrices)
                        <div class="h5 fw-bold text-danger mb-1 fs-6 fs-sm-4">Rp {{ number_format($summary['total_pembelian'] ?? 0, 0, ',', '.') }}</div>
                        <div class="small text-muted" style="font-size: 0.72rem;">Belanja pengadaan</div>
                    @else
                        <div class="h6 fw-bold text-muted mb-1"><i class="bi bi-shield-lock me-1"></i> Restricted</div>
                        <div class="small text-muted" style="font-size: 0.72rem;">Akses dilindungi</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Telah Dibayar</div>
                    @if($canViewPurchasePrices)
                        <div class="h5 fw-bold text-success mb-1 fs-6 fs-sm-4">Rp {{ number_format($summary['total_dibayar_supplier'] ?? 0, 0, ',', '.') }}</div>
                        <div class="small text-muted" style="font-size: 0.72rem;">Pelunasan &amp; DP PO</div>
                    @else
                        <div class="h6 fw-bold text-muted mb-1"><i class="bi bi-shield-lock me-1"></i> Restricted</div>
                        <div class="small text-muted" style="font-size: 0.72rem;">Akses dilindungi</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Hutang Supplier</div>
                    @if($canViewPurchasePrices)
                        <div class="h5 fw-bold text-warning-emphasis mb-1 fs-6 fs-sm-4">Rp {{ number_format($summary['total_hutang_supplier'] ?? 0, 0, ',', '.') }}</div>
                        <div class="small text-muted" style="font-size: 0.72rem;">{{ $summary['total_po_hutang'] ?? 0 }} PO belum lunas</div>
                    @else
                        <div class="h6 fw-bold text-muted mb-1"><i class="bi bi-shield-lock me-1"></i> Restricted</div>
                        <div class="small text-muted" style="font-size: 0.72rem;">Akses dilindungi</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Transaksi PO</div>
                    <div class="h5 fw-bold text-primary mb-1 fs-6 fs-sm-4">{{ number_format($summary['total_transaksi'] ?? 0, 0, ',', '.') }} PO</div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Dokumen PO aktif</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Suppliers Breakdown -->
    @if($canViewPurchasePrices && $topSuppliers->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3 mb-md-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-building-check text-danger me-2"></i>Top 5 Supplier / Pabrik Berdasarkan Volume Pengadaan
                </h6>
                <span class="badge bg-light text-secondary border small">Belanja &amp; Posisi Hutang</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-3" style="width: 45px;">#</th>
                                <th>Nama Supplier / Pabrik</th>
                                <th class="text-center">Jumlah PO</th>
                                <th class="text-end">Total Belanja</th>
                                <th class="text-end pe-3">Sisa Hutang Tempo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topSuppliers as $idx => $sup)
                                <tr>
                                    <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td class="fw-semibold text-dark">{{ $sup['nama_supplier'] }}</td>
                                    <td class="text-center">{{ $sup['total_po'] }} dokumen</td>
                                    <td class="text-end fw-bold text-dark">Rp {{ number_format($sup['total_belanja'], 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 {{ $sup['sisa_hutang'] > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                        {{ $sup['sisa_hutang'] > 0 ? 'Rp ' . number_format($sup['sisa_hutang'], 0, ',', '.') : 'Lunas' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-3 mb-md-4">
        <div class="card-body p-2.5 p-md-3">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Cari No PO / Supplier</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik no PO / supplier...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Supplier</label>
                    <select wire:model.live="supplier_id" class="form-select form-select-sm">
                        <option value="">Semua Supplier</option>
                        @foreach($supplierList as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Gudang Penerima</label>
                    <select wire:model.live="gudang_id" class="form-select form-select-sm">
                        <option value="">Semua Gudang</option>
                        @foreach($gudangList as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status Bayar</label>
                    <select wire:model.live="status_pembayaran" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="lunas">Lunas</option>
                        <option value="sebagian">Cicil / Sebagian</option>
                        <option value="tempo">Tempo (Hutang)</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Periode Cepat</label>
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
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Rentang Tanggal</label>
                        <div class="input-group input-group-sm">
                            <input type="date" wire:model.live="startDate" class="form-control">
                            <span class="input-group-text">s/d</span>
                            <input type="date" wire:model.live="endDate" class="form-control">
                        </div>
                    </div>
                @endif
                <div class="col-12 col-md-1 d-flex align-items-end">
                    <button wire:click="resetFilter" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-2.5 py-md-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-gray-800">
                <i class="bi bi-receipt me-2"></i>Daftar Dokumen Pembelian
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Baris:</span>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 75px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <!-- Mobile Card List View (< 768px) -->
        <div class="d-md-none">
            @forelse($purchases as $row)
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <div>
                            <a href="{{ route('pembelian.show', $row->id) }}" class="fw-bold text-danger text-decoration-none font-monospace">
                                {{ $row->no_pembelian }}
                            </a>
                            <div class="fw-semibold text-dark mt-0.5">{{ $row->supplier->nama_supplier ?? '-' }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}
                                &bull; {{ $row->details->pluck('gudang.nama_gudang')->filter()->unique()->join(', ') ?: '-' }}
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            @if($row->status_pembayaran)
                                <span class="badge {{ $row->status_pembayaran->badgeClass() }}">{{ $row->status_pembayaran->label() }}</span>
                            @endif
                            @if($row->status)
                                <div class="mt-0.5"><span class="badge {{ $row->status->badgeClass() }}" style="font-size: 0.65rem;">{{ $row->status->label() }}</span></div>
                            @endif
                        </div>
                    </div>

                    @if($canViewPurchasePrices)
                        <div class="p-2 rounded-3 bg-light border my-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 0.76rem;">
                            <div>
                                <span class="text-muted">Total PO:</span>
                                <span class="fw-bold text-dark font-monospace">Rp {{ number_format($row->total, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-muted">Sisa Hutang:</span>
                                <span class="fw-bold font-monospace {{ (float) $row->sisa_hutang > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ (float) $row->sisa_hutang > 0 ? 'Rp ' . number_format($row->sisa_hutang, 0, ',', '.') : 'Lunas' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center pt-1">
                        <span class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-person me-1"></i>{{ $row->creator->name ?? '-' }}</span>
                        <a href="{{ route('pembelian.show', $row->id) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2">
                            <i class="bi bi-eye me-1"></i>Detail
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 px-3 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Tidak ada data pembelian yang sesuai dengan kriteria filter.
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (>= 768px) -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>No Pembelian</th>
                        <th>Supplier</th>
                        <th>Gudang Penerima</th>
                        <th class="text-center">Status Fisik</th>
                        <th class="text-center">Status Bayar</th>
                        @if($canViewPurchasePrices)
                            <th class="text-end">Total Pembelian</th>
                            <th class="text-end">Sisa Hutang</th>
                        @endif
                        <th>Petugas</th>
                        <th class="pe-3 text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $index => $row)
                        <tr>
                            <td class="ps-3 text-muted">{{ $purchases->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-bold text-danger">{{ $row->no_pembelian }}</div>
                                @if($row->no_faktur_supplier)
                                    <small class="text-muted font-monospace" style="font-size: 0.7rem;">Fak: {{ $row->no_faktur_supplier }}</small>
                                @endif
                            </td>
                            <td>{{ $row->supplier->nama_supplier ?? '-' }}</td>
                            <td>{{ $row->details->pluck('gudang.nama_gudang')->filter()->unique()->join(', ') ?: '-' }}</td>
                            <td class="text-center">
                                @if($row->status)
                                    <span class="badge {{ $row->status->badgeClass() }}">{{ $row->status->label() }}</span>
                                @else
                                    <span class="badge bg-success">Diterima</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->status_pembayaran)
                                    <span class="badge {{ $row->status_pembayaran->badgeClass() }}">{{ $row->status_pembayaran->label() }}</span>
                                @else
                                    <span class="badge bg-success">Lunas</span>
                                @endif
                            </td>
                            @if($canViewPurchasePrices)
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($row->total, 0, ',', '.') }}
                                </td>
                                <td class="text-end {{ (float) $row->sisa_hutang > 0 ? 'fw-bold text-danger' : 'text-muted' }}">
                                    {{ (float) $row->sisa_hutang > 0 ? 'Rp ' . number_format($row->sisa_hutang, 0, ',', '.') : 'Lunas' }}
                                </td>
                            @endif
                            <td class="small text-muted">{{ $row->creator->name ?? '-' }}</td>
                            <td class="pe-3 text-center">
                                <a href="{{ route('pembelian.show', $row->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canViewPurchasePrices ? 11 : 9 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data pembelian yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
