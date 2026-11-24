<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Retur</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-arrow-return-left text-danger me-2"></i>Laporan Retur (Supplier & Pelanggan)
            </h1>
            <p class="text-muted small mb-0">Rekapitulasi data retur ke supplier dan penerimaan retur dari pelanggan.</p>
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
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Dokumen Retur Supplier</div>
                    <div class="h4 fw-bold text-warning mb-1">{{ number_format($summary['dokumen_supplier'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Transaksi retur ke supplier</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Qty Retur Supplier</div>
                    <div class="h4 fw-bold text-warning mb-1">{{ number_format($summary['total_qty_supplier'] ?? 0, 3, ',', '.') }}</div>
                    <div class="small text-muted">Unit barang keluar retur</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Dokumen Retur Pelanggan</div>
                    <div class="h4 fw-bold text-info mb-1">{{ number_format($summary['dokumen_pelanggan'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Transaksi retur dari pelanggan</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Qty Retur Pelanggan</div>
                    <div class="h4 fw-bold text-info mb-1">{{ number_format($summary['total_qty_pelanggan'] ?? 0, 3, ',', '.') }}</div>
                    <div class="small text-muted">Unit barang masuk retur</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link fw-semibold {{ $type === 'supplier' ? 'active' : '' }}" wire:click="$set('type', 'supplier')">
                <i class="bi bi-truck me-1"></i> Retur Supplier (Pembelian)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold {{ $type === 'pelanggan' ? 'active' : '' }}" wire:click="$set('type', 'pelanggan')">
                <i class="bi bi-person-badge me-1"></i> Retur Pelanggan (Penjualan)
            </button>
        </li>
    </ul>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari No Retur / Nomor Referensi</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik nomor...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Gudang</label>
                    <select wire:model.live="gudang_id" class="form-select form-select-sm">
                        <option value="">Semua Gudang</option>
                        @foreach($gudangList as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                @if($type === 'supplier')
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Supplier</label>
                        <select wire:model.live="supplier_id" class="form-select form-select-sm">
                            <option value="">Semua Supplier</option>
                            @foreach($supplierList as $s)
                                <option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Pelanggan</label>
                        <select wire:model.live="pelanggan_id" class="form-select form-select-sm">
                            <option value="">Semua Pelanggan</option>
                            @foreach($pelangganList as $p)
                                <option value="{{ $p->id }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
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
                <i class="bi bi-list-check me-2"></i>
                Daftar Retur - {{ $type === 'supplier' ? 'Retur ke Supplier' : 'Retur dari Pelanggan' }}
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
                        <th>No Retur</th>
                        <th>{{ $type === 'supplier' ? 'Supplier' : 'Pelanggan' }}</th>
                        <th>Gudang</th>
                        <th>Referensi Transaksi</th>
                        <th>Rincian Barang (Produk & Qty)</th>
                        <th>Alasan / Catatan</th>
                        <th class="pe-3 text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $index => $row)
                        <tr>
                            <td class="ps-3 text-muted">{{ $returns->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                            <td class="fw-bold {{ $type === 'supplier' ? 'text-warning' : 'text-info' }}">
                                {{ $row->no_retur }}
                            </td>
                            <td>
                                @if($type === 'supplier')
                                    {{ $row->supplier->nama_supplier ?? '-' }}
                                @else
                                    {{ $row->pelanggan->nama ?? '-' }}
                                @endif
                            </td>
                            <td>{{ $row->gudang->nama_gudang ?? '-' }}</td>
                            <td class="small">
                                @if($type === 'supplier')
                                    {{ $row->pembelian->no_pembelian ?? '-' }}
                                @else
                                    {{ $row->penjualan->no_penjualan ?? '-' }}
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    <span class="fw-semibold">{{ $row->produk->nama ?? '-' }}</span>
                                    <span class="badge bg-warning-subtle text-warning border ms-1">
                                        {{ number_format($row->jumlah, 3, ',', '.') }} {{ $row->produk->satuan->label() ?? '' }}
                                    </span>
                                </div>
                            </td>
                            <td class="small text-muted">{{ $row->alasan ?? $row->keterangan ?? '-' }}</td>
                            <td class="pe-3 text-center">
                                @if($type === 'supplier')
                                    <a href="{{ route('retur-supplier.show', $row->id) }}" class="btn btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @else
                                    <a href="{{ route('retur-pelanggan.show', $row->id) }}" class="btn btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data retur yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</div>
