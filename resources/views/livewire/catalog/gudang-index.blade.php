<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-sm-center mb-3 mb-md-4 flex-column flex-sm-row gap-2">
        <div>
            <h4 class="fw-bold mb-1">Master Gudang</h4>
            <p class="text-muted small mb-0">Kelola daftar gudang, PIC, dan lokasi penyimpanan material.</p>
        </div>
        @can('create', App\Domains\Catalog\Models\Gudang::class)
            <div class="w-100 w-sm-auto d-grid d-sm-block">
                <button wire:click="openCreateModal" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Gudang
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
                            placeholder="Cari nama gudang, lokasi, atau PIC..."
                        >
                    </div>
                </div>
                <div class="col-12 col-sm-5 col-md-6 col-lg-4 ms-auto text-sm-end">
                    <div class="form-check form-switch d-inline-block mb-0">
                        <input class="form-check-input" type="checkbox" id="showTrashedGudang" wire:model.live="showTrashed">
                        <label class="form-check-label small" for="showTrashedGudang">Tampilkan Data Terhapus</label>
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
                    @forelse ($gudangs as $index => $gudang)
                        <div class="list-group-item p-3">
                            <!-- Top Row: Icon, Name, Location & Status -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="d-flex align-items-start gap-2 min-w-0">
                                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="bi bi-building fs-6"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-muted small d-block">#{{ $gudangs->firstItem() + $index }}</span>
                                        <div class="fw-bold text-dark">{{ $gudang->nama_gudang }}</div>
                                        @if ($gudang->lokasi)
                                            <small class="text-secondary d-block">
                                                <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $gudang->lokasi }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                @if ($gudang->trashed())
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle flex-shrink-0">Nonaktif</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0">Aktif</span>
                                @endif
                            </div>

                            <!-- PIC & Stock Capacity Info Box -->
                            <div class="bg-light rounded-2 p-2 mb-2 border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="small">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Kontak / PIC</span>
                                    @if ($gudang->kontak)
                                        <span class="fw-semibold text-dark">
                                            <i class="bi bi-person-badge me-1 text-primary"></i>{{ $gudang->kontak }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Kapasitas & Isi Stok</span>
                                    <a href="{{ route('stok.index', ['filterGudang' => $gudang->id]) }}"
                                       class="badge bg-white text-dark border text-decoration-none">
                                        <i class="bi bi-boxes me-1 text-primary"></i>{{ $gudang->total_item_count ?? 0 }} Produk
                                        <span class="text-muted fw-normal">({{ number_format($gudang->total_kuantitas_stok ?? 0, 0, ',', '.') }} Qty)</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Footer: Creator Info & Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center gap-2 pt-1">
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-person me-1"></i>{{ $gudang->creator?->name ?? '-' }}
                                    @if ($gudang->created_at)
                                        &bull; {{ $gudang->created_at->format('d/m/Y') }}
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    @if ($gudang->trashed())
                                        @can('restore', $gudang)
                                            <button wire:click="restore({{ $gudang->id }})" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        @can('update', $gudang)
                                            <button wire:click="openEditModal({{ $gudang->id }})" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @endcan
                                        @can('delete', $gudang)
                                            <button wire:click="confirmDelete({{ $gudang->id }})" class="btn btn-sm btn-outline-danger" title="Nonaktifkan">
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
                                <i class="bi bi-building display-4 d-block mb-2 text-secondary"></i>
                                <p class="mb-0">Tidak ada data gudang ditemukan.</p>
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
                            <th>Nama Gudang</th>
                            <th>Lokasi / Alamat</th>
                            <th>Kontak / PIC</th>
                            <th>Kapasitas & Isi Stok</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gudangs as $index => $gudang)
                            <tr>
                                <td class="ps-4 text-muted">{{ $gudangs->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-building fs-6"></i>
                                        </div>
                                        <span class="fw-bold text-dark">{{ $gudang->nama_gudang }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-secondary small">{{ $gudang->lokasi ?? '-' }}</span>
                                </td>
                                <td>
                                    @if ($gudang->kontak)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person-badge me-1 text-primary"></i>{{ $gudang->kontak }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('stok.index', ['filterGudang' => $gudang->id]) }}"
                                       class="badge bg-secondary-subtle text-secondary border text-decoration-none"
                                       title="Klik untuk melihat rincian stok di gudang {{ $gudang->nama_gudang }}">
                                        <i class="bi bi-boxes me-1 text-primary"></i>{{ $gudang->total_item_count ?? 0 }} Produk
                                        <span class="text-muted fw-normal">({{ number_format($gudang->total_kuantitas_stok ?? 0, 0, ',', '.') }} Qty)</span>
                                    </a>
                                </td>
                                <td>
                                    <small class="d-block text-dark">{{ $gudang->creator?->name ?? '-' }}</small>
                                    <small class="text-muted">{{ $gudang->created_at?->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if ($gudang->trashed())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($gudang->trashed())
                                        @can('restore', $gudang)
                                            <button wire:click="restore({{ $gudang->id }})" class="btn btn-sm btn-outline-success" title="Pulihkan Gudang">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            @can('update', $gudang)
                                                <button wire:click="openEditModal({{ $gudang->id }})" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete', $gudang)
                                                <button wire:click="confirmDelete({{ $gudang->id }})" class="btn btn-outline-danger" title="Nonaktifkan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-building display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data gudang ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($gudangs->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $gudangs->links() }}
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
                            {{ $gudangId ? 'Edit Gudang' : 'Tambah Gudang Baru' }}
                        </h5>
                        <button type="button" wire:click="closeFormModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body p-3 p-sm-4">
                            <div class="mb-3">
                                <label for="nama_gudang" class="form-label fw-semibold">
                                    Nama Gudang <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_gudang"
                                    wire:model="nama_gudang"
                                    class="form-control @error('nama_gudang') is-invalid @enderror"
                                    placeholder="Contoh: Gudang Utama, Gudang Toko Depan"
                                    autofocus
                                    required
                                >
                                @error('nama_gudang')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="lokasi" class="form-label fw-semibold">Lokasi / Alamat</label>
                                <textarea
                                    id="lokasi"
                                    wire:model="lokasi"
                                    class="form-control @error('lokasi') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Contoh: Jl. Raya Industri Blok A No. 12"
                                ></textarea>
                                @error('lokasi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="kontak" class="form-label fw-semibold">Kontak / Penanggung Jawab (PIC)</label>
                                <input
                                    type="text"
                                    id="kontak"
                                    wire:model="kontak"
                                    class="form-control @error('kontak') is-invalid @enderror"
                                    placeholder="Contoh: Bpk. Budi (Kepala Gudang) - 0813-9876-5432"
                                >
                                @error('kontak')
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
                            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Nonaktifkan Gudang
                        </h5>
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin menonaktifkan gudang <strong>{{ $gudangToDeleteName }}</strong>?
                        </p>
                        <small class="text-muted d-block mt-2">
                            Data master gudang ini akan dinonaktifkan dari sistem.
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
