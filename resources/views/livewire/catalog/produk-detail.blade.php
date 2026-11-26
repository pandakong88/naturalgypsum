<div>
    <div class="row align-items-center mb-4 g-3">
        <div class="col-12 col-sm-auto flex-grow-1">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('master.produk.index') }}" class="text-decoration-none">Master Produk</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $produk->kode_katalog }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h3 fw-bold mb-0">{{ $produk->nama }}</h1>
                @if($produk->trashed())
                    <span class="badge bg-danger">Terhapus (Soft Deleted)</span>
                @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                @endif
            </div>
        </div>
        <div class="col-12 col-sm-auto d-flex gap-2 flex-wrap">
            @can('update', $produk)
                <a href="{{ route('master.produk.index', ['edit' => $produk->id]) }}" class="btn btn-primary shadow-sm flex-fill flex-sm-grow-0">
                    <i class="bi bi-pencil-square me-1"></i> Edit Data Produk
                </a>
            @endcan
            <button type="button" wire:click="openBarcodeModal" class="btn btn-outline-dark flex-fill flex-sm-grow-0">
                <i class="bi bi-upc-scan me-1"></i> Cetak Label Rak / Barcode
            </button>
            @if(request()->query('from') === 'stok')
                <a href="{{ route('stok.index') }}" class="btn btn-outline-primary flex-fill flex-sm-grow-0">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Stok
                </a>
            @else
                <a href="{{ route('master.produk.index') }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Product Specifications -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-box-seam text-primary me-2"></i>Spesifikasi Produk
                    </h5>
                </div>
                <div class="card-body">
                    @if ($produk->foto)
                        <div class="mb-4 text-center p-3 bg-light rounded border position-relative">
                            <a href="{{ $produk->foto_url }}" target="_blank" title="Klik untuk membuka gambar ukuran penuh di tab baru" class="d-inline-block">
                                <img src="{{ $produk->foto_url }}" alt="{{ $produk->nama }}" class="img-fluid rounded shadow-sm" style="max-height: 280px; object-fit: contain; cursor: zoom-in;">
                            </a>
                            <div class="small text-muted mt-2">
                                <a href="{{ $produk->foto_url }}" target="_blank" class="text-decoration-none text-secondary">
                                    <i class="bi bi-zoom-in me-1"></i>Klik gambar untuk melihat resolusi penuh
                                </a>
                            </div>
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Kode Katalog / SKU</label>
                            <span class="fw-bold font-monospace fs-6">{{ $produk->kode_katalog }}</span>
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Satuan Dasar</label>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                {{ $produk->satuan->label() }} ({{ $produk->satuan->value }})
                            </span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Nama Lengkap Produk</label>
                            <span class="fw-semibold">{{ $produk->nama }}</span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Ukuran / Spesifikasi</label>
                            <span class="fw-medium">{{ $produk->ukuran ?: '-' }}</span>
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Kategori Induk</label>
                            @if ($produk->kategori?->parent)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="bi bi-folder-fill me-1"></i>{{ $produk->kategori->parent->nama_kategori }}
                                </span>
                            @else
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <i class="bi bi-folder me-1"></i>{{ $produk->kategori->nama_kategori ?? '-' }}
                                </span>
                            @endif
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Sub-Kategori</label>
                            @if ($produk->kategori?->parent)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-arrow-return-right me-1"></i>{{ $produk->kategori->nama_kategori }}
                                </span>
                            @else
                                <span class="badge bg-light text-muted border px-2 py-1">- (Kategori Utama)</span>
                            @endif
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Merek</label>
                            <span class="badge bg-light text-dark border px-2 py-1">{{ $produk->merek->nama_merek ?? '-' }}</span>
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Panjang Fisik per Lembar / Batang</label>
                            <span class="fw-semibold font-monospace">
                                {{ $produk->panjang_meter ? number_format((float) $produk->panjang_meter, 2, ',', '.') . ' meter / lbr' : '-' }}
                            </span>
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Kemasan Pabrik (Truk Datang)</label>
                            @if ($produk->kemasan)
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                    <i class="bi bi-box-seam me-1"></i>{{ $produk->kemasan }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                        <div class="col-6 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Isi Fisik per Kemasan</label>
                            <span class="fw-semibold">
                                @if ($produk->isi_per_kemasan)
                                    {{ number_format((float) $produk->isi_per_kemasan, 0, ',', '.') }} {{ $produk->satuan->value }} / {{ $produk->kemasan ?? 'Kemasan' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="text-muted small mb-1 d-block">Panjang per Roll (Khusus Roll)</label>
                            <span class="fw-semibold">
                                {{ $produk->panjang_per_roll ? number_format((float) $produk->panjang_per_roll, 1, ',', '.') . ' meter / roll' : '-' }}
                            </span>
                        </div>

                        <!-- Live Simulasi Konversi Fisik Gudang & POS -->
                        @php
                            $sampleQty = $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan * ($produk->panjang_meter ?: 1) : 1;
                            $konversiSample = $produk->getKonversiFisik($sampleQty);
                        @endphp
                        @if ($konversiSample['has_conversion'])
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border border-primary-subtle d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary text-white rounded p-2">
                                            <i class="bi bi-calculator fs-5"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block text-dark">Standar Konversi Fisik Gudang & POS:</span>
                                            <span class="small text-muted">Contoh konversi untuk 1 kemasan pabrik ({{ number_format($sampleQty, 0, ',', '.') }} {{ $produk->satuan->value }})</span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge bg-white text-primary border border-primary shadow-xs fs-6 py-2 px-3 font-monospace">
                                            {{ $konversiSample['badge_text'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($produk->keterangan)
                            <div class="col-12">
                                <label class="text-muted small mb-1 d-block">Keterangan / Catatan</label>
                                <p class="text-secondary mb-0 bg-light p-3 rounded border">{{ $produk->keterangan }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stok Fisik per Gudang (Real-time) -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-building text-primary me-2"></i>Stok Fisik per Gudang
                    </h5>
                    <span class="badge bg-light text-dark border">
                        Total Fisik: {{ number_format((float)$stokGudangs->sum('jumlah'), 2, ',', '.') }} {{ $produk->satuan->value }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gudang</th>
                                    <th>Lokasi</th>
                                    <th class="text-end text-nowrap">Jumlah Stok</th>
                                    <th class="text-center pe-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stokGudangs as $stokItem)
                                    @php
                                        $qty = (float)$stokItem->jumlah;
                                        $min = (float)$produk->stok_minimum;
                                        $isKritis = $qty <= $min && $qty > 0;
                                        $isKosong = $qty <= 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark text-nowrap">
                                            <i class="bi bi-geo-alt text-primary me-1"></i>{{ $stokItem->gudang?->nama_gudang ?? '-' }}
                                        </td>
                                        <td class="text-muted small">
                                            <div class="text-truncate" style="max-width: 220px;" title="{{ $stokItem->gudang?->lokasi ?? '-' }}">
                                                {{ $stokItem->gudang?->lokasi ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold font-monospace text-nowrap">
                                            {{ number_format($qty, 2, ',', '.') }} {{ $produk->satuan->value }}
                                        </td>
                                        <td class="text-center pe-3">
                                            @if($isKosong)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Habis</span>
                                            @elseif($isKritis)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Kritis</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Aman</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox d-block fs-3 mb-1 text-secondary"></i>
                                            Belum ada catatan stok di gudang manapun.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Riwayat Mutasi Stok Terbaru (Kartu Stok Mini) -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-secondary me-2"></i>Riwayat Mutasi Stok Terbaru
                    </h5>
                    @can('view_stock_mutations')
                        <a href="{{ route('stok.mutasi.index') }}" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;">
                            Lihat Semua Mutasi <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-nowrap">Tanggal</th>
                                    <th class="text-nowrap">Gudang</th>
                                    <th class="text-nowrap">Jenis Mutasi</th>
                                    <th class="text-end text-nowrap">Jumlah</th>
                                    <th class="text-nowrap">Referensi</th>
                                    <th class="pe-3 text-nowrap">Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mutasiTerakhir as $mutasi)
                                    @php
                                        $isPlus = (float)$mutasi->jumlah > 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-3 text-muted text-nowrap">
                                            {{ $mutasi->tanggal ? $mutasi->tanggal->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td class="fw-semibold text-nowrap">
                                            {{ $mutasi->gudang?->nama_gudang ?? '-' }}
                                        </td>
                                        <td class="text-nowrap">
                                            <span class="badge {{ $mutasi->jenis?->badgeClass() ?? 'bg-light text-dark border' }}">
                                                {{ $mutasi->jenis?->label() ?? $mutasi->jenis }}
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-nowrap {{ $isPlus ? 'text-success' : 'text-danger' }}">
                                            {{ $isPlus ? '+' : '' }}{{ number_format((float)$mutasi->jumlah, 2, ',', '.') }}
                                        </td>
                                        <td class="text-nowrap">
                                            <span class="font-monospace small text-muted">{{ $mutasi->referensi ?? '-' }}</span>
                                        </td>
                                        <td class="pe-3 text-muted small text-nowrap">
                                            {{ $mutasi->user?->name ?? 'Sistem' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-arrow-left-right d-block fs-3 mb-1 text-secondary"></i>
                                            Belum ada riwayat mutasi stok tercatat untuk produk ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing & Audit Trail Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Pricing Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-tag text-success me-2"></i>Informasi Finansial & Stok
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="text-muted d-block">Harga Jual Umum</span>
                                <small class="text-secondary">Retail / Tunai</small>
                            </div>
                            <span class="fw-bold fs-5 text-success">Rp {{ number_format((float)$produk->harga_jual, 0, ',', '.') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="text-muted d-block">Harga Aplikator</span>
                                <small class="text-secondary">Tukang / Pemborong</small>
                            </div>
                            <div class="text-end">
                                @if ($produk->harga_aplikator)
                                    <span class="fw-semibold text-primary d-block">Rp {{ number_format((float)$produk->harga_aplikator, 0, ',', '.') }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.68rem;">Manual Tetap</span>
                                @elseif ($pemborongDiscount['has_discount'])
                                    <span class="fw-semibold text-primary d-block">Rp {{ number_format((float)$pemborongDiscount['harga_setelah_diskon'], 0, ',', '.') }}</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;" title="{{ $pemborongDiscount['aturan_nama'] }}">
                                        Diskon Otomatis {{ (float)$pemborongDiscount['nilai_diskon'] }}%
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border">Mengikuti Harga Umum</span>
                                @endif
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="text-muted d-block">Harga Grosir</span>
                                <small class="text-secondary">Langganan Toko</small>
                            </div>
                            <div class="text-end">
                                @if ($produk->harga_grosir)
                                    <span class="fw-semibold text-primary d-block">Rp {{ number_format((float)$produk->harga_grosir, 0, ',', '.') }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.68rem;">Manual Tetap</span>
                                @elseif ($langgananDiscount['has_discount'])
                                    <span class="fw-semibold text-primary d-block">Rp {{ number_format((float)$langgananDiscount['harga_setelah_diskon'], 0, ',', '.') }}</span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;" title="{{ $langgananDiscount['aturan_nama'] }}">
                                        Diskon Otomatis {{ (float)$langgananDiscount['nilai_diskon'] }}%
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border">Mengikuti Harga Umum</span>
                                @endif
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="text-muted d-block">Harga Beli / Pokok</span>
                                <small class="text-secondary">HPP per {{ $produk->satuan->value }}</small>
                            </div>
                            @can('view_purchase_prices')
                                <span class="fw-semibold text-dark">Rp {{ number_format((float)$produk->harga_beli, 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted fst-italic"><i class="bi bi-lock-fill me-1"></i>Akses Terbatas</span>
                            @endcan
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Ambang Batas Minimum</span>
                            <span class="fw-semibold badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                {{ number_format((float)$produk->stok_minimum, 2, ',', '.') }} {{ $produk->satuan->value }}
                            </span>
                        </li>
                    </ul>

                    <!-- Info Diskon Terpusat Card Footer -->
                    <div class="mt-3 p-3 bg-light rounded-3 border border-secondary-subtle">
                        <div class="d-flex align-items-center gap-1 text-primary fw-semibold mb-1 small">
                            <i class="bi bi-tag-fill me-1"></i>
                            <span>Aturan Diskon Terpusat</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size: 0.725rem; line-height: 1.35;">
                            Harga khusus Mitra Pemborong dan Toko Grosir dikelola terpusat via menu Diskon. Kasir otomatis menghitung potongan harga saat memilih pelanggan.
                        </p>
                        @can('view_discounts')
                            <a href="{{ route('master.diskon.index') }}" class="btn btn-sm btn-outline-primary w-100 py-1 fw-semibold" style="font-size: 0.75rem;">
                                <i class="bi bi-sliders me-1"></i>Kelola Aturan Diskon
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Audit Trail Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-secondary me-2"></i>Audit Trail
                    </h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-3">
                            <span class="text-muted d-block">Dibuat Pada:</span>
                            <span class="fw-semibold">{{ $produk->created_at ? $produk->created_at->translatedFormat('d F Y, H:i') : '-' }}</span>
                            @if($produk->creator)
                                <div class="text-muted">oleh <span class="fw-medium text-dark">{{ $produk->creator->name }}</span></div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block">Terakhir Diperbarui:</span>
                            <span class="fw-semibold">{{ $produk->updated_at ? $produk->updated_at->translatedFormat('d F Y, H:i') : '-' }}</span>
                            @if($produk->updater)
                                <div class="text-muted">oleh <span class="fw-medium text-dark">{{ $produk->updater->name }}</span></div>
                            @endif
                        </div>
                        @if($produk->deleted_at)
                            <div class="p-2 bg-danger-subtle text-danger rounded border border-danger-subtle">
                                <span class="d-block fw-bold">Dihapus Pada:</span>
                                <span>{{ $produk->deleted_at->translatedFormat('d F Y, H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Stylesheet for Shelf Label / Barcode -->
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #printable-barcode-area, #printable-barcode-area * {
                visibility: visible !important;
            }
            #printable-barcode-area {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- Barcode & Shelf Tag Print Modal -->
    @if ($isBarcodeModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-light border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-dark text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-upc-scan fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">Cetak Label Rak & Barcode</h5>
                                <small class="text-muted">Pratinjau label harga rak pajangan untuk ditempel pada rak etalase toko.</small>
                            </div>
                        </div>
                        <button type="button" wire:click="closeBarcodeModal" class="btn-close" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-2 p-sm-4 bg-light-subtle">
                        <div class="row g-3 mb-3 no-print">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small mb-1">Jumlah Label yang Dicetak</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-files"></i></span>
                                    <select wire:model.live="barcodeQty" class="form-select">
                                        <option value="1">1 Lembar Label</option>
                                        <option value="2">2 Lembar Label</option>
                                        <option value="4">4 Lembar Label</option>
                                        <option value="8">8 Lembar Label</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Ukuran label pas untuk rak gondola standar toko (7 x 4 cm) atau stiker barcode.
                                </small>
                            </div>
                        </div>

                        <!-- Printable Area -->
                        <div id="printable-barcode-area" class="p-2 p-sm-3 bg-white border rounded shadow-xs overflow-x-auto">
                            <div class="d-flex flex-wrap gap-3 justify-content-center">
                                @for ($i = 0; $i < $barcodeQty; $i++)
                                    <div class="border border-dark rounded-2 bg-white text-dark d-flex flex-column justify-content-between position-relative shadow-xs"
                                         style="width: 295px; height: 185px; padding: 10px 14px; box-sizing: border-box; overflow: hidden; font-family: system-ui, -apple-system, sans-serif;">
                                        <!-- Header Store & Kategori -->
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-1" style="height: 22px;">
                                            <span class="fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">NATURAL GYPSUM & PVC</span>
                                            <span class="badge bg-light text-dark border px-2 py-0 text-truncate" style="font-size: 0.65rem; max-width: 110px;">
                                                {{ $produk->kategori?->nama_kategori ?? 'UMUM' }}
                                            </span>
                                        </div>

                                        <!-- Product Name & Spec -->
                                        <div style="min-height: 38px;">
                                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem; line-height: 1.25;" title="{{ $produk->nama }}">
                                                {{ $produk->nama }}
                                            </div>
                                            <div class="text-muted text-truncate" style="font-size: 0.7rem; line-height: 1.2;">
                                                {{ $produk->ukuran ?: 'Standar' }} &bull; {{ $produk->merek?->nama_merek ?? 'Generic' }}
                                            </div>
                                        </div>

                                        <!-- Real Scannable Barcode SVG & Code -->
                                        <div class="text-center my-0 py-1" style="min-height: 48px;">
                                            {!! app(\App\Domains\Catalog\Services\BarcodeService::class)->generateCode39Svg($produk->kode_katalog, 26) !!}
                                            <div class="font-monospace fw-bold text-dark text-center" style="font-size: 0.72rem; letter-spacing: 2px; margin-top: 2px;">
                                                *{{ $produk->kode_katalog }}*
                                            </div>
                                        </div>

                                        <!-- Price Footer -->
                                        <div class="d-flex justify-content-between align-items-center border-top pt-1 mt-1 bg-white" style="height: 32px;">
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                                HARGA RESMI
                                            </span>
                                            <div class="text-end">
                                                <span class="fw-bold text-danger font-monospace" style="font-size: 1.18rem; font-weight: 800; line-height: 1;">
                                                    Rp {{ number_format($produk->harga_jual, 0, ',', '.') }}
                                                </span>
                                                <small class="text-muted fw-normal" style="font-size: 0.68rem;">/{{ $produk->satuan->value }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top py-2 px-3 px-sm-4 d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
                        <button type="button" wire:click="closeBarcodeModal" class="btn btn-outline-secondary">
                            Tutup
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-dark px-3 px-sm-4 fw-semibold shadow-sm">
                            <i class="bi bi-printer me-1"></i>Cetak Sekarang (Print)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
