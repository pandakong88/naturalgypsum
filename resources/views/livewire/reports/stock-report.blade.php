<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Stok</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-boxes text-primary me-2"></i>Laporan Stok, Valuasi Modal &amp; Pergerakan Barang
            </h1>
            <p class="text-muted small mb-0">Informasi kuantitas fisik per gudang, konversi kemasan, deteksi dead-stock 30 hari, dan valuasi modal persediaan.</p>
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
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Item Tercatat</div>
                    <div class="h4 fw-bold text-primary mb-1">{{ number_format($summary['total_items'] ?? 0, 0, ',', '.') }} Item</div>
                    <div class="small text-muted">Akumulasi: {{ number_format($summary['total_stok'] ?? 0, 0, ',', '.') }} Unit</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Stok Rendah (&le; Min)</div>
                    <div class="h4 fw-bold text-warning mb-1">{{ number_format($summary['total_stok_rendah'] ?? 0, 0, ',', '.') }} Item</div>
                    <div class="small text-muted">Habis / Nol: <strong class="text-danger">{{ number_format($summary['total_stok_habis'] ?? 0, 0, ',', '.') }} Item</strong></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-secondary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Dead-Stock (30 Hari)</div>
                    <div class="h4 fw-bold text-secondary mb-1">{{ number_format($summary['total_dead_stock'] ?? 0, 0, ',', '.') }} Item</div>
                    <div class="small text-muted">Stok mengendap (0 terjual 30 hr)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Valuasi Modal Stok</div>
                    @if($canViewPurchasePrices)
                        <div class="h4 fw-bold text-success mb-1">Rp {{ number_format($summary['total_nilai_stok'] ?? 0, 0, ',', '.') }}</div>
                        <div class="small text-muted">Potensi Jual: Rp {{ number_format($summary['total_nilai_jual_stok'] ?? 0, 0, ',', '.') }}</div>
                    @else
                        <div class="h5 fw-bold text-muted mb-1"><i class="bi bi-shield-lock me-1"></i> Restricted</div>
                        <div class="small text-muted">Akses harga dilindungi</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse Distribution Strip -->
    @if($warehouseBreakdown->count() > 0)
        <div class="row g-3 mb-4">
            @foreach($warehouseBreakdown as $wh)
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100 {{ (string) $gudang_id === (string) $wh['gudang_id'] ? 'border border-primary bg-primary-subtle bg-opacity-10' : '' }}">
                        <div class="card-body py-2.5 px-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark small">
                                    <i class="bi bi-building text-primary me-1"></i>{{ $wh['nama_gudang'] }}
                                </span>
                                <div class="d-flex gap-1">
                                    <span class="badge bg-light text-dark border">{{ $wh['total_sku'] }} SKU</span>
                                    @if($wh['kritis_count'] > 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">{{ $wh['kritis_count'] }} Kritis</span>
                                    @endif
                                </div>
                            </div>
                            @if($canViewPurchasePrices)
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span>Modal: <strong class="text-success">Rp {{ number_format($wh['nilai_modal'], 0, ',', '.') }}</strong></span>
                                    <span>Potensi Jual: <strong class="text-dark">Rp {{ number_format($wh['nilai_jual'], 0, ',', '.') }}</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
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
                    <label class="form-label small fw-semibold">Pergerakan 30 Hari</label>
                    <select wire:model.live="pergerakan" class="form-select form-select-sm">
                        <option value="">Semua Pergerakan</option>
                        <option value="fast_moving">Aktif Terjual (Fast-Moving)</option>
                        <option value="dead_stock">Mengendap / Dead-Stock (30 Hr)</option>
                        <option value="habis">Stok Habis (0)</option>
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
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-gray-800">
                <i class="bi bi-table me-2"></i>Daftar Posisi Stok &amp; Analisis Perputaran
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
                        <th class="ps-3" style="width: 45px;">No</th>
                        <th>Kode</th>
                        <th>Nama Produk &amp; Kemasan</th>
                        <th>Kategori</th>
                        <th>Gudang</th>
                        <th class="text-end">Stok Fisik</th>
                        <th class="text-end">Min. Stok</th>
                        <th class="text-center">Terjual (30 Hr)</th>
                        @if($canViewPurchasePrices)
                            <th class="text-end">Harga Beli</th>
                            <th class="text-end">Estimasi Nilai</th>
                        @endif
                        <th class="pe-3 text-center" style="width: 100px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $index => $row)
                        @php
                            $isLow = $row->jumlah <= $row->produk->stok_minimum && $row->jumlah > 0;
                            $isOut = $row->jumlah <= 0;
                            $lineValue = $row->jumlah * $row->produk->harga_beli;
                            $qtySold30d = (float) ($sales30DaysMap[$row->produk_id] ?? 0);
                            $konversi = $row->produk->getKonversiFisik((float) $row->jumlah);
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $stocks->firstItem() + $index }}</td>
                            <td class="fw-semibold text-primary font-monospace small">{{ $row->produk->kode_katalog ?? '-' }}</td>
                            <td>
                                <div class="fw-bold">{{ $row->produk->nama ?? '-' }}</div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mt-0.5">
                                    @if(!empty($row->produk->ukuran))
                                        <span class="small text-muted">Ukuran: {{ $row->produk->ukuran }}</span>
                                    @endif
                                    @if($konversi && $konversi['has_conversion'] && $row->jumlah > 0)
                                        <span class="badge bg-info-subtle text-info-emphasis border" style="font-size: 0.68rem;">
                                            📦 {{ $konversi['badge_text'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $row->produk->kategori->nama_kategori ?? '-' }}</td>
                            <td>{{ $row->gudang->nama_gudang ?? '-' }}</td>
                            <td class="text-end fw-bold {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : 'text-dark') }}">
                                {{ number_format($row->jumlah, 0, ',', '.') }} {{ $row->produk->satuan->label() ?? '' }}
                            </td>
                            <td class="text-end text-muted small">
                                {{ number_format($row->produk->stok_minimum, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($qtySold30d > 0)
                                    <span class="badge bg-success-subtle text-success border">
                                        {{ number_format($qtySold30d, 0, ',', '.') }} {{ $row->produk->satuan->label() ?? '' }}
                                    </span>
                                @elseif($row->jumlah > 0)
                                    <span class="badge bg-secondary-subtle text-secondary border" title="Tidak ada penjualan 30 hari terakhir">
                                        0 (Mengendap)
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
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
                            <td colspan="{{ $canViewPurchasePrices ? 11 : 9 }}" class="text-center py-5 text-muted">
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
