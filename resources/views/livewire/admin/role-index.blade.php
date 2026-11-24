<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 font-weight-bold text-dark">Role & Hak Akses</h4>
            <p class="text-muted mb-0 small">Struktur peran otorisasi pengguna berbasis Spatie Permission RBAC.</p>
        </div>
    </div>

    <!-- Flash message -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Roles List -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-lock me-1 text-primary"></i> Daftar Role Sistem</h6>
                </div>
                <div class="list-group list-group-flush border-top-0">
                    @foreach($roles as $role)
                        <button type="button" wire:click="selectRole({{ $role->id }})" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 {{ ($selectedRole && $selectedRole->id === $role->id) ? 'active bg-primary text-white' : '' }}">
                            <div>
                                <div class="fw-bold">{{ $role->name }}</div>
                                <small class="{{ ($selectedRole && $selectedRole->id === $role->id) ? 'text-white-50' : 'text-muted' }}">
                                    {{ $role->users->count() }} pengguna aktif
                                </small>
                            </div>
                            <span class="badge {{ ($selectedRole && $selectedRole->id === $role->id) ? 'bg-light text-primary' : 'bg-primary-subtle text-primary border border-primary-subtle' }} rounded-pill">
                                {{ $role->permissions->count() }} Izin
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right: Role Detail & Permission Matrix -->
        <div class="col-lg-8">
            @if($selectedRole)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">Rincian Hak Akses: <span class="text-primary">{{ $selectedRole->name }}</span></h5>
                            <small class="text-muted">
                                @can('manage_roles')
                                    <i class="bi bi-hand-index-thumb me-1 text-primary"></i>Klik izin di bawah untuk mengaktifkan atau menonaktifkan hak akses role ini secara instan.
                                @else
                                    Daftar izin yang diberikan kepada pengguna dengan role ini.
                                @endcan
                            </small>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                            Total: {{ $selectedRole->permissions->count() }} Permission Aktif
                        </span>
                    </div>
                    <div class="card-body">
                        @php
                            $rolePermissionNames = $selectedRole->permissions->pluck('name')->toArray();
                            $canManage = auth()->user()->can('manage_roles');
                        @endphp

                        @foreach($groupedPermissions as $category => $perms)
                            <div class="mb-4">
                                <h6 class="fw-bold text-secondary text-uppercase fs-7 mb-2 border-bottom pb-1">
                                    <i class="bi bi-folder2-open me-1 text-primary"></i> {{ $category }}
                                </h6>
                                <div class="row g-2">
                                    @foreach($perms as $perm)
                                        @php
                                            $hasPerm = in_array($perm->name, $rolePermissionNames, true);
                                            $isCancelSales = $perm->name === 'cancel_sales';
                                        @endphp
                                        <div class="col-md-6 col-xl-4">
                                            <div
                                                @if($canManage)
                                                    wire:click="togglePermission({{ $selectedRole->id }}, '{{ $perm->name }}')"
                                                    style="cursor: pointer;"
                                                    role="button"
                                                    title="Klik untuk {{ $hasPerm ? 'mencabut' : 'memberikan' }} izin ini"
                                                @endif
                                                class="p-2 rounded border transition-all {{ $hasPerm ? ($isCancelSales ? 'bg-danger-subtle border-danger text-dark fw-bold' : 'bg-success-subtle border-success-subtle text-dark') : 'bg-light text-muted opacity-50 border-0' }} d-flex align-items-center justify-content-between small"
                                            >
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($hasPerm)
                                                        <i class="bi {{ $isCancelSales ? 'bi-check-circle-fill text-danger' : 'bi-check-circle-fill text-success' }} fs-6"></i>
                                                    @else
                                                        <i class="bi bi-dash-circle text-secondary fs-6"></i>
                                                    @endif
                                                    <div>
                                                        <div class="{{ $hasPerm ? 'fw-semibold' : '' }}">{{ $perm->name }}</div>
                                                        @if($isCancelSales)
                                                            <span class="badge bg-danger text-white" style="font-size: 0.65rem;">Batal Nota</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($canManage)
                                                    <span class="badge {{ $hasPerm ? 'bg-success' : 'bg-secondary' }}" style="font-size: 0.65rem;">
                                                        {{ $hasPerm ? 'ON' : 'OFF' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
