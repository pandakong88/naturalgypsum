<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Pembayaran</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-cash-stack text-success me-2"></i>Laporan Penerimaan Pembayaran &amp; Alokasi Kas
            </h1>
            <p class="text-muted small mb-0">Rekapitulasi arus kas masuk tunai/transfer, rincian faktur yang dilunasi, dan pemantauan dana titipan/deposit.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Cetak
            </button>
            <a href="{{ route('laporan.hub') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Hub
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Pembayaran Masuk</div>
                    <div class="h4 fw-bold text-success mb-1">Rp {{ number_format($summary['total_pembayaran'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">{{ $summary['total_transaksi'] ?? 0 }} transaksi tercatat</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Tunai</div>
                    <div class="h4 fw-bold text-primary mb-1">Rp {{ number_format($summary['total_tunai'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Metode Kas / Tunai</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Transfer Bank</div>
                    <div class="h4 fw-bold text-info mb-1">Rp {{ number_format($summary['total_transfer'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Lainnya: Rp {{ number_format($summary['total_lainnya'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Alokasi Faktur vs Deposit</div>
                    <div class="h4 fw-bold text-dark mb-1">Rp {{ number_format($summary['total_dialokasikan'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Titipan/Deposit: <strong class="text-primary">Rp {{ number_format($summary['total_deposit'] ?? 0, 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari No Pembayaran / Pelanggan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik nomor / nama / bank...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Metode Pembayaran</label>
                    <select wire:model.live="metode" class="form-select form-select-sm">
                        <option value="">Semua Metode</option>
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="giro">Giro</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Pelanggan</label>
                    <select wire:model.live="pelanggan_id" class="form-select form-select-sm">
                        <option value="">Semua Pelanggan</option>
                        @foreach($pelangganList as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Periode Cepat</label>
                    <select wire:model.live="period" class="form-select form-select-sm">
                        <option value="all">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="last_7_days">7 Hari Terakhir</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="custom">Kustom Tanggal</option>
                    </select>
                </div>
                @if($period === 'custom')
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Rentang Tanggal</label>
                        <div class="input-group input-group-sm">
                            <input type="date" wire:model.live="startDate" class="form-control">
                            <span class="input-group-text">s/d</span>
                            <input type="date" wire:model.live="endDate" class="form-control">
                        </div>
                    </div>
                @endif
                <div class="col-md-auto d-flex align-items-end">
                    <button wire:click="resetFilter" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-gray-800">
                <i class="bi bi-receipt-cutoff me-2"></i>Daftar Penerimaan Pembayaran
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Per halaman:</span>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>No Pembayaran</th>
                        <th>Pelanggan</th>
                        <th>Metode</th>
                        <th>Alokasi Faktur</th>
                        <th>Catatan</th>
                        <th class="text-end">Jumlah Bayar</th>
                        <th>Petugas</th>
                        <th class="pe-3 text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $row)
                        <tr>
                            <td class="ps-3 text-muted">{{ $payments->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                            <td class="fw-bold font-monospace">{{ $row->no_pembayaran }}</td>
                            <td>{{ $row->pelanggan->nama ?? '-' }}</td>
                            <td>
                                @if($row->metode->value === 'tunai')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Tunai</span>
                                @elseif($row->metode->value === 'transfer')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Transfer</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ ucfirst($row->metode->value) }}</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($row->alokasi->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($row->alokasi as $alok)
                                            <span class="badge bg-light text-dark border font-monospace">
                                                {{ $alok->penjualan->no_penjualan ?? 'Faktur' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border">Titipan / Deposit</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $row->catatan ?: '-' }}</td>
                            <td class="text-end fw-bold text-success">
                                Rp {{ number_format($row->jumlah_bayar, 0, ',', '.') }}
                            </td>
                            <td class="small text-muted">{{ $row->creator->name ?? '-' }}</td>
                            <td class="pe-3 text-center">
                                <a href="{{ route('pembayaran.show', $row->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data pembayaran yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
