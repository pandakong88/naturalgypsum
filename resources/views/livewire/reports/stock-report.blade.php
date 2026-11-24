<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Stok</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-boxes text-primary me-2"></i>Laporan Stok & Estimasi Nilai
            </h1>
            <p class="text-muted small mb-0">Informasi kuantitas fisik persediaan barang per gudang dan estimasi nilai persediaan.</p>
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
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Item Tercatat</div>
                    <div class="h4 fw-bold text-primary mb-1">{{ number_format($summary['total_items'] ?? 0, 0, ',', '.') }} Item</div>
                    <div class="small text-muted">Akumulasi stok: {{ number_format($summary['total_stok'] ?? 0, 3, ',', '.') }} Unit</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Stok Rendah (&le; Minimum)</div>
                    <div class="h4 fw-bold text-warning mb-1">{{ number_format($summary['total_stok_rendah'] ?? 0, 0, ',', '.') }} Item</div>
                    <div class="small text-muted">Perlu re-order ke supplier</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Stok Habis / Nol</div>
                    <div class="h4 fw-bold text-danger mb-1">{{ number_format($summary['total_stok_habis'] ?? 0, 0, ',', '.') }} Item</div>
                    <div class="small text-muted">Stok kosong di gudang</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Estimasi Nilai Stok</div>
                    @if($canViewPurchasePrices)
                        <div class="h4 fw-bold text-success mb-1">Rp {{ number_format($summary['total_nilai_stok'] ?? 0, 0, ',', '.') }}</div>
                        <div class="small text-muted">Formula: Qty &times; Harga Beli Saat Ini</div>
                    @else
                        <div class="h5 fw-bold text-muted mb-1"><i class="bi bi-shield-lock me-1"></i> Restricted</div>
                        <div class="small text-muted">Akses harga dilindungi</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Operational Disclaimer Alert -->
    @if($canViewPurchasePrices)
        <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center">
            <i class="bi bi-info-circle-fill fs-5 me-2"></i>
            <span class="small">
                <strong>Catatan Estimasi Nilai Stok:</strong> Nilai dihitung dari <code>Stok Fisik &times; Harga Beli Terakhir</code>. Ini merupakan estimasi indikatif operasional dan bukan merupakan valuasi akuntansi formal (HPP / FIFO / LIFO).
            </span>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari Kode / Nama Produk</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik nama / kode...">
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
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Kategori</label>
                    <select wire:model.live="kategori_id" class="form-select form-select-sm">
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
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Merek</label>
                    <select wire:model.live="merek_id" class="form-select form-select-sm">
                        <option value="">Semua Merek</option>
                        @foreach($merekList as $m)
                            <option value="{{ $m->id }}">{{ $m->nama_merek }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" role="switch" id="stokRendahSwitch" wire:model.live="stok_rendah">
                        <label class="form-check-label small fw-semibold" for="stokRendahSwitch">Hanya Stok Rendah</label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button wire:click="resetFilter" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-gray-800">
                <i class="bi bi-table me-2"></i>Daftar Posisi Stok
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
                        <th>Kode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Gudang</th>
                        <th class="text-end">Stok Fisik</th>
                        <th class="text-end">Min. Stok</th>
                        @if($canViewPurchasePrices)
                            <th class="text-end">Harga Beli</th>
                            <th class="text-end">Estimasi Nilai</th>
                        @endif
                        <th class="pe-3 text-center" style="width: 110px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $index => $row)
                        @php
                            $isLow = $row->jumlah <= $row->produk->stok_minimum && $row->jumlah > 0;
                            $isOut = $row->jumlah <= 0;
                            $lineValue = $row->jumlah * $row->produk->harga_beli;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $stocks->firstItem() + $index }}</td>
                            <td class="fw-semibold text-primary">{{ $row->produk->kode_katalog ?? '-' }}</td>
                            <td>
                                <div class="fw-bold">{{ $row->produk->nama ?? '-' }}</div>
                                @if(!empty($row->produk->ukuran))
                                    <div class="small text-muted">Ukuran: {{ $row->produk->ukuran }}</div>
                                @endif
                            </td>
                            <td>{{ $row->produk->kategori->nama_kategori ?? '-' }}</td>
                            <td>{{ $row->gudang->nama_gudang ?? '-' }}</td>
                            <td class="text-end fw-bold {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : 'text-dark') }}">
                                {{ number_format($row->jumlah, 3, ',', '.') }} {{ $row->produk->satuan->label() ?? '' }}
                            </td>
                            <td class="text-end text-muted small">
                                {{ number_format($row->produk->stok_minimum, 3, ',', '.') }}
                            </td>
                            @if($canViewPurchasePrices)
                                <td class="text-end small">
                                    Rp {{ number_format($row->produk->harga_beli, 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold text-success small">
                                    Rp {{ number_format($lineValue, 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="pe-3 text-center">
                                @if($isOut)
                                    <span class="badge bg-danger">Habis</span>
                                @elseif($isLow)
                                    <span class="badge bg-warning text-dark">Rendah</span>
                                @else
                                    <span class="badge bg-success">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canViewPurchasePrices ? 10 : 8 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data stok yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($stocks->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>
</div>
