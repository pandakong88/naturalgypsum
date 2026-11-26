<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Daftar Penjualan</h4>
            <p class="text-muted small mb-0">Kelola dan pantau seluruh transaksi penjualan barang kepada pelanggan.</p>
        </div>
        <div class="d-flex gap-2 w-100 w-sm-auto">
            @can('create_sales')
                <a href="{{ route('penjualan.create') }}" class="btn btn-primary px-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5 flex-fill flex-sm-grow-0">
                    <i class="bi bi-cart-plus fs-6"></i>
                    <span class="fw-semibold">Tambah Penjualan</span>
                </a>
            @endcan
        </div>
    </div>

    <!-- Unified Sleek Filter Card -->
    <div class="card mb-3 mb-md-4 border-0 shadow-sm overflow-hidden">
        <!-- Top Status & Method Filter Pills -->
        <div class="card-header bg-white border-bottom py-2 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills nav-pills-scrollable flex-nowrap gap-1.5 pb-1 pb-md-0" role="tablist">
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('semua')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $statusTransaksiFilter === 'aktif' && empty($metodePembayaranFilter) ? 'active bg-primary text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            Semua Aktif
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('tunai')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $statusTransaksiFilter === 'aktif' && $metodePembayaranFilter === 'tunai' ? 'active bg-success text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-cash-stack me-1"></i>Tunai (Cash)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('transfer')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $statusTransaksiFilter === 'aktif' && $metodePembayaranFilter === 'transfer' ? 'active bg-info text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-bank me-1"></i>Transfer Bank
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('tempo')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $statusTransaksiFilter === 'aktif' && $metodePembayaranFilter === 'tempo' ? 'active bg-warning text-dark fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-clock-history me-1"></i>Tempo (Piutang)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            wire:click="setQuickFilter('batal')"
                            class="nav-link py-1 px-3 small rounded-pill text-nowrap {{ $statusTransaksiFilter === 'dibatalkan' ? 'active bg-danger text-white fw-bold shadow-xs' : 'text-secondary bg-light-subtle border' }}"
                        >
                            <i class="bi bi-x-circle me-1"></i>Dibatalkan (Void)
                        </button>
                    </li>
                </ul>

                @if ($search || $gudangFilter || $statusPembayaranFilter || $metodePembayaranFilter || $statusTransaksiFilter !== 'aktif' || $startDate || $endDate)
                    <button type="button" wire:click="resetAllFilters" class="btn btn-sm btn-link text-danger text-decoration-none py-1 px-2 small">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua Filter
                    </button>
                @endif
            </div>
        </div>

        <!-- Filter Controls: Search, Warehouse, & Dates -->
        <div class="card-body p-2.5 p-md-3">
            <div class="row g-2 align-items-center">
                <!-- Search Bar -->
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Cari no faktur, pelanggan, kontak, sopir, plat..."
                        >
                        @if ($search)
                            <button type="button" wire:click="$set('search', '')" class="btn btn-outline-secondary border-start-0 border text-muted">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Gudang Filter -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="bi bi-building"></i></span>
                        <select wire:model.live="gudangFilter" class="form-select border-start-0 ps-0">
                            <option value="">Semua Gudang</option>
                            @foreach ($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                            @endforeach
                        </select>
                        @if ($gudangFilter)
                            <button type="button" wire:click="$set('gudangFilter', '')" class="btn btn-outline-secondary border-start-0 border text-muted">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Date Range & Shortcuts -->
                <div class="col-12 col-lg-5 d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
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
                            class="form-control form-control-sm flex-fill"
                            style="min-width: 115px;"
                            title="Dari Tanggal"
                        >
                        <span class="text-muted small">-</span>
                        <input
                            type="date"
                            wire:model.live="endDate"
                            class="form-control form-control-sm flex-fill"
                            style="min-width: 115px;"
                            title="Sampai Tanggal"
                        >
                        @if ($startDate || $endDate)
                            <button
                                type="button"
                                wire:click="clearDateFilter"
                                class="btn btn-sm btn-outline-danger py-1 px-1.5 flex-shrink-0"
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

    <!-- Flash message -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Data Table & Mobile Card List Card -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                @forelse ($penjualans as $penjualan)
                    @php
                        $isBatal = $penjualan->isDibatalkan();
                    @endphp
                    <div class="p-3 border-bottom {{ $isBatal ? 'bg-danger-subtle bg-opacity-10' : '' }}" wire:key="penjualan-mob-{{ $penjualan->id }}">
                        <!-- Top Row: No Faktur, Date & Status -->
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('penjualan.show', $penjualan->id) }}"
                                       class="fw-bold font-monospace text-decoration-none fs-6 {{ $isBatal ? 'text-decoration-line-through text-danger' : 'text-primary' }}">
                                        {{ $penjualan->no_penjualan }}
                                    </a>
                                    @if ($isBatal)
                                        <span class="badge bg-danger" style="font-size: 0.65rem;">BATAL</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size: 0.74rem;">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $penjualan->tanggal->format('d/m/Y') }} &bull; {{ $penjualan->created_at->format('H:i') }} WIB
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                @if ($isBatal)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Dibatalkan</span>
                                @else
                                    <span class="badge {{ $penjualan->status_pembayaran->badgeClass() }}">
                                        {{ $penjualan->status_pembayaran->label() }}
                                    </span>
                                    <div class="text-muted mt-0.5" style="font-size: 0.7rem;">
                                        @if ($penjualan->metode_pembayaran === 'tunai')
                                            <i class="bi bi-cash-stack text-success me-1"></i>Tunai POS
                                        @elseif ($penjualan->metode_pembayaran === 'transfer')
                                            <i class="bi bi-bank text-primary me-1"></i>Transfer Bank
                                        @elseif (in_array($penjualan->metode_pembayaran, ['tempo', 'belum_bayar']))
                                            <i class="bi bi-clock-history text-warning-emphasis me-1"></i>Tempo (Piutang)
                                        @else
                                            {{ $penjualan->getMetodeLabel() }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Customer & Warehouse Row -->
                        <div class="mb-2">
                            <div class="fw-bold text-dark">{{ $penjualan->pelanggan->nama ?? 'Pelanggan Umum' }}</div>
                            <div class="d-flex align-items-center flex-wrap gap-1.5 mt-1" style="font-size: 0.74rem;">
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-building me-1"></i>{{ $penjualan->gudang->nama_gudang ?? '-' }}
                                </span>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $penjualan->details_count ?? $penjualan->details->count() }} item
                                </span>
                                @if (filled($penjualan->pelanggan?->kontak) && trim($penjualan->pelanggan->kontak) !== '-')
                                    <span class="text-muted">
                                        <i class="bi bi-telephone me-1"></i>{{ $penjualan->pelanggan->kontak }}
                                    </span>
                                @endif
                                @if ($penjualan->nama_sopir || $penjualan->plat_nomor)
                                    <span class="text-muted">
                                        <i class="bi bi-truck me-1"></i>{{ $penjualan->nama_sopir ?: 'Sopir' }}
                                        @if ($penjualan->plat_nomor) ({{ $penjualan->plat_nomor }}) @endif
                                    </span>
                                @endif
                            </div>
                            @if ($isBatal && $penjualan->alasan_batal)
                                <div class="text-danger small mt-1" style="font-size: 0.73rem;">
                                    <i class="bi bi-chat-left-quote me-1"></i>Alasan: {{ $penjualan->alasan_batal }}
                                </div>
                            @endif
                        </div>

                        <!-- Total & Cashier Box -->
                        <div class="p-2 rounded-3 bg-light border d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted" style="font-size: 0.72rem;">
                                <i class="bi bi-person-check me-1"></i>Kasir: <strong>{{ $penjualan->user->name ?? ($penjualan->creator->name ?? '-') }}</strong>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Total Akhir</span>
                                <span class="fw-bold font-monospace fs-6 {{ $isBatal ? 'text-muted text-decoration-line-through' : 'text-dark' }}">
                                    Rp {{ number_format((float) $penjualan->total_akhir, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <a href="{{ route('penjualan.show', $penjualan->id) }}" class="btn btn-sm btn-outline-primary py-1 px-2.5 flex-fill">
                                <i class="bi bi-eye me-1"></i>Detail
                            </a>
                            @if (! $isBatal)
                                <a href="{{ route('penjualan.print.faktur', $penjualan->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2 flex-fill">
                                    <i class="bi bi-file-earmark-text me-1"></i>Faktur
                                </a>
                                <a href="{{ route('penjualan.print.thermal', $penjualan->id) }}" target="_blank" class="btn btn-sm btn-outline-dark py-1 px-2 flex-fill">
                                    <i class="bi bi-receipt me-1"></i>Struk
                                </a>
                            @endif
                            @can('create_sales')
                                <a href="{{ route('penjualan.create', ['duplicate_id' => $penjualan->id]) }}" class="btn btn-sm btn-light border py-1 px-2" title="Duplikat Pesanan">
                                    <i class="bi bi-copy text-info"></i>
                                </a>
                            @endcan
                            @if (! $isBatal)
                                @can('cancel_sales')
                                    <button type="button" wire:click="openCancelModal({{ $penjualan->id }})" class="btn btn-sm btn-outline-danger py-1 px-2" title="Batalkan Nota">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 px-3 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <div>Tidak ada data penjualan yang ditemukan sesuai filter yang dipilih.</div>
                        @if ($search || $gudangFilter || $metodePembayaranFilter || $statusTransaksiFilter !== 'aktif' || $startDate || $endDate)
                            <button type="button" wire:click="resetAllFilters" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>

            <!-- Desktop Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle border-bottom">
                        <tr class="text-secondary small fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <th class="ps-4 py-3" style="width: 45px;">No</th>
                            <th class="py-3">No. Penjualan</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3">Pelanggan</th>
                            <th class="py-3">Gudang</th>
                            <th class="text-center py-3">Item</th>
                            <th class="text-end py-3 pe-3">Total Akhir</th>
                            <th class="text-center py-3">Status & Bayar</th>
                            <th class="py-3">Kasir</th>
                            <th class="text-end pe-4 py-3" style="width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($penjualans as $index => $penjualan)
                            @php
                                $isBatal = $penjualan->isDibatalkan();
                            @endphp
                            <tr class="{{ $isBatal ? 'table-danger-subtle bg-opacity-25' : '' }}">
                                <!-- No -->
                                <td class="ps-4 text-muted small">{{ $penjualans->firstItem() + $index }}</td>

                                <!-- No. Penjualan -->
                                <td>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <a
                                            href="{{ route('penjualan.show', $penjualan->id) }}"
                                            class="fw-bold font-monospace text-decoration-none {{ $isBatal ? 'text-decoration-line-through text-danger' : 'text-primary' }}"
                                        >
                                            {{ $penjualan->no_penjualan }}
                                        </a>
                                        @if ($isBatal)
                                            <span class="badge bg-danger ms-1" style="font-size: 0.65rem;" title="Dibatalkan pada {{ $penjualan->dibatalkan_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $penjualan->dibatalkanBy->name ?? 'User' }}">
                                                BATAL
                                            </span>
                                        @endif
                                    </div>
                                    @if ($penjualan->nama_sopir || $penjualan->plat_nomor)
                                        <div class="text-muted small mt-0.5" style="font-size: 0.74rem;">
                                            <i class="bi bi-truck me-1"></i>{{ $penjualan->nama_sopir ?: 'Sopir' }}
                                            @if ($penjualan->plat_nomor)
                                                ({{ $penjualan->plat_nomor }})
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- Tanggal -->
                                <td class="text-nowrap">
                                    <span class="text-dark">{{ $penjualan->tanggal->format('d/m/Y') }}</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $penjualan->created_at->format('H:i') }} WIB</div>
                                </td>

                                <!-- Pelanggan -->
                                <td>
                                    <div class="fw-semibold text-dark">{{ $penjualan->pelanggan->nama ?? '-' }}</div>
                                    @if (filled($penjualan->pelanggan?->kontak) && trim($penjualan->pelanggan->kontak) !== '-')
                                        <div class="text-muted small" style="font-size: 0.74rem;">
                                            <i class="bi bi-telephone me-1 text-secondary"></i>{{ $penjualan->pelanggan->kontak }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Gudang -->
                                <td class="text-nowrap">
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        <i class="bi bi-building me-1"></i>{{ $penjualan->gudang->nama_gudang ?? '-' }}
                                    </span>
                                </td>

                                <!-- Total Item -->
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                        {{ $penjualan->details_count ?? $penjualan->details->count() }} item
                                    </span>
                                </td>

                                <!-- Total Akhir (text-nowrap prevents line wrapping on big amounts) -->
                                <td class="text-end pe-3 text-nowrap">
                                    <span class="fw-bold font-monospace fs-6 {{ $isBatal ? 'text-muted text-decoration-line-through' : 'text-dark' }}">
                                        Rp {{ number_format((float) $penjualan->total_akhir, 2, ',', '.') }}
                                    </span>
                                </td>

                                <!-- Status & Metode Pembayaran (Clean unified layout without badge overload) -->
                                <td class="text-center text-nowrap">
                                    @if ($isBatal)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            Dibatalkan
                                        </span>
                                        @if ($penjualan->alasan_batal)
                                            <div class="text-danger small mt-0.5 text-truncate" style="max-width: 140px; font-size: 0.7rem;" title="{{ $penjualan->alasan_batal }}">
                                                <i class="bi bi-chat-left-quote me-1"></i>{{ $penjualan->alasan_batal }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge {{ $penjualan->status_pembayaran->badgeClass() }} px-2 py-1">
                                            {{ $penjualan->status_pembayaran->label() }}
                                        </span>
                                        <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                            @if ($penjualan->metode_pembayaran === 'tunai')
                                                <i class="bi bi-cash-stack text-success me-1"></i>Tunai POS
                                            @elseif ($penjualan->metode_pembayaran === 'transfer')
                                                <i class="bi bi-bank text-primary me-1"></i>Transfer Bank
                                            @elseif (in_array($penjualan->metode_pembayaran, ['tempo', 'belum_bayar']))
                                                <i class="bi bi-clock-history text-warning-emphasis me-1"></i>Tempo (Piutang)
                                            @else
                                                {{ $penjualan->getMetodeLabel() }}
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- Kasir / User -->
                                <td>
                                    <span class="small text-muted text-truncate d-inline-block" style="max-width: 130px;" title="{{ $penjualan->user->name ?? ($penjualan->creator->name ?? '-') }}">
                                        {{ $penjualan->user->name ?? ($penjualan->creator->name ?? '-') }}
                                    </span>
                                </td>

                                <!-- Aksi Dropdown -->
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle py-1 px-2.5 text-secondary shadow-2xs" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical me-0.5"></i>Aksi
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('penjualan.show', $penjualan->id) }}">
                                                    <i class="bi bi-eye text-primary me-2"></i>Lihat Detail
                                                </a>
                                            </li>
                                            @can('create_sales')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('penjualan.create', ['duplicate_id' => $penjualan->id]) }}">
                                                        <i class="bi bi-copy text-info me-2"></i>Duplikat Pesanan
                                                    </a>
                                                </li>
                                            @endcan
                                            @if (! $isBatal)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('penjualan.print.faktur', $penjualan->id) }}" target="_blank">
                                                        <i class="bi bi-file-earmark-text text-secondary me-2"></i>Cetak Faktur (A4)
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('penjualan.print.thermal', $penjualan->id) }}" target="_blank">
                                                        <i class="bi bi-receipt text-dark me-2"></i>Cetak Struk (Kasir)
                                                    </a>
                                                </li>
                                                @can('cancel_sales')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" wire:click="openCancelModal({{ $penjualan->id }})" class="dropdown-item text-danger">
                                                            <i class="bi bi-x-circle text-danger me-2"></i>Batalkan Nota
                                                        </button>
                                                    </li>
                                                @endcan
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <div>Tidak ada data penjualan yang ditemukan sesuai filter yang dipilih.</div>
                                    @if ($search || $gudangFilter || $metodePembayaranFilter || $statusTransaksiFilter !== 'aktif' || $startDate || $endDate)
                                        <button type="button" wire:click="resetAllFilters" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($penjualans->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $penjualans->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Pembatalan Nota (Void) -->
    @if ($isCancelModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Batalkan Nota Penjualan
                        </h5>
                        <button type="button" wire:click="closeCancelModal" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 small mb-3">
                            <h6 class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i> Konsekuensi Pembatalan:</h6>
                            <ul class="mb-0 ps-3">
                                <li>Stok barang yang ada di nota akan <strong>dikembalikan ke gudang</strong> secara otomatis.</li>
                                <li>Pembayaran kasir yang terikat akan <strong>dibatalkan/dibalikkan</strong> (uang dikembalikan).</li>
                                <li>Piutang yang timbul dari nota ini akan <strong>dinolkan</strong>.</li>
                                <li>Nota akan ditandai berstatus <strong>BATAL</strong> dan riwayat pembatalan tetap dicatat.</li>
                            </ul>
                        </div>

                        <p class="mb-3">
                            Apakah Anda yakin ingin membatalkan nota <strong>{{ $cancellingNoPenjualan }}</strong>?
                        </p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">
                                Alasan Pembatalan <span class="text-danger">*</span>
                            </label>
                            <textarea
                                wire:model="alasanBatal"
                                class="form-control @error('alasanBatal') is-invalid @enderror"
                                rows="3"
                                placeholder="Contoh: Pembeli salah memilih ukuran gypsum, salah input harga kasir, pesanan dibatalkan pelanggan..."
                            ></textarea>
                            @error('alasanBatal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="closeCancelModal" class="btn btn-secondary">
                            Batal
                        </button>
                        <button type="button" wire:click="confirmCancelPenjualan" class="btn btn-danger" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="confirmCancelPenjualan">
                                <i class="bi bi-check-circle me-1"></i>Ya, Batalkan Nota
                            </span>
                            <span wire:loading wire:target="confirmCancelPenjualan">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
