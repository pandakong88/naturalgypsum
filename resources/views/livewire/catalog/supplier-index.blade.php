<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Master Supplier</h4>
            <p class="text-muted small mb-0">Kelola daftar pemasok bahan material, kontak PIC, alamat pabrik, dan rekening pembayaran.</p>
        </div>
        @can('create', App\Domains\Catalog\Models\Supplier::class)
            <button wire:click="openCreateModal" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Supplier
            </button>
        @endcan
    </div>

    <!-- Flash Notifications -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari nama supplier, kontak, alamat, bank, atau rekening..."
                        >
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 ms-auto text-md-end">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" id="showTrashedSupplier" wire:model.live="showTrashed">
                        <label class="form-check-label small" for="showTrashedSupplier">Tampilkan Data Terhapus</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Nama Supplier & Alamat</th>
                            <th>Kontak & Email</th>
                            <th>Rekening Bank</th>
                            <th>Transaksi Pembelian</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $index => $supplier)
                            <tr>
                                <td class="ps-4 text-muted">{{ $suppliers->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-truck fs-6"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block">{{ $supplier->nama_supplier }}</span>
                                            @if ($supplier->alamat)
                                                <small class="text-secondary d-block text-truncate" style="max-width: 280px;" title="{{ $supplier->alamat }}">
                                                    <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $supplier->alamat }}
                                                </small>
                                            @else
                                                <small class="text-muted d-block">-</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @if ($supplier->kontak)
                                            <div class="small fw-semibold text-dark">
                                                <i class="bi bi-telephone me-1 text-primary"></i>{{ $supplier->kontak }}
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif

                                        @if ($supplier->email)
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-envelope me-1 text-secondary"></i>{{ $supplier->email }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($supplier->nama_bank || $supplier->no_rekening)
                                        <div>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-credit-card me-1 text-info"></i>{{ $supplier->nama_bank ?? 'Bank' }}: {{ $supplier->no_rekening ?? '-' }}
                                            </span>
                                            @if ($supplier->atas_nama_rekening)
                                                <small class="d-block text-muted mt-1 ps-1">
                                                    a.n. {{ $supplier->atas_nama_rekening }}
                                                </small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a
                                        href="{{ route('pembelian.index', ['supplierFilter' => $supplier->id]) }}"
                                        class="badge {{ $supplier->pembelians_count > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border' }} text-decoration-none"
                                        title="Klik untuk melihat riwayat transaksi pembelian dari supplier {{ $supplier->nama_supplier }}"
                                    >
                                        <i class="bi bi-cart-check me-1"></i>{{ $supplier->pembelians_count }} Pembelian
                                    </a>
                                </td>
                                <td>
                                    <small class="d-block text-dark">{{ $supplier->creator?->name ?? '-' }}</small>
                                    <small class="text-muted">{{ $supplier->created_at?->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if ($supplier->trashed())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($supplier->trashed())
                                        @can('restore', $supplier)
                                            <button wire:click="restore({{ $supplier->id }})" class="btn btn-sm btn-outline-success" title="Pulihkan Supplier">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            @can('update', $supplier)
                                                <button wire:click="openEditModal({{ $supplier->id }})" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete', $supplier)
                                                <button wire:click="confirmDelete({{ $supplier->id }})" class="btn btn-outline-danger" title="Nonaktifkan">
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
                                        <i class="bi bi-truck display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data supplier ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($suppliers->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if ($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-truck me-2 text-primary"></i>{{ $supplierId ? 'Edit Supplier' : 'Tambah Supplier Baru' }}
                        </h5>
                        <button type="button" wire:click="closeFormModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body p-4">
                            <!-- Section: Informasi Umum -->
                            <div class="mb-4">
                                <h6 class="fw-bold text-uppercase text-secondary small border-bottom pb-2 mb-3">
                                    <i class="bi bi-info-circle me-1"></i>Informasi Umum & Kontak
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="nama_supplier" class="form-label fw-semibold">
                                            Nama Supplier / Badan Usaha <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="nama_supplier"
                                            wire:model="nama_supplier"
                                            class="form-control @error('nama_supplier') is-invalid @enderror"
                                            placeholder="Contoh: PT Vifon Indah Perkasa, CV Gypsum Mandiri"
                                            autofocus
                                            required
                                        >
                                        @error('nama_supplier')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="kontak" class="form-label fw-semibold">Kontak / No. Telepon / PIC</label>
                                        <input
                                            type="text"
                                            id="kontak"
                                            wire:model="kontak"
                                            class="form-control @error('kontak') is-invalid @enderror"
                                            placeholder="Contoh: 0812-3456-7890 (Ibu Linda - Sales)"
                                        >
                                        @error('kontak')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">Alamat Email</label>
                                        <input
                                            type="email"
                                            id="email"
                                            wire:model="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            placeholder="Contoh: sales@vifonpvc.co.id"
                                        >
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label for="alamat" class="form-label fw-semibold">Alamat Kantor / Pabrik / Gudang</label>
                                        <textarea
                                            id="alamat"
                                            wire:model="alamat"
                                            class="form-control @error('alamat') is-invalid @enderror"
                                            rows="2"
                                            placeholder="Contoh: Kawasan Industri Cikupa Mas Blok C-12, Tangerang"
                                        ></textarea>
                                        @error('alamat')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Informasi Rekening Bank -->
                            <div>
                                <h6 class="fw-bold text-uppercase text-secondary small border-bottom pb-2 mb-3">
                                    <i class="bi bi-credit-card me-1"></i>Informasi Rekening Bank (Pembayaran Pembelian)
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="nama_bank" class="form-label fw-semibold">Nama Bank</label>
                                        <input
                                            type="text"
                                            id="nama_bank"
                                            wire:model="nama_bank"
                                            class="form-control @error('nama_bank') is-invalid @enderror"
                                            placeholder="Contoh: BCA, Mandiri, BRI"
                                        >
                                        @error('nama_bank')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="no_rekening" class="form-label fw-semibold">Nomor Rekening</label>
                                        <input
                                            type="text"
                                            id="no_rekening"
                                            wire:model="no_rekening"
                                            class="form-control @error('no_rekening') is-invalid @enderror"
                                            placeholder="Contoh: 8830192831"
                                        >
                                        @error('no_rekening')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="atas_nama_rekening" class="form-label fw-semibold">Atas Nama Rekening</label>
                                        <input
                                            type="text"
                                            id="atas_nama_rekening"
                                            wire:model="atas_nama_rekening"
                                            class="form-control @error('atas_nama_rekening') is-invalid @enderror"
                                            placeholder="Contoh: PT Vifon Indah Perkasa"
                                        >
                                        @error('atas_nama_rekening')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeFormModal" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
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
                            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Nonaktifkan Supplier
                        </h5>
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin menonaktifkan supplier <strong>{{ $supplierToDeleteName }}</strong>?
                        </p>
                        <small class="text-muted d-block mt-2">
                            Supplier yang memiliki data transaksi riwayat pembelian atau retur tidak dapat dinonaktifkan demi integritas pembukuan.
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
