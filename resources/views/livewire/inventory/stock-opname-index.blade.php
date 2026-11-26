<div>
    <style>
        @media print {
            .no-print, .btn, .pagination, .alert, .modal, .modal-backdrop {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .table {
                font-size: 11px !important;
                width: 100% !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background: #fff !important;
                font-size: 12px !important;
            }
        }
        .print-only {
            display: none;
        }
        .kpi-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
    </style>

    <!-- Print Only Header -->
    <div class="print-only mb-4 pb-2 border-bottom">
        <h3 class="fw-bold mb-1">LAPORAN STOK OPNAME</h3>
        <p class="text-muted small mb-0">Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }} WIB | Operator: {{ auth()->user()->name ?? 'Staff' }}</p>
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2 no-print">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Stok Opname</h4>
            <p class="text-muted mb-0 small">Pencatatan fisik stok, audit periodik, dan penyesuaian inventory sistem.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary flex-fill flex-md-grow-0">
                <i class="bi bi-printer me-1"></i> Cetak
            </button>

            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" class="btn btn-success text-white flex-fill flex-md-grow-0">
                <span wire:loading.remove wire:target="exportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel</span>
                <span wire:loading wire:target="exportExcel">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Menyiapkan File...
                </span>
            </button>

            @can('create_stock_opnames')
                <a href="{{ route('inventory.stock-opname.create') }}" class="btn btn-primary shadow-sm flex-fill flex-md-grow-0">
                    <i class="bi bi-plus-lg me-1"></i> Mulai Stok Opname
                </a>
            @endcan
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Stock Opname KPI Metric Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4 no-print">
        <!-- Total Dokumen -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Total Dokumen</span>
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-2 d-none d-sm-flex">
                            <i class="bi bi-clipboard-check fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($metrics['totalDokumen'], 0, ',', '.') }}</h4>
                    <small class="text-muted d-block text-truncate">Riwayat audit tercatat</small>
                </div>
            </div>
        </div>

        <!-- Selesai (Disesuaikan) -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Opname Selesai</span>
                        <div class="p-2 bg-success bg-opacity-10 text-success rounded-2 d-none d-sm-flex">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($metrics['totalSelesai'], 0, ',', '.') }}</h4>
                    <small class="text-success fw-medium d-block text-truncate">Penyesuaian diterapkan</small>
                </div>
            </div>
        </div>

        <!-- Draft (Berjalan) -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Draft / Berjalan</span>
                        <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-2 d-none d-sm-flex">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($metrics['totalDraft'], 0, ',', '.') }}</h4>
                    <small class="text-warning fw-medium d-block text-truncate">Belum difinalisasi</small>
                </div>
            </div>
        </div>

        <!-- Net Selisih Kuantitas -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Net Selisih Fisik</span>
                        <div class="p-2 {{ $metrics['netSelisih'] < 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-info bg-opacity-10 text-info' }} rounded-2 d-none d-sm-flex">
                            <i class="bi {{ $metrics['netSelisih'] < 0 ? 'bi-graph-down-arrow' : 'bi-graph-up-arrow' }} fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 {{ $metrics['netSelisih'] < 0 ? 'text-danger' : ($metrics['netSelisih'] > 0 ? 'text-primary' : 'text-dark') }}">
                        {{ ($metrics['netSelisih'] > 0 ? '+' : '') . number_format($metrics['netSelisih'], 0, ',', '.') }}
                    </h4>
                    <small class="text-muted d-block text-truncate">{{ number_format($metrics['totalItemSelisih'], 0, ',', '.') }} item berbeda fisik</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Section -->
    <div class="card border-0 shadow-sm mb-3 mb-md-4 no-print">
        <div class="card-body p-3">
            <!-- Tier 1: Search & Dropdowns -->
            <div class="row g-2 mb-2">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-start-0" placeholder="Cari No. Opname, catatan, nama produk, gudang...">
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <select wire:model.live="filterGudang" class="form-select form-select-sm">
                        <option value="">-- Semua Lokasi Gudang --</option>
                        @foreach($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tier 2: Date Range, PerPage & Reset -->
            <div class="row g-2 align-items-center">
                <div class="col-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Dari</span>
                        <input type="date" wire:model.live="startDate" class="form-control">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Sampai</span>
                        <input type="date" wire:model.live="endDate" class="form-control">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Baris</span>
                        <select wire:model.live="perPage" class="form-select">
                            <option value="10">10 per hal</option>
                            <option value="15">15 per hal</option>
                            <option value="25">25 per hal</option>
                            <option value="50">50 per hal</option>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-3 text-end">
                    <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm w-100" @if(!$this->hasActiveFilters) disabled @endif>
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                </div>
            </div>

            <!-- Active Filter Badges -->
            @if ($this->hasActiveFilters)
                <div class="d-flex align-items-center gap-1 mt-3 flex-wrap pt-2 border-top">
                    <span class="text-muted small me-1">Filter Aktif:</span>
                    @if (filled($search))
                        <span class="badge bg-light text-dark border">
                            Pencarian: "{{ $search }}"
                            <button type="button" wire:click="$set('search', '')" class="btn-close ms-1" style="font-size: 0.65rem;" aria-label="Hapus"></button>
                        </span>
                    @endif
                    @if (filled($filterGudang))
                        <span class="badge bg-light text-dark border">
                            Gudang: {{ $gudangs->firstWhere('id', $filterGudang)?->nama_gudang ?? $filterGudang }}
                            <button type="button" wire:click="$set('filterGudang', '')" class="btn-close ms-1" style="font-size: 0.65rem;" aria-label="Hapus"></button>
                        </span>
                    @endif
                    @if (filled($filterStatus))
                        <span class="badge bg-light text-dark border">
                            Status: {{ $filterStatus }}
                            <button type="button" wire:click="$set('filterStatus', '')" class="btn-close ms-1" style="font-size: 0.65rem;" aria-label="Hapus"></button>
                        </span>
                    @endif
                    @if (filled($startDate) || filled($endDate))
                        <span class="badge bg-light text-dark border">
                            Periode: {{ $startDate ?: '...' }} s/d {{ $endDate ?: '...' }}
                            <button type="button" wire:click="$set('startDate', ''); $set('endDate', '');" class="btn-close ms-1" style="font-size: 0.65rem;" aria-label="Hapus"></button>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Table (Desktop & Tablet) & Card List (Mobile) -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none no-print">
                <div class="list-group list-group-flush">
                    @forelse($stockOpnames as $so)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <a href="{{ route('inventory.stock-opname.detail', $so->id) }}" class="fw-bold text-primary font-monospace text-decoration-none d-block">
                                        {{ $so->no_stock_opname }}
                                    </a>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $so->tanggal->format('d/m/Y') }} ({{ $so->tanggal->diffForHumans() }})
                                    </small>
                                </div>
                                <span class="badge bg-{{ $so->status->badgeColor() }} px-2 py-1 flex-shrink-0">
                                    {{ $so->status->label() }}
                                </span>
                            </div>

                            <div class="bg-light rounded-2 p-2 mb-2 border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-white text-dark border">
                                        <i class="bi bi-building me-1 text-secondary"></i>{{ $so->gudang->nama_gudang }}
                                    </span>
                                    @if($so->catatan)
                                        <small class="d-block text-muted mt-1">{{ $so->catatan }}</small>
                                    @endif
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    {{ $so->details_count ?? $so->details->count() }} Produk
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center gap-2 pt-1">
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-person me-1"></i>{{ $so->user->name ?? '-' }}
                                </small>
                                <div class="d-flex gap-1">
                                    <button type="button" wire:click="openQuickModal({{ $so->id }})" class="btn btn-sm btn-outline-secondary" title="Pratinjau Cepat">
                                        <i class="bi bi-search me-1"></i>Pratinjau
                                    </button>
                                    <a href="{{ route('inventory.stock-opname.detail', $so->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Buka
                                    </a>
                                    @if($so->status === \App\Domains\Inventory\Enums\StatusStockOpname::Draft)
                                        @can('delete', $so)
                                            <button type="button" wire:click="promptDeleteDraft({{ $so->id }})" class="btn btn-sm btn-outline-danger" title="Hapus Draft">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 px-3 text-muted">
                            <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i>
                            Tidak ada data Stok Opname yang sesuai dengan filter yang dipilih.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Desktop & Tablet Table View (>= 768px & Print) -->
            <div class="table-responsive d-none d-md-block d-print-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3">No. Dokumen</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3">Gudang</th>
                            <th class="py-3">Petugas</th>
                            <th class="py-3 text-center">Item Produk</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="pe-3 py-3 text-end no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($stockOpnames as $so)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-primary font-monospace">{{ $so->no_stock_opname }}</div>
                                    @if($so->catatan)
                                        <div class="small text-muted text-truncate" style="max-width: 200px;">{{ $so->catatan }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $so->tanggal->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $so->tanggal->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-building me-1 text-secondary"></i>{{ $so->gudang->nama_gudang }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small fw-medium text-dark">{{ $so->user->name ?? '-' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        {{ $so->details_count ?? $so->details->count() }} Produk
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $so->status->badgeColor() }} px-2 py-1">
                                        {{ $so->status->label() }}
                                    </span>
                                </td>
                                <td class="pe-3 text-end no-print">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" wire:click="openQuickModal({{ $so->id }})" class="btn btn-outline-secondary" title="Pratinjau Cepat">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <a href="{{ route('inventory.stock-opname.detail', $so->id) }}" class="btn btn-outline-primary" title="Buka Detail">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka
                                        </a>
                                        @if($so->status === \App\Domains\Inventory\Enums\StatusStockOpname::Draft)
                                            @can('delete', $so)
                                                <button type="button" wire:click="promptDeleteDraft({{ $so->id }})" class="btn btn-outline-danger" title="Hapus Draft">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i>
                                    Tidak ada data Stok Opname yang sesuai dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($stockOpnames->hasPages())
            <div class="card-footer bg-white border-top-0 py-3 no-print">
                {{ $stockOpnames->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Preview Modal -->
    @if ($isQuickModalOpen && $selectedStockOpname)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(3px); z-index: 1050;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-light py-3 border-bottom">
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">
                                <i class="bi bi-clipboard-data text-primary me-2"></i>Pratinjau: {{ $selectedStockOpname->no_stock_opname }}
                            </h5>
                            <small class="text-muted">Gudang {{ $selectedStockOpname->gudang->nama_gudang ?? '-' }} &bull; Tanggal {{ $selectedStockOpname->tanggal->format('d F Y') }}</small>
                        </div>
                        <button type="button" wire:click="closeQuickModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Top Metadata -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Status Dokumen</small>
                                    <span class="badge bg-{{ $selectedStockOpname->status->badgeColor() }}">
                                        {{ $selectedStockOpname->status->label() }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Dicatat Oleh</small>
                                    <span class="fw-semibold text-dark">{{ $selectedStockOpname->user->name ?? 'Sistem' }}</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Total Item Dihitung</small>
                                    <span class="fw-bold text-primary">{{ $selectedStockOpname->details->count() }} Produk</span>
                                </div>
                            </div>
                        </div>

                        @if(filled($selectedStockOpname->catatan))
                            <div class="alert alert-secondary py-2 small mb-3">
                                <strong>Catatan:</strong> {{ $selectedStockOpname->catatan }}
                            </div>
                        @endif

                        <!-- Items Table -->
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-boxes me-1 text-primary"></i> Rincian Selisih Fisik vs Sistem:</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 35px;">#</th>
                                        <th>Nama Produk</th>
                                        <th class="text-center">Stok Sistem</th>
                                        <th class="text-center">Stok Fisik</th>
                                        <th class="text-center pe-3">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedStockOpname->details as $idx => $d)
                                        <tr>
                                            <td class="ps-3 text-muted small">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $d->produk->nama ?? '-' }}</div>
                                                <small class="text-muted font-monospace">{{ $d->produk->kode_katalog ?? '-' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">
                                                    {{ number_format((float) $d->stok_sistem, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold">
                                                {{ number_format((float) $d->stok_fisik, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center pe-3">
                                                @if($d->selisih > 0)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        +{{ number_format((float) $d->selisih, 0, ',', '.') }}
                                                    </span>
                                                @elseif($d->selisih < 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                        {{ number_format((float) $d->selisih, 0, ',', '.') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border">0 (Cocok)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <a href="{{ route('inventory.stock-opname.detail', $selectedStockOpname->id) }}" class="btn btn-primary me-auto">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Lengkap
                        </a>
                        @if($selectedStockOpname->status === \App\Domains\Inventory\Enums\StatusStockOpname::Draft)
                            @can('delete', $selectedStockOpname)
                                <button type="button" wire:click="promptDeleteDraft({{ $selectedStockOpname->id }})" class="btn btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i> Hapus Draft
                                </button>
                            @endcan
                        @endif
                        <button type="button" wire:click="closeQuickModal" class="btn btn-secondary">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Draft Confirmation Modal -->
    @if ($isDeleteDraftModalOpen && $draftToDelete)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1065;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-body p-4 text-center">
                        <div class="d-inline-flex p-3 rounded-circle bg-danger bg-opacity-10 text-danger mb-3 shadow-sm">
                            <i class="bi bi-trash-fill display-5"></i>
                        </div>

                        <h4 class="fw-bold text-dark mb-1">Hapus Draft Opname?</h4>
                        <p class="text-muted small mb-3">
                            Draft dokumen <strong class="font-monospace text-danger">{{ $draftToDelete->no_stock_opname }}</strong> beserta seluruh rincian item di dalamnya akan dihapus secara permanen.
                        </p>

                        <div class="alert alert-secondary text-start py-2 px-3 small border-0 mb-4 rounded-3 d-flex align-items-start gap-2">
                            <i class="bi bi-shield-check text-success fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                Dokumen ini masih berstatus <strong>Draft</strong> sehingga belum pernah mempengaruhi saldo stok gudang.
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" wire:click="closeDeleteDraftModal" class="btn btn-outline-secondary px-4 py-2">
                                Batal
                            </button>
                            <button type="button" wire:click="executeDeleteDraft" class="btn btn-danger px-4 py-2" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="executeDeleteDraft"><i class="bi bi-trash me-1"></i> Ya, Hapus Sekarang</span>
                                <span wire:loading wire:target="executeDeleteDraft">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Menghapus...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

