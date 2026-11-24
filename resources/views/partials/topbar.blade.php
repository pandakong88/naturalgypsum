<header id="topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle Navigation">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="d-none d-sm-block">
            <span class="text-muted small">Sistem Operasional & Manajemen Stok</span>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        @auth
            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5 text-primary"></i>
                    <span class="fw-semibold">{{ auth()->user()->name }}</span>
                    @if(auth()->user()->roles->first())
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">
                            {{ auth()->user()->roles->first()->name }}
                        </span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @endauth
    </div>
</header>
