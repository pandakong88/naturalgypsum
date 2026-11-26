<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h4 class="fw-bold mb-0">Pembelian #{{ $pembelian->no_pembelian }}</h4>
                <span class="badge {{ $pembelian->status?->badgeClass() ?? 'bg-secondary' }}">
                    {{ $pembelian->status?->label() ?? ucfirst($pembelian->status?->value ?? 'Diterima') }}
                </span>
                <span class="badge {{ $pembelian->status_pembayaran?->badgeClass() ?? 'bg-secondary' }}">
                    {{ $pembelian->status_pembayaran?->label() ?? ucfirst($pembelian->status_pembayaran?->value ?? 'Lunas') }}
                </span>
            </div>
            <p class="text-muted small mb-0">
                Dibuat pada {{ $pembelian->created_at->format('d/m/Y H:i') }} oleh {{ $pembelian->user->name ?? ($pembelian->creator->name ?? 'Sistem') }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
            <a href="{{ route('pembelian.index') }}" class="btn btn-outline-secondary flex-fill flex-md-grow-0">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            @if ($pembelian->isCancelled())
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 align-self-center px-3 py-2">
                    <i class="bi bi-x-circle me-1"></i>Transaksi Dibatalkan
                </span>
                <a href="{{ route('pembelian.print.po', $pembelian->id) }}" target="_blank" class="btn btn-outline-secondary flex-fill flex-md-grow-0" title="Cetak Arsip PO">
                    <i class="bi bi-printer me-1"></i>Cetak PO
                </a>
            @elseif ($pembelian->isOrdered())
                @can('update', $pembelian)
                    <button wire:click="openReceiveModal" class="btn btn-success shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Terima Barang
                    </button>
                    <a href="{{ route('pembelian.edit', $pembelian->id) }}" class="btn btn-warning shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-pencil me-1"></i>Edit PO
                    </a>
                @endcan

                @can('cancel', $pembelian)
                    <button type="button" wire:click="openCancelModal" class="btn btn-outline-danger flex-fill flex-md-grow-0">
                        <i class="bi bi-x-circle me-1"></i>Batalkan PO
                    </button>
                @endcan

                @can('delete', $pembelian)
                    @if ((float) $pembelian->nominal_bayar == 0 && $pembelian->pembayaran->isEmpty())
                        <button type="button" wire:click="openDeleteModal" class="btn btn-outline-danger flex-fill flex-md-grow-0" title="Hapus PO yang belum ada pembayaran">
                            <i class="bi bi-trash me-1"></i>Hapus PO
                        </button>
                    @endif
                @endcan

                <a href="{{ route('pembelian.print.po', $pembelian->id) }}" target="_blank" class="btn btn-outline-primary flex-fill flex-md-grow-0" title="Cetak Surat Pesanan PO">
                    <i class="bi bi-printer me-1"></i>Cetak PO
                </a>
            @else
                @can('update', $pembelian)
                    <button type="button" wire:click="openEditShippingModal" class="btn btn-outline-info flex-fill flex-md-grow-0">
                        <i class="bi bi-pencil-square me-1"></i>Edit Logistik
                    </button>
                @endcan

                @can('cancel', $pembelian)
                    <button type="button" wire:click="openCancelModal" class="btn btn-outline-danger flex-fill flex-md-grow-0">
                        <i class="bi bi-x-circle me-1"></i>Batalkan
                    </button>
                @endcan

                <a href="{{ route('pembelian.print.faktur', $pembelian->id) }}" target="_blank" class="btn btn-primary flex-fill flex-md-grow-0" title="Cetak Bukti Penerimaan Barang">
                    <i class="bi bi-printer me-1"></i>Cetak Bukti
                </a>
                <a href="{{ route('pembelian.print.po', $pembelian->id) }}" target="_blank" class="btn btn-outline-secondary flex-fill flex-md-grow-0" title="Cetak Surat Pesanan PO">
                    <i class="bi bi-file-earmark-text me-1"></i>Cetak PO
                </a>
            @endif
        </div>
    </div>

    <!-- Flash message -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
            <span>{{ session('message') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Cancellation Banner if Cancelled -->
    @if ($pembelian->isCancelled())
        <div class="alert alert-danger border-danger-subtle shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-x-octagon-fill fs-3 text-danger me-3"></i>
            <div>
                <h6 class="fw-bold mb-0 text-danger">Transaksi Pembelian Ini Telah Dibatalkan</h6>
                <small class="text-muted">{{ $pembelian->keterangan ?: 'Transaksi ini telah dibatalkan. Seluruh stok gudang dan kewajiban hutang telah disesuaikan.' }}</small>
            </div>
        </div>
    @endif

    <!-- Workflow Banner if Ordered -->
    @if ($pembelian->isOrdered())
        <div class="alert alert-warning border-warning-subtle shadow-sm mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-3 text-warning me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Status: Pesanan Pembelian (PO) - Menunggu Kedatangan Armada / Surat Jalan</h6>
                    <small class="text-muted">Kuantitas produk belum dimasukkan ke kartu stok gudang. Klik tombol "Terima Barang" saat truk ekspedisi tiba membawa Surat Jalan fisik.</small>
                </div>
            </div>
            @can('update', $pembelian)
                <button wire:click="openReceiveModal" class="btn btn-sm btn-success fw-bold px-3">
                    <i class="bi bi-check-lg me-1"></i>Proses Penerimaan Fisik
                </button>
            @endcan
        </div>
    @endif

    <!-- Summary Info Cards -->
    <div class="row g-4 mb-4">
        <!-- Supplier Information -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-primary-subtle text-primary rounded me-3">
                            <i class="bi bi-buildings fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Informasi Supplier</span>
                            <h6 class="fw-bold mb-0">{{ $pembelian->supplier->nama_supplier ?? '-' }}</h6>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $pembelian->supplier->kontak ?? 'Tidak ada kontak' }}</li>
                        @if ($pembelian->supplier?->alamat)
                            <li class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $pembelian->supplier->alamat }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <!-- Logistics & Documents Information -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-info-subtle text-info rounded me-3">
                                <i class="bi bi-truck fs-4"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Dokumen & Logistik</span>
                                <h6 class="fw-bold mb-0">Tanggal PO: {{ $pembelian->tanggal->format('d/m/Y') }}</h6>
                            </div>
                        </div>
                        @can('update', $pembelian)
                            @if (!$pembelian->isCancelled())
                                <button type="button" wire:click="openEditShippingModal" class="btn btn-outline-info btn-sm py-1 px-2" title="Edit Dokumen Logistik">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>
                            @endif
                        @endcan
                    </div>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1 d-flex justify-content-between">
                            <span class="text-muted">No. Faktur Supplier:</span>
                            <span class="fw-bold font-monospace">{{ $pembelian->no_faktur_supplier ?: '-' }}</span>
                        </li>
                        <li class="mb-1 d-flex justify-content-between">
                            <span class="text-muted">No. Surat Jalan:</span>
                            <span class="fw-bold font-monospace">{{ $pembelian->no_surat_jalan ?: '-' }}</span>
                        </li>
                        <li class="mb-1 d-flex justify-content-between">
                            <span class="text-muted">No. Polisi / Plat Truk:</span>
                            <span class="fw-bold font-monospace">{{ $pembelian->no_polisi ?: '-' }}</span>
                        </li>
                        <li class="mb-1 d-flex justify-content-between">
                            <span class="text-muted">No. Kontainer:</span>
                            <span class="font-monospace">{{ $pembelian->no_kontainer ?: '-' }}</span>
                        </li>
                        @if ($pembelian->tanggal_diterima)
                            <li class="mb-1 d-flex justify-content-between">
                                <span class="text-muted">Diterima Pada:</span>
                                <span class="fw-semibold text-success">{{ $pembelian->tanggal_diterima->format('d/m/Y') }} ({{ $pembelian->penerima->name ?? 'Gudang' }})</span>
                            </li>
                        @endif
                        @if ($pembelian->keterangan)
                            <li class="mt-2 pt-1 border-top small text-muted fst-italic">
                                Catatan: {{ $pembelian->keterangan }}
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <!-- Financial & Debt Summary -->
        <div class="col-md-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 {{ $pembelian->isTempo() ? 'bg-danger text-white' : ($pembelian->isLunas() ? 'bg-primary text-white' : 'bg-warning text-dark') }}">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-uppercase small fw-semibold opacity-75">Ringkasan Pembayaran</span>
                            <span class="badge {{ $pembelian->status_pembayaran?->badgeClass() ?? 'bg-light text-dark' }}">
                                {{ $pembelian->status_pembayaran?->label() }}
                            </span>
                        </div>
                        <h4 class="fw-bold mb-1">
                            @can('view_purchase_prices')
                                Rp {{ number_format((float) $pembelian->total, 2, ',', '.') }}
                            @else
                                Rp ***
                            @endcan
                        </h4>
                        @can('view_purchase_prices')
                            <div class="small opacity-75 mt-2">
                                <div>Dibayar: <strong>Rp {{ number_format((float) $pembelian->nominal_bayar, 2, ',', '.') }}</strong></div>
                                <div>Sisa Hutang: <strong class="fs-6">Rp {{ number_format((float) $pembelian->sisa_hutang, 2, ',', '.') }}</strong></div>
                                @if ($pembelian->tanggal_jatuh_tempo && !$pembelian->isLunas())
                                    <div class="mt-1"><i class="bi bi-clock me-1"></i>Jatuh Tempo: <strong>{{ $pembelian->tanggal_jatuh_tempo->format('d/m/Y') }}</strong></div>
                                @endif
                            </div>
                        @endcan
                    </div>
                    @can('view_purchase_prices')
                        @if ((float) $pembelian->sisa_hutang > 0)
                            <div class="mt-3 pt-2 border-top border-white-50">
                                @can('update', $pembelian)
                                    <button wire:click="openPaymentModal" class="btn btn-sm btn-light w-100 fw-bold shadow-sm">
                                        <i class="bi bi-cash-coin me-1"></i>Catat Pembayaran Hutang
                                    </button>
                                @endcan
                            </div>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="card-title fw-bold mb-0 text-primary">
                <i class="bi bi-list-check me-1"></i> Rincian Barang & Distribusi Gudang ({{ $pembelian->details->count() }} Line Item)
            </h6>
            @if ($pembelian->isOrdered())
                <span class="badge bg-warning-subtle text-warning-emphasis">
                    <i class="bi bi-hourglass-split me-1"></i>Status Item: Dipesan
                </span>
            @else
                <span class="badge bg-success-subtle text-success">
                    <i class="bi bi-check2-circle me-1"></i>Status Item: Masuk Gudang
                </span>
            @endif
        </div>
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                <div class="list-group list-group-flush">
                    @foreach ($pembelian->details as $index => $detail)
                        @php
                            $konv = $detail->produk?->getKonversiFisik((float) $detail->jumlah);
                        @endphp
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <div class="min-w-0">
                                    <span class="text-muted small">#{{ $index + 1 }}</span>
                                    <div class="fw-bold text-dark">{{ $detail->produk->nama ?? '-' }}</div>
                                    <small class="text-muted font-monospace">{{ $detail->produk->kode_katalog ?? 'Tanpa SKU' }}</small>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle flex-shrink-0">
                                    <i class="bi bi-building me-1"></i>{{ $detail->gudang->nama_gudang ?? '-' }}
                                </span>
                            </div>

                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <span class="badge bg-light text-dark border">
                                    {{ $detail->produk->kategori->nama_kategori ?? '-' }}
                                </span>
                                @if ($detail->produk?->merek)
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        {{ $detail->produk->merek->nama_merek }}
                                    </span>
                                @endif
                                @if ($detail->qty_lembar && $detail->panjang_meter)
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace">
                                        {{ (int) $detail->qty_lembar }} lbr &times; {{ (float) $detail->panjang_meter }} m
                                    </span>
                                @endif
                                @if ($konv && $konv['has_conversion'])
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace">
                                        {{ $konv['badge_text'] }}
                                    </span>
                                @endif
                            </div>

                            <div class="bg-light rounded-2 p-2 border d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Kuantitas & Harga Satuan</span>
                                    <span class="fw-bold text-dark">
                                        {{ number_format((float) $detail->jumlah, 2, ',', '.') }} {{ $detail->produk->satuan->label() ?? '' }}
                                    </span>
                                    @can('view_purchase_prices')
                                        <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">
                                            @ Rp {{ number_format((float) $detail->harga_beli_satuan, 0, ',', '.') }}
                                        </small>
                                    @endcan
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Subtotal</span>
                                    @can('view_purchase_prices')
                                        <span class="fw-bold text-primary font-monospace">
                                            Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-muted fst-italic">Rp ***</span>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">Grand Total Pembelian:</span>
                    <span class="fw-bold fs-6 text-primary font-monospace">
                        @can('view_purchase_prices')
                            Rp {{ number_format((float) $pembelian->total, 2, ',', '.') }}
                        @else
                            Rp ***
                        @endcan
                    </span>
                </div>
            </div>

            <!-- Desktop & Tablet Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 45px;">#</th>
                            <th>Produk & SKU</th>
                            <th>Kategori & Merek</th>
                            <th>Gudang Tujuan</th>
                            <th class="text-end">Rincian Fisik / Kemasan</th>
                            <th class="text-end">Total Kuantitas</th>
                            <th class="text-end">Harga Beli Satuan</th>
                            <th class="text-end pe-4">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pembelian->details as $index => $detail)
                            <tr>
                                <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $detail->produk->nama ?? '-' }}</div>
                                    <small class="text-muted font-monospace">{{ $detail->produk->kode_katalog ?? 'Tanpa SKU' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border me-1">
                                        {{ $detail->produk->kategori->nama_kategori ?? '-' }}
                                    </span>
                                    <small class="text-muted">{{ $detail->produk->merek->nama_merek ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-building me-1"></i>{{ $detail->gudang->nama_gudang ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @php
                                        $konv = $detail->produk?->getKonversiFisik((float) $detail->jumlah);
                                    @endphp
                                    @if ($detail->qty_lembar && $detail->panjang_meter)
                                        <div>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace">
                                                {{ (int) $detail->qty_lembar }} lbr &times; {{ (float) $detail->panjang_meter }} m
                                            </span>
                                        </div>
                                        @if ($konv && !empty($konv['jumlah_kemasan_utuh']))
                                            <small class="text-muted d-block mt-1 font-monospace" style="font-size: 0.72rem;">
                                                {{ $konv['badge_text'] }}
                                            </small>
                                        @endif
                                    @elseif ($konv && $konv['has_conversion'])
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace">
                                            {{ $konv['badge_text'] }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format((float) $detail->jumlah, 2, ',', '.') }}
                                    <span class="text-muted fw-normal small">{{ $detail->produk->satuan->label() ?? '' }}</span>
                                </td>
                                <td class="text-end">
                                    @can('view_purchase_prices')
                                        <span class="fw-semibold">Rp {{ number_format((float) $detail->harga_beli_satuan, 2, ',', '.') }}</span>
                                        @if ($detail->diskon_satuan > 0)
                                            <div class="small text-danger" style="font-size: 0.72rem;">
                                                Bruto: Rp {{ number_format((float) $detail->harga_bruto, 0, ',', '.') }} (Disc: -{{ number_format((float) $detail->diskon_satuan, 0, ',', '.') }})
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted fst-italic">Rp ***</span>
                                    @endcan
                                </td>
                                <td class="text-end pe-4 fw-bold">
                                    @can('view_purchase_prices')
                                        <span>Rp {{ number_format((float) $detail->subtotal, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Rp ***</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="7" class="text-end fw-bold fs-6">Grand Total Pembelian:</td>
                            <td class="text-end pe-4 fw-bold fs-6 text-primary">
                                @can('view_purchase_prices')
                                    Rp {{ number_format((float) $pembelian->total, 2, ',', '.') }}
                                @else
                                    Rp ***
                                @endcan
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment History Table -->
    @can('view_purchase_prices')
        @if ($pembelian->pembayaran->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold mb-0 text-success">
                        <i class="bi bi-wallet2 me-1"></i> Riwayat Pembayaran / Angsuran ke Supplier
                    </h6>
                    <span class="badge bg-success-subtle text-success">
                        Total Dicatat: Rp {{ number_format((float) $pembelian->nominal_bayar, 2, ',', '.') }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 45px;">#</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Keterangan / Catatan</th>
                                    <th>Dicatat Oleh</th>
                                    <th class="text-end pe-4">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pembelian->pembayaran as $pIndex => $bayar)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $pIndex + 1 }}</td>
                                        <td>{{ $bayar->tanggal_bayar->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border text-uppercase">
                                                {{ $bayar->metode_pembayaran }}
                                            </span>
                                        </td>
                                        <td>{{ $bayar->keterangan ?: '-' }}</td>
                                        <td class="small text-muted">{{ $bayar->user->name ?? 'Sistem' }}</td>
                                        <td class="text-end pe-4 fw-bold text-success font-monospace">
                                            Rp {{ number_format((float) $bayar->nominal, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    <!-- Receive Goods Modal -->
    @if ($showReceiveModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-box-arrow-in-down me-2"></i>Verifikasi & Penerimaan Barang Masuk
                        </h5>
                        <button type="button" wire:click="closeReceiveModal" class="btn-close btn-close-white"></button>
                    </div>
                    <form wire:submit.prevent="processReceive">
                        <div class="modal-body p-4">
                            <div class="alert alert-info py-2 small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Masukkan nomor dokumen Surat Jalan dan armada yang datang membawa fisik barang. Stok akan bertambah otomatis ke kartu stok masing-masing gudang.
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Tanggal Barang Diterima <span class="text-danger">*</span></label>
                                <input type="date" wire:model="receive_tanggal_diterima" class="form-control @error('receive_tanggal_diterima') is-invalid @enderror" required>
                                @error('receive_tanggal_diterima') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor Surat Jalan Pabrik / Ekspedisi</label>
                                <input type="text" wire:model="receive_no_surat_jalan" class="form-control @error('receive_no_surat_jalan') is-invalid @enderror" placeholder="Contoh: SJ128402-031126-1">
                                @error('receive_no_surat_jalan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor Faktur / Invoice Supplier</label>
                                <input type="text" wire:model="receive_no_faktur_supplier" class="form-control @error('receive_no_faktur_supplier') is-invalid @enderror" placeholder="Contoh: IT124280-130726-3">
                                @error('receive_no_faktur_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor Plat Mobil / Truk / Armada</label>
                                <input type="text" wire:model="receive_no_polisi" class="form-control @error('receive_no_polisi') is-invalid @enderror" placeholder="Contoh: B 9812 UIK">
                                @error('receive_no_polisi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeReceiveModal" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-success fw-bold">
                                <span wire:loading.remove wire:target="processReceive">
                                    <i class="bi bi-check-circle me-1"></i>Simpan & Masukkan ke Stok
                                </span>
                                <span wire:loading wire:target="processReceive">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses Stok...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Record Payment Modal -->
    @if ($showPaymentModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-cash-coin me-2"></i>Catat Pembayaran / Angsuran Hutang
                        </h5>
                        <button type="button" wire:click="closePaymentModal" class="btn-close btn-close-white"></button>
                    </div>
                    <form wire:submit.prevent="processPayment">
                        <div class="modal-body p-4">
                            <div class="alert alert-light border small mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Total Nilai Faktur:</span>
                                    <span class="fw-bold">Rp {{ number_format((float) $pembelian->total, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 text-success">
                                    <span>Sudah Dibayar:</span>
                                    <span>Rp {{ number_format((float) $pembelian->nominal_bayar, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between text-danger fw-bold border-top pt-1">
                                    <span>Sisa Hutang Saat Ini:</span>
                                    <span>Rp {{ number_format((float) $pembelian->sisa_hutang, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">Rp</span>
                                    <input type="number" step="any" wire:model="pay_nominal" class="form-control fw-bold @error('pay_nominal') is-invalid @enderror" min="1" max="{{ $pembelian->sisa_hutang }}" required>
                                </div>
                                @error('pay_nominal') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Tanggal Bayar <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="pay_tanggal_bayar" class="form-control @error('pay_tanggal_bayar') is-invalid @enderror" required>
                                    @error('pay_tanggal_bayar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Metode Bayar <span class="text-danger">*</span></label>
                                    <select wire:model="pay_metode_pembayaran" class="form-select @error('pay_metode_pembayaran') is-invalid @enderror">
                                        <option value="transfer">Transfer Bank</option>
                                        <option value="cash">Tunai (Cash)</option>
                                        <option value="giro">Bilyet Giro / Cek</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                    @error('pay_metode_pembayaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Keterangan / Catatan Bukti Transfer</label>
                                <input type="text" wire:model="pay_keterangan" class="form-control" placeholder="Contoh: Transfer BCA Rekening Vifon / Setoran Tunai Kasir">
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closePaymentModal" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-primary fw-bold">
                                <span wire:loading.remove wire:target="processPayment">
                                    <i class="bi bi-check-circle me-1"></i>Konfirmasi Pembayaran
                                </span>
                                <span wire:loading wire:target="processPayment">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Shipping & Logistics Documents Modal -->
    @if ($showEditShippingModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>Perbarui Dokumen Logistik & Pengiriman
                        </h5>
                        <button type="button" wire:click="closeEditShippingModal" class="btn-close btn-close-white"></button>
                    </div>
                    <form wire:submit.prevent="processEditShipping">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">No. Surat Jalan (SJ) Pabrik</label>
                                <input type="text" wire:model="edit_no_surat_jalan" class="form-control font-monospace @error('edit_no_surat_jalan') is-invalid @enderror" placeholder="Contoh: SJ-2024-0891">
                                @error('edit_no_surat_jalan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">No. Faktur / Invoice Supplier</label>
                                <input type="text" wire:model="edit_no_faktur_supplier" class="form-control font-monospace @error('edit_no_faktur_supplier') is-invalid @enderror" placeholder="Contoh: INV-VIFON-2024-11">
                                @error('edit_no_faktur_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">No. Plat Polisi Truk</label>
                                    <input type="text" wire:model="edit_no_polisi" class="form-control font-monospace text-uppercase @error('edit_no_polisi') is-invalid @enderror" placeholder="Contoh: B 9123 UZX">
                                    @error('edit_no_polisi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">No. Kontainer</label>
                                    <input type="text" wire:model="edit_no_kontainer" class="form-control font-monospace text-uppercase @error('edit_no_kontainer') is-invalid @enderror" placeholder="Contoh: TGHU 918231">
                                    @error('edit_no_kontainer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Tanggal Jatuh Tempo Pembayaran</label>
                                <input type="date" wire:model="edit_tanggal_jatuh_tempo" class="form-control @error('edit_tanggal_jatuh_tempo') is-invalid @enderror">
                                @error('edit_tanggal_jatuh_tempo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Catatan / Keterangan</label>
                                <textarea wire:model="edit_keterangan" class="form-control @error('edit_keterangan') is-invalid @enderror" rows="2" placeholder="Catatan sopir, kondisi barang, dll"></textarea>
                                @error('edit_keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeEditShippingModal" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-info text-white fw-bold">
                                <span wire:loading.remove wire:target="processEditShipping">
                                    <i class="bi bi-check-circle me-1"></i>Simpan Perubahan Dokumen
                                </span>
                                <span wire:loading wire:target="processEditShipping">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Purchase / PO Modal -->
    @if ($showCancelModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-octagon me-2"></i>Batalkan {{ $pembelian->isOrdered() ? 'Pesanan Pembelian (PO)' : 'Transaksi Pembelian Masuk' }}
                        </h5>
                        <button type="button" wire:click="closeCancelModal" class="btn-close btn-close-white"></button>
                    </div>
                    <form wire:submit.prevent="processCancel">
                        <div class="modal-body p-4">
                            @if ($pembelian->isReceived())
                                <div class="alert alert-warning border-warning-subtle small mb-3">
                                    <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
                                    <strong>Perhatian Penting:</strong> Pembelian ini berstatus <em>Diterima di Gudang</em>. Membatalkan transaksi ini akan <strong>menarik kembali (mengurangi) stok fisik dari gudang</strong> sebesar jumlah item yang masuk. Sistem akan menolak pembatalan jika stok gudang saat ini sudah terpakai/terjual.
                                </div>
                            @else
                                <div class="alert alert-info border-info-subtle small mb-3">
                                    <i class="bi bi-info-circle-fill me-1 text-info"></i>
                                    Pesanan PO ini belum diterima di gudang, sehingga pembatalan <strong>tidak mempengaruhi stok fisik</strong>. Status akan diubah menjadi <em>Dibatalkan</em> dan kewajiban hutang dinolkan.
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Alasan Pembatalan <span class="text-danger">*</span></label>
                                <textarea wire:model="cancel_alasan" class="form-control @error('cancel_alasan') is-invalid @enderror" rows="3" placeholder="Contoh: Kesalahan input motif, pabrik kehabisan stok, atau pesanan dibatalkan supplier"></textarea>
                                @error('cancel_alasan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeCancelModal" class="btn btn-secondary">Kembali</button>
                            <button type="submit" class="btn btn-danger fw-bold">
                                <span wire:loading.remove wire:target="processCancel">
                                    <i class="bi bi-x-circle me-1"></i>Ya, Batalkan Transaksi
                                </span>
                                <span wire:loading wire:target="processCancel">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses Pembatalan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete PO Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-trash me-2"></i>Hapus Permanen Pesanan PO
                        </h5>
                        <button type="button" wire:click="closeDeleteModal" class="btn-close btn-close-white"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <i class="bi bi-trash3 text-danger fs-1 d-block mb-3"></i>
                        <h6 class="fw-bold mb-2">Hapus Surat Pesanan #{{ $pembelian->no_pembelian }}?</h6>
                        <p class="text-muted small mb-0">
                            Data draft PO ini akan dihapus dari sistem. Karena belum ada barang yang diterima maupun pembayaran yang disetor, tidak ada kartu stok atau jurnal kas yang terpengaruh.
                        </p>
                    </div>
                    <div class="modal-footer bg-light justify-content-center">
                        <button type="button" wire:click="closeDeleteModal" class="btn btn-secondary px-3">Batal</button>
                        <button type="button" wire:click="processDelete" class="btn btn-danger fw-bold px-3">
                            <span wire:loading.remove wire:target="processDelete">
                                <i class="bi bi-trash me-1"></i>Ya, Hapus PO
                            </span>
                            <span wire:loading wire:target="processDelete">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>Menghapus...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

