<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-sm-center mb-3 mb-md-4 flex-column flex-sm-row gap-2">
        <div>
            <h4 class="fw-bold mb-1">Master Pelanggan</h4>
            <p class="text-muted small mb-0">Kelola data pelanggan, tipe akun, dan batas limit piutang.</p>
        </div>
        @can('create', App\Domains\Catalog\Models\Pelanggan::class)
            <div class="w-100 w-sm-auto d-grid d-sm-block">
                <button wire:click="openCreateModal" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Pelanggan
                </button>
            </div>
        @endcan
    </div>

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
                            placeholder="Cari nama atau kontak..."
                        >
                    </div>
                </div>
                <div class="col-12 col-sm-7 col-md-4 col-lg-3">
                    <select wire:model.live="filterTipe" class="form-select">
                        <option value="">Semua Tipe Pelanggan</option>
                        @foreach ($tipeOptions as $tipeOption)
                            <option value="{{ $tipeOption->value }}">{{ $tipeOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-5 col-md-3 col-lg-5 text-sm-end">
                    <div class="form-check form-switch d-inline-block mb-0">
                        <input class="form-check-input" type="checkbox" id="showTrashedPelanggan" wire:model.live="showTrashed">
                        <label class="form-check-label small" for="showTrashedPelanggan">Tampilkan Data Terhapus</label>
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
                    @forelse ($pelanggans as $index => $pelanggan)
                        @php
                            $badgeClass = match($pelanggan->tipe) {
                                \App\Domains\Catalog\Enums\TipePelanggan::Umum => 'bg-secondary-subtle text-secondary',
                                \App\Domains\Catalog\Enums\TipePelanggan::Pemborong => 'bg-info-subtle text-info',
                                \App\Domains\Catalog\Enums\TipePelanggan::Langganan => 'bg-primary-subtle text-primary',
                            };
                        @endphp
                        <div class="list-group-item p-3">
                            <!-- Top Row: Icon, Name, Contact & Status -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="d-flex align-items-start gap-2 min-w-0">
                                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="bi bi-person fs-6"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <span class="text-muted small">#{{ $pelanggans->firstItem() + $index }}</span>
                                            <span class="badge {{ $badgeClass }} border" style="font-size: 0.68rem;">
                                                {{ $pelanggan->tipe->label() }}
                                            </span>
                                        </div>
                                        <div class="fw-bold text-dark">{{ $pelanggan->nama }}</div>
                                        @if ($pelanggan->kontak)
                                            <small class="text-secondary d-block">
                                                <i class="bi bi-telephone me-1 text-primary"></i>{{ $pelanggan->kontak }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                @if ($pelanggan->trashed())
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle flex-shrink-0">Nonaktif</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0">Aktif</span>
                                @endif
                            </div>

                            <!-- Limit Piutang & Creator Info Box -->
                            <div class="bg-light rounded-2 p-2 mb-2 border d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Batas Limit Piutang</span>
                                <span class="fw-bold font-monospace text-dark">
                                    Rp {{ number_format($pelanggan->limit_piutang, 0, ',', '.') }}
                                </span>
                            </div>

                            <!-- Footer: Creator & Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center gap-2 pt-1">
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-person me-1"></i>{{ $pelanggan->creator?->name ?? '-' }}
                                    @if ($pelanggan->created_at)
                                        &bull; {{ $pelanggan->created_at->format('d/m/Y') }}
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    @if ($pelanggan->trashed())
                                        @can('restore', $pelanggan)
                                            <button wire:click="restore({{ $pelanggan->id }})" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        @can('update', $pelanggan)
                                            <button wire:click="openEditModal({{ $pelanggan->id }})" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @endcan
                                        @can('delete', $pelanggan)
                                            <button wire:click="confirmDelete({{ $pelanggan->id }})" class="btn btn-sm btn-outline-danger" title="Nonaktifkan">
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
                                <i class="bi bi-people display-4 d-block mb-2 text-secondary"></i>
                                <p class="mb-0">Tidak ada data pelanggan ditemukan.</p>
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
                            <th>Nama Pelanggan</th>
                            <th>Kontak</th>
                            <th>Tipe</th>
                            <th>Limit Piutang</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pelanggans as $index => $pelanggan)
                            <tr>
                                <td class="ps-4 text-muted">{{ $pelanggans->firstItem() + $index }}</td>
                                <td class="fw-semibold">
                                    <i class="bi bi-person me-1 text-primary"></i>{{ $pelanggan->nama }}
                                </td>
                                <td>{{ $pelanggan->kontak ?? '-' }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($pelanggan->tipe) {
                                            \App\Domains\Catalog\Enums\TipePelanggan::Umum => 'bg-secondary-subtle text-secondary',
                                            \App\Domains\Catalog\Enums\TipePelanggan::Pemborong => 'bg-info-subtle text-info',
                                            \App\Domains\Catalog\Enums\TipePelanggan::Langganan => 'bg-primary-subtle text-primary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} border">
                                        {{ $pelanggan->tipe->label() }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark">
                                    Rp {{ number_format($pelanggan->limit_piutang, 0, ',', '.') }}
                                </td>
                                <td>
                                    <small class="d-block text-dark">{{ $pelanggan->creator?->name ?? '-' }}</small>
                                    <small class="text-muted">{{ $pelanggan->created_at?->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if ($pelanggan->trashed())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($pelanggan->trashed())
                                        @can('restore', $pelanggan)
                                            <button wire:click="restore({{ $pelanggan->id }})" class="btn btn-sm btn-outline-success" title="Pulihkan Pelanggan">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            @can('update', $pelanggan)
                                                <button wire:click="openEditModal({{ $pelanggan->id }})" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete', $pelanggan)
                                                <button wire:click="confirmDelete({{ $pelanggan->id }})" class="btn btn-outline-danger" title="Nonaktifkan">
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
                                        <i class="bi bi-people display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data pelanggan ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($pelanggans->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $pelanggans->links() }}
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
                            {{ $pelangganId ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}
                        </h5>
                        <button type="button" wire:click="closeFormModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body p-3 p-sm-4">
                            <div class="mb-3">
                                <label for="nama" class="form-label fw-semibold">
                                    Nama Pelanggan <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama"
                                    wire:model="nama"
                                    class="form-control @error('nama') is-invalid @enderror"
                                    placeholder="Contoh: Toko Bangunan Berkah, Bpk. Subur"
                                    autofocus
                                    required
                                >
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="kontak" class="form-label fw-semibold">Kontak / No. Telepon</label>
                                <input
                                    type="text"
                                    id="kontak"
                                    wire:model="kontak"
                                    class="form-control @error('kontak') is-invalid @enderror"
                                    placeholder="Contoh: 0812-3456-7890"
                                >
                                @error('kontak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="tipe" class="form-label fw-semibold">
                                    Tipe Pelanggan <span class="text-danger">*</span>
                                </label>
                                <select id="tipe" wire:model="tipe" class="form-select @error('tipe') is-invalid @enderror" required>
                                    @foreach ($tipeOptions as $option)
                                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                    @endforeach
                                </select>
                                @error('tipe')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="limit_piutang" class="form-label fw-semibold">
                                    Batas Limit Piutang (Rp) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input
                                        type="number"
                                        step="1000"
                                        min="0"
                                        id="limit_piutang"
                                        wire:model="limit_piutang"
                                        class="form-control @error('limit_piutang') is-invalid @enderror"
                                        placeholder="0"
                                        required
                                    >
                                </div>
                                <small class="text-muted">Batas maksimal nominal piutang yang diperbolehkan untuk pelanggan ini.</small>
                                @error('limit_piutang')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
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
                            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Nonaktifkan Pelanggan
                        </h5>
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin menonaktifkan pelanggan <strong>{{ $pelangganToDeleteName }}</strong>?
                        </p>
                        <small class="text-muted d-block mt-2">
                            Data master pelanggan ini akan dinonaktifkan dari sistem.
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
