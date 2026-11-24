<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h5 class="card-title fw-bold text-center mb-4">Masuk ke Akun Anda</h5>

        <form wire:submit="login">
            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <div class="input-group has-validation">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="contoh@naturalgypsum.com"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group has-validation">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        id="password"
                        wire:model="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Remember Me -->
            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" wire:model="remember">
                <label class="form-check-label small" for="remember">Ingat saya pada perangkat ini</label>
            </div>

            <!-- Submit Button -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary py-2 fw-semibold" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                    </span>
                    <span wire:loading wire:target="login">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Memproses...
                    </span>
                </button>
            </div>
        </form>

        <!-- Divider Quick Login -->
        <div class="position-relative my-4 text-center">
            <hr class="text-secondary opacity-25">
            <span class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Login (Testing Role)
            </span>
        </div>

        <p class="text-muted small text-center mb-3">
            Klik role untuk langsung masuk secara instan tanpa perlu mengetik:
        </p>

        <!-- Quick Login Role Cards -->
        <div class="d-flex flex-column gap-2">
            @foreach($demoRoles as $roleKey => $roleData)
                <div class="card border border-light-subtle rounded-3 p-2 bg-light shadow-none" wire:key="quick-role-{{ $roleKey }}">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            <div class="rounded-circle d-flex align-items-center justify-content-center border {{ $roleData['badge'] }}" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="bi {{ $roleData['icon'] }} fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="fw-bold text-dark small text-truncate">{{ $roleKey }}</span>
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.725rem;">
                                    {{ $roleData['email'] }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <!-- Fill Form Button -->
                            <button
                                type="button"
                                wire:click="fillCredentials('{{ $roleKey }}')"
                                class="btn btn-sm btn-outline-secondary px-2 py-1"
                                title="Isi form input dengan akun ini"
                            >
                                <i class="bi bi-pencil-square" style="font-size: 0.8rem;"></i>
                            </button>

                            <!-- 1-Click Instant Login Button -->
                            <button
                                type="button"
                                wire:click="quickLogin('{{ $roleKey }}')"
                                wire:loading.attr="disabled"
                                class="btn btn-sm {{ $roleData['btn_variant'] }} px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                                style="font-size: 0.8rem;"
                            >
                                <span wire:loading.remove wire:target="quickLogin('{{ $roleKey }}')">
                                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                                </span>
                                <span wire:loading wire:target="quickLogin('{{ $roleKey }}')">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width: 0.75rem; height: 0.75rem;"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-3 text-muted" style="font-size: 0.725rem;">
            <i class="bi bi-info-circle me-1"></i>Password default semua akun: <code>password</code>
        </div>
    </div>
</div>
