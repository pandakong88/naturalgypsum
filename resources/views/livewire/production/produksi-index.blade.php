<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Catatan Produksi Barang Jadi</h4>
            <p class="text-muted small mb-0">Kelola hasil cetak/produksi workshop internal yang masuk ke stok gudang beserta pemakaian bahan bakunya.</p>
        </div>
        <div class="d-flex gap-2">
            @can('view_stock')
                <a href="{{ route('stok.mutasi.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left-right me-1"></i>Mutasi Stok
                </a>
            @endcan
            @can('create', \App\Domains\Production\Models\Produksi::class)
                <a href="{{ route('produksi.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Catat Produksi Baru
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
                            <span class="text-muted small d-block mb-1">Total Batch Produksi</span>
                            <h5 class="fw-bold text-primary mb-1">{{ number_format($totalBatch) }} Batch</h5>
                            <small class="text-muted">Produksi selesai & aktif</small>
                        </div>
                        <div class="p-2 bg-primary-subtle text-primary rounded">
                            <i class="bi bi-gear-wide-connected fs-4"></i>
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
                            <span class="text-muted small d-block mb-1">Total Output Barang Jadi</span>
                            <h5 class="fw-bold text-dark mb-1">
                                {{ number_format($totalOutputQty, 0, ',', '.') }} Unit
                            </h5>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                {{ number_format($totalJenisProduk) }} Varian Produk
                            </span>
                        </div>
                        <div class="p-2 bg-info-subtle text-info-emphasis rounded">
                            <i class="bi bi-box-seam fs-4"></i>
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
                            <span class="text-muted small d-block mb-1">Estimasi Nilai Produksi (HPP)</span>
                            <h5 class="fw-bold text-success mb-1">
                                Rp {{ number_format($estimasiNilaiOutput, 0, ',', '.') }}
                            </h5>
                            <small class="text-muted">Nilai aset barang jadi masuk</small>
                        </div>
                        <div class="p-2 bg-success-subtle text-success rounded">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
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
                            <span class="text-muted small d-block mb-1">Status & Audit Produksi</span>
                            <h5 class="fw-bold {{ $totalDibatalkan > 0 ? 'text-danger' : 'text-secondary' }} mb-1">
                                {{ number_format($totalDibatalkan) }} Dibatalkan
                            </h5>
                            <small class="text-muted">Tanpa menimbulkan hutang supplier</small>
                        </div>
                        <div class="p-2 bg-secondary-subtle text-secondary rounded">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm mb-4">
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

                @if ($search !== '' || $gudangFilter !== '' || $statusFilter !== 'aktif' || $startDate !== '' || $endDate !== '')
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua Filter
                    </button>
                @endif
            </div>

            <div class="row g-3">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari No. Produksi / Produk / Catatan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="PRD-..., nama produk, shift..."
                        >
                        @if (filled($search))
                            <button type="button" wire:click="$set('search', '')" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1">Gudang Penerima</label>
                    <select wire:model.live="gudangFilter" class="form-select form-select-sm">
                        <option value="">Semua Gudang</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="aktif">Aktif / Selesai</option>
                        <option value="dibatalkan">Dibatalkan ({{ $totalDibatalkan }})</option>
                        <option value="semua">Semua</option>
                    </select>
                </div>

                <div class="col-6 col-md-6 col-lg-2">
                    <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-6 col-lg-2">
                    <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm">
                </div>
            </div>
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
                            <th>No. Produksi</th>
                            <th>Tanggal</th>
                            <th>Gudang Penerima</th>
                            <th>Rincian Barang Jadi & Bahan</th>
                            <th>Catatan / Shift</th>
                            <th class="text-end">Estimasi Nilai (HPP)</th>
                            <th>Dicatat Oleh</th>
                            <th class="text-end pe-4" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produksis as $index => $p)
                            @php
                                $isCancelled = $p->trashed();
                                $nilaiOutput = $p->estimasi_nilai_output;
                                $bahanCount = $p->bahanMutations->count();
                            @endphp
                            <tr class="{{ $isCancelled ? 'table-danger bg-opacity-10' : '' }}">
                                <td class="ps-4 text-muted">{{ $produksis->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('produksi.show', $p->id) }}" class="fw-bold font-monospace text-decoration-none {{ $isCancelled ? 'text-danger text-decoration-line-through' : 'text-primary' }}">
                                        {{ $p->no_produksi }}
                                    </a>
                                    @if ($isCancelled)
                                        <span class="badge bg-danger ms-1">DIBATALKAN</span>
                                    @endif
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $p->created_at?->format('H:i') }}
                                    </small>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $p->tanggal->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-building me-1"></i>{{ $p->gudang->nama_gudang ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                        @foreach ($p->details->take(2) as $detail)
                                            @php
                                                $qty = (float) $detail->jumlah;
                                                $qtyStr = floor($qty) == $qty ? number_format($qty, 0, ',', '.') : number_format($qty, 2, ',', '.');
                                            @endphp
                                            <span class="badge bg-light text-dark border fw-normal">
                                                <strong>{{ $detail->produk->nama ?? 'Produk' }}</strong>:
                                                <span class="text-success fw-bold">+{{ $qtyStr }} {{ $detail->produk?->satuan?->value ?? '' }}</span>
                                            </span>
                                        @endforeach
                                        @if ($p->details->count() > 2)
                                            <span class="badge bg-secondary-subtle text-secondary border">
                                                +{{ $p->details->count() - 2 }} item lainnya
                                            </span>
                                        @endif
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.74rem;">
                                        <span>Total: <strong>{{ $p->details->count() }} jenis</strong> ({{ number_format((float) $p->details->sum('jumlah'), 2, ',', '.') }} unit)</span>
                                        @if ($bahanCount > 0)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" style="font-size: 0.68rem;">
                                                <i class="bi bi-layers me-1"></i>{{ $bahanCount }} bahan baku dipotong
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if (filled($p->catatan))
                                        <small class="text-dark d-block text-truncate" style="max-width: 220px;" title="{{ $p->catatan }}">
                                            {{ $p->catatan }}
                                        </small>
                                    @else
                                        <span class="small text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $isCancelled ? 'text-muted text-decoration-line-through' : 'text-success' }}">
                                        Rp {{ number_format($nilaiOutput, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $p->user->name ?? ($p->creator->name ?? '-') }}</small>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('produksi.show', $p->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail Produksi">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Belum ada catatan produksi yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($produksis->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $produksis->links() }}
            </div>
        @endif
    </div>
</div>
