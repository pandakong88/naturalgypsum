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
        <h3 class="fw-bold mb-1">LAPORAN TRANSFER ANTAR GUDANG</h3>
        <p class="text-muted small mb-0">Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }} WIB | Operator: {{ auth()->user()->name ?? 'Staff' }}</p>
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2 no-print">
        <div>
            <h4 class="fw-bold mb-0">Transfer Antar Gudang</h4>
            <p class="text-muted small mb-0">Kelola dan pantau riwayat mutasi perpindahan stok antar lokasi gudang</p>
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

            @can('create', \App\Domains\Inventory\Models\TransferGudang::class)
                <a href="{{ route('transfer.create') }}" class="btn btn-primary flex-fill flex-md-grow-0">
                    <i class="bi bi-plus-lg me-1"></i> Transfer Baru
                </a>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Transfer KPI Metric Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4 no-print">
        <!-- Total Transaksi Transfer -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Total Dokumen</span>
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-2 d-none d-sm-flex">
                            <i class="bi bi-arrow-left-right fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($metrics['totalTransfers'], 0, ',', '.') }}</h4>
                    <small class="text-muted d-block text-truncate">Surat transfer tercatat</small>
                </div>
            </div>
        </div>

        <!-- Total Item/SKU Dipindahkan -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Total Variasi SKU</span>
                        <div class="p-2 bg-info bg-opacity-10 text-info rounded-2 d-none d-sm-flex">
                            <i class="bi bi-boxes fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-info">{{ number_format($metrics['totalItems'], 0, ',', '.') }}</h4>
                    <small class="text-muted d-block text-truncate">Item unik dipindahkan</small>
                </div>
            </div>
        </div>

        <!-- Total Volume Kuantitas Fisik -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Total Kuantitas</span>
                        <div class="p-2 bg-success bg-opacity-10 text-success rounded-2 d-none d-sm-flex">
                            <i class="bi bi-cart-check fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-success">{{ number_format($metrics['totalQuantity'], 2, ',', '.') }}</h4>
                    <small class="text-muted d-block text-truncate">Unit berpindah lokasi</small>
                </div>
            </div>
        </div>

        <!-- Rute Teraktif -->
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 kpi-card h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-medium text-truncate">Rute Terpopuler</span>
                        <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-2 d-none d-sm-flex">
                            <i class="bi bi-geo-alt fs-5"></i>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-0 text-truncate text-dark" title="{{ $metrics['mostActiveRoute'] }}">{{ $metrics['mostActiveRoute'] }}</h6>
                    <small class="text-muted d-block text-truncate">Asal → Tujuan terbanyak</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search (2-Tier Layout) -->
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body p-3">
            <!-- Top Row: Search, Per Page, Reset -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Cari nomor transfer, keterangan, nama gudang, nama produk..."
                        >
                        @if(filled($search))
                            <button class="btn btn-outline-secondary border-start-0 border" type="button" wire:click="$set('search', '')" title="Bersihkan pencarian">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-3">
                    <div class="d-flex align-items-center justify-content-md-end gap-2">
                        <label class="small text-muted text-nowrap mb-0 d-none d-sm-inline">Tampil:</label>
                        <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                            <option value="15">15 Baris</option>
                            <option value="25">25 Baris</option>
                            <option value="50">50 Baris</option>
                            <option value="100">100 Baris</option>
                        </select>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2 text-end">
                    @if($this->hasActiveFilters)
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                        </button>
                    @endif
                </div>
            </div>

            <!-- Second Row: Multi-Criteria Filter Dropdowns -->
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Gudang Asal</label>
                    <select wire:model.live="gudangAsalFilter" class="form-select form-select-sm">
                        <option value="">Semua Gudang Asal</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Gudang Tujuan</label>
                    <select wire:model.live="gudangTujuanFilter" class="form-select form-select-sm">
                        <option value="">Semua Gudang Tujuan</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm">
                </div>
            </div>

            <!-- Active Filter Chips Bar -->
            @if($this->hasActiveFilters)
                <div class="d-flex flex-wrap align-items-center gap-1 mt-3 pt-2 border-top">
                    <span class="small text-muted me-1"><i class="bi bi-funnel me-1"></i>Filter Aktif:</span>

                    @if(filled($search))
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Pencarian: "<strong>{{ $search }}</strong>"</span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('search', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($gudangAsalFilter))
                        @php
                            $gAsal = $gudangs->firstWhere('id', $gudangAsalFilter);
                        @endphp
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Asal: <strong>{{ $gAsal->nama_gudang ?? '-' }}</strong></span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('gudangAsalFilter', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($gudangTujuanFilter))
                        @php
                            $gTujuan = $gudangs->firstWhere('id', $gudangTujuanFilter);
                        @endphp
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Tujuan: <strong>{{ $gTujuan->nama_gudang ?? '-' }}</strong></span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('gudangTujuanFilter', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($startDate) || filled($endDate))
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Periode: {{ $startDate ?: 'Awal' }} s/d {{ $endDate ?: 'Sekarang' }}</span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('startDate', ''); $set('endDate', '');" aria-label="Close"></button>
                        </span>
                    @endif

                    <a href="javascript:void(0)" wire:click="resetFilters" class="small text-danger ms-2 text-decoration-none">
                        Reset Semua
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Data Table (Desktop & Tablet) & Card List (Mobile) -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none no-print">
                <div class="list-group list-group-flush">
                    @forelse ($transfers as $t)
                        <div class="list-group-item p-3">
                            <!-- Top Row: No. Transfer, Date & Item Count -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <a href="{{ route('transfer.show', $t->id) }}" class="fw-bold text-primary text-decoration-none font-monospace d-block">
                                        {{ $t->no_transfer }}
                                    </a>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $t->tanggal ? $t->tanggal->format('d/m/Y') : '-' }}
                                        &bull; {{ $t->created_at ? $t->created_at->format('H:i') : '' }} WIB
                                    </small>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge bg-light text-dark border">
                                        {{ $t->details->count() }} Item
                                    </span>
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">
                                        {{ number_format((float) $t->details->sum('jumlah'), 2, ',', '.') }} unit
                                    </small>
                                </div>
                            </div>

                            <!-- Route Box -->
                            <div class="bg-light rounded-2 p-2 mb-2 border d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <i class="bi bi-box-arrow-right me-1"></i>{{ $t->gudangAsal->nama_gudang ?? '-' }}
                                </span>
                                <i class="bi bi-arrow-right text-primary"></i>
                                <span class="badge bg-primary-subtle text-primary border">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>{{ $t->gudangTujuan->nama_gudang ?? '-' }}
                                </span>
                            </div>

                            <!-- Footer: Creator & Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center gap-2 pt-1">
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-person me-1"></i>{{ $t->user->name ?? ($t->creator->name ?? 'Sistem') }}
                                </small>
                                <div class="d-flex gap-1">
                                    <button type="button" wire:click="openQuickModal({{ $t->id }})" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-list-ul me-1"></i>Pratinjau
                                    </button>
                                    <a href="{{ route('transfer.show', $t->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 px-3">
                            <div class="text-muted">
                                <i class="bi bi-arrow-left-right display-4 d-block mb-2 text-secondary"></i>
                                <p class="mb-0">Tidak ada data transfer stok yang sesuai kriteria filter.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Desktop & Tablet Table View (>= 768px & Print) -->
            <div class="table-responsive d-none d-md-block d-print-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No. Transfer</th>
                            <th>Tanggal</th>
                            <th>Gudang Asal</th>
                            <th>Gudang Tujuan</th>
                            <th>Total Barang</th>
                            <th>Dicatat Oleh</th>
                            <th class="text-end pe-4 no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $t)
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <a href="{{ route('transfer.show', $t->id) }}" class="text-primary text-decoration-none font-monospace">
                                        {{ $t->no_transfer }}
                                    </a>
                                </td>
                                <td>
                                    <span class="d-block fw-medium text-dark">{{ $t->tanggal ? $t->tanggal->format('d/m/Y') : '-' }}</span>
                                    <small class="text-muted">{{ $t->created_at ? $t->created_at->format('H:i') : '' }} WIB</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-box-arrow-right me-1"></i>{{ $t->gudangAsal->nama_gudang ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>{{ $t->gudangTujuan->nama_gudang ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $t->details->count() }} Item</span>
                                    <small class="d-block text-muted">({{ number_format((float) $t->details->sum('jumlah'), 2, ',', '.') }} unit)</small>
                                </td>
                                <td><small class="text-muted">{{ $t->user->name ?? ($t->creator->name ?? 'Sistem') }}</small></td>
                                <td class="text-end pe-4 no-print">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            type="button"
                                            wire:click="openQuickModal({{ $t->id }})"
                                            class="btn btn-outline-secondary"
                                            title="Pratinjau Barang"
                                        >
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                        <a href="{{ route('transfer.show', $t->id) }}" class="btn btn-outline-primary" title="Buka Detail Lengkap">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-arrow-left-right display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data transfer stok yang sesuai kriteria filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($transfers->hasPages())
            <div class="card-footer bg-white py-3 no-print">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Preview Modal -->
    @if ($isQuickModalOpen && $selectedTransfer)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Pratinjau Transfer {{ $selectedTransfer->no_transfer }}</h5>
                            <small class="text-muted">Tanggal: {{ $selectedTransfer->tanggal ? $selectedTransfer->tanggal->translatedFormat('d F Y') : '-' }}</small>
                        </div>
                        <button type="button" wire:click="closeQuickModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Route Card -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Gudang Asal (Pengirim):</small>
                                    <span class="fw-bold text-dark"><i class="bi bi-box-arrow-right me-1 text-secondary"></i>{{ $selectedTransfer->gudangAsal->nama_gudang ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Gudang Tujuan (Penerima):</small>
                                    <span class="fw-bold text-primary"><i class="bi bi-box-arrow-in-right me-1"></i>{{ $selectedTransfer->gudangTujuan->nama_gudang ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        @if(filled($selectedTransfer->keterangan))
                            <div class="alert alert-secondary py-2 small mb-3">
                                <strong>Catatan:</strong> {{ $selectedTransfer->keterangan }}
                            </div>
                        @endif

                        <!-- Transferred Items Table -->
                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-boxes me-1"></i>Daftar Barang yang Dipindahkan:</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;">#</th>
                                        <th>Nama Produk</th>
                                        <th>Kategori & Merek</th>
                                        <th class="text-end pe-3">Jumlah Transfer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedTransfer->details as $idx => $d)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $d->produk->nama ?? '-' }}</div>
                                                <small class="text-muted font-monospace">{{ $d->produk->kode_katalog ?? '-' }}</small>
                                            </td>
                                            <td>
                                                <small class="text-secondary">{{ $d->produk->kategori->nama_kategori ?? '-' }} / {{ $d->produk->merek->nama_merek ?? '-' }}</small>
                                            </td>
                                            <td class="text-end pe-3 fw-bold text-dark">
                                                {{ number_format((float) $d->jumlah, 2, ',', '.') }}
                                                <span class="text-muted fw-normal small">{{ $d->produk->satuan->label() ?? '' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <a href="{{ route('transfer.show', $selectedTransfer->id) }}" class="btn btn-outline-primary me-auto">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman Lengkap
                        </a>
                        @can('delete', $selectedTransfer)
                            <button type="button" wire:click="promptCancelTransfer({{ $selectedTransfer->id }})" class="btn btn-outline-danger">
                                <i class="bi bi-x-circle me-1"></i>Batalkan Transfer
                            </button>
                        @endcan
                        <button type="button" wire:click="closeQuickModal" class="btn btn-secondary">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Custom Modern Cancellation Confirmation Modal -->
    @if ($isCancelModalOpen && $transferToCancel)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1065;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-body p-4 text-center">
                        <!-- Icon Warning Glow -->
                        <div class="d-inline-flex p-3 rounded-circle bg-danger bg-opacity-10 text-danger mb-3 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill display-5"></i>
                        </div>

                        <h4 class="fw-bold text-dark mb-1">Batalkan Transfer Ini?</h4>
                        <p class="text-muted small mb-3">
                            Tindakan ini akan mengembalikan stok barang ke posisi semula dan mencatat jurnal pembalikan.
                        </p>

                        <!-- Document Details Card -->
                        <div class="card bg-light border-0 rounded-3 text-start mb-3 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted small">No. Transfer:</span>
                                <span class="fw-bold font-monospace text-danger">{{ $transferToCancel->no_transfer }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted small">Rute Pengiriman:</span>
                                <span class="fw-semibold text-dark small text-end">
                                    {{ $transferToCancel->gudangAsal->nama_gudang ?? '-' }}
                                    <i class="bi bi-arrow-right mx-1 text-primary"></i>
                                    {{ $transferToCancel->gudangTujuan->nama_gudang ?? '-' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Total Barang Terkena:</span>
                                <span class="fw-bold text-dark small">
                                    {{ $transferToCancel->details->count() }} item ({{ number_format((float) $transferToCancel->details->sum('jumlah'), 2, ',', '.') }} unit)
                                </span>
                            </div>
                        </div>

                        <!-- Consequence Notice -->
                        <div class="alert alert-warning text-start py-2 px-3 small border-0 mb-4 rounded-3 d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>Dampak Pembatalan:</strong>
                                <ul class="mb-0 ps-3 mt-1 text-muted">
                                    <li>Stok di <strong>{{ $transferToCancel->gudangTujuan->nama_gudang ?? 'Gudang Tujuan' }}</strong> akan otomatis dikurangi kembali.</li>
                                    <li>Stok di <strong>{{ $transferToCancel->gudangAsal->nama_gudang ?? 'Gudang Asal' }}</strong> akan otomatis dikembalikan utuh.</li>
                                    <li>Audit riwayat mutasi pembatalan otomatis dicatat.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 justify-content-center">
                            <button
                                type="button"
                                wire:click="closeCancelModal"
                                class="btn btn-light px-4 py-2 border rounded-3 fw-medium text-secondary"
                            >
                                Kembali / Batal
                            </button>
                            <button
                                type="button"
                                wire:click="executeCancelTransfer"
                                wire:loading.attr="disabled"
                                class="btn btn-danger px-4 py-2 rounded-3 fw-bold text-white shadow-sm"
                            >
                                <span wire:loading.remove wire:target="executeCancelTransfer">
                                    <i class="bi bi-x-circle me-1"></i> Ya, Batalkan Sekarang
                                </span>
                                <span wire:loading wire:target="executeCancelTransfer">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Memproses...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
