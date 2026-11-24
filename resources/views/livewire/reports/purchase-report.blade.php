<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Pembelian</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-truck text-danger me-2"></i>Laporan Pembelian
            </h1>
            <p class="text-muted small mb-0">Rekapitulasi transaksi pembelian dan pengadaan barang dari supplier.</p>
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
        <div class="col-md-6">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Nilai Pembelian</div>
                    @if($canViewPurchasePrices)
                        <div class="h4 fw-bold text-danger mb-1">Rp {{ number_format($summary['total_pembelian'] ?? 0, 0, ',', '.') }}</div>
                        <div class="small text-muted">Akumulasi nilai transaksi</div>
                    @else
                        <div class="h5 fw-bold text-muted mb-1"><i class="bi bi-shield-lock me-1"></i> Restricted</div>
                        <div class="small text-muted">Akses harga dilindungi</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Transaksi Pengadaan</div>
                    <div class="h4 fw-bold text-primary mb-1">{{ number_format($summary['total_transaksi'] ?? 0, 0, ',', '.') }} Transaksi</div>
                    <div class="small text-muted">Dokumen PO tercatat</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari No PO / Supplier</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik no PO / supplier...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Supplier</label>
                    <select wire:model.live="supplier_id" class="form-select form-select-sm">
                        <option value="">Semua Supplier</option>
                        @foreach($supplierList as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Gudang Penerima</label>
                    <select wire:model.live="gudang_id" class="form-select form-select-sm">
                        <option value="">Semua Gudang</option>
                        @foreach($gudangList as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Periode Cepat</label>
                    <select wire:model.live="period" class="form-select form-select-sm">
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
                <i class="bi bi-receipt me-2"></i>Daftar Dokumen Pembelian
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
                        <th>No Pembelian</th>
                        <th>Supplier</th>
                        <th>Gudang Penerima</th>
                        <th class="text-center">Status</th>
                        @if($canViewPurchasePrices)
                            <th class="text-end">Total Pembelian</th>
                        @endif
                        <th>Petugas</th>
                        <th class="pe-3 text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $index => $row)
                        <tr>
                            <td class="ps-3 text-muted">{{ $purchases->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                            <td class="fw-bold text-danger">{{ $row->no_pembelian }}</td>
                            <td>{{ $row->supplier->nama_supplier ?? '-' }}</td>
                            <td>{{ $row->details->pluck('gudang.nama_gudang')->filter()->unique()->join(', ') ?: '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-success">Selesai</span>
                            </td>
                            @if($canViewPurchasePrices)
                                <td class="text-end fw-bold text-danger">
                                    Rp {{ number_format($row->total, 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="small text-muted">{{ $row->creator->name ?? '-' }}</td>
                            <td class="pe-3 text-center">
                                <a href="{{ route('pembelian.show', $row->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canViewPurchasePrices ? 9 : 8 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data pembelian yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
