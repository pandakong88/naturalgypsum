<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-sm-center mb-3 mb-md-4 flex-column flex-sm-row gap-2">
        <div>
            <h4 class="fw-bold mb-1">Daftar Pembelian</h4>
            <p class="text-muted small mb-0">Kelola dan pantau seluruh transaksi penerimaan barang dari supplier.</p>
        </div>
        <div class="d-flex gap-2 w-100 w-sm-auto">
            @can('create_purchases')
                <a href="{{ route('pembelian.create') }}" class="btn btn-primary flex-fill flex-sm-grow-0">
                    <i class="bi bi-cart-plus me-1"></i>Tambah Pembelian
                </a>
            @endcan
        </div>
    </div>

    <!-- Flash message -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Summary Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <!-- Total Nilai Pembelian -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1 text-truncate" style="font-size: 0.72rem;">Total Nilai Pembelian</span>
                            <h5 class="fw-bold mb-0 text-primary text-truncate">
                                @can('view_purchase_prices')
                                    Rp {{ number_format($kpi['totalNilai'], 0, ',', '.') }}
                                @else
                                    <span class="text-muted fst-italic">Rp ***</span>
                                @endcan
                            </h5>
                        </div>
                        <div class="p-2 bg-primary-subtle text-primary rounded-3 d-none d-sm-flex flex-shrink-0">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1 text-truncate" style="font-size: 0.72rem;">Total Transaksi</span>
                            <h5 class="fw-bold mb-0 text-dark">
                                {{ number_format($kpi['totalTransaksi'], 0, ',', '.') }} <span class="fs-6 fw-normal text-muted">Faktur/PO</span>
                            </h5>
                        </div>
                        <div class="p-2 bg-success-subtle text-success rounded-3 d-none d-sm-flex flex-shrink-0">
                            <i class="bi bi-receipt fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Sisa Hutang Supplier -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1 text-truncate" style="font-size: 0.72rem;">Sisa Hutang Supplier</span>
                            <h5 class="fw-bold mb-0 text-danger text-truncate">
                                @can('view_purchase_prices')
                                    Rp {{ number_format($kpi['totalHutang'], 0, ',', '.') }}
                                @else
                                    <span class="text-muted fst-italic">Rp ***</span>
                                @endcan
                            </h5>
                        </div>
                        <div class="p-2 bg-danger-subtle text-danger rounded-3 d-none d-sm-flex flex-shrink-0">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PO Menunggu Diterima -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <span class="text-muted small text-uppercase fw-semibold d-block mb-1 text-truncate" style="font-size: 0.72rem;">PO Menunggu Masuk</span>
                            <h5 class="fw-bold mb-0 text-warning">
                                {{ number_format($kpi['totalOrdered'], 0, ',', '.') }} <span class="fs-6 fw-normal text-muted">PO</span>
                            </h5>
                        </div>
                        <div class="p-2 bg-warning-subtle text-warning-emphasis rounded-3 d-none d-sm-flex flex-shrink-0">
                            <i class="bi bi-box-arrow-in-down fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unified Sleek Filter Card -->
    <div class="card mb-3 mb-md-4 border-0 shadow-sm overflow-hidden">
        <!-- Top Status Filter Pills & Reset -->
        <div class="card-header bg-white border-bottom py-2 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills nav-pills-scrollable gap-1 pb-1 pb-lg-0 w-100 w-lg-auto" role="tablist">
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('semua')"
                            class="nav-link py-1 px-3 small rounded-pill {{ empty($statusFilter) && empty($statusPembayaranFilter) ? 'active bg-primary text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            Semua Transaksi
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('ordered')"
                            class="nav-link py-1 px-3 small rounded-pill {{ $statusFilter === 'ordered' ? 'active bg-warning text-dark fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-hourglass-split me-1"></i>Dipesan (PO)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('received')"
                            class="nav-link py-1 px-3 small rounded-pill {{ $statusFilter === 'received' ? 'active bg-success text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-box-arrow-in-down me-1"></i>Diterima (Gudang)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('tempo')"
                            class="nav-link py-1 px-3 small rounded-pill {{ $statusPembayaranFilter === 'tempo' ? 'active bg-danger text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-clock-history me-1"></i>Tempo (Hutang)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('cancelled')"
                            class="nav-link py-1 px-3 small rounded-pill {{ $statusFilter === 'cancelled' ? 'active bg-secondary text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-x-circle me-1"></i>Dibatalkan
                        </button>
                    </li>
                </ul>

                @if ($this->hasActiveFilters())
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-link text-danger text-decoration-none py-1 px-2 small ms-auto">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua Filter
                    </button>
                @endif
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="card-body p-3">
            <!-- Row 1: Search, Supplier, Gudang -->
            <div class="row g-2 align-items-center mb-2">
                <!-- Search Bar -->
                <div class="col-12 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Cari no faktur, surat jalan, plat, supplier..."
                        >
                        @if ($search)
                            <button type="button" wire:click="$set('search', '')" class="btn btn-outline-secondary border-start-0 border text-muted">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Supplier Filter -->
                <div class="col-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="bi bi-truck"></i></span>
                        <select wire:model.live="supplierFilter" class="form-select border-start-0 ps-0">
                            <option value="">Semua Supplier</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->nama_supplier }}</option>
                            @endforeach
                        </select>
                        @if ($supplierFilter)
                            <button type="button" wire:click="$set('supplierFilter', '')" class="btn btn-outline-secondary border-start-0 border text-muted">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Gudang Filter -->
                <div class="col-6 col-lg-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="bi bi-building"></i></span>
                        <select wire:model.live="gudangFilter" class="form-select border-start-0 ps-0">
                            <option value="">Semua Gudang</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        @if ($gudangFilter)
                            <button type="button" wire:click="$set('gudangFilter', '')" class="btn btn-outline-secondary border-start-0 border text-muted">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Row 2: Status Barang, Status Bayar, Date Range & Shortcuts -->
            <div class="row g-2 align-items-center">
                <!-- Status Barang Detail Dropdown -->
                <div class="col-6 col-lg-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="bi bi-box-seam"></i></span>
                        <select wire:model.live="statusFilter" class="form-select border-start-0 ps-0">
                            <option value="">Status Barang: Semua</option>
                            <option value="ordered">Dipesan (PO)</option>
                            <option value="received">Diterima (Gudang)</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                        @if ($statusFilter)
                            <button type="button" wire:click="$set('statusFilter', '')" class="btn btn-outline-secondary border-start-0 border text-muted">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Status Bayar Detail Dropdown -->
                <div class="col-6 col-lg-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="bi bi-wallet2"></i></span>
                        <select wire:model.live="statusPembayaranFilter" class="form-select border-start-0 ps-0">
                            <option value="">Status Bayar: Semua</option>
                            <option value="lunas">Lunas</option>
                            <option value="sebagian">Sebagian (DP/Cicil)</option>
                            <option value="tempo">Tempo (Belum Lunas)</option>
                        </select>
                        @if ($statusPembayaranFilter)
                            <button type="button" wire:click="$set('statusPembayaranFilter', '')" class="btn btn-outline-secondary border-start-0 border text-muted">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Date Range & Shortcuts -->
                <div class="col-12 col-lg-6 d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    <!-- Quick Date Buttons -->
                    <div class="btn-group btn-group-sm flex-fill flex-sm-grow-0" role="group">
                        <button
                            type="button"
                            wire:click="setDateFilter('today')"
                            class="btn {{ $startDate === now()->toDateString() && $endDate === now()->toDateString() ? 'btn-secondary text-white' : 'btn-outline-secondary' }}"
                        >
                            Hari Ini
                        </button>
                        <button
                            type="button"
                            wire:click="setDateFilter('week')"
                            class="btn {{ $startDate === now()->copy()->subDays(6)->toDateString() && $endDate === now()->toDateString() ? 'btn-secondary text-white' : 'btn-outline-secondary' }}"
                        >
                            7 Hari
                        </button>
                        <button
                            type="button"
                            wire:click="setDateFilter('month')"
                            class="btn {{ $startDate === now()->copy()->startOfMonth()->toDateString() && $endDate === now()->copy()->endOfMonth()->toDateString() ? 'btn-secondary text-white' : 'btn-outline-secondary' }}"
                        >
                            Bulan Ini
                        </button>
                    </div>

                    <!-- Date Pickers -->
                    <div class="d-flex align-items-center gap-1 flex-fill flex-sm-grow-0">
                        <input
                            type="date"
                            wire:model.live="startDate"
                            class="form-control form-control-sm"
                            title="Dari Tanggal"
                        >
                        <span class="text-muted small">-</span>
                        <input
                            type="date"
                            wire:model.live="endDate"
                            class="form-control form-control-sm"
                            title="Sampai Tanggal"
                        >
                        @if ($startDate || $endDate)
                            <button
                                type="button"
                                wire:click="clearDateFilter"
                                class="btn btn-sm btn-outline-danger py-1 px-2"
                                title="Reset Tanggal"
                            >
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0">Rincian Transaksi Pembelian</h6>
                <span class="badge bg-light text-dark border">
                    Total: {{ $pembelians->total() }} Data
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Tampilkan:</span>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                <div class="list-group list-group-flush">
                    @forelse ($pembelians as $index => $pembelian)
                        <div class="list-group-item p-3">
                            <!-- Top Row: Document No, Date & Status Badges -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="text-muted small">#{{ $pembelians->firstItem() + $index }}</span>
                                        <a href="{{ route('pembelian.show', $pembelian->id) }}" class="fw-bold text-primary text-decoration-none font-monospace">
                                            {{ $pembelian->no_pembelian }}
                                        </a>
                                    </div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $pembelian->tanggal->format('d/m/Y') }}
                                        @if ($pembelian->isOrdered())
                                            <span class="text-warning ms-1"><i class="bi bi-hourglass-split"></i> Dipesan</span>
                                        @elseif ($pembelian->tanggal_diterima)
                                            <span class="text-success ms-1"><i class="bi bi-check2-circle"></i> Masuk {{ $pembelian->tanggal_diterima->format('d/m/Y') }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                    <span class="badge {{ $pembelian->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $pembelian->status?->label() ?? ucfirst($pembelian->status?->value ?? 'Diterima') }}
                                    </span>
                                    <span class="badge {{ $pembelian->status_pembayaran?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $pembelian->status_pembayaran?->label() ?? ucfirst($pembelian->status_pembayaran?->value ?? 'Lunas') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Supplier & Logistics Badges -->
                            <div class="mb-2">
                                <div class="fw-bold text-dark">
                                    <i class="bi bi-truck me-1 text-primary"></i>{{ $pembelian->supplier->nama_supplier ?? '-' }}
                                </div>
                                @if ($pembelian->no_faktur_supplier || $pembelian->no_surat_jalan || $pembelian->no_kontainer || $pembelian->no_polisi)
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @if ($pembelian->no_faktur_supplier)
                                            <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                                <i class="bi bi-file-earmark-text text-primary me-1"></i>{{ $pembelian->no_faktur_supplier }}
                                            </span>
                                        @endif
                                        @if ($pembelian->no_surat_jalan)
                                            <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                                <i class="bi bi-truck me-1"></i>{{ $pembelian->no_surat_jalan }}
                                            </span>
                                        @endif
                                        @if ($pembelian->no_kontainer)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.68rem;">
                                                <i class="bi bi-box-seam me-1"></i>{{ $pembelian->no_kontainer }}
                                            </span>
                                        @endif
                                        @if ($pembelian->no_polisi)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.68rem;">
                                                {{ $pembelian->no_polisi }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Financial Box -->
                            <div class="bg-light rounded-2 p-2 mb-2 border d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Total Nilai</span>
                                    @can('view_purchase_prices')
                                        <span class="fw-bold text-dark font-monospace">Rp {{ number_format((float) $pembelian->total, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Rp ***</span>
                                    @endcan
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">
                                        Sisa Hutang
                                        @if ($pembelian->tanggal_jatuh_tempo && !$pembelian->isLunas())
                                            (TOP: {{ $pembelian->tanggal_jatuh_tempo->format('d/m/Y') }})
                                        @endif
                                    </span>
                                    @can('view_purchase_prices')
                                        @if ((float) $pembelian->sisa_hutang > 0)
                                            <span class="fw-bold text-danger font-monospace">Rp {{ number_format((float) $pembelian->sisa_hutang, 0, ',', '.') }}</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Lunas</span>
                                        @endif
                                    @else
                                        <span class="text-muted fst-italic">Rp ***</span>
                                    @endcan
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <a href="{{ route('pembelian.show', $pembelian->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-eye me-1"></i>Detail Pembelian
                                </a>
                                @if ($pembelian->isOrdered())
                                    @can('update', $pembelian)
                                        <a href="{{ route('pembelian.edit', $pembelian->id) }}" class="btn btn-sm btn-outline-warning flex-fill">
                                            <i class="bi bi-pencil me-1"></i>Edit PO
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 px-3 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada data pembelian yang ditemukan sesuai filter pencarian.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Desktop & Tablet Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 45px;">No</th>
                            <th>No. Referensi / Dokumen</th>
                            <th role="button" wire:click="sortBy('tanggal')" class="user-select-none text-nowrap">
                                <span>Tanggal</span>
                                @if ($sortField === 'tanggal')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted small"></i>
                                @endif
                            </th>
                            <th role="button" wire:click="sortBy('supplier')" class="user-select-none text-nowrap">
                                <span>Supplier</span>
                                @if ($sortField === 'supplier')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted small"></i>
                                @endif
                            </th>
                            <th class="text-center">Status Barang</th>
                            <th class="text-center">Status Bayar</th>
                            <th role="button" wire:click="sortBy('total')" class="text-end user-select-none text-nowrap">
                                <span>Total Nilai</span>
                                @if ($sortField === 'total')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted small"></i>
                                @endif
                            </th>
                            <th class="text-end">Sisa Hutang</th>
                            <th class="text-end pe-4" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pembelians as $index => $pembelian)
                            <tr>
                                <td class="ps-4 text-muted">{{ $pembelians->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('pembelian.show', $pembelian->id) }}" class="fw-bold text-decoration-none font-monospace">
                                        {{ $pembelian->no_pembelian }}
                                    </a>
                                    <div class="small mt-1 d-flex flex-wrap gap-1">
                                        @if ($pembelian->no_faktur_supplier)
                                            <span class="badge bg-light text-dark border" title="No Faktur Supplier">
                                                <i class="bi bi-file-earmark-text text-primary me-1"></i>{{ $pembelian->no_faktur_supplier }}
                                            </span>
                                        @endif
                                        @if ($pembelian->no_surat_jalan)
                                            <span class="badge bg-light text-secondary border" title="No Surat Jalan">
                                                <i class="bi bi-truck text-secondary me-1"></i>{{ $pembelian->no_surat_jalan }}
                                            </span>
                                        @endif
                                        @if ($pembelian->no_kontainer)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.7rem;" title="No Kontainer">
                                                <i class="bi bi-box-seam me-1"></i>{{ $pembelian->no_kontainer }}
                                            </span>
                                        @endif
                                        @if ($pembelian->no_polisi)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.7rem;" title="Plat Nomor">
                                                {{ $pembelian->no_polisi }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $pembelian->tanggal->format('d/m/Y') }}</div>
                                    @if ($pembelian->isOrdered())
                                        <div class="small text-warning" style="font-size: 0.75rem;"><i class="bi bi-hourglass-split me-1"></i>Dipesan</div>
                                    @elseif ($pembelian->tanggal_diterima)
                                        <div class="small text-success" style="font-size: 0.75rem;"><i class="bi bi-check2-circle me-1"></i>Masuk {{ $pembelian->tanggal_diterima->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $pembelian->supplier->nama_supplier ?? '-' }}</span>
                                    @if ($pembelian->supplier?->kontak)
                                        <div class="small text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-telephone me-1"></i>{{ $pembelian->supplier->kontak }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $pembelian->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $pembelian->status?->label() ?? ucfirst($pembelian->status?->value ?? 'Diterima') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $pembelian->status_pembayaran?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $pembelian->status_pembayaran?->label() ?? ucfirst($pembelian->status_pembayaran?->value ?? 'Lunas') }}
                                    </span>
                                    @if ($pembelian->tanggal_jatuh_tempo && !$pembelian->isLunas())
                                        <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                            Jatuh Tempo: {{ $pembelian->tanggal_jatuh_tempo->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">
                                    @can('view_purchase_prices')
                                        <span>Rp {{ number_format((float) $pembelian->total, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Rp ***</span>
                                    @endcan
                                </td>
                                <td class="text-end">
                                    @can('view_purchase_prices')
                                        @if ((float) $pembelian->sisa_hutang > 0)
                                            <span class="fw-bold text-danger">Rp {{ number_format((float) $pembelian->sisa_hutang, 2, ',', '.') }}</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Lunas</span>
                                        @endif
                                    @else
                                        <span class="text-muted fst-italic">Rp ***</span>
                                    @endcan
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pembelian.show', $pembelian->id) }}" class="btn btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                        @if ($pembelian->isOrdered())
                                            @can('update', $pembelian)
                                                <a href="{{ route('pembelian.edit', $pembelian->id) }}" class="btn btn-outline-warning" title="Edit Surat Pesanan (PO)">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Tidak ada data pembelian yang ditemukan sesuai filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($pembelians->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $pembelians->links() }}
            </div>
        @endif
    </div>
</div>
