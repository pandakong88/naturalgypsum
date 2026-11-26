<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-sm-center mb-3 mb-md-4 flex-column flex-sm-row gap-2">
        <div>
            <h4 class="fw-bold mb-1">Master Kategori</h4>
            <p class="text-muted small mb-0">Kelola kelompok kategori produk material bangunan.</p>
        </div>
        @can('create', App\Domains\Catalog\Models\Kategori::class)
            <div class="w-100 w-sm-auto d-grid d-sm-block">
                <button wire:click="openCreateModal" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Kategori
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
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari nama kategori / sub-kategori..."
                        >
                    </div>
                </div>
                <div class="col-12 col-sm-7 col-md-4 col-lg-3">
                    <select wire:model.live="filterParent" class="form-select">
                        <option value="">Semua Tingkatan</option>
                        <option value="root">📁 Hanya Kategori Induk</option>
                        <option value="child">↳ Hanya Sub-Kategori</option>
                        @if($parentOptions->isNotEmpty())
                            <optgroup label="Berdasarkan Induk:">
                                @foreach($parentOptions as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama_kategori }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>
                <div class="col-12 col-sm-5 col-md-3 col-lg-5 text-sm-end">
                    <div class="form-check form-switch d-inline-block mb-0">
                        <input class="form-check-input" type="checkbox" id="showTrashed" wire:model.live="showTrashed">
                        <label class="form-check-label small" for="showTrashed">Tampilkan Data Terhapus</label>
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
                    @forelse ($kategoris as $index => $kategori)
                        <div class="list-group-item p-3 {{ $kategori->parent_id ? 'bg-light-subtle ps-4' : '' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    @if ($kategori->parent_id)
                                        <i class="bi bi-arrow-return-right text-muted flex-shrink-0"></i>
                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="text-muted small">#{{ $kategoris->firstItem() + $index }}</span>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">Sub</span>
                                            </div>
                                            <div class="fw-semibold text-dark">{{ $kategori->nama_kategori }}</div>
                                        </div>
                                    @else
                                        <div class="rounded-circle bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                            <i class="bi bi-folder2-open fs-6"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-muted small d-block">#{{ $kategoris->firstItem() + $index }}</span>
                                            <div class="fw-bold text-dark">{{ $kategori->nama_kategori }}</div>
                                        </div>
                                    @endif
                                </div>

                                @if ($kategori->trashed())
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle flex-shrink-0">Nonaktif</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0">Aktif</span>
                                @endif
                            </div>

                            <!-- Badges: Hierarchy & Product Count -->
                            <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                                @if ($kategori->parent_id)
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-diagram-2 me-1"></i>Induk: <strong>{{ $kategori->parent?->nama_kategori ?? '-' }}</strong>
                                    </span>
                                    <a href="{{ route('master.produk.index', ['filterKategori' => $kategori->id]) }}"
                                       class="badge bg-secondary-subtle text-secondary border text-decoration-none">
                                        <i class="bi bi-box-seam me-1"></i>{{ $kategori->produks_count }} Produk
                                    </a>
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-folder-fill me-1"></i>Kategori Utama
                                    </span>
                                    @if ($kategori->children->isNotEmpty())
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $kategori->children->count() }} Sub-kategori
                                        </span>
                                    @endif
                                    <a href="{{ route('master.produk.index', ['filterKategori' => $kategori->id]) }}"
                                       class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none">
                                        <i class="bi bi-boxes me-1"></i>{{ $kategori->total_produk_count }} Produk
                                        @if ($kategori->children->isNotEmpty())
                                            <span class="text-muted fw-normal">(total)</span>
                                        @endif
                                    </a>
                                @endif
                            </div>

                            <!-- Footer: Creator Info & Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-person me-1"></i>{{ $kategori->creator?->name ?? '-' }}
                                    @if ($kategori->created_at)
                                        &bull; {{ $kategori->created_at->format('d/m/Y') }}
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    @if ($kategori->trashed())
                                        @can('restore', $kategori)
                                            <button wire:click="restore({{ $kategori->id }})" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        @if (! $kategori->parent_id)
                                            @can('create', App\Domains\Catalog\Models\Kategori::class)
                                                <button wire:click="openCreateModal({{ $kategori->id }})" class="btn btn-sm btn-outline-success" title="Tambah Sub-kategori">
                                                    <i class="bi bi-plus-lg"></i> Sub
                                                </button>
                                            @endcan
                                        @endif
                                        @can('update', $kategori)
                                            <button wire:click="openEditModal({{ $kategori->id }})" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @endcan
                                        @can('delete', $kategori)
                                            <button wire:click="confirmDelete({{ $kategori->id }})" class="btn btn-sm btn-outline-danger" title="Nonaktifkan">
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
                                <i class="bi bi-tags display-4 d-block mb-2 text-secondary"></i>
                                <p class="mb-0">Tidak ada data kategori ditemukan.</p>
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
                            <th>Nama Kategori</th>
                            <th>Tingkatan</th>
                            <th>Jumlah Produk</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 200px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kategoris as $index => $kategori)
                            <tr class="{{ $kategori->parent_id ? 'table-light-subtle' : 'fw-semibold' }}">
                                <td class="ps-4 text-muted">{{ $kategoris->firstItem() + $index }}</td>
                                <td>
                                    @if ($kategori->parent_id)
                                        <div class="d-flex align-items-center gap-2 ps-3">
                                            <i class="bi bi-arrow-return-right text-muted"></i>
                                            <span class="badge bg-light text-secondary border me-1">Sub</span>
                                            <span class="fw-semibold text-dark">{{ $kategori->nama_kategori }}</span>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="bi bi-folder2-open fs-6"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark fs-6">{{ $kategori->nama_kategori }}</span>
                                                @if ($kategori->children->isNotEmpty())
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle ms-2" style="font-size: 0.7rem;">
                                                        {{ $kategori->children->count() }} Sub-kategori
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($kategori->parent_id)
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="bi bi-diagram-2 me-1"></i>Induk: <strong>{{ $kategori->parent?->nama_kategori ?? '-' }}</strong>
                                        </span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-folder-fill me-1"></i>Kategori Utama
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($kategori->parent_id)
                                        <a href="{{ route('master.produk.index', ['filterKategori' => $kategori->id]) }}"
                                           class="badge bg-secondary-subtle text-secondary border text-decoration-none"
                                           title="Klik untuk melihat produk dalam sub-kategori {{ $kategori->nama_kategori }}">
                                            <i class="bi bi-box-seam me-1"></i>{{ $kategori->produks_count }} Produk
                                        </a>
                                    @else
                                        <a href="{{ route('master.produk.index', ['filterKategori' => $kategori->id]) }}"
                                           class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none"
                                           title="Klik untuk melihat semua produk dalam kategori {{ $kategori->nama_kategori }}">
                                            <i class="bi bi-boxes me-1"></i>{{ $kategori->total_produk_count }} Produk
                                            @if ($kategori->children->isNotEmpty())
                                                <span class="text-muted fw-normal" style="font-size: 0.72rem;">(total)</span>
                                            @endif
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    <small class="d-block text-dark">{{ $kategori->creator?->name ?? '-' }}</small>
                                    <small class="text-muted">{{ $kategori->created_at?->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if ($kategori->trashed())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($kategori->trashed())
                                        @can('restore', $kategori)
                                            <button wire:click="restore({{ $kategori->id }})" class="btn btn-sm btn-outline-success" title="Pulihkan Kategori">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            @if (! $kategori->parent_id)
                                                @can('create', App\Domains\Catalog\Models\Kategori::class)
                                                    <button wire:click="openCreateModal({{ $kategori->id }})" class="btn btn-outline-success" title="Tambah Sub-kategori ke {{ $kategori->nama_kategori }}">
                                                        <i class="bi bi-plus-lg"></i> Sub
                                                    </button>
                                                @endcan
                                            @endif
                                            @can('update', $kategori)
                                                <button wire:click="openEditModal({{ $kategori->id }})" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete', $kategori)
                                                <button wire:click="confirmDelete({{ $kategori->id }})" class="btn btn-outline-danger" title="Nonaktifkan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-tags display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data kategori ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($kategoris->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $kategoris->links() }}
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
                            {{ $kategoriId ? 'Edit Kategori / Sub-Kategori' : ($parent_id ? 'Tambah Sub-Kategori Baru' : 'Tambah Kategori Baru') }}
                        </h5>
                        <button type="button" wire:click="closeFormModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body p-3 p-sm-4">
                            <!-- Parent Category Select -->
                            <div class="mb-3">
                                <label for="parent_id" class="form-label fw-semibold">
                                    Kategori Induk
                                </label>
                                @if ($hasChildren)
                                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-2">
                                        <i class="bi bi-info-circle-fill me-2 fs-6"></i>
                                        <div>
                                            Kategori ini merupakan <strong>Kategori Utama</strong> yang memiliki {{ $childrenCount }} sub-kategori aktif sehingga tidak dapat diubah menjadi sub-kategori.
                                        </div>
                                    </div>
                                @endif
                                <select id="parent_id" wire:model="parent_id" class="form-select @error('parent_id') is-invalid @enderror" {{ $hasChildren ? 'disabled' : '' }}>
                                    <option value="">-- Jadikan Kategori Utama (Tanpa Induk) --</option>
                                    @foreach ($parentOptions as $parent)
                                        <option value="{{ $parent->id }}">📁 {{ $parent->nama_kategori }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                    {{ $hasChildren 
                                        ? 'Pindahkan atau hapus semua sub-kategori di bawahnya terlebih dahulu jika ingin mengubahnya menjadi sub-kategori.' 
                                        : 'Pilih kategori induk jika ini adalah sub-kategori, atau pilih "Jadikan Kategori Utama" untuk mengeluarkannya menjadi induk mandiri.' }}
                                </small>
                                @error('parent_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Category Name Input -->
                            <div class="mb-3">
                                <label for="nama_kategori" class="form-label fw-semibold">
                                    Nama Kategori / Sub-Kategori <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_kategori"
                                    wire:model="nama_kategori"
                                    class="form-control @error('nama_kategori') is-invalid @enderror"
                                    placeholder="Contoh: PVC, Gypsum, Seri Dof, Wallpanel, Lis Moulding"
                                    autofocus
                                    required
                                >
                                @error('nama_kategori')
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
                            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Nonaktifkan Kategori
                        </h5>
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin menonaktifkan kategori <strong>{{ $kategoriToDeleteName }}</strong>?
                        </p>
                        <small class="text-muted d-block mt-2">
                            Data yang dinonaktifkan tidak akan muncul pada pilihan pembuatan produk baru, namun data riwayat tetap tersimpan aman.
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
