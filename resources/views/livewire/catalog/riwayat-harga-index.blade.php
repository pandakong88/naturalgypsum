<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Perubahan Harga Produk
            </h4>
            <p class="text-muted small mb-0">
                Pantau rekam jejak kenaikan dan penurunan harga produk dari faktur pembelian supplier, edit katalog, maupun import massal.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 w-100 w-sm-auto">
            <button wire:click="exportCsv" class="btn btn-outline-success btn-sm shadow-sm flex-fill flex-sm-grow-0" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="exportCsv">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Ekspor CSV
                </span>
                <span wire:loading wire:target="exportCsv">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengekspor...
                </span>
            </button>
            <a href="{{ route('master.produk.index') }}" class="btn btn-outline-secondary btn-sm flex-fill flex-sm-grow-0">
                <i class="bi bi-boxes me-1"></i>Katalog Produk
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards (2x2 Grid on Mobile) -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Total Perubahan</span>
                            <h3 class="fw-bold mb-0 text-primary fs-4 fs-sm-3">{{ number_format($kpis['totalPerubahan'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary p-2.5 d-none d-sm-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Harga Naik</span>
                            <h3 class="fw-bold mb-0 text-danger fs-4 fs-sm-3">{{ number_format($kpis['totalNaik'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="rounded-circle bg-danger-subtle text-danger p-2.5 d-none d-sm-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Harga Turun</span>
                            <h3 class="fw-bold mb-0 text-success fs-4 fs-sm-3">{{ number_format($kpis['totalTurun'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success p-2.5 d-none d-sm-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-graph-down-arrow fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Bulan Ini</span>
                            <h3 class="fw-bold mb-0 text-info fs-4 fs-sm-3">{{ number_format($kpis['bulanIni'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="rounded-circle bg-info-subtle text-info p-2.5 d-none d-sm-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-calendar-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm mb-3 mb-md-4 overflow-hidden">
        <!-- Quick Price Type Filter Pills (Horizontal Swipe on Mobile) -->
        <div class="card-header bg-white border-bottom py-2 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills nav-pills-scrollable flex-nowrap gap-1.5 pb-1 pb-md-0" role="tablist">
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setJenisHargaFilter('')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $filterJenisHarga === '' ? 'active bg-primary text-white fw-bold' : 'text-secondary bg-light-subtle border' }}"
                        >
                            Semua Jenis Harga
                        </button>
                    </li>
                    @can('view_purchase_prices')
                        <li class="nav-item">
                            <button
                                type="button"
                                wire:click="setJenisHargaFilter('harga_beli')"
                                class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $filterJenisHarga === 'harga_beli' ? 'active bg-warning text-dark fw-bold' : 'text-secondary bg-light-subtle border' }}"
                            >
                                <i class="bi bi-box-arrow-in-down me-1"></i>Harga Beli (HPP)
                            </button>
                        </li>
                    @endcan
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setJenisHargaFilter('harga_jual')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $filterJenisHarga === 'harga_jual' ? 'active bg-success text-white fw-bold' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-tag me-1"></i>Harga Jual Umum
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setJenisHargaFilter('harga_aplikator')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $filterJenisHarga === 'harga_aplikator' ? 'active bg-info text-white fw-bold' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-tools me-1"></i>Harga Aplikator
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setJenisHargaFilter('harga_grosir')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $filterJenisHarga === 'harga_grosir' ? 'active bg-primary text-white fw-bold' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-shop me-1"></i>Harga Grosir
                        </button>
                    </li>
                </ul>

                @if ($search || $filterJenisHarga || $filterSumber || $filterArah || $filterKategori || $filterMerek || $startDate || $endDate)
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-link text-danger text-decoration-none py-0 px-2 small">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                    </button>
                @endif
            </div>
        </div>

        <div class="card-body p-2.5 p-md-3">
            <div class="row g-2">
                <!-- Search -->
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control border-start-0"
                            placeholder="Cari nama produk, SKU, no. faktur, supplier..."
                        >
                        @if ($search)
                            <button type="button" wire:click="$set('search', '')" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Filter Kategori -->
                <div class="col-6 col-md-4 col-lg-2">
                    <select wire:model.live="filterKategori" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach ($kategoris as $kat)
                            <option value="{{ $kat->id }}">{{ $kat->nama_kategori }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Merek -->
                <div class="col-6 col-md-4 col-lg-2">
                    <select wire:model.live="filterMerek" class="form-select form-select-sm">
                        <option value="">Semua Merek</option>
                        @foreach ($mereks as $mrk)
                            <option value="{{ $mrk->id }}">{{ $mrk->nama_merek }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Sumber Perubahan -->
                <div class="col-6 col-md-4 col-lg-2">
                    <select wire:model.live="filterSumber" class="form-select form-select-sm">
                        <option value="">Semua Sumber</option>
                        <option value="pembelian">Faktur Pembelian / PO</option>
                        <option value="master_update">Edit Manual Katalog</option>
                        <option value="import_excel">Import Excel / CSV</option>
                    </select>
                </div>

                <!-- Filter Arah (Naik / Turun) -->
                <div class="col-6 col-md-3 col-lg-1">
                    <select wire:model.live="filterArah" class="form-select form-select-sm">
                        <option value="">Naik & Turun</option>
                        <option value="naik">📈 Naik</option>
                        <option value="turun">📉 Turun</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-12 col-md-5 col-lg-2">
                    <div class="input-group input-group-sm">
                        <input type="date" wire:model.live="startDate" class="form-control" title="Dari Tanggal">
                        <span class="input-group-text bg-light px-1">-</span>
                        <input type="date" wire:model.live="endDate" class="form-control" title="Sampai Tanggal">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Card (Mobile Card List + Desktop Table) -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                @forelse ($riwayats as $item)
                    @php
                        $diff = (float) $item->selisih_nominal;
                        $isUp = $diff > 0;
                        $pct = $item->selisih_persen !== null ? (float) $item->selisih_persen : null;
                    @endphp
                    <div class="p-3 border-bottom" wire:key="riwayat-harga-mob-{{ $item->id }}">
                        <!-- Top Row: Product Name & Price Type Badge -->
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1.5">
                            <div class="min-w-0">
                                @if ($item->produk)
                                    <a href="{{ route('master.produk.show', $item->produk_id) }}" class="fw-bold text-dark text-decoration-none d-block" style="font-size: 0.92rem; line-height: 1.3;">
                                        {{ $item->produk->nama }}
                                    </a>
                                @else
                                    <span class="fw-bold text-muted">Produk Terhapus</span>
                                @endif
                                <div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size: 0.72rem;">
                                    <span class="badge bg-light text-secondary border font-monospace">{{ $item->produk?->kode_katalog ?? '-' }}</span>
                                    @if ($item->produk?->kategori)
                                        <span class="badge bg-light text-dark border">{{ $item->produk->kategori->nama_kategori }}</span>
                                    @endif
                                    @if ($item->produk?->merek)
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $item->produk->merek->nama_merek }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge {{ $item->jenisHargaBadgeClass() }} flex-shrink-0" style="font-size: 0.7rem;">
                                {{ $item->jenisHargaLabel() }}
                            </span>
                        </div>

                        <!-- Price Comparison Box -->
                        <div class="p-2.5 rounded-3 bg-light border my-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Harga Lama &rarr; Harga Baru</div>
                                <div class="d-flex align-items-center gap-1.5 font-monospace mt-0.5">
                                    <span class="text-muted text-decoration-line-through small">
                                        Rp {{ number_format((float) ($item->harga_lama ?? 0), 0, ',', '.') }}
                                    </span>
                                    <i class="bi bi-arrow-right text-secondary small"></i>
                                    <span class="fw-bold text-dark fs-6">
                                        Rp {{ number_format((float) ($item->harga_baru ?? 0), 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-end ms-auto">
                                <span class="badge {{ $isUp ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} font-monospace px-2 py-1">
                                    <i class="bi {{ $isUp ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }} me-0.5"></i>
                                    {{ $isUp ? '+' : '-' }}Rp {{ number_format(abs($diff), 0, ',', '.') }}
                                    @if ($pct !== null)
                                        ({{ $isUp ? '+' : '' }}{{ number_format($pct, 1, ',', '.') }}%)
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Footer Metadata: Source, Reference, Supplier, User & Time -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 text-muted" style="font-size: 0.73rem;">
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <span class="badge {{ $item->sumberBadgeClass() }}" style="font-size: 0.68rem;">
                                    {{ $item->sumberLabel() }}
                                </span>
                                @if ($item->referensi_type === 'pembelian' && $item->referensi_id)
                                    <a href="{{ route('pembelian.show', $item->referensi_id) }}" class="badge bg-white text-primary border font-monospace text-decoration-none">
                                        <i class="bi bi-receipt me-1"></i>{{ $item->referensi }}
                                    </a>
                                @elseif ($item->referensi)
                                    <span class="font-monospace">{{ $item->referensi }}</span>
                                @endif
                                @if ($item->supplier)
                                    <span class="text-dark fw-semibold">&bull; {{ $item->supplier->nama_supplier }}</span>
                                @endif
                            </div>
                            <div class="ms-auto text-end">
                                <i class="bi bi-person me-0.5"></i>{{ $item->user?->name ?? 'Sistem' }}
                                &bull; {{ $item->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 px-3 text-muted">
                        <i class="bi bi-clock-history display-6 d-block mb-2 text-secondary opacity-50"></i>
                        <div class="fw-semibold text-dark">Belum ada riwayat perubahan harga</div>
                        <small>Setiap perubahan harga dari Edit Produk, Pembelian/PO Supplier, atau Import Excel akan otomatis tercatat di sini.</small>
                    </div>
                @endforelse
            </div>

            <!-- Desktop Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3">Waktu</th>
                            <th class="py-3">Produk & SKU</th>
                            <th class="py-3">Jenis Harga</th>
                            <th class="py-3 text-end">Harga Lama</th>
                            <th class="py-3 text-end">Harga Baru</th>
                            <th class="py-3 text-end">Selisih (&Delta;)</th>
                            <th class="py-3">Sumber & Referensi</th>
                            <th class="pe-3 py-3">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayats as $item)
                            @php
                                $diff = (float) $item->selisih_nominal;
                                $isUp = $diff > 0;
                                $pct = $item->selisih_persen !== null ? (float) $item->selisih_persen : null;
                            @endphp
                            <tr wire:key="riwayat-harga-desk-{{ $item->id }}">
                                <td class="ps-3 text-nowrap">
                                    <div class="fw-semibold text-dark small">{{ $item->created_at?->format('d/m/Y H:i') }}</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">{{ $item->created_at?->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @if ($item->produk)
                                        <a href="{{ route('master.produk.show', $item->produk_id) }}" class="fw-bold text-dark text-decoration-none d-block">
                                            {{ $item->produk->nama }}
                                        </a>
                                    @else
                                        <span class="fw-bold text-muted">Produk Terhapus</span>
                                    @endif
                                    <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">
                                            {{ $item->produk?->kode_katalog ?? '-' }}
                                        </span>
                                        @if ($item->produk?->kategori)
                                            <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                                {{ $item->produk->kategori->nama_kategori }}
                                            </span>
                                        @endif
                                        @if ($item->produk?->merek)
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">
                                                {{ $item->produk->merek->nama_merek }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge {{ $item->jenisHargaBadgeClass() }} px-2 py-1">
                                        {{ $item->jenisHargaLabel() }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace text-muted text-nowrap">
                                    Rp {{ number_format((float) ($item->harga_lama ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark text-nowrap">
                                    Rp {{ number_format((float) ($item->harga_baru ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="text-end text-nowrap">
                                    <span class="badge {{ $isUp ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} font-monospace px-2 py-1">
                                        <i class="bi {{ $isUp ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }} me-0.5"></i>
                                        {{ $isUp ? '+' : '-' }}Rp {{ number_format(abs($diff), 0, ',', '.') }}
                                        @if ($pct !== null)
                                            ({{ $isUp ? '+' : '' }}{{ number_format($pct, 1, ',', '.') }}%)
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <span class="badge {{ $item->sumberBadgeClass() }}">
                                            {{ $item->sumberLabel() }}
                                        </span>
                                        @if ($item->referensi_type === 'pembelian' && $item->referensi_id)
                                            <a href="{{ route('pembelian.show', $item->referensi_id) }}" class="badge bg-light text-primary border font-monospace text-decoration-none">
                                                <i class="bi bi-receipt me-1"></i>{{ $item->referensi }}
                                            </a>
                                        @elseif ($item->referensi)
                                            <span class="small text-muted font-monospace">{{ $item->referensi }}</span>
                                        @endif
                                    </div>
                                    @if ($item->supplier)
                                        <div class="small text-dark fw-semibold mt-0.5" style="font-size: 0.74rem;">
                                            <i class="bi bi-truck me-1 text-primary"></i>{{ $item->supplier->nama_supplier }}
                                        </div>
                                    @elseif ($item->keterangan)
                                        <div class="small text-muted text-truncate mt-0.5" style="max-width: 240px; font-size: 0.73rem;" title="{{ $item->keterangan }}">
                                            {{ $item->keterangan }}
                                        </div>
                                    @endif
                                </td>
                                <td class="pe-3 text-nowrap">
                                    <span class="small text-dark fw-medium">
                                        <i class="bi bi-person-circle text-secondary me-1"></i>{{ $item->user?->name ?? 'Sistem' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-clock-history display-6 d-block mb-2 text-secondary opacity-50"></i>
                                    <div class="fw-semibold text-dark">Belum ada riwayat perubahan harga</div>
                                    <small>Setiap perubahan harga dari Edit Produk, Pembelian/PO Supplier, atau Import Excel akan otomatis tercatat di sini.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($riwayats->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $riwayats->links() }}
            </div>
        @endif
    </div>
</div>
