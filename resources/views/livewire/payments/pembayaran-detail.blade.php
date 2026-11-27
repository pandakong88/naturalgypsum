<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">Bukti Pembayaran {{ $pembayaran->no_pembayaran }}</h4>
                <span class="badge {{ $pembayaran->metode->badgeClass() }}">
                    <i class="bi bi-wallet2 me-1"></i>{{ $pembayaran->metode->label() }}
                </span>
                @if ($pembayaran->trashed())
                    <span class="badge bg-danger text-white">DIBATALKAN</span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Dicatat pada {{ $pembayaran->created_at->format('d/m/Y H:i') }} oleh {{ $pembayaran->user->name ?? ($pembayaran->creator->name ?? 'Sistem') }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('pembayaran.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <a href="{{ route('pembayaran.print.kuitansi', $pembayaran->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Kuitansi Resmi A4 / A5">
                <i class="bi bi-printer me-1"></i>Cetak Kuitansi (A4/A5)
            </a>
            <a href="{{ route('pembayaran.print.thermal', $pembayaran->id) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Cetak Struk Kasir Thermal POS">
                <i class="bi bi-receipt me-1"></i>Struk Thermal
            </a>
            @if (!$pembayaran->trashed())
                @can('cancel', $pembayaran)
                    <button type="button" wire:click="openCancelModal" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i>Batalkan Pembayaran
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <!-- Summary Info Cards -->
    <div class="row g-4 mb-4">
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
                            <h6 class="fw-bold mb-0">{{ $pembayaran->pelanggan->nama ?? '-' }}</h6>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $pembayaran->pelanggan->kontak ?? 'Tidak ada kontak' }}</li>
                        <li><i class="bi bi-tag me-2"></i>Tipe: <span class="badge bg-light text-dark border">{{ $pembayaran->pelanggan->tipe->label() ?? 'Umum' }}</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-success-subtle text-success rounded me-3">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Informasi Transaksi</span>
                            <h6 class="fw-bold mb-0">{{ $pembayaran->metode->label() }}</h6>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-1">
                            <span class="fw-semibold">Tanggal Pembayaran:</span>
                            <span class="ms-1">{{ $pembayaran->tanggal->format('d/m/Y') }}</span>
                        </li>
                        <li>
                            <span class="fw-semibold">Catatan:</span>
                            <span class="ms-1">{{ $pembayaran->catatan ?: 'Tidak ada catatan' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="col-md-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-white-50 small text-uppercase tracking-wider">Total Pembayaran Diterima</span>
                        <h3 class="fw-bold mb-0 mt-1">
                            Rp {{ number_format((float) $pembayaran->jumlah_bayar, 2, ',', '.') }}
                        </h3>
                    </div>
                    <div class="mt-3 pt-3 border-top border-white-50 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total Dialokasikan:</span>
                            <span class="fw-bold">Rp {{ number_format((float) $pembayaran->total_dialokasikan, 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Sisa Belum Dialokasikan:</span>
                            <span class="fw-bold">Rp {{ number_format((float) $pembayaran->sisa_belum_dialokasikan, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Allocation Breakdown Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="card-title fw-bold mb-0 text-primary">
                <i class="bi bi-list-check me-1"></i> Rincian Alokasi Faktur Penjualan
            </h6>
        </div>
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                @forelse ($pembayaran->alokasi as $index => $alokasi)
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1.5">
                            <div>
                                @if ($alokasi->penjualan)
                                    <a href="{{ route('penjualan.show', $alokasi->penjualan->id) }}" class="fw-bold text-decoration-none font-monospace text-primary">
                                        {{ $alokasi->penjualan->no_penjualan }}
                                    </a>
                                @else
                                    <span class="text-muted font-monospace">Faktur Terhapus</span>
                                @endif
                                <div class="text-muted small" style="font-size: 0.74rem;">
                                    Tanggal Faktur: {{ $alokasi->penjualan?->tanggal?->format('d/m/Y') ?? '-' }}
                                </div>
                            </div>
                            @if ($alokasi->penjualan)
                                <span class="badge {{ $alokasi->penjualan->status_pembayaran->badgeClass() }}">
                                    {{ $alokasi->penjualan->status_pembayaran->label() }}
                                </span>
                            @endif
                        </div>

                        <div class="p-2.5 rounded-3 bg-light border mt-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div style="font-size: 0.74rem;">
                                <div class="text-muted">Nilai Faktur: <span class="text-dark fw-semibold font-monospace">Rp {{ number_format((float) ($alokasi->penjualan?->total_akhir ?? 0), 0, ',', '.') }}</span></div>
                                <div class="text-muted">Sisa Piutang: <span class="text-danger fw-semibold font-monospace">Rp {{ number_format((float) ($alokasi->penjualan?->sisa_piutang ?? 0), 0, ',', '.') }}</span></div>
                            </div>
                            <div class="text-end ms-auto">
                                <div class="text-muted" style="font-size: 0.7rem;">Dialokasikan</div>
                                <div class="fw-bold text-success font-monospace fs-6">
                                    Rp {{ number_format((float) $alokasi->jumlah_dialokasikan, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 px-3 text-muted small">
                        Pembayaran ini tidak dialokasikan ke faktur manapun (seluruh dana berstatus deposit/titipan).
                    </div>
                @endforelse
                <div class="p-3 bg-primary-subtle d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small">Total Dialokasikan:</span>
                    <span class="fw-bold text-primary font-monospace fs-6">Rp {{ number_format((float) $pembayaran->total_dialokasikan, 2, ',', '.') }}</span>
                </div>
            </div>

            <!-- Desktop Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>No. Faktur Penjualan</th>
                            <th>Tanggal Faktur</th>
                            <th class="text-end">Total Nilai Faktur</th>
                            <th class="text-end">Sisa Piutang Faktur Saat Ini</th>
                            <th class="text-center">Status Faktur</th>
                            <th class="text-end pe-4">Jumlah Yang Dialokasikan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pembayaran->alokasi as $index => $alokasi)
                            <tr>
                                <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                                <td>
                                    @if ($alokasi->penjualan)
                                        <a href="{{ route('penjualan.show', $alokasi->penjualan->id) }}" class="fw-bold text-decoration-none font-monospace">
                                            {{ $alokasi->penjualan->no_penjualan }}
                                        </a>
                                    @else
                                        <span class="text-muted font-monospace">Faktur Terhapus</span>
                                    @endif
                                </td>
                                <td>
                                    <span>{{ $alokasi->penjualan?->tanggal?->format('d/m/Y') ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format((float) ($alokasi->penjualan?->total_akhir ?? 0), 2, ',', '.') }}
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp {{ number_format((float) ($alokasi->penjualan?->sisa_piutang ?? 0), 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if ($alokasi->penjualan)
                                        <span class="badge {{ $alokasi->penjualan->status_pembayaran->badgeClass() }}">
                                            {{ $alokasi->penjualan->status_pembayaran->label() }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4 fw-bold text-success">
                                    Rp {{ number_format((float) $alokasi->jumlah_dialokasikan, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Pembayaran ini tidak dialokasikan ke faktur manapun (seluruh dana berstatus deposit/titipan).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="table-primary">
                            <td colspan="6" class="text-end fw-bold fs-6">Total Dialokasikan:</td>
                            <td class="text-end pe-4 fw-bold fs-6 text-primary">
                                Rp {{ number_format((float) $pembayaran->total_dialokasikan, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Cancel Pembayaran -->
    @if ($isCancelModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger-subtle text-danger">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Pembatalan Pembayaran
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeCancelModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            Apakah Anda yakin ingin membatalkan transaksi pembayaran <strong>{{ $pembayaran->no_pembayaran }}</strong> sebesar <strong>Rp {{ number_format((float) $pembayaran->jumlah_bayar, 2, ',', '.') }}</strong>?
                        </p>
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Tindakan ini akan <strong>menghapus seluruh alokasi pembayaran</strong> dan <strong>mengembalikan saldo piutang faktur terkait</strong> ke kondisi sebelumnya.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeCancelModal">Batal</button>
                        <button type="button" class="btn btn-danger fw-bold" wire:click="cancelPayment" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="cancelPayment">
                                <i class="bi bi-trash me-1"></i>Ya, Batalkan Pembayaran
                            </span>
                            <span wire:loading wire:target="cancelPayment">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
