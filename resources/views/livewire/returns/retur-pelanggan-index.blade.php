<div>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Retur Barang dari Pelanggan</h4>
            <p class="text-muted small mb-0">Pengembalian barang dari pelanggan yang masuk kembali ke stok gudang</p>
        </div>
        @can('create', \App\Domains\Returns\Models\ReturPelanggan::class)
            <a href="{{ route('retur-pelanggan.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Catat Retur Baru
            </a>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Cari</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="No. Retur / Pelanggan / Produk...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Pelanggan</label>
                    <select wire:model.live="pelangganFilter" class="form-select">
                        <option value="">Semua Pelanggan</option>
                        @foreach ($pelanggans as $p)
                            <option value="{{ $p->id }}">{{ $p->nama_pelanggan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Gudang Penerima</label>
                    <select wire:model.live="gudangFilter" class="form-select">
                        <option value="">Semua Gudang</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" wire:model.live="startDate" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No. Retur</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th>Gudang Penerima</th>
                            <th>Jumlah</th>
                            <th>Ref. Penjualan</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returs as $r)
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <a href="{{ route('retur-pelanggan.show', $r->id) }}" class="text-primary text-decoration-none">
                                        {{ $r->no_retur }}
                                    </a>
                                </td>
                                <td>{{ $r->tanggal->format('d/m/Y') }}</td>
                                <td>{{ $r->pelanggan->nama_pelanggan ?? '-' }}</td>
                                <td>{{ $r->produk->nama ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border">
                                        {{ $r->gudang->nama_gudang ?? '-' }}
                                    </span>
                                </td>
                                <td class="fw-bold">{{ number_format((float) $r->jumlah, 2, ',', '.') }} {{ $r->produk->satuan->label() ?? '' }}</td>
                                <td>
                                    @if ($r->penjualan)
                                        <a href="{{ route('penjualan.show', $r->penjualan_id) }}" class="badge bg-light text-dark border text-decoration-none font-monospace">
                                            {{ $r->penjualan->no_penjualan }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('retur-pelanggan.show', $r->id) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada data retur pelanggan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($returs->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $returs->links() }}
            </div>
        @endif
    </div>
</div>
