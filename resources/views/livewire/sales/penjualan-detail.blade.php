<div>
    @php
        $isBatal = $penjualan->isDibatalkan();
    @endphp

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($isBatal)
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start gap-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-danger mt-1"></i>
            <div>
                <h5 class="fw-bold mb-1">FAKTUR INI TELAH DIBATALKAN (VOID)</h5>
                <p class="mb-1">
                    Nota ini dibatalkan pada <strong>{{ $penjualan->dibatalkan_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                    oleh <strong>{{ $penjualan->dibatalkanBy->name ?? 'User' }}</strong>.
                </p>
                <div class="p-2 bg-white rounded border border-danger-subtle text-danger small">
                    <strong>Alasan Pembatalan:</strong> {{ $penjualan->alasan_batal ?: 'Tidak ada alasan yang dicantumkan.' }}
                </div>
                <small class="text-muted mt-1 d-block">
                    * Seluruh stok produk telah dikembalikan ke gudang dan alokasi pembayaran telah dibalikkan.
                </small>
            </div>
        </div>
    @endif

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h4 class="fw-bold mb-0 {{ $isBatal ? 'text-decoration-line-through text-danger' : '' }}">
                    Faktur Penjualan {{ $penjualan->no_penjualan }}
                </h4>
                @if ($isBatal)
                    <span class="badge bg-danger">
                        <i class="bi bi-x-circle me-1"></i>DIBATALKAN
                    </span>
                @else
                    <span class="badge {{ $penjualan->status_pembayaran->badgeClass() }}">
                        <i class="bi bi-wallet2 me-1"></i>{{ $penjualan->status_pembayaran->label() }}
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border">
                        <i class="bi bi-credit-card me-1"></i>{{ $penjualan->getMetodeLabel() }}
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Dicatat pada {{ $penjualan->created_at->format('d/m/Y H:i') }} oleh {{ $penjualan->user->name ?? ($penjualan->creator->name ?? 'Sistem') }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
            <a href="{{ route('penjualan.index') }}" class="btn btn-sm btn-outline-secondary flex-fill flex-md-grow-0">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            @can('create_sales')
                <a href="{{ route('penjualan.create', ['duplicate_id' => $penjualan->id]) }}" class="btn btn-sm btn-outline-info shadow-sm flex-fill flex-md-grow-0">
                    <i class="bi bi-copy me-1"></i>Duplikat
                </a>
            @endcan
            @if (! $isBatal)
                <a href="{{ route('penjualan.print.faktur', $penjualan) }}" target="_blank" class="btn btn-sm btn-primary shadow-sm flex-fill flex-md-grow-0">
                    <i class="bi bi-file-earmark-text me-1"></i>Cetak Faktur
                </a>
                <a href="{{ route('penjualan.print.surat-jalan', $penjualan) }}" target="_blank" class="btn btn-sm btn-success shadow-sm flex-fill flex-md-grow-0">
                    <i class="bi bi-truck me-1"></i>Surat Jalan
                </a>
                <a href="{{ route('penjualan.print.thermal', $penjualan) }}" target="_blank" class="btn btn-sm btn-outline-dark shadow-sm flex-fill flex-md-grow-0">
                    <i class="bi bi-receipt me-1"></i>Struk Kasir
                </a>
                @can('cancel_sales')
                    <button type="button" wire:click="openCancelModal" class="btn btn-sm btn-outline-danger shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-x-circle me-1"></i>Batalkan Nota
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <!-- Summary Info Cards -->
    <div class="row g-2 g-md-4 mb-3 mb-md-4">
        <!-- Customer Information -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-primary-subtle text-primary rounded me-3">
                            <i class="bi bi-person-badge fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Pelanggan</span>
                            <h6 class="fw-bold mb-0">{{ $penjualan->pelanggan->nama ?? '-' }}</h6>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $penjualan->pelanggan->kontak ?? 'Tidak ada kontak' }}</li>
                        <li><i class="bi bi-tag me-2"></i>Tipe: <span class="badge bg-light text-dark border">{{ $penjualan->pelanggan->tipe->label() ?? 'Umum' }}</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Transaction & Warehouse Details -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-info-subtle text-info rounded me-3">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Gudang Sumber</span>
                            <h6 class="fw-bold mb-0">{{ $penjualan->gudang->nama_gudang ?? '-' }}</h6>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-1">
                            <span class="fw-semibold">Tanggal & Waktu:</span>
                            <span class="ms-1">{{ $penjualan->tanggal->format('d/m/Y') }}</span>
                            @if ($penjualan->created_at)
                                <span class="badge bg-light text-secondary border font-monospace ms-1">{{ $penjualan->created_at->format('H:i') }} WIB</span>
                            @endif
                        </li>
                        <li>
                            <span class="fw-semibold">Catatan:</span>
                            <span class="ms-1">{{ $penjualan->keterangan ?: 'Tidak ada catatan' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="col-md-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-white-50 small text-uppercase tracking-wider">Total Nilai Faktur</span>
                        <h3 class="fw-bold mb-0 mt-1">
                            Rp {{ number_format((float) $penjualan->total_akhir, 2, ',', '.') }}
                        </h3>
                    </div>
                    <div class="mt-3 pt-3 border-top border-white-50 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Metode:</span>
                            <span class="fw-bold">{{ $penjualan->getMetodeLabel() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total Dibayar:</span>
                            <span class="fw-bold">Rp {{ number_format((float) $penjualan->total_dibayar, 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Sisa Piutang:</span>
                            <span class="fw-bold">Rp {{ number_format((float) $penjualan->sisa_piutang, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($penjualan->nama_sopir || $penjualan->plat_nomor || $penjualan->alamat_pengiriman)
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-light-subtle">
                    <div class="card-body py-2.5 px-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-truck me-1"></i>Ekspedisi & Pengiriman
                                </span>
                                <div class="small">
                                    <span class="text-muted">Sopir / Armada:</span>
                                    <strong class="text-dark ms-1">{{ $penjualan->nama_sopir ?: '-' }}</strong>
                                    @if ($penjualan->plat_nomor)
                                        <span class="badge bg-light text-dark border ms-1 font-monospace">{{ $penjualan->plat_nomor }}</span>
                                    @endif
                                </div>
                            </div>
                            @if ($penjualan->alamat_pengiriman)
                                <div class="small">
                                    <span class="text-muted"><i class="bi bi-geo-alt me-1 text-danger"></i>Tujuan Kirim:</span>
                                    <strong class="text-dark ms-1">{{ $penjualan->alamat_pengiriman }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Line Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="card-title fw-bold mb-0 text-primary">
                <i class="bi bi-list-check me-1"></i> Rincian Barang Penjualan
            </h6>
        </div>
        <div class="card-body p-0">
            @php $totalFisik = 0; @endphp
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                @foreach ($penjualan->details as $index => $detail)
                    @php
                        $totalFisik += (float) $detail->jumlah;
                    @endphp
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <div class="fw-bold text-dark" style="font-size: 0.92rem; line-height: 1.3;">
                                <span class="badge bg-light text-secondary border me-1">#{{ $index + 1 }}</span>
                                {{ $detail->produk->nama ?? '-' }}
                            </div>
                        </div>

                        <div class="d-flex align-items-center flex-wrap gap-1 mb-2" style="font-size: 0.74rem;">
                            <span class="badge bg-light text-secondary border font-monospace">{{ $detail->produk->kode_katalog ?? 'Tanpa SKU' }}</span>
                            <span class="badge bg-light text-dark border">{{ $detail->produk->kategori->nama_kategori ?? '-' }}</span>
                            @if ($detail->produk?->merek)
                                <span class="badge bg-secondary-subtle text-secondary">{{ $detail->produk->merek->nama_merek }}</span>
                            @endif
                            @if ($detail->gudang && $detail->gudang->id !== $penjualan->gudang_id)
                                <span class="badge bg-primary-subtle text-primary border">
                                    <i class="bi bi-building me-1"></i>Ambil dari: {{ $detail->gudang->nama_gudang }}
                                </span>
                            @endif
                        </div>

                        @if ($konversi = $detail->getDeskripsiKemasan())
                            <div class="mb-2">
                                <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;">
                                    <i class="bi bi-box-seam me-1"></i>{{ $konversi }}
                                </span>
                            </div>
                        @endif

                        <div class="p-2 rounded-3 bg-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small">
                                <div class="fw-bold text-dark font-monospace">
                                    {{ number_format((float) $detail->jumlah, 2, ',', '.') }} {{ $detail->produk->satuan->label() ?? '' }}
                                    <span class="text-muted fw-normal">&times;</span>
                                    Rp {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}
                                </div>
                                @if ($detail->isHargaDiubah())
                                    @php $selisihHarga = (float) $detail->harga_satuan - (float) $detail->harga_normal; @endphp
                                    <div class="mt-0.5">
                                        <span class="text-muted text-decoration-line-through me-1" style="font-size: 0.72rem;">
                                            Rp {{ number_format((float) $detail->harga_normal, 0, ',', '.') }}
                                        </span>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.65rem;">
                                            Harga diubah ({{ $selisihHarga > 0 ? '+' : '-' }}Rp {{ number_format(abs($selisihHarga), 0, ',', '.') }})
                                        </span>
                                    </div>
                                @endif
                                @if ($detail->nilai_diskon > 0)
                                    <div class="text-danger small" style="font-size: 0.74rem;">
                                        Diskon:
                                        @if ($detail->jenis_diskon?->value === 'persentase')
                                            -{{ number_format((float) $detail->nilai_diskon, 1, ',', '.') }}%
                                        @else
                                            -Rp {{ number_format((float) $detail->nilai_diskon, 0, ',', '.') }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="text-end ms-auto">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Subtotal</span>
                                <span class="fw-bold text-primary font-monospace fs-6">
                                    Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Mobile Footer Summary -->
                <div class="p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted fw-semibold">Total Fisik Muatan:</span>
                        <span class="fw-bold font-monospace text-dark">{{ number_format($totalFisik, 2, ',', '.') }} unit</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span class="text-muted fw-semibold">Biaya Ongkir:</span>
                        <span class="fw-bold font-monospace text-dark">Rp {{ number_format((float) $penjualan->biaya_ongkir, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="fw-bold text-dark">Grand Total Penjualan:</span>
                        <span class="fw-bold fs-5 text-primary font-monospace">Rp {{ number_format((float) $penjualan->total_akhir, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Desktop Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Produk & SKU</th>
                            <th>Kategori & Merek</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end pe-4">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalFisik = 0; @endphp
                        @foreach ($penjualan->details as $index => $detail)
                            @php $totalFisik += (float) $detail->jumlah; @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $detail->produk->nama ?? '-' }}</div>
                                    @if ($konversi = $detail->getDeskripsiKemasan())
                                        <div class="mt-1">
                                            <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;" title="Rincian Fisik Kemasan">
                                                <i class="bi bi-box-seam me-1"></i>{{ $konversi }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        <small class="text-muted font-monospace me-1">{{ $detail->produk->kode_katalog ?? 'Tanpa SKU' }}</small>
                                        @if ($detail->gudang && $detail->gudang->id !== $penjualan->gudang_id)
                                            <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.7rem;">
                                                <i class="bi bi-building me-1"></i>Ambil dari: {{ $detail->gudang->nama_gudang }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border me-1">
                                        {{ $detail->produk->kategori->nama_kategori ?? '-' }}
                                    </span>
                                    <small class="text-muted">{{ $detail->produk->merek->nama_merek ?? '-' }}</small>
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format((float) $detail->jumlah, 2, ',', '.') }}
                                    <span class="text-muted fw-normal small">{{ $detail->produk->satuan->label() ?? '' }}</span>
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format((float) $detail->harga_satuan, 2, ',', '.') }}
                                    @if ($detail->isHargaDiubah())
                                        @php $selisihHarga = (float) $detail->harga_satuan - (float) $detail->harga_normal; @endphp
                                        <div class="text-muted text-decoration-line-through" style="font-size: 0.75rem;">
                                            Rp {{ number_format((float) $detail->harga_normal, 2, ',', '.') }}
                                        </div>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.65rem;" title="Harga normal sistem: Rp {{ number_format((float) $detail->harga_normal, 0, ',', '.') }}">
                                            <i class="bi bi-pencil-square me-1"></i>Harga diubah
                                            ({{ $selisihHarga > 0 ? '+' : '-' }}Rp {{ number_format(abs($selisihHarga), 0, ',', '.') }})
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($detail->nilai_diskon > 0)
                                        <span class="text-danger">
                                            @if ($detail->jenis_diskon?->value === 'persentase')
                                                {{ number_format((float) $detail->nilai_diskon, 2, ',', '.') }}%
                                            @else
                                                Rp {{ number_format((float) $detail->nilai_diskon, 2, ',', '.') }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4 fw-bold text-primary">
                                    Rp {{ number_format((float) $detail->subtotal, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold text-muted">Total Fisik Muatan:</td>
                            <td class="text-end fw-bolder font-monospace text-dark">
                                {{ number_format($totalFisik, 2, ',', '.') }} unit
                            </td>
                            <td colspan="2" class="text-end fw-bold">Biaya Ongkir:</td>
                            <td class="text-end pe-4 fw-bold">
                                Rp {{ number_format((float) $penjualan->biaya_ongkir, 2, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end fw-bold fs-6">Grand Total Penjualan:</td>
                            <td class="text-end pe-4 fw-bold fs-6 text-primary">
                                Rp {{ number_format((float) $penjualan->total_akhir, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pembatalan Nota (Void) -->
    @if ($isCancelModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Batalkan Faktur Penjualan
                        </h5>
                        <button type="button" wire:click="closeCancelModal" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 small mb-3">
                            <h6 class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i> Konsekuensi Pembatalan:</h6>
                            <ul class="mb-0 ps-3">
                                <li>Stok barang akan <strong>dikembalikan ke gudang</strong> secara otomatis.</li>
                                <li>Pembayaran kasir yang terikat akan <strong>dibatalkan/dibalikkan</strong>.</li>
                                <li>Piutang yang timbul dari nota ini akan <strong>dinolkan</strong>.</li>
                                <li>Faktur ini akan ditandai <strong>DIBATALKAN</strong> dan riwayat pembatalan dicatat.</li>
                            </ul>
                        </div>

                        <p class="mb-3">
                            Apakah Anda yakin ingin membatalkan faktur <strong>{{ $penjualan->no_penjualan }}</strong>?
                        </p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">
                                Alasan Pembatalan <span class="text-danger">*</span>
                            </label>
                            <textarea
                                wire:model="alasanBatal"
                                class="form-control @error('alasanBatal') is-invalid @enderror"
                                rows="3"
                                placeholder="Contoh: Pembeli salah memilih barang, salah harga, transaksi dibatalkan..."
                            ></textarea>
                            @error('alasanBatal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="closeCancelModal" class="btn btn-secondary">
                            Batal
                        </button>
                        <button type="button" wire:click="confirmCancelPenjualan" class="btn btn-danger" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="confirmCancelPenjualan">
                                <i class="bi bi-check-circle me-1"></i>Ya, Batalkan Nota
                            </span>
                            <span wire:loading wire:target="confirmCancelPenjualan">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
