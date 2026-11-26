<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-sm-center mb-3 mb-md-4 flex-column flex-sm-row gap-2">
        <div>
            <h4 class="fw-bold mb-1">Master Merek</h4>
            <p class="text-muted small mb-0">Kelola daftar merek / brand produk material.</p>
        </div>
        @can('create', App\Domains\Catalog\Models\Merek::class)
            <div class="w-100 w-sm-auto d-grid d-sm-block">
                <button wire:click="openCreateModal" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Merek
                </button>
            </div>
        @endcan
    </div>

    <!-- Flash Notifications -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3 mb-md-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3 mb-md-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card mb-3 mb-md-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 g-md-3 align-items-center">
                <div class="col-12 col-sm-7 col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari nama merek..."
                        >
                    </div>
                </div>
                <div class="col-12 col-sm-5 col-md-6 col-lg-4 ms-auto text-sm-end">
                    <div class="form-check form-switch d-inline-block mb-0">
                        <input class="form-check-input" type="checkbox" id="showTrashedMerek" wire:model.live="showTrashed">
                        <label class="form-check-label small" for="showTrashedMerek">Tampilkan Data Terhapus</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table (Desktop & Tablet) & Card List (Mobile) -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                <div class="list-group list-group-flush">
                    @forelse ($mereks as $index => $merek)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <div class="rounded-circle bg-secondary-subtle text-secondary p-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                        <i class="bi bi-tag-fill fs-6 text-primary"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-muted small d-block">#{{ $mereks->firstItem() + $index }}</span>
                                        <div class="fw-bold text-dark">{{ $merek->nama_merek }}</div>
                                    </div>
                                </div>
                                @if ($merek->trashed())
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle flex-shrink-0">Nonaktif</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0">Aktif</span>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <a href="{{ route('master.produk.index', ['filterMerek' => $merek->id]) }}"
                                       class="badge bg-secondary-subtle text-secondary border text-decoration-none">
                                        <i class="bi bi-box-seam me-1"></i>{{ $merek->produks_count }} Produk
                                    </a>
                                    <span class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-person me-1"></i>{{ $merek->creator?->name ?? '-' }}
                                        @if ($merek->created_at)
                                            &bull; {{ $merek->created_at->format('d/m/Y') }}
                                        @endif
                                    </span>
                                </div>
                                <div class="d-flex gap-1">
                                    @if ($merek->trashed())
                                        @can('restore', $merek)
                                            <button wire:click="restore({{ $merek->id }})" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        @can('update', $merek)
                                            <button wire:click="openEditModal({{ $merek->id }})" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @endcan
                                        @can('delete', $merek)
                                            <button wire:click="confirmDelete({{ $merek->id }})" class="btn btn-sm btn-outline-danger" title="Nonaktifkan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 px-3">
                            <div class="text-muted">
                                <i class="bi bi-patch-check display-4 d-block mb-2 text-secondary"></i>
                                <p class="mb-0">Tidak ada data merek ditemukan.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Desktop & Tablet Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 60px;">No</th>
                            <th>Nama Merek</th>
                            <th>Jumlah Produk</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mereks as $index => $merek)
                            <tr>
                                <td class="ps-4 text-muted">{{ $mereks->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-secondary-subtle text-secondary p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-tag-fill fs-6 text-primary"></i>
                                        </div>
                                        <span class="fw-bold text-dark">{{ $merek->nama_merek }}</span>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('master.produk.index', ['filterMerek' => $merek->id]) }}"
                                       class="badge bg-secondary-subtle text-secondary border text-decoration-none"
                                       title="Klik untuk melihat produk dengan merek {{ $merek->nama_merek }}">
                                        <i class="bi bi-box-seam me-1"></i>{{ $merek->produks_count }} Produk
                                    </a>
                                </td>
                                <td>
                                    <small class="d-block text-dark">{{ $merek->creator?->name ?? '-' }}</small>
                                    <small class="text-muted">{{ $merek->created_at?->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if ($merek->trashed())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($merek->trashed())
                                        @can('restore', $merek)
                                            <button wire:click="restore({{ $merek->id }})" class="btn btn-sm btn-outline-success" title="Pulihkan Merek">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            @can('update', $merek)
                                                <button wire:click="openEditModal({{ $merek->id }})" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete', $merek)
                                                <button wire:click="confirmDelete({{ $merek->id }})" class="btn btn-outline-danger" title="Nonaktifkan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-patch-check display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data merek ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($mereks->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $mereks->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if ($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            {{ $merekId ? 'Edit Merek' : 'Tambah Merek Baru' }}
                        </h5>
                        <button type="button" wire:click="closeFormModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body p-3 p-sm-4">
                            <div class="mb-3">
                                <label for="nama_merek" class="form-label fw-semibold">
                                    Nama Merek <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_merek"
                                    wire:model="nama_merek"
                                    class="form-control @error('nama_merek') is-invalid @enderror"
                                    placeholder="Contoh: Vifon, Natural Gypsum"
                                    autofocus
                                    required
                                >
                                @error('nama_merek')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light d-flex gap-2">
                            <button type="button" wire:click="closeFormModal" class="btn btn-secondary flex-fill flex-sm-grow-0">Batal</button>
                            <button type="submit" class="btn btn-primary flex-fill flex-sm-grow-0" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-check-lg me-1"></i>Simpan
                                </span>
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($isDeleteModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Nonaktifkan Merek
                        </h5>
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin menonaktifkan merek <strong>{{ $merekToDeleteName }}</strong>?
                        </p>
                        <small class="text-muted d-block mt-2">
                            Data yang dinonaktifkan tidak akan muncul pada pilihan produk baru.
                        </small>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn btn-secondary">Batal</button>
                        <button type="button" wire:click="delete" class="btn btn-danger" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="delete">
                                <i class="bi bi-trash me-1"></i>Ya, Nonaktifkan
                            </span>
                            <span wire:loading wire:target="delete">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
