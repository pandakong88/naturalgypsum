<div>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">Retur Supplier {{ $retur->no_retur }}</h4>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check-circle me-1"></i> Selesai / Stok Keluar
                </span>
            </div>
            <p class="text-muted small mb-0">
                Dicatat pada {{ $retur->created_at->format('d/m/Y H:i') }} oleh {{ $retur->user->name ?? ($retur->creator->name ?? 'Sistem') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('retur-supplier.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            @can('delete', $retur)
                <button type="button" wire:click="cancel" wire:confirm="Yakin ingin membatalkan retur supplier ini? Stok akan dikembalikan ke gudang." class="btn btn-outline-danger">
                    <i class="bi bi-x-circle me-1"></i> Batalkan Retur
                </button>
            @endcan
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Bukti Retur
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
                            <i class="bi bi-truck fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Supplier Penerima Retur</span>
                            <h6 class="fw-bold mb-0">{{ $retur->supplier->nama_supplier ?? '-' }}</h6>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">{{ $retur->supplier->kontak ?? 'Tidak ada kontak' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-secondary-subtle text-secondary rounded me-3">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Gudang Asal Barang</span>
                            <h6 class="fw-bold mb-0">{{ $retur->gudang->nama_gudang ?? '-' }}</h6>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Faktur Terkait: {{ $retur->pembelian->no_pembelian ?? 'Tidak ada referensi faktur' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="card-title fw-bold mb-0 text-primary">
                <i class="bi bi-box-seam me-1"></i> Rincian Barang yang Diretur
            </h6>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-1">{{ $retur->produk->nama ?? '-' }}</h5>
                    <p class="text-muted small mb-0">SKU: {{ $retur->produk->kode_katalog ?? 'Tanpa SKU' }} | Kategori: {{ $retur->produk->kategori->nama_kategori ?? '-' }}</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <span class="text-muted small d-block">Jumlah Retur:</span>
                    <h3 class="fw-bold text-danger mb-0">
                        {{ number_format((float) $retur->jumlah, 2, ',', '.') }} {{ $retur->produk->satuan->label() ?? '' }}
                    </h3>
                </div>
            </div>
            @if ($retur->alasan)
                <div class="mt-3 pt-3 border-top">
                    <span class="text-muted small d-block fw-semibold">Alasan Retur:</span>
                    <p class="mb-0 text-muted">{{ $retur->alasan }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
