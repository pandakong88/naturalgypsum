<div>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Catatan Produksi Barang Jadi</h4>
            <p class="text-muted small mb-0">Pencatatan hasil output produksi / barang siap jual yang masuk ke gudang</p>
        </div>
        @can('create', \App\Domains\Production\Models\Produksi::class)
            <a href="{{ route('produksi.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Catat Produksi Baru
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
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Cari</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="No. Produksi / Produk / Catatan...">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Gudang Penerima</label>
                    <select wire:model.live="gudangFilter" class="form-select">
                        <option value="">Semua Gudang</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
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
                            <th class="ps-4">No. Produksi</th>
                            <th>Tanggal</th>
                            <th>Gudang Penerima</th>
                            <th>Item Barang Jadi</th>
                            <th>Dicatat Oleh</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produksis as $p)
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <a href="{{ route('produksi.show', $p->id) }}" class="text-primary text-decoration-none">
                                        {{ $p->no_produksi }}
                                    </a>
                                </td>
                                <td>{{ $p->tanggal->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border">
                                        <i class="bi bi-building me-1"></i>{{ $p->gudang->nama_gudang ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $p->details->count() }} item ({{ number_format($p->details->sum('jumlah'), 2, ',', '.') }})</td>
                                <td><small class="text-muted">{{ $p->user->name ?? '-' }}</small></td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('produksi.show', $p->id) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada catatan produksi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($produksis->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $produksis->links() }}
            </div>
        @endif
    </div>
</div>
