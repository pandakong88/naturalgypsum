<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Daftar Pembayaran</h4>
            <p class="text-muted small mb-0">Buku kas masuk: pantau seluruh penerimaan pembayaran piutang maupun pembayaran kasir langsung.</p>
        </div>
        <div class="d-flex gap-2">
            @can('view_receivables')
                <a href="{{ route('piutang.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-journal-bookmark me-1"></i>Daftar Piutang
                </a>
            @endcan
            @can('record_payments')
                <a href="{{ route('pembayaran.create') }}" class="btn btn-primary">
                    <i class="bi bi-cash-stack me-1"></i>Catat Pembayaran Piutang
                </a>
            @endcan
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small d-block mb-1">Total Uang Masuk (Aktif)</span>
                            <h5 class="fw-bold text-success mb-1">
                                Rp {{ number_format($totalNominal, 0, ',', '.') }}
                            </h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                {{ number_format($totalTransaksi) }} Transaksi
                            </span>
                        </div>
                        <div class="p-2 bg-success-subtle text-success rounded">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small d-block mb-1">Penerimaan Tunai (Cash)</span>
                            <h5 class="fw-bold text-dark mb-1">
                                Rp {{ number_format($totalTunai, 0, ',', '.') }}
                            </h5>
                            <small class="text-muted">Uang fisik masuk laci kasir</small>
                        </div>
                        <div class="p-2 bg-primary-subtle text-primary rounded">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small d-block mb-1">Penerimaan Transfer Bank</span>
                            <h5 class="fw-bold text-info-emphasis mb-1">
                                Rp {{ number_format($totalTransfer, 0, ',', '.') }}
                            </h5>
                            <small class="text-muted">Mutasi masuk rekening bank</small>
                        </div>
                        <div class="p-2 bg-info-subtle text-info-emphasis rounded">
                            <i class="bi bi-bank fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="w-100">
                            <span class="text-muted small d-block mb-1">Komposisi Sumber Kas</span>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Cicilan/Piutang:</span>
                                <span class="fw-bold text-primary">Rp {{ number_format($totalPelunasanPiutang, 0, ',', '.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Kasir POS:</span>
                                <span class="fw-semibold text-secondary">Rp {{ number_format($totalKasirLangsung, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="p-2 bg-warning-subtle text-warning-emphasis rounded ms-2">
                            <i class="bi bi-pie-chart fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <!-- Quick Date Presets -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="small text-muted me-2"><i class="bi bi-calendar3 me-1"></i>Periode Cepat:</span>
                    <button type="button" wire:click="setDatePreset('today')" class="btn btn-sm {{ $datePreset === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Hari Ini
                    </button>
                    <button type="button" wire:click="setDatePreset('yesterday')" class="btn btn-sm {{ $datePreset === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Kemarin
                    </button>
                    <button type="button" wire:click="setDatePreset('7days')" class="btn btn-sm {{ $datePreset === '7days' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        7 Hari Terakhir
                    </button>
                    <button type="button" wire:click="setDatePreset('month')" class="btn btn-sm {{ $datePreset === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Bulan Ini
                    </button>
                    <button type="button" wire:click="setDatePreset('all')" class="btn btn-sm {{ $datePreset === '' && $startDate === '' && $endDate === '' ? 'btn-dark' : 'btn-outline-secondary' }}">
                        Semua Waktu
                    </button>
                </div>

                @if ($search !== '' || $metodeFilter !== '' || $sumberFilter !== '' || $statusFilter !== 'aktif' || $startDate !== '' || $endDate !== '')
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua Filter
                    </button>
                @endif
            </div>

            <div class="row g-3">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1">Pencarian No. Bukti / Faktur / Pelanggan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="BY-..., PJ-..., nama, bank..."
                        >
                        @if (filled($search))
                            <button type="button" wire:click="$set('search', '')" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-muted mb-1">Metode</label>
                    <select wire:model.live="metodeFilter" class="form-select form-select-sm">
                        <option value="">Semua Metode</option>
                        @foreach ($metodeOptions as $metode)
                            <option value="{{ $metode->value }}">{{ $metode->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-muted mb-1">Sumber Pembayaran</label>
                    <select wire:model.live="sumberFilter" class="form-select form-select-sm">
                        <option value="">Semua Sumber</option>
                        <option value="piutang">Pelunasan Piutang</option>
                        <option value="kasir">Kasir Langsung (POS)</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-1">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="aktif">Aktif</option>
                        <option value="dibatalkan">Dibatalkan ({{ $totalDibatalkan }})</option>
                        <option value="semua">Semua</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </div>

    <!-- Flash message -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>No. Pembayaran</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Faktur Terkait & Sumber</th>
                            <th>Metode & Keterangan</th>
                            <th class="text-end">Jumlah Bayar</th>
                            <th>Diterima Oleh</th>
                            <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pembayarans as $index => $pembayaran)
                            @php
                                $isCancelled = $pembayaran->trashed();
                                $isKasir = $pembayaran->is_kasir_langsung;
                                $sisaUnallocated = $pembayaran->sisa_belum_dialokasikan;
                            @endphp
                            <tr class="{{ $isCancelled ? 'table-danger bg-opacity-10' : '' }}">
                                <td class="ps-4 text-muted">{{ $pembayarans->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('pembayaran.show', $pembayaran->id) }}" class="fw-bold text-decoration-none font-monospace {{ $isCancelled ? 'text-decoration-line-through text-danger' : '' }}">
                                        {{ $pembayaran->no_pembayaran }}
                                    </a>
                                    @if ($isCancelled)
                                        <span class="badge bg-danger ms-1">DIBATALKAN</span>
                                    @endif
                                    <small class="text-muted d-block" style="font-size: 0.73rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $pembayaran->created_at?->format('H:i') }}
                                    </small>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $pembayaran->tanggal->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $pembayaran->pelanggan->nama ?? '-' }}</div>
                                    @if ($pembayaran->pelanggan?->tipe)
                                        <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                            {{ $pembayaran->pelanggan->tipe->label() }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                        @forelse ($pembayaran->alokasi->take(3) as $alok)
                                            @if ($alok->penjualan)
                                                <a
                                                    href="{{ route('penjualan.show', $alok->penjualan->id) }}"
                                                    class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none font-monospace"
                                                    title="Alokasi: Rp {{ number_format((float) $alok->jumlah_dialokasikan, 0, ',', '.') }}"
                                                >
                                                    {{ $alok->penjualan->no_penjualan }}
                                                </a>
                                            @endif
                                        @empty
                                            @if (!$isCancelled)
                                                <span class="badge bg-secondary-subtle text-secondary">Tanpa Alokasi Faktur</span>
                                            @else
                                                <span class="small text-muted fst-italic">Alokasi dilepas (batal)</span>
                                            @endif
                                        @endforelse
                                        @if ($pembayaran->alokasi->count() > 3)
                                            <span class="badge bg-light text-dark border">
                                                +{{ $pembayaran->alokasi->count() - 3 }} faktur
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($isKasir)
                                            <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                                <i class="bi bi-shop me-1"></i>Kasir Langsung
                                            </span>
                                        @else
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: 0.7rem;">
                                                <i class="bi bi-journal-check me-1"></i>Pelunasan Piutang
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $pembayaran->metode->badgeClass() }}">
                                        {{ $pembayaran->metode->label() }}
                                    </span>
                                    @if (filled($pembayaran->catatan) && !$isKasir)
                                        <small class="text-muted d-block mt-1 text-truncate" style="max-width: 220px;" title="{{ $pembayaran->catatan }}">
                                            {{ $pembayaran->catatan }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold {{ $isCancelled ? 'text-muted text-decoration-line-through' : 'text-success' }}">
                                        Rp {{ number_format((float) $pembayaran->jumlah_bayar, 2, ',', '.') }}
                                    </div>
                                    @if (!$isCancelled && $sisaUnallocated > 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.7rem;" title="Sisa belum dialokasikan ke faktur">
                                            Belum alokasi: Rp {{ number_format($sisaUnallocated, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $pembayaran->user->name ?? ($pembayaran->creator->name ?? '-') }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pembayaran.show', $pembayaran->id) }}" class="btn btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if (!$isCancelled)
                                            <a href="{{ route('pembayaran.print.kuitansi', $pembayaran->id) }}" target="_blank" class="btn btn-outline-secondary" title="Cetak Kuitansi A4/A5">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <a href="{{ route('pembayaran.print.thermal', $pembayaran->id) }}" target="_blank" class="btn btn-outline-dark" title="Cetak Struk Thermal">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Tidak ada data pembayaran yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($pembayarans->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $pembayarans->links() }}
            </div>
        @endif
    </div>
</div>
