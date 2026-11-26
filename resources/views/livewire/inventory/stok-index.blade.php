<div>
    <style>
        @media print {
            #sidebar-wrapper, .sidebar-overlay, #topbar, .btn, .card-footer, .input-group, select, .d-print-none, .modal, .modal-backdrop {
                display: none !important;
            }
            #main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .content-body {
                padding: 0 !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .table {
                width: 100% !important;
                font-size: 11px !important;
            }
            .print-header {
                display: block !important;
            }
        }
        .print-header {
            display: none;
        }
        .metric-card-interactive {
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }
        .metric-card-interactive:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
        .metric-card-active {
            border: 2px solid var(--bs-primary) !important;
            background-color: var(--bs-primary-bg-subtle, rgba(13, 110, 253, 0.05)) !important;
        }
    </style>

    <!-- Print Header (Only visible on paper print) -->
    <div class="print-header mb-4 text-center">
        <h3 class="fw-bold mb-1">LAPORAN STOK FISIK SAAT INI</h3>
        <p class="text-muted small mb-1">Natural Gypsum & PVC &bull; Dicetak: {{ now()->translatedFormat('d F Y, H:i') }}</p>
        @if(filled($filterGudang))
            <p class="small fw-semibold mb-0">Lokasi: {{ $gudangs->firstWhere('id', $filterGudang)?->nama_gudang ?? 'Gudang Terpilih' }}</p>
        @else
            <p class="small fw-semibold mb-0">Lokasi: Seluruh Gudang</p>
        @endif
    </div>

    <!-- Page Header (Screen) -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Stok Saat Ini</h4>
            <p class="text-muted small mb-0">Monitor kuantitas stok fisik aktual per gudang, ambang batas minimum, dan status ketersediaan barang.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary flex-fill flex-md-grow-0" title="Cetak data stok saat ini">
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
            <button
                type="button"
                wire:click="exportExcel"
                wire:loading.attr="disabled"
                class="btn btn-outline-success flex-fill flex-md-grow-0"
                title="Unduh data stok dalam format Excel (.xlsx)"
            >
                <span wire:loading.remove wire:target="exportExcel"><i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel</span>
                <span wire:loading wire:target="exportExcel"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Membuat Excel...</span>
            </button>
            <a href="{{ route('stok.mutasi.index') }}" class="btn btn-outline-primary flex-fill flex-md-grow-0">
                <i class="bi bi-clock-history me-1"></i>Lihat Mutasi Stok
            </a>
        </div>
    </div>

    <!-- POIN A: KPI Metric Cards (Interactive click-to-filter) -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4 d-print-none">
        <!-- Total SKU Card -->
        <div class="col-6 col-xl-{{ $canViewPurchasePrices ? '2' : '3' }}">
            <div
                wire:click="filterByStatus('')"
                class="card border-0 shadow-sm h-100 metric-card-interactive {{ $filterStatus === '' ? 'metric-card-active' : '' }}"
                title="Klik untuk tampilkan semua status stok"
            >
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-semibold text-truncate">Total SKU</span>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary d-none d-sm-flex">
                            <i class="bi bi-boxes fs-6"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 font-monospace">{{ number_format($metrics['totalSku'], 0, ',', '.') }}</h4>
                    <span class="text-muted small d-block text-truncate">Jenis Barang Aktif</span>
                </div>
            </div>
        </div>

        <!-- Total Unit Fisik Card -->
        <div class="col-6 col-xl-{{ $canViewPurchasePrices ? '2' : '3' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-semibold text-truncate">Total Unit Fisik</span>
                        <div class="rounded-circle bg-info bg-opacity-10 p-2 text-info d-none d-sm-flex">
                            <i class="bi bi-layers-fill fs-6"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 font-monospace">{{ number_format($metrics['totalFisik'], 2, ',', '.') }}</h4>
                    <span class="text-muted small d-block text-truncate">Akumulasi Kuantitas</span>
                </div>
            </div>
        </div>

        <!-- Stok Menipis Card (Interactive) -->
        <div class="col-6 col-xl-{{ $canViewPurchasePrices ? '2' : '3' }}">
            <div
                wire:click="filterByStatus('menipis')"
                class="card border-0 shadow-sm h-100 metric-card-interactive {{ $filterStatus === 'menipis' ? 'metric-card-active' : '' }}"
                title="Klik untuk filter barang menipis"
            >
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-semibold text-truncate">Stok Menipis</span>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-2 text-warning d-none d-sm-flex">
                            <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 font-monospace text-warning">{{ number_format($metrics['totalMenipis'], 0, ',', '.') }}</h4>
                    <span class="text-muted small d-block text-truncate">&le; Batas Minimum</span>
                </div>
            </div>
        </div>

        <!-- Stok Habis Card (Interactive) -->
        <div class="col-6 col-xl-{{ $canViewPurchasePrices ? '3' : '3' }}">
            <div
                wire:click="filterByStatus('habis')"
                class="card border-0 shadow-sm h-100 metric-card-interactive {{ $filterStatus === 'habis' ? 'metric-card-active' : '' }}"
                title="Klik untuk filter barang habis/kosong"
            >
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                        <span class="text-muted small fw-semibold text-truncate">Stok Habis</span>
                        <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger d-none d-sm-flex">
                            <i class="bi bi-x-circle-fill fs-6"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 font-monospace text-danger">{{ number_format($metrics['totalHabis'], 0, ',', '.') }}</h4>
                    <span class="text-muted small d-block text-truncate">Stok &le; 0 (Kosong)</span>
                </div>
            </div>
        </div>

        <!-- Nilai Aset Stok (Only for users who can view purchase prices) -->
        @if ($canViewPurchasePrices)
            <div class="col-12 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-success-subtle bg-opacity-50">
                    <div class="card-body p-2 p-sm-3">
                        <div class="d-flex align-items-center justify-content-between mb-1 mb-sm-2">
                            <span class="text-success-emphasis small fw-semibold">Estimasi Valuasi Aset</span>
                            <div class="rounded-circle bg-success bg-opacity-20 p-2 text-success d-none d-sm-flex">
                                <i class="bi bi-cash-stack fs-6"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-0 font-monospace text-success">
                            Rp {{ number_format($metrics['totalNilaiAset'], 0, ',', '.') }}
                        </h5>
                        <span class="text-muted small">Valuasi Harga Pokok</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- POIN C: Filters, Search, Sorting & Reset -->
    <div class="card mb-4 border-0 shadow-sm d-print-none">
        <div class="card-body p-3 p-md-4">
            <!-- Tier 1: Search Bar + Sort & Per Page + Reset in unified row -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col-12 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Cari kode katalog, nama barang, ukuran spesifikasi..."
                        >
                        @if(filled($search))
                            <button
                                type="button"
                                wire:click="$set('search', '')"
                                class="btn bg-white border border-start-0 text-muted"
                                title="Hapus teks pencarian"
                            >
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="d-flex gap-2 justify-content-lg-end align-items-center flex-wrap">
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted small text-nowrap"><i class="bi bi-sort-down me-1"></i>Urutan:</span>
                            <select wire:model.live="sortBy" class="form-select form-select-sm" style="width: auto; min-width: 170px;">
                                <option value="terbaru">Terakhir Diperbarui</option>
                                <option value="stok_asc">Stok: Sedikit &rarr; Banyak</option>
                                <option value="stok_desc">Stok: Banyak &rarr; Sedikit</option>
                                <option value="nama_asc">Nama Produk (A &rarr; Z)</option>
                                <option value="nama_desc">Nama Produk (Z &rarr; A)</option>
                                <option value="kode_asc">Kode Katalog (A &rarr; Z)</option>
                            </select>
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted small text-nowrap"><i class="bi bi-list-ol me-1"></i>Baris:</span>
                            <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto; min-width: 90px;">
                                <option value="15">15 Baris</option>
                                <option value="25">25 Baris</option>
                                <option value="50">50 Baris</option>
                                <option value="100">100 Baris</option>
                            </select>
                        </div>

                        @if ($hasActiveFilters)
                            <button
                                type="button"
                                wire:click="resetFilters"
                                class="btn btn-sm btn-outline-danger"
                                title="Kembalikan semua filter ke pengaturan awal"
                            >
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tier 2: 4 Balanced Classification Columns -->
            <div class="row g-2 pt-3 border-top">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-building me-1 text-primary"></i>Gudang
                    </label>
                    <select wire:model.live="filterGudang" class="form-select form-select-sm bg-light-subtle">
                        <option value="">Semua Gudang</option>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-grid me-1 text-info"></i>Kategori
                    </label>
                    <select wire:model.live="filterKategori" class="form-select form-select-sm bg-light-subtle">
                        <option value="">Semua Kategori</option>
                        @foreach ($rootKategoris as $root)
                            @if ($root->children->isNotEmpty())
                                <optgroup label="📁 {{ $root->nama_kategori }}">
                                    <option value="{{ $root->id }}">&bull; Semua {{ $root->nama_kategori }}</option>
                                    @foreach ($root->children as $child)
                                        <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;↳ {{ $child->nama_kategori }}</option>
                                    @endforeach
                                </optgroup>
                            @else
                                <option value="{{ $root->id }}">📁 {{ $root->nama_kategori }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-tag me-1 text-warning"></i>Merek
                    </label>
                    <select wire:model.live="filterMerek" class="form-select form-select-sm bg-light-subtle">
                        <option value="">Semua Merek</option>
                        @foreach ($mereks as $merek)
                            <option value="{{ $merek->id }}">{{ $merek->nama_merek }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-shield-check me-1 text-success"></i>Status Stok
                    </label>
                    <select wire:model.live="filterStatus" class="form-select form-select-sm bg-light-subtle">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Active Filter Chips (Instant individual dismissal) -->
            @if ($hasActiveFilters)
                <div class="d-flex align-items-center gap-1 flex-wrap mt-3 pt-2 border-top small">
                    <span class="text-muted me-1"><i class="bi bi-funnel-fill text-primary"></i> Filter Aktif:</span>
                    @if(filled($search))
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2">
                            <span>Teks: "{{ $search }}"</span>
                            <button type="button" wire:click="$set('search', '')" class="btn-close btn-close-sm p-0 m-0" style="font-size: 0.5rem;" title="Hapus pencarian ini"></button>
                        </span>
                    @endif
                    @if(filled($filterGudang))
                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle d-flex align-items-center gap-1 py-1 px-2">
                            <span>Gudang: {{ $gudangs->firstWhere('id', $filterGudang)?->nama_gudang }}</span>
                            <button type="button" wire:click="$set('filterGudang', '')" class="btn-close btn-close-sm p-0 m-0" style="font-size: 0.5rem;" title="Hapus filter gudang"></button>
                        </span>
                    @endif
                    @if(filled($filterKategori))
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle d-flex align-items-center gap-1 py-1 px-2">
                            <span>Kategori: {{ $kategoris->firstWhere('id', $filterKategori)?->nama_kategori }}</span>
                            <button type="button" wire:click="$set('filterKategori', '')" class="btn-close btn-close-sm p-0 m-0" style="font-size: 0.5rem;" title="Hapus filter kategori"></button>
                        </span>
                    @endif
                    @if(filled($filterMerek))
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-flex align-items-center gap-1 py-1 px-2">
                            <span>Merek: {{ $mereks->firstWhere('id', $filterMerek)?->nama_merek }}</span>
                            <button type="button" wire:click="$set('filterMerek', '')" class="btn-close btn-close-sm p-0 m-0" style="font-size: 0.5rem;" title="Hapus filter merek"></button>
                        </span>
                    @endif
                    @if(filled($filterStatus))
                        <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle d-flex align-items-center gap-1 py-1 px-2">
                            <span>Status: {{ ucfirst($filterStatus) }}</span>
                            <button type="button" wire:click="$set('filterStatus', '')" class="btn-close btn-close-sm p-0 m-0" style="font-size: 0.5rem;" title="Hapus filter status"></button>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- POIN D: Data Table with Product Thumbnail, Valuation & POIN B: Direct Actions -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none d-print-none">
                <div class="list-group list-group-flush">
                    @forelse ($stoks as $index => $item)
                        @php
                            $produk = $isConsolidated ? $item : $item->produk;
                            $qty = $isConsolidated ? (float) ($item->total_stok ?? 0) : (float) $item->jumlah;
                            $min = (float) $produk->stok_minimum;
                            $status = $isConsolidated
                                ? \App\Domains\Inventory\Enums\StatusStok::determine($qty, $min)
                                : $item->status;
                            $isNegative = $qty < 0;
                            $isEmpty = $qty == 0;
                            $isCritical = $qty > 0 && $qty <= $min;
                            $produkId = $produk->id;
                        @endphp
                        <div class="list-group-item p-3 {{ ($isNegative || $isEmpty) ? 'bg-danger-subtle bg-opacity-10' : ($isCritical ? 'bg-warning-subtle bg-opacity-10' : '') }}">
                            <!-- Top Row: Image, SKU, Status & Product Name -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                @if ($produk->foto)
                                    <div
                                        role="button"
                                        wire:click="openImageModal({{ $produkId }})"
                                        class="position-relative overflow-hidden rounded border flex-shrink-0"
                                        style="width: 48px; height: 48px; cursor: pointer;"
                                    >
                                        <img src="{{ $produk->foto_url ?? asset('storage/' . $produk->foto) }}" alt="{{ $produk->nama }}" class="w-100 h-100 object-fit-cover" loading="lazy">
                                    </div>
                                @else
                                    <div class="rounded bg-light border d-flex align-items-center justify-content-center text-secondary flex-shrink-0" style="width: 48px; height: 48px;">
                                        <i class="bi bi-box-seam fs-6"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <span class="text-muted small">#{{ $stoks->firstItem() + $index }}</span>
                                            <span class="badge bg-light text-dark border font-monospace fw-semibold">
                                                {{ $produk->kode_katalog }}
                                            </span>
                                        </div>
                                        <span class="badge {{ $status->badgeClass() }}">
                                            {{ $status->label() }}
                                        </span>
                                    </div>
                                    <a href="{{ route('master.produk.show', ['id' => $produkId, 'from' => 'stok']) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $produk->nama }}
                                    </a>
                                    @if ($produk->ukuran)
                                        <small class="text-muted d-block">Ukuran: {{ $produk->ukuran }}</small>
                                    @endif
                                </div>
                            </div>

                            <!-- Category, Brand & Warehouse Info -->
                            <div class="d-flex align-items-center flex-wrap gap-1 mb-2">
                                <span class="badge bg-light text-dark border">
                                    {{ $produk->kategori->nama_kategori }}
                                </span>
                                @if ($produk->merek)
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        {{ $produk->merek->nama_merek }}
                                    </span>
                                @endif
                                @if ($isConsolidated)
                                    @php
                                        $activeStoks = $produk->stok->filter(fn ($s) => (float) $s->jumlah != 0);
                                    @endphp
                                    @if ($activeStoks->isEmpty())
                                        <span class="badge bg-secondary-subtle text-secondary border">Belum Ada Stok</span>
                                    @elseif ($activeStoks->count() === 1)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-building me-1"></i>{{ $activeStoks->first()->gudang->nama_gudang }}
                                        </span>
                                    @else
                                        <button type="button" wire:click="openDetailModal({{ $produkId }})" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none">
                                            <i class="bi bi-buildings me-1"></i>{{ $activeStoks->count() }} Gudang (Detail)
                                        </button>
                                    @endif
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-building me-1"></i>{{ $item->gudang->nama_gudang }}
                                    </span>
                                @endif
                            </div>

                            <!-- Stock & Valuation Summary Box -->
                            <div class="bg-light rounded-2 p-2 mb-2 border d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Stok Fisik (Min: {{ number_format($min, 0, ',', '.') }})</span>
                                    <span class="fw-bold font-monospace fs-6 {{ $isNegative ? 'text-danger' : 'text-dark' }}">
                                        {{ number_format($qty, 2, ',', '.') }} {{ $produk->satuan->value }}
                                    </span>
                                    @if ($qty != 0 && !$isNegative)
                                        @php $konversi = $produk->getKonversiFisik($qty); @endphp
                                        @if ($konversi['has_conversion'] && filled($konversi['badge_text']))
                                            <small class="d-block text-primary fw-semibold" style="font-size: 0.7rem;">
                                                <i class="bi bi-box-seam me-1"></i>{{ $konversi['badge_text'] }}
                                            </small>
                                        @endif
                                    @endif
                                </div>
                                @if ($canViewPurchasePrices)
                                    <div class="text-end">
                                        <span class="text-muted d-block" style="font-size: 0.7rem;">Estimasi Nilai</span>
                                        <span class="fw-bold text-dark font-monospace">
                                            Rp {{ number_format($qty * (float)$produk->harga_beli, 0, ',', '.') }}
                                        </span>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">@ Rp {{ number_format((float)$produk->harga_beli, 0, ',', '.') }}</small>
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-1">
                                <button type="button" wire:click="openDetailModal({{ $produkId }})" class="btn btn-sm btn-outline-secondary flex-fill">
                                    <i class="bi bi-diagram-3 me-1"></i>Gudang
                                </button>
                                <a href="{{ route('stok.mutasi.index', $isConsolidated ? ['filterProduk' => $produkId, 'from' => 'stok'] : ['filterProduk' => $produkId, 'filterGudang' => $item->gudang_id, 'from' => 'stok']) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-clock-history me-1"></i>Mutasi
                                </a>
                                <a href="{{ route('master.produk.show', ['id' => $produkId, 'from' => 'stok']) }}" class="btn btn-sm btn-outline-info flex-fill">
                                    <i class="bi bi-eye me-1"></i>Produk
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 px-3">
                            <div class="text-muted">
                                <i class="bi bi-boxes display-4 d-block mb-2 text-secondary"></i>
                                <p class="mb-0">Tidak ada data stok yang sesuai dengan filter.</p>
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
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Produk</th>
                            <th>Kategori &amp; Merek</th>
                            <th>Gudang</th>
                            <th class="text-end">Stok Fisik</th>
                            <th class="text-end">Stok Min</th>
                            <th class="text-center">Status</th>
                            @if ($canViewPurchasePrices)
                                <th class="text-end">Estimasi Nilai</th>
                            @endif
                            <th class="text-end pe-4 d-print-none" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stoks as $index => $item)
                            @php
                                $produk = $isConsolidated ? $item : $item->produk;
                                $qty = $isConsolidated ? (float) ($item->total_stok ?? 0) : (float) $item->jumlah;
                                $min = (float) $produk->stok_minimum;
                                $status = $isConsolidated
                                    ? \App\Domains\Inventory\Enums\StatusStok::determine($qty, $min)
                                    : $item->status;
                                $isNegative = $qty < 0;
                                $isEmpty = $qty == 0;
                                $isCritical = $qty > 0 && $qty <= $min;
                                $rowClass = $isNegative ? 'table-danger bg-opacity-10' : ($isEmpty ? 'table-danger bg-opacity-10' : ($isCritical ? 'table-warning bg-opacity-10' : ''));
                                $produkId = $produk->id;
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="ps-4 text-muted">{{ $stoks->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Thumbnail Foto Produk (Klik untuk melihat detail / lightbox) -->
                                        @if ($produk->foto)
                                            <div
                                                role="button"
                                                wire:click="openImageModal({{ $produkId }})"
                                                class="position-relative overflow-hidden rounded border flex-shrink-0"
                                                style="width: 42px; height: 42px; cursor: pointer;"
                                                title="Klik untuk memperbesar foto {{ $produk->nama }}"
                                            >
                                                <img
                                                    src="{{ $produk->foto_url ?? asset('storage/' . $produk->foto) }}"
                                                    alt="{{ $produk->nama }}"
                                                    class="w-100 h-100 object-fit-cover"
                                                    loading="lazy"
                                                >
                                                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 text-white"
                                                     style="opacity: 0; transition: opacity 0.15s ease-in-out;"
                                                     onmouseenter="this.style.opacity='1'"
                                                     onmouseleave="this.style.opacity='0'">
                                                    <i class="bi bi-zoom-in" style="font-size: 0.85rem;"></i>
                                                </div>
                                            </div>
                                        @else
                                            <div
                                                class="rounded bg-light border d-flex align-items-center justify-content-center text-secondary flex-shrink-0"
                                                style="width: 42px; height: 42px;"
                                                title="Tanpa Foto"
                                            >
                                                <i class="bi bi-box-seam fs-6"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="badge bg-light text-dark border font-monospace fw-semibold mb-1">
                                                {{ $produk->kode_katalog }}
                                            </span>
                                            <a
                                                href="{{ route('master.produk.show', ['id' => $produkId, 'from' => 'stok']) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="fw-semibold text-dark text-decoration-none d-block"
                                                title="Lihat Detail Master Produk (Buka di tab baru)"
                                            >
                                                {{ $produk->nama }} <i class="bi bi-box-arrow-up-right text-muted small ms-1" style="font-size: 0.72rem;"></i>
                                            </a>
                                            @if ($produk->ukuran)
                                                <small class="text-muted d-block">Ukuran: {{ $produk->ukuran }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $produk->kategori->nama_kategori }}
                                    </span>
                                    @if ($produk->merek)
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            {{ $produk->merek->nama_merek }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($isConsolidated)
                                        @php
                                            $activeStoks = $produk->stok->filter(fn ($s) => (float) $s->jumlah != 0);
                                        @endphp
                                        @if ($activeStoks->isEmpty())
                                            <span class="badge bg-secondary-subtle text-secondary border">
                                                <i class="bi bi-dash-circle me-1"></i>Belum Ada Stok
                                            </span>
                                        @elseif ($activeStoks->count() === 1)
                                            @php $single = $activeStoks->first(); @endphp
                                            <span class="fw-medium text-dark">
                                                <i class="bi bi-building me-1 text-secondary"></i>{{ $single->gudang->nama_gudang }}
                                            </span>
                                        @else
                                            <div class="d-flex flex-column gap-1">
                                                <button
                                                    type="button"
                                                    wire:click="openDetailModal({{ $produkId }})"
                                                    class="btn btn-sm btn-link text-decoration-none p-0 text-start d-inline-flex align-items-center gap-1"
                                                    title="Klik untuk melihat rincian stok tiap gudang"
                                                >
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        <i class="bi bi-buildings me-1"></i>{{ $activeStoks->count() }} Gudang
                                                    </span>
                                                    <i class="bi bi-info-circle text-primary" style="font-size: 0.75rem;"></i>
                                                </button>
                                                <div class="small text-muted" style="font-size: 0.75rem; line-height: 1.3;">
                                                    @foreach ($activeStoks as $s)
                                                        <span class="me-2 text-nowrap">
                                                            <span class="text-secondary">{{ $s->gudang->nama_gudang }}:</span>
                                                            <span class="fw-semibold text-dark">{{ number_format((float) $s->jumlah, 0, ',', '.') }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <span class="fw-medium text-dark">
                                            <i class="bi bi-building me-1 text-secondary"></i>{{ $item->gudang->nama_gudang }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold font-monospace fs-6 {{ $isNegative ? 'text-danger' : 'text-dark' }}">
                                        {{ number_format($qty, 2, ',', '.') }}
                                    </span>
                                    <small class="text-muted d-block">{{ $produk->satuan->value }}</small>
                                    @if ($isNegative)
                                        <div class="mt-1">
                                            <span class="badge bg-danger text-white py-1 px-2" style="font-size: 0.7rem;">
                                                <i class="bi bi-exclamation-octagon-fill me-1"></i>Minus (Perlu Restock)
                                            </span>
                                        </div>
                                    @elseif ($qty != 0)
                                        @php
                                            $konversi = $produk->getKonversiFisik($qty);
                                        @endphp
                                        @if ($konversi['has_conversion'] && filled($konversi['badge_text']))
                                            <div class="mt-1" title="Estimasi fisik di gudang & kemasan">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal text-nowrap" style="font-size: 0.72rem;">
                                                    <i class="bi bi-box-seam me-1"></i>{{ $konversi['badge_text'] }}
                                                </span>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="text-muted font-monospace">
                                        {{ number_format($min, 2, ',', '.') }}
                                    </span>
                                    <small class="text-muted d-block">{{ $produk->satuan->value }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $status->badgeClass() }} px-2 py-1">
                                        {{ $status->label() }}
                                    </span>
                                    @if ($min > 0)
                                        @php
                                            $ratio = min(100, max(0, round(($qty / $min) * 100)));
                                            $progressColor = $qty > $min ? 'bg-success' : ($qty > 0 ? 'bg-warning' : 'bg-danger');
                                        @endphp
                                        <div class="progress mt-1 mx-auto" style="height: 4px; width: 60px;" title="Ketersediaan: {{ $ratio }}% dari batas minimum">
                                            <div class="progress-bar {{ $progressColor }}" style="width: {{ $ratio }}%"></div>
                                        </div>
                                    @endif
                                </td>
                                @if ($canViewPurchasePrices)
                                    <td class="text-end">
                                        <span class="fw-semibold text-dark font-monospace">
                                            Rp {{ number_format($qty * (float)$produk->harga_beli, 0, ',', '.') }}
                                        </span>
                                        <small class="text-muted d-block">@ Rp {{ number_format((float)$produk->harga_beli, 0, ',', '.') }}</small>
                                    </td>
                                @endif
                                <td class="text-end pe-4 d-print-none">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button
                                            type="button"
                                            wire:click="openDetailModal({{ $produkId }})"
                                            class="btn btn-outline-secondary"
                                            title="Rincian Multi-Gudang"
                                        >
                                            <i class="bi bi-diagram-3"></i>
                                        </button>
                                        <a
                                            href="{{ route('stok.mutasi.index', $isConsolidated ? ['filterProduk' => $produkId, 'from' => 'stok'] : ['filterProduk' => $produkId, 'filterGudang' => $item->gudang_id, 'from' => 'stok']) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-outline-primary"
                                            title="{{ $isConsolidated ? 'Riwayat Mutasi Barang (Semua Gudang)' : 'Riwayat Mutasi Barang di Gudang Ini' }} (Buka di tab baru)"
                                        >
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                        <a
                                            href="{{ route('master.produk.show', ['id' => $produkId, 'from' => 'stok']) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-outline-info"
                                            title="Buka Master Produk (Buka di tab baru)"
                                        >
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canViewPurchasePrices ? '9' : '8' }}" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-boxes display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data stok yang sesuai dengan filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($stoks->hasPages())
            <div class="card-footer bg-white py-3 d-print-none">
                {{ $stoks->links() }}
            </div>
        @endif
    </div>

    <!-- POIN B: Enhanced Multi-Warehouse Detail Modal with Direct Actions -->
    @if ($isDetailModalOpen && $selectedProduk)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-2 py-sm-3 px-3 px-sm-4">
                        <div class="min-w-0 pe-2">
                            <h5 class="modal-title fw-bold mb-0">Rincian Stok Lintas Gudang</h5>
                            <small class="text-muted font-monospace d-block text-truncate">{{ $selectedProduk->kode_katalog }} &mdash; {{ $selectedProduk->nama }}</small>
                        </div>
                        <button type="button" wire:click="closeDetailModal" class="btn-close flex-shrink-0" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3 p-sm-4">
                        <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3 py-2 px-3 flex-wrap gap-2">
                            <div>
                                <span class="text-muted small d-block">Total Akumulasi Stok Seluruh Gudang:</span>
                                <span class="fw-bold fs-5 {{ $totalCrossWarehouseStock < 0 ? 'text-danger' : 'text-primary' }}">
                                    {{ number_format($totalCrossWarehouseStock, 2, ',', '.') }} {{ $selectedProduk->satuan->value }}
                                </span>
                            </div>
                            <div class="text-sm-end">
                                <span class="text-muted small d-block">Ambang Batas Minimum:</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                    {{ number_format((float)$selectedProduk->stok_minimum, 2, ',', '.') }} {{ $selectedProduk->satuan->value }}
                                </span>
                            </div>
                        </div>

                        <!-- Mobile Compact Card List (< 768px) -->
                        <div class="d-md-none">
                            <div class="list-group">
                                @foreach ($warehouseBreakdown as $row)
                                    <div class="list-group-item p-2 px-3">
                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                            <div class="fw-bold text-dark text-truncate">
                                                <i class="bi bi-building me-1 text-secondary"></i>{{ $row['gudang'] }}
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                <span class="font-monospace fw-bold {{ $row['jumlah'] < 0 ? 'text-danger' : 'text-dark' }}">
                                                    {{ number_format($row['jumlah'], 2, ',', '.') }} {{ $selectedProduk->satuan->value }}
                                                </span>
                                                <span class="badge {{ $row['status']->badgeClass() }}">
                                                    {{ $row['status']->label() }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <small class="text-muted text-truncate d-block" style="max-width: 58%;" title="{{ $row['lokasi'] }}">
                                                <i class="bi bi-geo-alt me-1"></i>{{ $row['lokasi'] ?: '-' }}
                                            </small>
                                            <div class="d-flex gap-1 flex-shrink-0">
                                                @can('create', App\Domains\Inventory\Models\TransferGudang::class)
                                                    @if ($row['jumlah'] > 0)
                                                        <a
                                                            href="{{ route('transfer.create', ['from_gudang_id' => $row['gudang_id'], 'produk_id' => $selectedProduk->id]) }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="btn btn-xs btn-outline-success py-0 px-2"
                                                            style="font-size: 0.72rem;"
                                                        >
                                                            <i class="bi bi-arrow-right-circle me-1"></i>Transfer
                                                        </a>
                                                    @endif
                                                @endcan
                                                <a
                                                    href="{{ route('stok.mutasi.index', ['filterProduk' => $selectedProduk->id, 'filterGudang' => $row['gudang_id'], 'from' => 'stok']) }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="btn btn-xs btn-outline-primary py-0 px-2"
                                                    style="font-size: 0.72rem;"
                                                >
                                                    <i class="bi bi-clock-history me-1"></i>Mutasi
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Desktop & Tablet Table (>= 768px) -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-nowrap ps-3">Nama Gudang</th>
                                        <th>Lokasi</th>
                                        <th class="text-end text-nowrap">Kuantitas Stok</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-3 text-nowrap" style="width: 190px;">Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($warehouseBreakdown as $row)
                                        <tr>
                                            <td class="fw-semibold text-nowrap ps-3">
                                                <i class="bi bi-building me-1 text-secondary"></i>{{ $row['gudang'] }}
                                            </td>
                                            <td class="text-muted small">
                                                <div class="text-truncate" style="max-width: 210px;" title="{{ $row['lokasi'] }}">
                                                    {{ $row['lokasi'] ?: '-' }}
                                                </div>
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-nowrap {{ $row['jumlah'] < 0 ? 'text-danger' : 'text-dark' }}">
                                                {{ number_format($row['jumlah'], 2, ',', '.') }} {{ $selectedProduk->satuan->value }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $row['status']->badgeClass() }}">
                                                    {{ $row['status']->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-3 text-nowrap">
                                                <div class="d-flex justify-content-end gap-1">
                                                    @can('create', App\Domains\Inventory\Models\TransferGudang::class)
                                                        @if ($row['jumlah'] > 0)
                                                            <a
                                                                href="{{ route('transfer.create', ['from_gudang_id' => $row['gudang_id'], 'produk_id' => $selectedProduk->id]) }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="btn btn-xs btn-outline-success py-0 px-2"
                                                                title="Transfer keluar dari {{ $row['gudang'] }} (Tab Baru)"
                                                            >
                                                                <i class="bi bi-arrow-right-circle me-1"></i>Transfer <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.65rem;"></i>
                                                            </a>
                                                        @endif
                                                    @endcan
                                                    <a
                                                        href="{{ route('stok.mutasi.index', ['filterProduk' => $selectedProduk->id, 'filterGudang' => $row['gudang_id'], 'from' => 'stok']) }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="btn btn-xs btn-outline-primary py-0 px-2"
                                                        title="Lihat Riwayat Mutasi di {{ $row['gudang'] }} (Tab Baru)"
                                                    >
                                                        <i class="bi bi-clock-history me-1"></i>Mutasi <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.65rem;"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-3 px-sm-4 d-flex justify-content-between flex-wrap gap-2">
                        <div class="d-flex gap-2 flex-wrap flex-fill flex-sm-grow-0">
                            @can('create', App\Domains\Inventory\Models\TransferGudang::class)
                                <a
                                    href="{{ route('transfer.create', ['produk_id' => $selectedProduk->id]) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-primary flex-fill flex-sm-grow-0"
                                    title="Buat Transfer Antar Gudang untuk produk ini (Tab Baru)"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>Transfer Antar Gudang
                                </a>
                            @endcan
                            <a
                                href="{{ route('master.produk.show', ['id' => $selectedProduk->id, 'from' => 'stok']) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-sm btn-outline-info flex-fill flex-sm-grow-0"
                                title="Buka Master Produk di tab baru"
                            >
                                <i class="bi bi-box-arrow-up-right me-1"></i>Detail Produk
                            </a>
                        </div>
                        <button type="button" wire:click="closeDetailModal" class="btn btn-sm btn-secondary flex-fill flex-sm-grow-0">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Image Preview / Lightbox Modal -->
    @if ($isImageModalOpen && $imageModalProduk)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.75); z-index: 1070;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                    <div class="modal-header bg-light border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <span class="badge bg-primary font-monospace">{{ $imageModalProduk->kode_katalog }}</span>
                            <h6 class="modal-title fw-bold text-dark text-truncate mb-0" title="{{ $imageModalProduk->nama }}">
                                {{ $imageModalProduk->nama }}
                            </h6>
                            @if ($imageModalProduk->kategori)
                                <span class="badge bg-light text-dark border d-none d-sm-inline-block">{{ $imageModalProduk->kategori->nama_kategori }}</span>
                            @endif
                        </div>
                        <button type="button" wire:click="closeImageModal" class="btn-close" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-0 text-center bg-dark d-flex align-items-center justify-content-center position-relative" style="min-height: 380px; max-height: 70vh;">
                        <img
                            src="{{ $imageModalProduk->foto_url }}"
                            alt="{{ $imageModalProduk->nama }}"
                            class="img-fluid p-2"
                            style="max-height: 68vh; object-fit: contain; user-select: none;"
                        >
                        <a
                            href="{{ $imageModalProduk->foto_url }}"
                            target="_blank"
                            class="btn btn-sm btn-dark bg-opacity-75 text-white position-absolute top-0 end-0 m-3 border shadow-sm"
                            title="Buka gambar di tab baru dengan ukuran asli"
                        >
                            <i class="bi bi-box-arrow-up-right me-1"></i>Ukuran Penuh
                        </a>
                    </div>

                    <div class="modal-footer bg-light border-top py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">
                            @if ($imageModalProduk->ukuran)
                                <span class="me-3"><i class="bi bi-aspect-ratio me-1"></i>{{ $imageModalProduk->ukuran }}</span>
                            @endif
                            <span class="fw-bold text-success fs-6">
                                Rp {{ number_format($imageModalProduk->harga_jual, 0, ',', '.') }}
                            </span>
                            <span class="text-secondary">/ {{ $imageModalProduk->satuan->label() }}</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('master.produk.show', ['id' => $imageModalProduk->id, 'from' => 'stok']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Detail Produk (Tab Baru)
                            </a>
                            <button type="button" wire:click="closeImageModal" class="btn btn-sm btn-secondary">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

