<div>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">Catatan Produksi {{ $produksi->no_produksi }}</h4>
                @if ($produksi->trashed())
                    <span class="badge bg-danger text-white">
                        <i class="bi bi-x-circle me-1"></i> DIBATALKAN
                    </span>
                @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-check-circle me-1"></i> Selesai / Stok Masuk
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Dicatat pada {{ $produksi->created_at->format('d/m/Y H:i') }} oleh {{ $produksi->user->name ?? ($produksi->creator->name ?? 'Sistem') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('produksi.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Laporan
            </button>
            @if (!$produksi->trashed())
                @can('delete', $produksi)
                    <button type="button" wire:click="openCancelModal" class="btn btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i> Batalkan Produksi
                    </button>
                @endcan
            @endif
        </div>
    </div>

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $nilaiOutput = $produksi->estimasi_nilai_output;
        $biayaBahan = $produksi->estimasi_biaya_bahan;
        $totalQtyOutput = (float) $produksi->details->sum('jumlah');
    @endphp

    <!-- Summary Info Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-primary-subtle text-primary rounded me-3">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Gudang Penyimpanan Output</span>
                            <h6 class="fw-bold mb-0">{{ $produksi->gudang->nama_gudang ?? '-' }}</h6>
                        </div>
                    </div>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-geo-alt me-1"></i>{{ $produksi->gudang->lokasi ?? 'Lokasi gudang belum diisi' }}
                    </p>
                    @if ($produksi->gudang?->kontak)
                        <p class="text-muted small mb-0">
                            <i class="bi bi-telephone me-1"></i>{{ $produksi->gudang->kontak }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-info-subtle text-info-emphasis rounded me-3">
                            <i class="bi bi-calendar-check fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Tanggal & Keterangan Batch</span>
                            <h6 class="fw-bold mb-0">{{ $produksi->tanggal->format('d/m/Y') }}</h6>
                        </div>
                    </div>
                    <p class="text-dark small mb-0">{{ $produksi->catatan ?: 'Tidak ada catatan khusus' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 {{ $produksi->trashed() ? 'bg-secondary' : 'bg-success' }} text-white">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-white-50 small text-uppercase">Estimasi Nilai Barang Jadi (HPP)</span>
                        <h3 class="fw-bold mb-0 mt-1">
                            Rp {{ number_format($nilaiOutput, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="mt-3 pt-3 border-top border-white-50 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total Output Barang Jadi:</span>
                            <span class="fw-bold">{{ $produksi->details->count() }} jenis ({{ number_format($totalQtyOutput, 2, ',', '.') }} unit)</span>
                        </div>
                        @if ($produksi->bahanMutations->isNotEmpty())
                            <div class="d-flex justify-content-between">
                                <span>Biaya Bahan Baku Gudang:</span>
                                <span class="fw-bold">Rp {{ number_format($biayaBahan, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Output Finished Goods Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="card-title fw-bold mb-0 text-primary">
                <i class="bi bi-box-seam me-1"></i> Rincian Barang Jadi yang Masuk (`Produksi Masuk`)
            </h6>
            <span class="badge bg-success-subtle text-success border border-success-subtle">
                +{{ $produksi->details->count() }} Item Produk
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Produk & SKU</th>
                            <th>Kategori & Merek</th>
                            <th class="text-end">HPP Satuan</th>
                            <th class="text-end">Jumlah Output</th>
                            <th class="text-end pe-4">Subtotal Nilai (HPP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($produksi->details as $index => $detail)
                            @php
                                $p = $detail->produk;
                                $qty = (float) $detail->jumlah;
                                $hpp = (float) ($p?->harga_beli ?: $p?->harga_jual ?: 0);
                                $subtotal = $qty * $hpp;
                                $konv = ($p && $qty > 0) ? $p->getKonversiFisik($qty) : null;
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $p->nama ?? '-' }}</div>
                                    <small class="text-muted font-monospace">{{ $p->kode_katalog ?? 'Tanpa SKU' }}</small>
                                    @if ($p?->ukuran)
                                        <small class="text-muted ms-1">• {{ $p->ukuran }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border me-1">
                                        {{ $p->kategori->nama_kategori ?? '-' }}
                                    </span>
                                    <small class="text-muted">{{ $p->merek->nama_merek ?? '-' }}</small>
                                </td>
                                <td class="text-end text-muted">
                                    Rp {{ number_format($hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold text-success">
                                    +{{ number_format($qty, 2, ',', '.') }}
                                    <span class="text-muted fw-normal small">{{ $p?->satuan?->label() ?? '' }}</span>
                                    @if ($konv && !empty($konv['has_conversion']))
                                        <div class="small fw-normal">
                                            <span class="badge bg-info-subtle text-info-emphasis border" style="font-size: 0.68rem;">
                                                {{ $konv['badge_text'] }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end pe-4 fw-bold text-dark">
                                    Rp {{ number_format($subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="table-success">
                            <td colspan="4" class="text-end fw-bold">Total Nilai Barang Jadi Dihasilkan:</td>
                            <td class="text-end fw-bold text-success">+{{ number_format($totalQtyOutput, 2, ',', '.') }} Unit</td>
                            <td class="text-end pe-4 fw-bold text-success">Rp {{ number_format($nilaiOutput, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Raw Materials Consumed Table (if any) -->
    @if ($produksi->bahanMutations->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold mb-0 text-warning-emphasis">
                    <i class="bi bi-layers-half me-1"></i> Rincian Bahan Baku Gudang yang Terpakai (`Pemakaian Internal`)
                </h6>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                    {{ $produksi->bahanMutations->count() }} Jenis Bahan Baku
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th>Bahan Baku & SKU</th>
                                <th>Kategori</th>
                                <th class="text-end">HPP Satuan</th>
                                <th class="text-end">Jumlah Dipakai</th>
                                <th class="text-end pe-4">Subtotal Biaya Bahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($produksi->bahanMutations as $mIndex => $mutasi)
                                @php
                                    $mp = $mutasi->produk;
                                    $mQty = abs((float) $mutasi->jumlah);
                                    $mHpp = (float) ($mp?->harga_beli ?: 0);
                                    $mSub = $mQty * $mHpp;
                                @endphp
                                <tr>
                                    <td class="ps-4 text-muted">{{ $mIndex + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $mp->nama ?? '-' }}</div>
                                        <small class="text-muted font-monospace">{{ $mp->kode_katalog ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $mp->kategori->nama_kategori ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-muted">
                                        Rp {{ number_format($mHpp, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        -{{ number_format($mQty, 2, ',', '.') }}
                                        <span class="text-muted fw-normal small">{{ $mp?->satuan?->label() ?? '' }}</span>
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-danger">
                                        Rp {{ number_format($mSub, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="table-warning">
                                <td colspan="5" class="text-end fw-bold">Total Biaya Bahan Baku Terpakai:</td>
                                <td class="text-end pe-4 fw-bold text-danger">Rp {{ number_format($biayaBahan, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Cancel Produksi -->
    @if ($isCancelModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger-subtle text-danger">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Pembatalan Produksi
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeCancelModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            Apakah Anda yakin ingin membatalkan catatan produksi <strong>{{ $produksi->no_produksi }}</strong>?
                        </p>
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Tindakan ini akan <strong>menarik kembali (mengurangi) stok barang jadi</strong> yang telah masuk ke <strong>{{ $produksi->gudang->nama_gudang ?? 'Gudang' }}</strong>
                            @if ($produksi->bahanMutations->isNotEmpty())
                                dan <strong>mengembalikan stok bahan baku</strong> yang telah dipotong.
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeCancelModal">Batal</button>
                        <button type="button" class="btn btn-danger fw-bold" wire:click="cancel" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="cancel">
                                <i class="bi bi-trash me-1"></i>Ya, Batalkan Produksi
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
    @endif
</div>
