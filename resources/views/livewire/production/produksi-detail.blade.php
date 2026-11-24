<div>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">Catatan Produksi {{ $produksi->no_produksi }}</h4>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check-circle me-1"></i> Selesai / Stok Masuk
                </span>
            </div>
            <p class="text-muted small mb-0">
                Dicatat pada {{ $produksi->created_at->format('d/m/Y H:i') }} oleh {{ $produksi->user->name ?? ($produksi->creator->name ?? 'Sistem') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('produksi.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            @can('delete', $produksi)
                <button type="button" wire:click="cancel" wire:confirm="Yakin ingin membatalkan catatan produksi ini? Stok yang telah masuk akan dikurangi kembali." class="btn btn-outline-danger">
                    <i class="bi bi-x-circle me-1"></i> Batalkan Produksi
                </button>
            @endcan
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Laporan
            </button>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-6">
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
                    <p class="text-muted small mb-0">{{ $produksi->gudang->alamat ?? 'Tidak ada alamat' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-info-subtle text-info rounded me-3">
                            <i class="bi bi-calendar-check fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Tanggal & Catatan</span>
                            <h6 class="fw-bold mb-0">{{ $produksi->tanggal->format('d/m/Y') }}</h6>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">{{ $produksi->catatan ?: 'Tidak ada catatan khusus' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="card-title fw-bold mb-0 text-primary">
                <i class="bi bi-list-check me-1"></i> Rincian Barang Jadi yang Masuk
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
                            <th class="text-end pe-4">Jumlah Output</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($produksi->details as $index => $detail)
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
