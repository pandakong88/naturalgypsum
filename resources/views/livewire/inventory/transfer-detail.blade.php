<div>
    <style>
        @media print {
            .no-print, .btn, .alert {
                display: none !important;
            }
            .card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .table {
                font-size: 11px !important;
                width: 100% !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background: #fff !important;
                font-size: 12px !important;
            }
        }
        .print-only {
            display: none;
        }
        .signature-box {
            height: 80px;
            border-bottom: 1px solid #000;
        }
    </style>

    <!-- Print Only Official Header -->
    <div class="print-only mb-4 pb-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold mb-0">NATURAL GYPSUM & PVC</h3>
                <small class="text-muted">Distributor Gypsum & Plafon PVC Berkualitas</small>
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-0 text-uppercase">SURAT JALAN TRANSFER</h4>
                <div class="font-monospace fw-bold">{{ $transfer->no_transfer }}</div>
            </div>
        </div>
        <div class="row g-2 mt-2 pt-2 border-top small">
            <div class="col-6">
                <div><strong>Tanggal:</strong> {{ $transfer->tanggal ? $transfer->tanggal->translatedFormat('d F Y') : '-' }}</div>
                <div><strong>Gudang Pengirim:</strong> {{ $transfer->gudangAsal->nama_gudang ?? '-' }}</div>
                <div><strong>Alamat Asal:</strong> {{ $transfer->gudangAsal->alamat ?? '-' }}</div>
            </div>
            <div class="col-6 text-end">
                <div><strong>Operator:</strong> {{ $transfer->user->name ?? ($transfer->creator->name ?? 'Staff') }}</div>
                <div><strong>Gudang Penerima:</strong> {{ $transfer->gudangTujuan->nama_gudang ?? '-' }}</div>
                <div><strong>Alamat Tujuan:</strong> {{ $transfer->gudangTujuan->alamat ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Page Header (Screen) -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2 no-print">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h4 class="fw-bold mb-0">Transfer {{ $transfer->no_transfer }}</h4>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check-circle me-1"></i> Selesai
                </span>
            </div>
            <p class="text-muted small mb-0">
                Dicatat pada {{ $transfer->created_at->format('d/m/Y H:i') }} oleh {{ $transfer->user->name ?? ($transfer->creator->name ?? 'Sistem') }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
            <a href="{{ route('transfer.index') }}" class="btn btn-outline-secondary flex-fill flex-md-grow-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            @can('delete', $transfer)
                <button type="button" wire:click="openCancelModal" class="btn btn-outline-danger flex-fill flex-md-grow-0">
                    <i class="bi bi-x-circle me-1"></i> Batalkan Transfer
                </button>
            @endcan
            <button type="button" class="btn btn-primary flex-fill flex-md-grow-0" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Surat Jalan
            </button>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Warehouse Route Cards (Screen) -->
    <div class="row g-2 g-md-4 mb-3 mb-md-4 no-print">
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center mb-1 mb-sm-2">
                        <div class="p-2 bg-secondary-subtle text-secondary rounded me-2 d-none d-sm-flex">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-muted small d-block text-truncate">Gudang Asal (Pengirim)</span>
                            <h6 class="fw-bold mb-0 text-truncate">{{ $transfer->gudangAsal->nama_gudang ?? '-' }}</h6>
                        </div>
                    </div>
                    <p class="text-muted small mb-0 text-truncate">{{ $transfer->gudangAsal->alamat ?? 'Tidak ada alamat' }}</p>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-sm-3">
                    <div class="d-flex align-items-center mb-1 mb-sm-2">
                        <div class="p-2 bg-primary-subtle text-primary rounded me-2 d-none d-sm-flex">
                            <i class="bi bi-box-arrow-in-right fs-5"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-muted small d-block text-truncate">Gudang Tujuan (Penerima)</span>
                            <h6 class="fw-bold mb-0 text-truncate">{{ $transfer->gudangTujuan->nama_gudang ?? '-' }}</h6>
                        </div>
                    </div>
                    <p class="text-muted small mb-0 text-truncate">{{ $transfer->gudangTujuan->alamat ?? 'Tidak ada alamat' }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(filled($transfer->keterangan))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <span class="text-muted small d-block mb-1 fw-bold">Catatan Pengiriman / Keterangan:</span>
                <p class="mb-0 text-dark">{{ $transfer->keterangan }}</p>
            </div>
        </div>
    @endif

    <!-- Transferred Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 no-print">
            <h6 class="card-title fw-bold mb-0 text-primary">
                <i class="bi bi-list-check me-1"></i> Rincian Barang yang Dipindahkan
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Produk & SKU</th>
                            <th>Kategori & Merek</th>
                            <th class="text-end pe-4">Jumlah Ditransfer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfer->details as $index => $detail)
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
                                <td class="text-end pe-4 fw-bold">
                                    {{ number_format((float) $detail->jumlah, 2, ',', '.') }}
                                    <span class="text-muted fw-normal small">{{ $detail->produk->satuan->label() ?? '' }}</span>
                                    @php
                                        $konv = $detail->produk?->getKonversiFisik((float) $detail->jumlah);
                                    @endphp
                                    @if ($konv && $konv['has_conversion'])
                                        <div class="small text-muted font-monospace fw-normal" style="font-size: 0.75rem;">
                                            ({{ $konv['badge_text'] }})
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="ps-4 fw-bold text-end">Total Kuantitas Barang:</td>
                            <td class="text-end pe-4 fw-bold text-primary font-monospace fs-6">
                                {{ number_format((float) $transfer->details->sum('jumlah'), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Official Signature Boxes (Print Only) -->
    <div class="print-only mt-5 pt-3">
        <div class="row text-center">
            <div class="col-4">
                <p class="mb-5 small fw-bold">Diserahkan Oleh (Gudang Asal),</p>
                <div class="signature-box mx-4"></div>
                <p class="small text-muted mt-2">( ............................................ )</p>
            </div>
            <div class="col-4">
                <p class="mb-5 small fw-bold">Dibawa Oleh (Sopir / Kurir),</p>
                <div class="signature-box mx-4"></div>
                <p class="small text-muted mt-2">( ............................................ )</p>
            </div>
            <div class="col-4">
                <p class="mb-5 small fw-bold">Diterima Oleh (Gudang Tujuan),</p>
                <div class="signature-box mx-4"></div>
                <p class="small text-muted mt-2">( ............................................ )</p>
            </div>
        </div>
    </div>

    <!-- Custom Modern Cancellation Confirmation Modal -->
    @if ($isCancelModalOpen)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-body p-4 text-center">
                        <!-- Icon Warning Glow -->
                        <div class="d-inline-flex p-3 rounded-circle bg-danger bg-opacity-10 text-danger mb-3 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill display-5"></i>
                        </div>

                        <h4 class="fw-bold text-dark mb-1">Batalkan Transfer Ini?</h4>
                        <p class="text-muted small mb-3">
                            Tindakan ini akan mengembalikan stok barang ke posisi semula dan mencatat jurnal pembalikan.
                        </p>

                        <!-- Document Details Card -->
                        <div class="card bg-light border-0 rounded-3 text-start mb-3 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted small">No. Transfer:</span>
                                <span class="fw-bold font-monospace text-danger">{{ $transfer->no_transfer }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted small">Rute Pengiriman:</span>
                                <span class="fw-semibold text-dark small text-end">
                                    {{ $transfer->gudangAsal->nama_gudang ?? '-' }}
                                    <i class="bi bi-arrow-right mx-1 text-primary"></i>
                                    {{ $transfer->gudangTujuan->nama_gudang ?? '-' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Total Barang Terkena:</span>
                                <span class="fw-bold text-dark small">
                                    {{ $transfer->details->count() }} item ({{ number_format((float) $transfer->details->sum('jumlah'), 2, ',', '.') }} unit)
                                </span>
                            </div>
                        </div>

                        <!-- Consequence Notice -->
                        <div class="alert alert-warning text-start py-2 px-3 small border-0 mb-4 rounded-3 d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>Dampak Pembatalan:</strong>
                                <ul class="mb-0 ps-3 mt-1 text-muted">
                                    <li>Stok di <strong>{{ $transfer->gudangTujuan->nama_gudang ?? 'Gudang Tujuan' }}</strong> akan otomatis dikurangi kembali.</li>
                                    <li>Stok di <strong>{{ $transfer->gudangAsal->nama_gudang ?? 'Gudang Asal' }}</strong> akan otomatis dikembalikan utuh.</li>
                                    <li>Audit riwayat mutasi pembatalan otomatis tercatat.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 justify-content-center">
                            <button
                                type="button"
                                wire:click="closeCancelModal"
                                class="btn btn-light px-4 py-2 border rounded-3 fw-medium text-secondary"
                            >
                                Kembali / Batal
                            </button>
                            <button
                                type="button"
                                wire:click="cancel"
                                wire:loading.attr="disabled"
                                class="btn btn-danger px-4 py-2 rounded-3 fw-bold text-white shadow-sm"
                            >
                                <span wire:loading.remove wire:target="cancel">
                                    <i class="bi bi-x-circle me-1"></i> Ya, Batalkan Sekarang
                                </span>
                                <span wire:loading wire:target="cancel">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Memproses...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
