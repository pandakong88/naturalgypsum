<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 font-weight-bold text-dark">Manajemen User</h4>
            <p class="text-muted mb-0 small">Kelola akun pengguna, peran sistem, dan status aktifasi.</p>
        </div>
        <div>
            @can('create_users')
                <button wire:click="openCreateModal" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="bi bi-person-plus me-1"></i> Tambah User Baru
                </button>
            @endcan
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-start-0" placeholder="Cari Nama / Email User...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="filterRole" class="form-select form-select-sm">
                        <option value="">-- Semua Role --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="active">User Aktif</option>
                        <option value="trashed">Nonaktif (Terhapus)</option>
                        <option value="all">Semua Status</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3">Nama Pengguna</th>
                            <th class="py-3">Email</th>
                            <th class="py-3">Role / Hak Akses</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="py-3">Dibuat Pada</th>
                            <th class="pe-3 py-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($users as $user)
                            <tr class="{{ $user->trashed() ? 'bg-light text-muted opacity-75' : '' }}">
                                <td class="ps-3 fw-semibold">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="text-dark fw-bold">{{ $user->name }}</div>
                                            @if(auth()->id() === $user->id)
                                                <span class="badge bg-info-subtle text-info border border-info-subtle fs-7">Akun Anda</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code>{{ $user->email }}</code>
                                </td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="badge bg-secondary-subtle text-muted border px-2 py-1">Tanpa Role</span>
                                    @endforelse
                                </td>
                                <td class="text-center">
                                    @if($user->trashed())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Nonaktif</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</small>
                                </td>
                                <td class="pe-3 text-end">
                                    @if($user->trashed())
                                        @can('restore', $user)
                                            <button wire:click="restoreUser({{ $user->id }})" class="btn btn-outline-success btn-sm py-1 px-2" title="Aktifkan Kembali">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                            </button>
                                        @endcan
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            @can('update', $user)
                                                <button wire:click="openEditModal({{ $user->id }})" class="btn btn-outline-primary py-1 px-2" title="Edit Profil & Role">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            @endcan
                                            @can('delete', $user)
                                                <button wire:click="deleteUser({{ $user->id }})" onclick="return confirm('Apakah Anda yakin ingin menonaktifkan akun user ini?') || event.stopImmediatePropagation()" class="btn btn-outline-danger py-1 px-2" title="Nonaktifkan Akun">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people display-6 d-block mb-2 text-secondary"></i>
                                    Tidak ada data user yang sesuai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Create / Edit User -->
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-person-gear me-1 text-primary"></i>
                            {{ $editingUserId ? 'Edit Akun User' : 'Tambah User Baru' }}
                        </h5>
                        <button type="button" wire:click="$set('showModal', false)" class="btn-close"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" wire:model="name" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="Contoh: Budi Santoso">
                                @error('name') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" wire:model="email" class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="user@naturalgypsum.com">
                                @error('email') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Peran / Role Sistem <span class="text-danger">*</span></label>
                                <select wire:model="selectedRole" class="form-select form-select-sm @error('selectedRole') is-invalid @enderror">
                                    <option value="">-- Pilih Role --</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->name }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedRole') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                            </div>

                            <hr class="my-3 text-muted">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">
                                    Password {{ $editingUserId ? '(Kosongkan jika tidak diubah)' : '*' }}
                                </label>
                                <input type="password" wire:model="password" class="form-control form-control-sm @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter">
                                @error('password') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Konfirmasi Password</label>
                                <input type="password" wire:model="password_confirmation" class="form-control form-control-sm" placeholder="Ulangi password">
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="$set('showModal', false)" class="btn btn-secondary btn-sm">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3" wire:loading.attr="disabled">
                                <i class="bi bi-check-lg me-1"></i> Simpan User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
