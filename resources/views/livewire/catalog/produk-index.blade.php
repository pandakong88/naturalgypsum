<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Master Produk</h4>
            <p class="text-muted small mb-0">Kelola katalog produk material (PVC, Ceiling Board, Moulding/List) & konfigurasi harga.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button wire:click="exportExcel" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </button>
            @can('create', App\Domains\Catalog\Models\Produk::class)
                <button wire:click="openImportModal" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel
                </button>
                <button wire:click="openCreateModal" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Produk
                </button>
            @endcan
        </div>
    </div>

    <!-- Print Stylesheet for Shelf Label / Barcode -->
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #printable-barcode-area, #printable-barcode-area * {
                visibility: visible !important;
            }
            #printable-barcode-area {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- 4 KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div role="button" wire:click="setTab('all')" class="card border-0 shadow-sm rounded-3 transition-all h-100 {{ $tabFilter === 'all' ? 'border-primary border-2 shadow' : '' }}" style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Total Katalog</span>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($totalCount, 0, ',', '.') }}</h4>
                            <small class="text-muted" style="font-size: 0.72rem;">Semua SKU Terdaftar</small>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-box-seam fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div role="button" wire:click="setTab('safe')" class="card border-0 shadow-sm rounded-3 transition-all h-100 {{ $tabFilter === 'safe' ? 'border-success border-2 shadow' : '' }}" style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Stok Aman</span>
                            <h4 class="fw-bold mb-0 text-success">{{ number_format($safeCount, 0, ',', '.') }}</h4>
                            <small class="text-success" style="font-size: 0.72rem;">Di atas ambang batas</small>
                        </div>
                        <div class="bg-success-subtle text-success p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div role="button" wire:click="setTab('critical')" class="card border-0 shadow-sm rounded-3 transition-all h-100 {{ $tabFilter === 'critical' ? 'border-warning border-2 shadow' : '' }}" style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Stok Menipis / Kritis</span>
                            <h4 class="fw-bold mb-0 text-warning-emphasis">{{ number_format($criticalCount, 0, ',', '.') }}</h4>
                            <small class="text-warning-emphasis" style="font-size: 0.72rem;">Perlu restock / PO</small>
                        </div>
                        <div class="bg-warning-subtle text-warning-emphasis p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div role="button" wire:click="setTab('empty')" class="card border-0 shadow-sm rounded-3 transition-all h-100 {{ $tabFilter === 'empty' ? 'border-danger border-2 shadow' : '' }}" style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Stok Kosong</span>
                            <h4 class="fw-bold mb-0 text-danger">{{ number_format($emptyCount, 0, ',', '.') }}</h4>
                            <small class="text-danger" style="font-size: 0.72rem;">Stok 0 di semua gudang</small>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-x-octagon fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Filter Tabs & PerPage Selector -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <ul class="nav nav-pills gap-1">
            <li class="nav-item">
                <button type="button" wire:click="setTab('all')" class="nav-link py-1 px-3 {{ $tabFilter === 'all' ? 'active' : '' }}">
                    Semua Produk <span class="badge bg-secondary ms-1">{{ $totalCount }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button type="button" wire:click="setTab('safe')" class="nav-link py-1 px-3 {{ $tabFilter === 'safe' ? 'active bg-success text-white' : 'text-success' }}">
                    <i class="bi bi-check-circle me-1"></i>Stok Aman
                    @if ($safeCount > 0)
                        <span class="badge {{ $tabFilter === 'safe' ? 'bg-white text-success' : 'bg-success text-white' }} ms-1">{{ $safeCount }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button type="button" wire:click="setTab('critical')" class="nav-link py-1 px-3 {{ $tabFilter === 'critical' ? 'active bg-warning text-dark' : 'text-warning-emphasis' }}">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Stok Kritis
                    @if ($criticalCount > 0)
                        <span class="badge {{ $tabFilter === 'critical' ? 'bg-dark text-warning' : 'bg-warning text-dark' }} ms-1">{{ $criticalCount }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button type="button" wire:click="setTab('empty')" class="nav-link py-1 px-3 {{ $tabFilter === 'empty' ? 'active bg-danger text-white' : 'text-danger' }}">
                    <i class="bi bi-x-circle me-1"></i>Stok Kosong
                    @if ($emptyCount > 0)
                        <span class="badge {{ $tabFilter === 'empty' ? 'bg-white text-danger' : 'bg-danger text-white' }} ms-1">{{ $emptyCount }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button type="button" wire:click="setTab('trashed')" class="nav-link py-1 px-3 {{ $tabFilter === 'trashed' ? 'active' : '' }}">
                    <i class="bi bi-trash me-1"></i>Nonaktif / Sampah
                    @if ($trashedCount > 0)
                        <span class="badge bg-secondary ms-1">{{ $trashedCount }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted">Tampilkan:</span>
            <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 85px;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="small text-muted">baris</span>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari kode, nama, ukuran..."
                        >
                        @if (filled($search))
                            <button type="button" wire:click="$set('search', '')" class="btn btn-outline-secondary" title="Hapus pencarian">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <select wire:model.live="filterKategori" class="form-select">
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
                <div class="col-6 col-md-2 col-lg-2">
                    <select wire:model.live="filterMerek" class="form-select">
                        <option value="">Semua Merek</option>
                        @foreach ($mereks as $merek)
                            <option value="{{ $merek->id }}">{{ $merek->nama_merek }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 col-lg-2">
                    <select wire:model.live="filterSatuan" class="form-select">
                        <option value="">Semua Satuan</option>
                        @foreach ($satuanOptions as $satuan)
                            <option value="{{ $satuan->value }}">{{ $satuan->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="btn w-100 d-flex align-items-center justify-content-center gap-1 {{ $this->hasActiveFilters() ? 'btn-outline-danger fw-semibold' : 'btn-outline-secondary disabled' }}"
                        title="Kosongkan semua saringan dan kata kunci pencarian"
                        {{ ! $this->hasActiveFilters() ? 'disabled' : '' }}
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            @if ($this->hasActiveFilters())
                <div class="d-flex align-items-center gap-2 flex-wrap mt-3 pt-2 border-top small">
                    <span class="text-muted"><i class="bi bi-funnel-fill me-1 text-primary"></i>Filter Aktif:</span>
                    @if (filled($search))
                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                            Pencarian: "{{ $search }}"
                            <i role="button" wire:click="$set('search', '')" class="bi bi-x ms-1 text-danger fw-bold" style="cursor: pointer;" title="Hapus filter ini"></i>
                        </span>
                    @endif
                    @if (filled($filterKategori))
                        @php
                            $activeKat = $kategoris->firstWhere('id', $filterKategori);
                        @endphp
                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                            Kategori: {{ $activeKat?->nama_kategori ?? $filterKategori }}
                            <i role="button" wire:click="$set('filterKategori', '')" class="bi bi-x ms-1 text-danger fw-bold" style="cursor: pointer;" title="Hapus filter ini"></i>
                        </span>
                    @endif
                    @if (filled($filterMerek))
                        @php
                            $activeMrk = $mereks->firstWhere('id', $filterMerek);
                        @endphp
                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                            Merek: {{ $activeMrk?->nama_merek ?? $filterMerek }}
                            <i role="button" wire:click="$set('filterMerek', '')" class="bi bi-x ms-1 text-danger fw-bold" style="cursor: pointer;" title="Hapus filter ini"></i>
                        </span>
                    @endif
                    @if (filled($filterSatuan))
                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                            Satuan: {{ $filterSatuan }}
                            <i role="button" wire:click="$set('filterSatuan', '')" class="bi bi-x ms-1 text-danger fw-bold" style="cursor: pointer;" title="Hapus filter ini"></i>
                        </span>
                    @endif
                    @if ($tabFilter !== 'all')
                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                            Tab: {{ ucfirst($tabFilter) }}
                            <i role="button" wire:click="setTab('all')" class="bi bi-x ms-1 text-danger fw-bold" style="cursor: pointer;" title="Kembali ke semua produk"></i>
                        </span>
                    @endif
                    <button type="button" wire:click="resetFilters" class="btn btn-link btn-sm text-danger p-0 ms-auto text-decoration-none fw-semibold" style="font-size: 0.78rem;">
                        <i class="bi bi-x-circle me-1"></i>Hapus Semua Filter
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th role="button" wire:click="sortBy('kode_katalog')">
                                Kode Katalog
                                @if ($sortField === 'kode_katalog')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </th>
                            <th role="button" wire:click="sortBy('nama')">
                                Nama Produk
                                @if ($sortField === 'nama')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </th>
                            <th>Kategori & Merek</th>
                            <th>Satuan</th>
                            <th class="text-end" role="button" wire:click="sortBy('stok_total')">
                                Total Stok
                                @if ($sortField === 'stok_total')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </th>
                            @can('view_purchase_prices')
                                <th class="text-end">Harga Beli</th>
                            @endcan
                            <th class="text-end" role="button" wire:click="sortBy('harga_jual')">
                                Harga Jual
                                @if ($sortField === 'harga_jual')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 190px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produks as $index => $produk)
                            <tr>
                                <td class="ps-4 text-muted">{{ $produks->firstItem() + $index }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fw-semibold">
                                        {{ $produk->kode_katalog }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($produk->foto)
                                            <div
                                                role="button"
                                                wire:click="openImageModal({{ $produk->id }})"
                                                class="position-relative overflow-hidden rounded border flex-shrink-0"
                                                style="width: 42px; height: 42px; cursor: pointer;"
                                                title="Klik untuk memperbesar foto {{ $produk->nama }}"
                                            >
                                                <img src="{{ $produk->foto_url }}" alt="{{ $produk->nama }}" class="w-100 h-100 object-fit-cover" loading="lazy">
                                                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 text-white"
                                                     style="opacity: 0; transition: opacity 0.15s ease-in-out;"
                                                     onmouseenter="this.style.opacity='1'"
                                                     onmouseleave="this.style.opacity='0'">
                                                    <i class="bi bi-zoom-in" style="font-size: 0.85rem;"></i>
                                                </div>
                                            </div>
                                        @else
                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-secondary flex-shrink-0" style="width: 42px; height: 42px;" title="Belum ada foto produk">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('master.produk.show', $produk->id) }}" class="fw-semibold text-decoration-none text-primary">
                                                {{ $produk->nama }}
                                            </a>
                                            @if ($produk->ukuran)
                                                <small class="d-block text-muted">Ukuran: {{ $produk->ukuran }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($produk->kategori->parent)
                                        <div class="small text-muted mb-1" style="font-size: 0.725rem;">
                                            <i class="bi bi-folder-fill me-1 text-primary"></i>{{ $produk->kategori->parent->nama_kategori }}
                                        </div>
                                    @endif
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        {{ $produk->kategori->nama_kategori }}
                                    </span>
                                    @if ($produk->merek)
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            {{ $produk->merek->nama_merek }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $produk->satuan->label() }}
                                    </span>
                                    @if ($produk->panjang_meter)
                                        <small class="d-block text-primary fw-semibold" style="font-size: 0.72rem;">
                                            {{ number_format($produk->panjang_meter, 1, ',', '.') }} m / lbr
                                        </small>
                                    @endif
                                    @if ($produk->isi_per_kemasan)
                                        <small class="d-block text-muted" style="font-size: 0.7rem;">
                                            {{ $produk->kemasan ?: 'Dus' }}: {{ number_format($produk->isi_per_kemasan, 0, ',', '.') }}
                                        </small>
                                    @elseif ($produk->satuan === \App\Domains\Catalog\Enums\SatuanProduk::Meter && $produk->panjang_per_roll)
                                        <small class="d-block text-muted" style="font-size: 0.7rem;">Roll: {{ $produk->panjang_per_roll }} m</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @php
                                        $stokTotal = (float) ($produk->stok_sum_jumlah ?? 0);
                                        $isKritis = $stokTotal <= (float) $produk->stok_minimum;
                                        $konv = $produk->getKonversiFisik($stokTotal);
                                    @endphp
                                    <span class="badge {{ $isKritis ? 'bg-danger text-white' : 'bg-light text-dark border' }}">
                                        {{ number_format($stokTotal, 2, ',', '.') }}
                                    </span>
                                    @if ($stokTotal > 0 && $konv['has_conversion'])
                                        <small class="d-block text-secondary mt-0" style="font-size: 0.68rem;" title="{{ $konv['badge_text'] }}">
                                            {{ $konv['badge_text'] }}
                                        </small>
                                    @endif
                                    @if ($isKritis)
                                        <small class="d-block text-danger" style="font-size: 0.7rem;">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Min: {{ number_format($produk->stok_minimum, 0) }}
                                        </small>
                                    @endif
                                </td>
                                @can('view_purchase_prices')
                                    <td class="text-end text-muted font-monospace">
                                        Rp {{ number_format($produk->harga_beli, 0, ',', '.') }}
                                    </td>
                                @endcan
                                <td class="text-end fw-bold font-monospace text-dark">
                                    Rp {{ number_format($produk->harga_jual, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if ($produk->trashed())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($produk->trashed())
                                        @can('restore', $produk)
                                            <button wire:click="restore({{ $produk->id }})" class="btn btn-sm btn-outline-success" title="Pulihkan Produk">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('master.produk.show', $produk->id) }}" class="btn btn-outline-secondary" title="Detail Produk">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" wire:click="openBarcodeModal({{ $produk->id }})" class="btn btn-outline-dark" title="Cetak Label Rak & Barcode">
                                                <i class="bi bi-upc-scan"></i>
                                            </button>
                                            @can('create', App\Domains\Catalog\Models\Produk::class)
                                                <button wire:click="duplicate({{ $produk->id }})" class="btn btn-outline-info" title="Duplikat / Salin Produk ini">
                                                    <i class="bi bi-copy"></i>
                                                </button>
                                            @endcan
                                            @can('update', $produk)
                                                <button wire:click="openEditModal({{ $produk->id }})" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete', $produk)
                                                <button wire:click="confirmDelete({{ $produk->id }})" class="btn btn-outline-danger" title="Nonaktifkan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('view_purchase_prices') ? '10' : '9' }}" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-box-seam display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data produk yang sesuai filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($produks->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $produks->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if ($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-scrollable my-3 my-md-4">
                <form wire:submit="save" class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-light border-bottom py-2 py-md-3 px-3 px-md-4 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary-subtle text-primary rounded p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-box-seam fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">
                                    {{ $produkId ? 'Edit Data Produk' : 'Tambah Produk Baru' }}
                                </h5>
                                <small class="text-muted">Lengkapi spesifikasi material, level harga pelanggan, dan foto motif produk.</small>
                            </div>
                        </div>
                        <button type="button" wire:click="closeFormModal" class="btn-close" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-3 p-md-4 bg-light-subtle">
                        <div class="row g-3 g-md-4">
                            <!-- Left Column: Identitas Produk & Stok -->
                            <div class="col-12 col-md-7">
                                <!-- Card 1: Identitas & Spesifikasi Produk -->
                                <div class="card border shadow-sm mb-3">
                                    <div class="card-header bg-white py-2 px-3 border-bottom">
                                        <h6 class="fw-bold mb-0 text-primary">
                                            <i class="bi bi-info-circle me-1"></i>1. Identitas & Spesifikasi Produk
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3">
                                            <!-- Kode Katalog with Auto button -->
                                            <div class="col-sm-6">
                                                <label for="kode_katalog" class="form-label fw-semibold small mb-1">
                                                    Kode Katalog / SKU <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input
                                                        type="text"
                                                        id="kode_katalog"
                                                        wire:model="kode_katalog"
                                                        class="form-control font-monospace fw-semibold @error('kode_katalog') is-invalid @enderror"
                                                        placeholder="Contoh: PVC-VF-001"
                                                        required
                                                    >
                                                    <button
                                                        type="button"
                                                        wire:click="generateKodeKatalog"
                                                        class="btn btn-outline-primary"
                                                        title="Generate kode katalog unik otomatis"
                                                    >
                                                        <i class="bi bi-magic me-1"></i>Auto
                                                    </button>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.75rem;">Manual atau klik <strong>Auto</strong></small>
                                                @error('kode_katalog')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Satuan Dasar -->
                                            <div class="col-sm-6">
                                                <label for="satuan" class="form-label fw-semibold small mb-1">
                                                    Satuan Dasar <span class="text-danger">*</span>
                                                </label>
                                                <select id="satuan" wire:model.live="satuan" class="form-select form-select-sm @error('satuan') is-invalid @enderror" required>
                                                    @foreach ($satuanOptions as $option)
                                                        <option value="{{ $option->value }}">
                                                            {{ $option->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    {{ ($satuan instanceof \App\Domains\Catalog\Enums\SatuanProduk ? $satuan : \App\Domains\Catalog\Enums\SatuanProduk::tryFrom((string) $satuan))?->description() }}
                                                </small>
                                                @error('satuan')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Nama Produk -->
                                            <div class="col-12">
                                                <label for="nama" class="form-label fw-semibold small mb-1">
                                                    Nama Produk <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    id="nama"
                                                    wire:model="nama"
                                                    class="form-control form-control-sm @error('nama') is-invalid @enderror"
                                                    placeholder="Contoh: PVC Vinyl Vifon Putih Glossy 4m"
                                                    required
                                                >
                                                @error('nama')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Kategori Induk -->
                                            <div class="col-sm-6">
                                                <label for="kategori_induk_id" class="form-label fw-semibold small mb-1">
                                                    Kategori Induk <span class="text-danger">*</span>
                                                </label>
                                                <select id="kategori_induk_id" wire:model.live="kategori_induk_id" class="form-select form-select-sm" required>
                                                    <option value="">-- Pilih Kategori Induk --</option>
                                                    @foreach ($rootKategoris as $root)
                                                        <option value="{{ $root->id }}">{{ $root->nama_kategori }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted" style="font-size: 0.72rem;">Rumpun kategori utama produk.</small>
                                            </div>

                                            <!-- Sub-Kategori -->
                                            <div class="col-sm-6">
                                                <label for="kategori_id" class="form-label fw-semibold small mb-1">
                                                    Sub-Kategori <span class="text-danger">*</span>
                                                </label>
                                                <select id="kategori_id" wire:model="kategori_id" class="form-select form-select-sm @error('kategori_id') is-invalid @enderror" required>
                                                    @if ($subKategoris->isNotEmpty())
                                                        <option value="">-- Pilih Sub-Kategori --</option>
                                                        @foreach ($subKategoris as $child)
                                                            <option value="{{ $child->id }}">&bull; {{ $child->nama_kategori }}</option>
                                                        @endforeach
                                                    @elseif (filled($kategori_induk_id))
                                                        <option value="{{ $kategori_induk_id }}">-- {{ $rootKategoris->firstWhere('id', (int) $kategori_induk_id)?->nama_kategori ?? 'Kategori Utama' }} (Tanpa Sub) --</option>
                                                    @else
                                                        <option value="">-- Pilih Kategori Induk Terlebih Dahulu --</option>
                                                    @endif
                                                </select>
                                                <small class="text-muted" style="font-size: 0.72rem;">Sub-bagian spesifik kategori induk.</small>
                                                @error('kategori_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Merek -->
                                            <div class="col-sm-6">
                                                <label for="merek_id" class="form-label fw-semibold small mb-1">Merek (Opsional)</label>
                                                <select id="merek_id" wire:model="merek_id" class="form-select form-select-sm @error('merek_id') is-invalid @enderror">
                                                    <option value="">-- Tanpa Merek / Lainnya --</option>
                                                    @foreach ($mereks as $merek)
                                                        <option value="{{ $merek->id }}">{{ $merek->nama_merek }}</option>
                                                    @endforeach
                                                </select>
                                                @error('merek_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Ukuran / Dimensi -->
                                            <div class="col-sm-6">
                                                <label for="ukuran" class="form-label fw-semibold small mb-1">Ukuran / Spesifikasi</label>
                                                <input
                                                    type="text"
                                                    id="ukuran"
                                                    wire:model="ukuran"
                                                    class="form-control form-control-sm @error('ukuran') is-invalid @enderror"
                                                    placeholder="Contoh: 4m x 20cm x 8mm, Tebal 9mm"
                                                >
                                                @error('ukuran')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: Stok Minimum, Kemasan Pabrik & Konversi Fisik -->
                                <div class="card border shadow-sm">
                                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-primary">
                                            <i class="bi bi-box-seam me-1"></i>2. Stok Minimum & Konversi Kemasan
                                        </h6>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Multi-Satuan</span>
                                    </div>
                                    <div class="card-body p-3">
                                        @php
                                            $currentSatuanVal = $satuan instanceof \App\Domains\Catalog\Enums\SatuanProduk ? $satuan->value : (string) $satuan;
                                        @endphp
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <label for="stok_minimum" class="form-label fw-semibold small mb-1">
                                                    Ambang Batas Stok Minimum <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input
                                                        type="number"
                                                        step="0.001"
                                                        min="0"
                                                        id="stok_minimum"
                                                        wire:model="stok_minimum"
                                                        class="form-control @error('stok_minimum') is-invalid @enderror"
                                                        required
                                                    >
                                                    <span class="input-group-text">{{ $currentSatuanVal }}</span>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.72rem;">Peringatan jika stok gudang kurang dari ini.</small>
                                                @error('stok_minimum')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="panjang_meter" class="form-label fw-semibold small mb-1">
                                                    Panjang Fisik per Lembar / Batang
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.1"
                                                        max="20"
                                                        id="panjang_meter"
                                                        wire:model="panjang_meter"
                                                        class="form-control @error('panjang_meter') is-invalid @enderror"
                                                        placeholder="Contoh: 4.00, 5.00, 6.00"
                                                    >
                                                    <span class="input-group-text">meter</span>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.72rem;">Panjang 1 lembar PVC / batang Hollow.</small>
                                                @error('panjang_meter')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="kemasan" class="form-label fw-semibold small mb-1">
                                                    Kemasan Pabrik (Truk Datang)
                                                </label>
                                                <input
                                                    type="text"
                                                    id="kemasan"
                                                    wire:model="kemasan"
                                                    list="kemasan-options"
                                                    class="form-control form-control-sm @error('kemasan') is-invalid @enderror"
                                                    placeholder="Contoh: Dus, Ikat, Sak, Pallet"
                                                >
                                                <datalist id="kemasan-options">
                                                    <option value="Dus">Dus (Karton)</option>
                                                    <option value="Ikat">Ikat</option>
                                                    <option value="Pallet">Pallet</option>
                                                    <option value="Sak">Sak (Zak)</option>
                                                    <option value="Roll">Roll</option>
                                                    <option value="Kotak">Kotak</option>
                                                </datalist>
                                                <small class="text-muted" style="font-size: 0.72rem;">Satuan wadah pabrik.</small>
                                                @error('kemasan')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="isi_per_kemasan" class="form-label fw-semibold small mb-1">
                                                    Isi per Kemasan Pabrik
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        id="isi_per_kemasan"
                                                        wire:model="isi_per_kemasan"
                                                        class="form-control @error('isi_per_kemasan') is-invalid @enderror"
                                                        placeholder="Contoh: 15 (PVC), 20 (Hollow)"
                                                    >
                                                    <span class="input-group-text">isi</span>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.72rem;">1 Dus = 15 lbr PVC; 1 Ikat = 20 btg.</small>
                                                @error('isi_per_kemasan')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            @if ($currentSatuanVal === 'meter')
                                                <div class="col-12 border-top pt-2 mt-2">
                                                    <label for="panjang_per_roll" class="form-label fw-semibold small mb-1 text-muted">
                                                        <i class="bi bi-disc me-1"></i>Panjang per Roll (Khusus Barang Gulungan/Roll)
                                                    </label>
                                                    <div class="input-group input-group-sm">
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0.01"
                                                            id="panjang_per_roll"
                                                            wire:model="panjang_per_roll"
                                                            class="form-control @error('panjang_per_roll') is-invalid @enderror"
                                                            placeholder="Opsional, khusus Bubble Foil / Lakban: 25.00"
                                                        >
                                                        <span class="input-group-text">m / roll</span>
                                                    </div>
                                                    <small class="text-muted" style="font-size: 0.7rem;">Hanya diisi jika barang berbentuk roll/gulungan.</small>
                                                    @error('panjang_per_roll')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Harga Bertingkat & Foto Motif -->
                            <div class="col-12 col-md-5">
                                <!-- Card 3: Konfigurasi Harga Bertingkat -->
                                <div class="card border shadow-sm mb-3">
                                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-success">
                                            <i class="bi bi-tags me-1"></i>3. Konfigurasi Harga
                                        </h6>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">Multi-Tier</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <!-- Harga Beli / HPP -->
                                            @can('view_purchase_prices')
                                                <div class="col-12">
                                                    <label for="harga_beli" class="form-label fw-semibold small mb-1 d-flex justify-content-between">
                                                        <span>Harga Beli (HPP) <span class="text-danger">*</span></span>
                                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">Modal Pokok</span>
                                                    </label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">Rp</span>
                                                        <input
                                                            type="number"
                                                            step="100"
                                                            min="0"
                                                            id="harga_beli"
                                                            wire:model.live.debounce.300ms="harga_beli"
                                                            class="form-control @error('harga_beli') is-invalid @enderror"
                                                            required
                                                        >
                                                    </div>
                                                    @error('harga_beli')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            @else
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small mb-1 d-flex justify-content-between">
                                                        <span>Harga Beli (HPP)</span>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">
                                                            <i class="bi bi-lock-fill me-1"></i>Akses Terbatas
                                                        </span>
                                                    </label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                                        <input type="text" class="form-control bg-light text-muted font-monospace" value="•••••••• (Dirahasiakan)" readonly disabled>
                                                    </div>
                                                    <small class="text-muted" style="font-size: 0.72rem;">Hanya Owner atau Manager yang memiliki wewenang melihat HPP.</small>
                                                </div>
                                            @endcan

                                            <!-- Harga Jual Standar -->
                                            <div class="col-12 mt-2">
                                                <label for="harga_jual" class="form-label fw-semibold small mb-1 d-flex justify-content-between">
                                                    <span>Harga Jual Umum <span class="text-danger">*</span></span>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Retail / Standar</span>
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-success-subtle text-success fw-bold">Rp</span>
                                                    <input
                                                        type="number"
                                                        step="100"
                                                        min="0"
                                                        id="harga_jual"
                                                        wire:model.live.debounce.300ms="harga_jual"
                                                        class="form-control fw-bold border-success @error('harga_jual') is-invalid @enderror"
                                                        required
                                                    >
                                                </div>
                                                @error('harga_jual')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Live Margin Calculation for Authorized Users -->
                                            @can('view_purchase_prices')
                                                @if((float)$harga_jual > 0 && (float)$harga_beli > 0)
                                                    @php
                                                        $marginRp = (float)$harga_jual - (float)$harga_beli;
                                                        $marginPersen = ((float)$harga_jual > 0) ? ($marginRp / (float)$harga_jual) * 100 : 0;
                                                        $isLoss = $marginRp < 0;
                                                    @endphp
                                                    <div class="col-12 mt-1">
                                                        <div class="p-2 rounded border {{ $isLoss ? 'bg-danger-subtle border-danger text-danger' : 'bg-success-subtle border-success text-success' }} d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                                                            <div>
                                                                <i class="bi {{ $isLoss ? 'bi-exclamation-triangle-fill text-danger' : 'bi-graph-up-arrow text-success' }} me-1"></i>
                                                                <strong>Margin:</strong> Rp {{ number_format($marginRp, 0, ',', '.') }}
                                                                <span class="fw-bold">({{ number_format($marginPersen, 1) }}%)</span>
                                                            </div>
                                                            @if($isLoss)
                                                                <span class="badge bg-danger text-white">Jual Rugi!</span>
                                                            @else
                                                                <span class="badge bg-success text-white">Profit</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            @endcan

                                            <!-- Info Diskon Terpusat Banner -->
                                            <div class="col-12 mt-2">
                                                <div class="p-2 rounded bg-light border border-secondary-subtle small">
                                                    <div class="d-flex align-items-center gap-1 text-primary fw-semibold mb-1" style="font-size: 0.75rem;">
                                                        <i class="bi bi-info-circle-fill"></i>
                                                        <span>Harga Khusus Diskon Terpusat</span>
                                                    </div>
                                                    <p class="text-muted mb-0" style="font-size: 0.7rem; line-height: 1.3;">
                                                        Harga khusus Tukang/Pemborong dan Toko/Grosir dikelola via menu <strong>Pengaturan Diskon</strong>. Cukup kosongkan kolom di bawah ini agar diskon otomatis dihitung kasir saat memilih pelanggan.
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Harga Aplikator -->
                                            <div class="col-12 mt-2">
                                                <label for="harga_aplikator" class="form-label fw-semibold small mb-1 d-flex justify-content-between">
                                                    <span>Harga Aplikator / Pemborong</span>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">Mitra Tukang</span>
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Rp</span>
                                                    <input
                                                        type="number"
                                                        step="100"
                                                        min="0"
                                                        id="harga_aplikator"
                                                        wire:model="harga_aplikator"
                                                        class="form-control @error('harga_aplikator') is-invalid @enderror"
                                                        placeholder="Opsional (otomatis diskon terpusat)"
                                                    >
                                                </div>
                                                @if(empty($harga_aplikator) && (float)$harga_jual > 0)
                                                    <small class="text-primary fw-semibold d-block mt-1" style="font-size: 0.72rem;">
                                                        <i class="bi bi-magic me-1"></i>Estimasi Diskon: Rp {{ number_format(round((float)$harga_jual * 0.95), 0, ',', '.') }} (Diskon 5%)
                                                    </small>
                                                @else
                                                    <small class="text-muted" style="font-size: 0.72rem;">Kosongkan jika mengikuti aturan diskon.</small>
                                                @endif
                                                @error('harga_aplikator')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Harga Grosir -->
                                            <div class="col-12 mt-2">
                                                <label for="harga_grosir" class="form-label fw-semibold small mb-1 d-flex justify-content-between">
                                                    <span>Harga Grosir / Toko</span>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;">Partai Besar</span>
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Rp</span>
                                                    <input
                                                        type="number"
                                                        step="100"
                                                        min="0"
                                                        id="harga_grosir"
                                                        wire:model="harga_grosir"
                                                        class="form-control @error('harga_grosir') is-invalid @enderror"
                                                        placeholder="Opsional (otomatis diskon terpusat)"
                                                    >
                                                </div>
                                                @if(empty($harga_grosir) && (float)$harga_jual > 0)
                                                    <small class="text-warning-emphasis fw-semibold d-block mt-1" style="font-size: 0.72rem;">
                                                        <i class="bi bi-magic me-1"></i>Estimasi Diskon: Rp {{ number_format(round((float)$harga_jual * 0.92), 0, ',', '.') }} (Diskon 8%)
                                                    </small>
                                                @else
                                                    <small class="text-muted" style="font-size: 0.72rem;">Kosongkan jika mengikuti aturan diskon.</small>
                                                @endif
                                                @error('harga_grosir')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 4: Foto Motif Produk -->
                                <div class="card border shadow-sm">
                                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            <i class="bi bi-camera me-1"></i>4. Foto Motif Produk
                                        </h6>
                                        <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">Opsional</span>
                                    </div>
                                    <div class="card-body p-3">
                                        @if ($foto)
                                            <div class="d-flex align-items-center gap-3 mb-2 p-2 bg-light rounded border">
                                                <img src="{{ $foto->temporaryUrl() }}" alt="Preview" class="rounded border object-fit-cover flex-shrink-0" style="width: 60px; height: 60px;">
                                                <div class="flex-grow-1 min-w-0">
                                                    <span class="badge bg-success mb-1">Foto Baru Terpilih</span>
                                                    <small class="d-block text-truncate text-muted" style="font-size: 0.75rem;">{{ $foto->getClientOriginalName() }}</small>
                                                </div>
                                                <button type="button" wire:click="$set('foto', null)" class="btn btn-sm btn-outline-danger" title="Batal pilih foto">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        @elseif ($existingFoto && ! $removeFoto)
                                            <div class="d-flex align-items-center gap-3 mb-2 p-2 bg-light rounded border">
                                                <img src="{{ asset('storage/' . $existingFoto) }}" alt="Foto Tersimpan" class="rounded border object-fit-cover flex-shrink-0" style="width: 60px; height: 60px;">
                                                <div class="flex-grow-1 min-w-0">
                                                    <span class="badge bg-primary mb-1">Foto Tersimpan</span>
                                                    <small class="d-block text-muted" style="font-size: 0.75rem;">Akan dipertahankan</small>
                                                </div>
                                                <button type="button" wire:click="markRemoveFoto" class="btn btn-sm btn-outline-danger" title="Hapus foto produk">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        @endif

                                        <div>
                                            <input
                                                type="file"
                                                id="foto"
                                                wire:model="foto"
                                                accept="image/png,image/jpeg,image/webp"
                                                class="form-control form-control-sm @error('foto') is-invalid @enderror"
                                            >
                                            <div wire:loading wire:target="foto" class="text-primary small mt-1">
                                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                                Mengunggah foto sementara...
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                                Format: JPG, PNG, WebP. Otomatis dikompresi WebP hemat hosting.
                                            </small>
                                            @error('foto')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top py-2 py-md-3 px-3 px-md-4 flex-shrink-0 d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <span class="text-danger">*</span> Kolom wajib diisi.
                        </small>
                        <div class="d-flex gap-2">
                            <button type="button" wire:click="closeFormModal" class="btn btn-outline-secondary px-4">
                                Batal
                            </button>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-check-lg me-1"></i>Simpan Produk
                                </span>
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($isDeleteModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Nonaktifkan Produk
                        </h5>
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin menonaktifkan produk <strong>{{ $produkToDeleteName }}</strong> ({{ $produkToDeleteCode }})?
                        </p>
                        <small class="text-muted d-block mt-2">
                            Produk yang dinonaktifkan tidak akan muncul pada pilihan transaksi baru.
                        </small>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn btn-secondary">Batal</button>
                        <button type="button" wire:click="delete" class="btn btn-danger" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="delete">
                                <i class="bi bi-trash me-1"></i>Ya, Nonaktifkan
                            </span>
                            <span wire:loading wire:target="delete">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Import Excel / CSV & Batch Photo Sync Modal -->
    @if ($isImportModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered {{ ($importTab === 'excel' && $previewData) ? 'modal-xl' : 'modal-lg' }}">
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-light border-bottom py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-{{ $importTab === 'excel' ? 'success' : 'primary' }}-subtle text-{{ $importTab === 'excel' ? 'success' : 'primary' }} rounded p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-{{ $importTab === 'excel' ? 'file-earmark-excel' : 'images' }} fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">
                                    @if ($importTab === 'excel')
                                        {{ $previewData ? 'Pratinjau Data Sebelum Import' : 'Import Data Katalog Produk (Excel / CSV)' }}
                                    @else
                                        Sinkronisasi Foto Massal (ZIP)
                                    @endif
                                </h5>
                                <small class="text-muted">
                                    @if ($importTab === 'excel')
                                        {{ $previewData ? 'Periksa data di bawah ini sebelum disimpan secara permanen ke database.' : 'Tambah atau perbarui ratusan produk sekaligus dengan file spreadsheet Excel.' }}
                                    @else
                                        Tautkan ratusan foto produk secara otomatis berdasarkan kesesuaian Kode Barang / SKU file.
                                    @endif
                                </small>
                            </div>
                        </div>
                        <button type="button" wire:click="closeImportModal" class="btn-close" aria-label="Close"></button>
                    </div>

                    @if (! $previewData)
                        <ul class="nav nav-tabs px-4 border-bottom bg-light">
                            <li class="nav-item">
                                <button type="button" wire:click="setImportTab('excel')" class="nav-link py-2 px-3 fw-semibold small {{ $importTab === 'excel' ? 'active text-success' : 'text-muted' }}">
                                    <i class="bi bi-file-earmark-excel me-1"></i>1. Import Data Katalog (Excel / CSV)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" wire:click="setImportTab('photos')" class="nav-link py-2 px-3 fw-semibold small {{ $importTab === 'photos' ? 'active text-primary' : 'text-muted' }}">
                                    <i class="bi bi-images me-1"></i>2. Sinkronisasi Foto Massal (ZIP)
                                </button>
                            </li>
                        </ul>
                    @endif

                    @if ($importTab === 'excel')
                        <form wire:submit="importExcel">
                            <div class="modal-body p-4">
                                @if (! $previewData)
                                    {{-- Step 1: Upload File --}}
                                    <div class="alert alert-success bg-success-subtle border-0 d-flex gap-3 align-items-start mb-3">
                                        <i class="bi bi-file-earmark-check fs-4 text-success mt-1"></i>
                                        <div class="small">
                                            <strong class="text-success-emphasis">Format Tabel Ramah Awam & Mudah Digunakan:</strong>
                                            <p class="mb-1 text-dark">
                                                Kolom tabel menggunakan Bahasa Indonesia: <strong>KODE BARANG</strong>, <strong>NAMA PRODUK</strong>, <strong>KATEGORI</strong>, <strong>MEREK</strong>, <strong>SATUAN</strong> (meter, lembar, batang, zak, dus, pcs), <strong>HARGA BELI (HPP)</strong>, <strong>HARGA JUAL UMUM</strong>, dll. Sistem otomatis membersihkan format angka "Rp" dan titik ribuan.
                                            </p>
                                            <button type="button" wire:click="downloadTemplate" class="btn btn-sm btn-success text-white fw-semibold mt-1">
                                                <i class="bi bi-download me-1"></i>Unduh Template Excel Resmi (.xlsx)
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="excelFile" class="form-label fw-semibold small">Pilih File Excel (.xlsx / .csv) <span class="text-danger">*</span></label>
                                        <input
                                            type="file"
                                            id="excelFile"
                                            wire:model="excelFile"
                                            accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                                            class="form-control @error('excelFile') is-invalid @enderror"
                                            required
                                        >
                                        @error('excelFile')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div wire:loading wire:target="excelFile" class="small text-primary mt-2">
                                            <span class="spinner-border spinner-border-sm me-1"></span>Membaca file spreadsheet & menyiapkan pratinjau data...
                                        </div>
                                    </div>

                                    <div class="form-check form-switch mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="updateExistingOnImport"
                                            wire:model="updateExistingOnImport"
                                        >
                                        <label class="form-check-label small" for="updateExistingOnImport">
                                            <strong>Perbarui produk yang sudah ada</strong> jika Kode Barang cocok (Sangat berguna untuk update harga massal via Excel).
                                        </label>
                                    </div>
                                @else
                                    {{-- Step 2: Interactive Preview --}}
                                    <!-- Banner File Info -->
                                    <div class="d-flex flex-wrap align-items-center justify-content-between p-3 bg-light rounded-3 border mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark-spreadsheet-fill text-success fs-4"></i>
                                            <div>
                                                <div class="fw-bold text-dark small">
                                                    {{ $excelFile ? $excelFile->getClientOriginalName() : ($csvFile ? $csvFile->getClientOriginalName() : 'File Terpilih') }}
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    Total {{ $previewData['total_rows'] }} baris produk. Saldo awal stok akan otomatis dicatat ke <strong>Gudang Utama</strong>.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 mt-2 mt-sm-0">
                                            <div class="form-check form-switch mb-0">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    id="updateExistingOnImportPreview"
                                                    wire:model.live="updateExistingOnImport"
                                                >
                                                <label class="form-check-label small fw-semibold" for="updateExistingOnImportPreview">
                                                    Perbarui jika kode cocok
                                                </label>
                                            </div>
                                            <button type="button" wire:click="resetImportFile" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-arrow-repeat me-1"></i>Ganti File
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Statistik Ringkasan Preview -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded-2 bg-success-subtle border-success-subtle text-center">
                                                <div class="fs-4 fw-bold text-success">{{ $previewData['new_count'] }}</div>
                                                <div class="small fw-semibold text-success-emphasis">
                                                    <i class="bi bi-plus-circle me-1"></i>Produk Baru
                                                </div>
                                                <div class="text-muted" style="font-size: 0.7rem;">Akan ditambahkan</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded-2 bg-primary-subtle border-primary-subtle text-center">
                                                <div class="fs-4 fw-bold text-primary">{{ $previewData['update_count'] }}</div>
                                                <div class="small fw-semibold text-primary-emphasis">
                                                    <i class="bi bi-pencil-square me-1"></i>Diperbarui
                                                </div>
                                                <div class="text-muted" style="font-size: 0.7rem;">Kode sudah ada di DB</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded-2 bg-secondary-subtle border-secondary-subtle text-center">
                                                <div class="fs-4 fw-bold text-secondary">{{ $previewData['skip_count'] }}</div>
                                                <div class="small fw-semibold text-secondary-emphasis">
                                                    <i class="bi bi-dash-circle me-1"></i>Dilewati
                                                </div>
                                                <div class="text-muted" style="font-size: 0.7rem;">Update nonaktif</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded-2 {{ $previewData['error_count'] > 0 ? 'bg-danger-subtle border-danger-subtle' : 'bg-light' }} text-center">
                                                <div class="fs-4 fw-bold {{ $previewData['error_count'] > 0 ? 'text-danger' : 'text-muted' }}">
                                                    {{ $previewData['error_count'] }}
                                                </div>
                                                <div class="small fw-semibold {{ $previewData['error_count'] > 0 ? 'text-danger-emphasis' : 'text-muted' }}">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Bermasalah
                                                </div>
                                                <div class="text-muted" style="font-size: 0.7rem;">Data tidak lengkap</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Peringatan jika ada baris error -->
                                    @if ($previewData['error_count'] > 0)
                                        <div class="alert alert-danger border-0 p-3 small mb-3" style="max-height: 120px; overflow-y: auto;">
                                            <strong class="d-block mb-1 text-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Ditemukan {{ $previewData['error_count'] }} baris yang tidak dapat diproses:
                                            </strong>
                                            <ul class="mb-0 ps-3">
                                                @foreach ($previewData['errors'] as $err)
                                                    <li>{{ $err }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <!-- Tabel Pratinjau Baris Produk -->
                                    <div class="card border shadow-none mb-0">
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold text-dark">
                                                <i class="bi bi-table me-1"></i>Cuplikan Data Produk
                                                @if ($previewData['has_more'])
                                                    <span class="text-muted fw-normal">(Menampilkan 50 baris pertama)</span>
                                                @endif
                                            </span>
                                            <span class="badge bg-light text-dark border">
                                                {{ count($previewData['preview_rows']) }} Baris Ditampilkan
                                            </span>
                                        </div>
                                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                                <thead class="table-light sticky-top" style="z-index: 2;">
                                                    <tr>
                                                        <th class="text-center" style="width: 35px;">#</th>
                                                        <th style="min-width: 90px;">Status</th>
                                                        <th style="min-width: 100px;">Kode Barang</th>
                                                        <th style="min-width: 180px;">Nama Produk</th>
                                                        <th style="min-width: 110px;">Kategori</th>
                                                        <th style="min-width: 90px;">Merek</th>
                                                        <th class="text-center" style="min-width: 70px;">Satuan</th>
                                                        <th class="text-center" style="min-width: 80px;">Stok Awal</th>
                                                        @can('view_purchase_prices')
                                                            <th class="text-end" style="min-width: 95px;">HPP (Modal)</th>
                                                        @endcan
                                                        <th class="text-end" style="min-width: 95px;">Jual Umum</th>
                                                        <th class="text-end" style="min-width: 95px;">Tukang</th>
                                                        <th class="text-end" style="min-width: 95px;">Grosir</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($previewData['preview_rows'] as $idx => $row)
                                                        <tr class="{{ $row['status'] === 'ERROR' ? 'table-danger' : ($row['status'] === 'UPDATE' ? 'table-primary-subtle' : '') }}">
                                                            <td class="text-center text-muted">{{ $row['row_number'] }}</td>
                                                            <td>
                                                                <span class="badge bg-{{ $row['status_badge'] }} px-2 py-1">
                                                                    {{ $row['status_label'] }}
                                                                </span>
                                                            </td>
                                                            <td class="fw-semibold text-dark">{{ $row['kode_katalog'] ?: '-' }}</td>
                                                            <td>
                                                                <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="{{ $row['nama'] }}">
                                                                    {{ $row['nama'] ?: '(Nama Kosong)' }}
                                                                </div>
                                                                @if ($row['ukuran'])
                                                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $row['ukuran'] }}</div>
                                                                @endif
                                                            </td>
                                                            <td>{{ $row['kategori'] ?: '-' }}</td>
                                                            <td>{{ $row['merek'] ?: '-' }}</td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light text-dark border">{{ $row['satuan'] }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                @if ($row['stok_awal'] > 0)
                                                                    <span class="badge bg-success-subtle text-success fw-bold border border-success-subtle">
                                                                        +{{ number_format($row['stok_awal'], 0, ',', '.') }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-muted">0</span>
                                                                @endif
                                                            </td>
                                                            @can('view_purchase_prices')
                                                                <td class="text-end">Rp {{ number_format($row['harga_beli'], 0, ',', '.') }}</td>
                                                            @endcan
                                                            <td class="text-end fw-bold text-primary">Rp {{ number_format($row['harga_jual'], 0, ',', '.') }}</td>
                                                            <td class="text-end text-muted">
                                                                {{ $row['harga_aplikator'] !== null ? 'Rp ' . number_format($row['harga_aplikator'], 0, ',', '.') : '-' }}
                                                            </td>
                                                            <td class="text-end text-muted">
                                                                {{ $row['harga_grosir'] !== null ? 'Rp ' . number_format($row['harga_grosir'], 0, ',', '.') : '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between align-items-center">
                                <button type="button" wire:click="closeImportModal" class="btn btn-outline-secondary">
                                    Batal
                                </button>

                                <div class="d-flex gap-2">
                                    @if ($previewData)
                                        <button type="button" wire:click="resetImportFile" class="btn btn-light border text-muted">
                                            <i class="bi bi-arrow-left me-1"></i>Pilih File Lain
                                        </button>
                                        <button
                                            type="submit"
                                            class="btn btn-success px-4 fw-bold shadow-sm"
                                            wire:loading.attr="disabled"
                                            @if ($previewData['total_rows'] === 0 || ($previewData['new_count'] === 0 && $previewData['update_count'] === 0)) disabled @endif
                                        >
                                            <span wire:loading.remove wire:target="importExcel">
                                                <i class="bi bi-check-circle-fill me-1"></i>Konfirmasi & Simpan ke Database
                                            </span>
                                            <span wire:loading wire:target="importExcel">
                                                <span class="spinner-border spinner-border-sm me-1"></span>Memproses Import...
                                            </span>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-secondary" disabled>
                                            Pilih File Dulu
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </form>
                    @else
                        {{-- Tab 2: Batch Photo Sync via ZIP --}}
                        <div class="modal-body p-4">
                            <!-- Info Panduan -->
                            <div class="alert alert-primary bg-primary-subtle border-0 d-flex gap-3 align-items-start mb-3">
                                <i class="bi bi-info-circle-fill fs-4 text-primary mt-1"></i>
                                <div class="small">
                                    <strong class="text-primary-emphasis">Cara Praktis Menyiapkan File Foto:</strong>
                                    <ol class="mb-2 ps-3 text-dark">
                                        <li>Beri nama setiap file foto sesuai <strong>Kode Barang / SKU</strong> di katalog (contoh: <code>PVC-001.jpg</code>, <code>GYP-002.png</code>, <code>LST-003.webp</code>).</li>
                                        <li>Format file didukung: <strong>JPG, JPEG, PNG, WEBP</strong>. Sistem bersifat <em>case-insensitive</em> (huruf besar/kecil tidak berpengaruh).</li>
                                        <li>Pilih semua file foto tersebut, klik kanan lalu <strong>Compress to ZIP</strong>.</li>
                                        <li>Sistem otomatis me-resize dan mengonversi ke format <strong>WebP modern</strong> yang sangat ringan dan tajam.</li>
                                    </ol>
                                    <div class="pt-2 border-top border-primary-subtle d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <span class="text-dark">
                                            <i class="bi bi-lightbulb-fill text-warning me-1"></i>
                                            <strong>Mau copas kode SKU saat rename foto?</strong> Unduh daftar semua SKU produk & status fotonya:
                                        </span>
                                        <button type="button" wire:click="downloadSkuPhotoCheatSheet" class="btn btn-sm btn-primary text-white fw-bold shadow-sm">
                                            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i>Unduh Daftar SKU Produk (.xlsx)
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if (! $photoSyncSummary)
                                <div class="mb-3">
                                    <label for="zipFile" class="form-label fw-semibold small">Pilih File Arsip Foto (.zip) <span class="text-danger">*</span></label>
                                    <input
                                        type="file"
                                        id="zipFile"
                                        wire:model="zipFile"
                                        accept=".zip,application/zip,application/x-zip-compressed"
                                        class="form-control @error('zipFile') is-invalid @enderror"
                                    >
                                    @error('zipFile')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div wire:loading wire:target="zipFile" class="small text-primary mt-2">
                                        <span class="spinner-border spinner-border-sm me-1"></span>Mengunggah arsip foto ke server...
                                    </div>
                                    <div class="form-text small text-muted">
                                        Maksimal ukuran upload file: 100 MB. (Untuk koleksi foto bergiga-giga, gunakan terminal command: <code>php artisan catalog:sync-photos "path/folder"</code>).
                                    </div>
                                </div>

                                @if ($zipFile)
                                    <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark-zip-fill text-warning fs-3"></i>
                                            <div>
                                                <div class="fw-bold small text-dark">{{ $zipFile->getClientOriginalName() }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Siap diekstrak dan disinkronkan ke katalog produk</div>
                                            </div>
                                        </div>
                                        <button type="button" wire:click="resetPhotoSync" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-circle me-1"></i>Batal
                                        </button>
                                    </div>
                                @endif
                            @else
                                <!-- Laporan Hasil Sinkronisasi -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6 col-md-4">
                                        <div class="p-3 border rounded-3 bg-success-subtle border-success-subtle text-center">
                                            <div class="fs-3 fw-bold text-success">{{ $photoSyncSummary['matched'] }}</div>
                                            <div class="small fw-semibold text-success-emphasis">
                                                <i class="bi bi-check-circle-fill me-1"></i>Foto Ditautkan
                                            </div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Produk berhasil diupdate</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="p-3 border rounded-3 {{ $photoSyncSummary['unmatched'] > 0 ? 'bg-warning-subtle border-warning-subtle' : 'bg-light' }} text-center">
                                            <div class="fs-3 fw-bold {{ $photoSyncSummary['unmatched'] > 0 ? 'text-warning-emphasis' : 'text-muted' }}">
                                                {{ $photoSyncSummary['unmatched'] }}
                                            </div>
                                            <div class="small fw-semibold {{ $photoSyncSummary['unmatched'] > 0 ? 'text-warning-emphasis' : 'text-muted' }}">
                                                <i class="bi bi-question-circle-fill me-1"></i>SKU Tidak Ada
                                            </div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Tidak ada di katalog</div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <div class="p-3 border rounded-3 bg-secondary-subtle border-secondary-subtle text-center">
                                            <div class="fs-3 fw-bold text-secondary">{{ $photoSyncSummary['ignored'] }}</div>
                                            <div class="small fw-semibold text-secondary-emphasis">
                                                <i class="bi bi-slash-circle me-1"></i>File Non-Gambar
                                            </div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Diabaikan otomatis</div>
                                        </div>
                                    </div>
                                </div>

                                @if (! empty($photoSyncSummary['unmatched_files']))
                                    <div class="card border border-warning-subtle mb-3">
                                        <div class="card-header bg-warning-subtle py-2 px-3 border-bottom border-warning-subtle d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold text-warning-emphasis">
                                                <i class="bi bi-exclamation-triangle me-1"></i>File dengan SKU tidak cocok di database:
                                            </span>
                                            <span class="badge bg-warning text-dark">{{ count($photoSyncSummary['unmatched_files']) }} File</span>
                                        </div>
                                        <div class="card-body p-2" style="max-height: 160px; overflow-y: auto;">
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($photoSyncSummary['unmatched_files'] as $unmatchedFile)
                                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.75rem;">
                                                        {{ $unmatchedFile }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if (! empty($photoSyncSummary['errors']))
                                    <div class="alert alert-danger border-0 p-3 small mb-3">
                                        <strong class="d-block mb-1 text-danger">
                                            <i class="bi bi-exclamation-octagon-fill me-1"></i>Catatan Kendala:
                                        </strong>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($photoSyncSummary['errors'] as $err)
                                                <li>{{ $err }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="text-center pt-2">
                                    <button type="button" wire:click="resetPhotoSync" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-arrow-repeat me-1"></i>Unggah Arsip ZIP Lainnya
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between align-items-center">
                            <button type="button" wire:click="closeImportModal" class="btn btn-outline-secondary">
                                {{ $photoSyncSummary ? 'Tutup' : 'Batal' }}
                            </button>

                            <div class="d-flex gap-2">
                                @if (! $photoSyncSummary)
                                    <button
                                        type="button"
                                        wire:click="syncPhotos"
                                        class="btn btn-primary px-4 fw-bold shadow-sm"
                                        wire:loading.attr="disabled"
                                        @if (! $zipFile) disabled @endif
                                    >
                                        <span wire:loading.remove wire:target="syncPhotos">
                                            <i class="bi bi-arrow-repeat me-1"></i>Mulai Sinkronisasi Foto
                                        </span>
                                        <span wire:loading wire:target="syncPhotos">
                                            <span class="spinner-border spinner-border-sm me-1"></span>Memproses & Mengonversi WebP...
                                        </span>
                                    </button>
                                @else
                                    <button type="button" wire:click="closeImportModal" class="btn btn-success px-4 fw-bold shadow-sm">
                                        <i class="bi bi-check-lg me-1"></i>Selesai
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Barcode & Shelf Tag Print Modal -->
    @if ($isBarcodeModalOpen && $barcodeProduk)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-light border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-dark text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-upc-scan fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">Cetak Label Rak & Barcode</h5>
                                <small class="text-muted">Pratinjau label harga rak pajangan untuk ditempel pada rak etalase toko.</small>
                            </div>
                        </div>
                        <button type="button" wire:click="closeBarcodeModal" class="btn-close" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 bg-light-subtle">
                        <div class="row g-3 mb-3 no-print">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small mb-1">Jumlah Label yang Dicetak</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-files"></i></span>
                                    <select wire:model.live="barcodeQty" class="form-select">
                                        <option value="1">1 Lembar Label</option>
                                        <option value="2">2 Lembar Label</option>
                                        <option value="4">4 Lembar Label</option>
                                        <option value="8">8 Lembar Label</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Ukuran label pas untuk rak gondola standar toko (7 x 4 cm) atau stiker barcode.
                                </small>
                            </div>
                        </div>

                        <!-- Printable Area -->
                        <div id="printable-barcode-area" class="p-3 bg-white border rounded shadow-xs">
                            <div class="d-flex flex-wrap gap-3 justify-content-center">
                                @for ($i = 0; $i < $barcodeQty; $i++)
                                    <div class="border border-dark rounded-2 bg-white text-dark d-flex flex-column justify-content-between position-relative shadow-xs"
                                         style="width: 295px; height: 185px; padding: 10px 14px; box-sizing: border-box; overflow: hidden; font-family: system-ui, -apple-system, sans-serif;">
                                        <!-- Header Store & Kategori -->
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-1" style="height: 22px;">
                                            <span class="fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">NATURAL GYPSUM & PVC</span>
                                            <span class="badge bg-light text-dark border px-2 py-0 text-truncate" style="font-size: 0.65rem; max-width: 110px;">
                                                {{ $barcodeProduk->kategori?->nama_kategori ?? 'UMUM' }}
                                            </span>
                                        </div>

                                        <!-- Product Name & Spec -->
                                        <div style="min-height: 38px;">
                                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem; line-height: 1.25;" title="{{ $barcodeProduk->nama }}">
                                                {{ $barcodeProduk->nama }}
                                            </div>
                                            <div class="text-muted text-truncate" style="font-size: 0.7rem; line-height: 1.2;">
                                                {{ $barcodeProduk->ukuran ?: 'Standar' }} &bull; {{ $barcodeProduk->merek?->nama_merek ?? 'Generic' }}
                                            </div>
                                        </div>

                                        <!-- Real Scannable Barcode SVG & Code -->
                                        <div class="text-center my-0 py-1" style="min-height: 48px;">
                                            {!! app(\App\Domains\Catalog\Services\BarcodeService::class)->generateCode39Svg($barcodeProduk->kode_katalog, 26) !!}
                                            <div class="font-monospace fw-bold text-dark text-center" style="font-size: 0.72rem; letter-spacing: 2px; margin-top: 2px;">
                                                *{{ $barcodeProduk->kode_katalog }}*
                                            </div>
                                        </div>

                                        <!-- Price Footer -->
                                        <div class="d-flex justify-content-between align-items-center border-top pt-1 mt-1 bg-white" style="height: 32px;">
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                                HARGA RESMI
                                            </span>
                                            <div class="text-end">
                                                <span class="fw-bold text-danger font-monospace" style="font-size: 1.18rem; font-weight: 800; line-height: 1;">
                                                    Rp {{ number_format($barcodeProduk->harga_jual, 0, ',', '.') }}
                                                </span>
                                                <small class="text-muted fw-normal" style="font-size: 0.68rem;">/{{ $barcodeProduk->satuan->value }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between align-items-center no-print">
                        <button type="button" wire:click="closeBarcodeModal" class="btn btn-outline-secondary">
                            Tutup
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-dark px-4 fw-semibold shadow-sm">
                            <i class="bi bi-printer me-1"></i>Cetak Sekarang (Print)
                        </button>
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
                            <a href="{{ route('master.produk.show', $imageModalProduk->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Detail Produk
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
