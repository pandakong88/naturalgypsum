<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Daftar Pembayaran</h4>
            <p class="text-muted small mb-0">Kelola dan pantau seluruh transaksi penerimaan pembayaran piutang dari pelanggan.</p>
        </div>
        <div class="d-flex gap-2">
            @can('record_payments')
                <a href="{{ route('pembayaran.create') }}" class="btn btn-primary">
                    <i class="bi bi-cash-stack me-1"></i>Catat Pembayaran
                </a>
            @endcan
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4 col-lg-4">
                    <label class="form-label small text-muted mb-1">Pencarian No. Pembayaran / Pelanggan / Catatan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari no. pembayaran, pelanggan..."
                        >
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small text-muted mb-1">Metode Pembayaran</label>
                    <select wire:model.live="metodeFilter" class="form-select">
                        <option value="">Semua Metode</option>
                        @foreach ($metodeOptions as $metode)
                            <option value="{{ $metode->value }}">{{ $metode->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-lg-2">
                    <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                    <input type="date" wire:model.live="startDate" class="form-control">
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" wire:model.live="endDate" class="form-control">
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
                            <th>Metode</th>
                            <th class="text-end">Jumlah Bayar</th>
                            <th class="text-end">Total Dialokasikan</th>
                            <th class="text-end">Sisa Belum Dialokasikan</th>
                            <th>Diterima Oleh</th>
                            <th class="text-end pe-4" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pembayarans as $index => $pembayaran)
                            <tr>
                                <td class="ps-4 text-muted">{{ $pembayarans->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('pembayaran.show', $pembayaran->id) }}" class="fw-bold text-decoration-none font-monospace">
                                        {{ $pembayaran->no_pembayaran }}
                                    </a>
                                </td>
                                <td>
                                    <span>{{ $pembayaran->tanggal->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $pembayaran->pelanggan->nama ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $pembayaran->metode->badgeClass() }}">
                                        {{ $pembayaran->metode->label() }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rp {{ number_format((float) $pembayaran->jumlah_bayar, 2, ',', '.') }}
                                </td>
                                <td class="text-end fw-semibold text-primary">
                                    Rp {{ number_format((float) $pembayaran->total_dialokasikan, 2, ',', '.') }}
                                </td>
                                <td class="text-end fw-semibold {{ $pembayaran->sisa_belum_dialokasikan > 0 ? 'text-warning-emphasis' : 'text-muted' }}">
                                    Rp {{ number_format((float) $pembayaran->sisa_belum_dialokasikan, 2, ',', '.') }}
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $pembayaran->user->name ?? ($pembayaran->creator->name ?? '-') }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('pembayaran.show', $pembayaran->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
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
