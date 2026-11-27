<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Produksi</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-gear-wide-connected text-primary me-2"></i>Laporan Produksi
            </h1>
            <p class="text-muted small mb-0">Rekapitulasi batch produksi barang jadi (output-only tracking) per gudang dan produk.</p>
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
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Batch Produksi</div>
                    <div class="h5 fw-bold text-primary mb-1 fs-6 fs-sm-4">{{ number_format($summary['total_batch'] ?? 0, 0, ',', '.') }} Batch</div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Dokumen produksi tercatat</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Unit Output</div>
                    <div class="h5 fw-bold text-success mb-1 fs-6 fs-sm-4">{{ number_format($summary['total_qty_dihasilkan'] ?? 0, 2, ',', '.') }} Unit</div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Output barang jadi</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-3 mb-md-4">
        <div class="card-body p-2.5 p-md-3">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Cari No Produksi / Catatan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik nomor / catatan...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Gudang Penerima</label>
                    <select wire:model.live="gudang_id" class="form-select form-select-sm">
                        <option value="">Semua Gudang</option>
                        @foreach($gudangList as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Produk Jadi</label>
                    <select wire:model.live="produk_id" class="form-select form-select-sm">
                        <option value="">Semua Produk</option>
                        @foreach($produkList as $p)
                            <option value="{{ $p->id }}">{{ $p->kode_katalog }} - {{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-8 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Periode Cepat</label>
                    <select wire:model.live="period" class="form-select form-select-sm">
                        <option value="today">Hari Ini</option>
                        <option value="last_7_days">7 Hari Terakhir</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="custom">Kustom Tanggal</option>
                    </select>
                </div>
                @if($period === 'custom')
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Rentang Tanggal</label>
                        <div class="input-group input-group-sm">
                            <input type="date" wire:model.live="startDate" class="form-control">
                            <span class="input-group-text">s/d</span>
                            <input type="date" wire:model.live="endDate" class="form-control">
                        </div>
                    </div>
                @endif
                <div class="col-4 col-md-auto d-flex align-items-end">
                    <button wire:click="resetFilter" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-2.5 py-md-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-gray-800">
                <i class="bi bi-list-check me-2"></i>Daftar Batch Produksi
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Baris:</span>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 75px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <!-- Mobile Card List View (< 768px) -->
        <div class="d-md-none">
            @forelse($productions as $row)
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1.5">
                        <div>
                            <a href="{{ route('produksi.show', $row->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                {{ $row->no_produksi }}
                            </a>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }} &bull; {{ $row->creator->name ?? '-' }}
                            </div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.7rem;">
                            <i class="bi bi-building me-1"></i>{{ $row->gudang->nama_gudang ?? '-' }}
                        </span>
                    </div>

                    <div class="p-2 rounded-3 bg-light border my-2">
                        @foreach($row->details as $d)
                            <div class="d-flex justify-content-between align-items-center py-0.5 small" style="font-size: 0.76rem;">
                                <span class="fw-semibold text-dark text-truncate pe-2">{{ $d->produk->nama ?? '-' }}</span>
                                <span class="badge bg-success-subtle text-success border flex-shrink-0 font-monospace">
                                    +{{ number_format($d->jumlah, 2, ',', '.') }} {{ $d->produk->satuan->label() ?? '' }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted text-truncate small" style="font-size: 0.72rem;">{{ $row->keterangan ?? '-' }}</span>
                        <a href="{{ route('produksi.show', $row->id) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2 flex-shrink-0">
                            <i class="bi bi-eye me-1"></i>Detail
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 px-3 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Tidak ada data produksi yang sesuai dengan kriteria filter.
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (>= 768px) -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>No Produksi</th>
                        <th>Gudang Output</th>
                        <th>Rincian Output (Produk & Qty)</th>
                        <th>Catatan</th>
                        <th>Petugas</th>
                        <th class="pe-3 text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productions as $index => $row)
                        <tr>
                            <td class="ps-3 text-muted">{{ $productions->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                            <td class="fw-bold text-primary">{{ $row->no_produksi }}</td>
                            <td>{{ $row->gudang->nama_gudang ?? '-' }}</td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @foreach($row->details as $d)
                                        <div class="small">
                                            <span class="fw-semibold">{{ $d->produk->nama ?? '-' }}</span>
                                            <span class="badge bg-success-subtle text-success border ms-1">
                                                {{ number_format($d->jumlah, 3, ',', '.') }} {{ $d->produk->satuan->label() ?? '' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="small text-muted">{{ $row->keterangan ?? '-' }}</td>
                            <td class="small text-muted">{{ $row->creator->name ?? '-' }}</td>
                            <td class="pe-3 text-center">
                                <a href="{{ route('produksi.show', $row->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data produksi yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($productions->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $productions->links() }}
            </div>
        @endif
    </div>
</div>
