<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                Aturan Diskon & Promosi
                @if ($showTrashed)
                    <span class="badge bg-danger ms-1 ms-sm-2"><i class="bi bi-trash3 me-1"></i>Data Terhapus</span>
                @endif
            </h4>
            <p class="text-muted small mb-0">Kelola skema potongan harga otomatis berdasarkan kategori, merek, kuantiti, atau jenis pelanggan.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap w-100 w-md-auto">
            <button wire:click="toggleShowTrashed" class="btn {{ $showTrashed ? 'btn-outline-danger' : 'btn-outline-secondary' }} flex-fill flex-md-grow-0" title="{{ $showTrashed ? 'Kembali ke Data Aktif' : 'Lihat Aturan Terhapus' }}">
                <i class="bi {{ $showTrashed ? 'bi-arrow-left me-1' : 'bi-trash3 me-1' }}"></i>
                {{ $showTrashed ? 'Kembali ke Aturan Aktif' : 'Data Terhapus' }}
                @if ($trashedCount > 0 && ! $showTrashed)
                    <span class="badge bg-danger ms-1">{{ $trashedCount }}</span>
                @endif
            </button>
            @if (! $showTrashed)
                <button wire:click="openCreateModal" class="btn btn-primary flex-fill flex-md-grow-0">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Aturan Diskon
                </button>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3 mb-md-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Trashed Notice Banner -->
    @if ($showTrashed)
        <div class="alert alert-warning border-0 shadow-sm mb-3 mb-md-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning flex-shrink-0"></i>
                <div>
                    <strong>Mode Data Terhapus (Tempat Sampah)</strong>
                    <div class="small text-muted">Menampilkan aturan diskon yang sebelumnya dihapus. Anda dapat memulihkan (*Restore*) atau menghapus permanen data ini.</div>
                </div>
            </div>
            <button wire:click="toggleShowTrashed" class="btn btn-sm btn-outline-dark ms-auto">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </button>
        </div>
    @endif

    @if (! $showTrashed)
        <!-- Summary KPI Cards -->
        <div class="row g-2 g-md-3 mb-3 mb-md-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-2 p-sm-3 d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="text-muted small text-truncate">Total Aturan</div>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($totalCount) }}</h4>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded p-2 d-none d-sm-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi bi-tags fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-2 p-sm-3 d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="text-muted small text-truncate">Sedang Berjalan</div>
                            <h4 class="fw-bold mb-0 text-success">{{ number_format($activeCount) }}</h4>
                        </div>
                        <div class="bg-success-subtle text-success rounded p-2 d-none d-sm-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi bi-lightning-charge fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-2 p-sm-3 d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="text-muted small text-truncate">Akan Datang</div>
                            <h4 class="fw-bold mb-0 text-info">{{ number_format($upcomingCount) }}</h4>
                        </div>
                        <div class="bg-info-subtle text-info rounded p-2 d-none d-sm-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi bi-calendar-event fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-2 p-sm-3 d-flex align-items-center justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="text-muted small text-truncate">Kedaluwarsa / Off</div>
                            <h4 class="fw-bold mb-0 text-secondary">{{ number_format($expiredOrInactiveCount) }}</h4>
                        </div>
                        <div class="bg-secondary-subtle text-secondary rounded p-2 d-none d-sm-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi bi-pause-circle fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card mb-3 mb-md-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 g-md-3">
                <div class="col-12 col-md-4 col-lg-{{ $showTrashed ? '4' : '3' }}">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari nama promo / aturan..."
                        >
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-{{ $showTrashed ? '4' : '3' }}">
                    <select wire:model.live="filterTargetPelanggan" class="form-select">
                        <option value="">Semua Target Pelanggan</option>
                        <option value="semua">Semua Tipe Pelanggan</option>
                        <option value="umum">Khusus Umum</option>
                        <option value="pemborong">Khusus Aplikator</option>
                        <option value="langganan">Khusus Toko</option>
                        <option value="spesifik">Pelanggan Spesifik</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-{{ $showTrashed ? '4' : '3' }}">
                    <select wire:model.live="filterTargetProduk" class="form-select">
                        <option value="">Semua Target Produk</option>
                        <option value="semua">Semua Produk Katalog</option>
                        <option value="kategori">Berdasarkan Kategori</option>
                        <option value="merek">Berdasarkan Merek</option>
                        <option value="spesifik">Produk Spesifik</option>
                    </select>
                </div>
                @if (! $showTrashed)
                    <div class="col-12 col-md-12 col-lg-3">
                        <select wire:model.live="filterStatus" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active">Sedang Aktif Berjalan</option>
                            <option value="inactive">Nonaktif</option>
                            <option value="expired">Sudah Berakhir</option>
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Data Table (Desktop & Tablet) & Card List (Mobile) -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                <div class="list-group list-group-flush">
                    @forelse ($aturans as $index => $aturan)
                        @php
                            $today = \Carbon\Carbon::today();
                            $isExpired = $aturan->tanggal_selesai && $today->gt($aturan->tanggal_selesai);
                            $isNotStarted = $aturan->tanggal_mulai && $today->lt($aturan->tanggal_mulai);
                        @endphp
                        <div class="list-group-item p-3">
                            <!-- Top Row: Number, Status/Priority & Discount Value -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="d-flex align-items-center flex-wrap gap-1">
                                    <span class="text-muted small">#{{ $aturans->firstItem() + $index }}</span>
                                    @if (! $showTrashed)
                                        @if (! $aturan->is_active)
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                <i class="bi bi-pause-circle me-1"></i>Nonaktif
                                            </span>
                                        @elseif ($isExpired)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-x-circle me-1"></i>Kedaluwarsa
                                            </span>
                                        @elseif ($isNotStarted)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                <i class="bi bi-clock me-1"></i>Akan Datang
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle-fill me-1"></i>Aktif Berjalan
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-trash3 me-1"></i>{{ $aturan->deleted_at->format('d/m/Y H:i') }}
                                        </span>
                                    @endif

                                    @if ($aturan->priority > 0)
                                        <span class="badge bg-light text-primary border">
                                            <i class="bi bi-arrow-up-circle me-1"></i>Prioritas: {{ $aturan->priority }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-end flex-shrink-0">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fs-6">
                                        @if ($aturan->jenis_diskon === 'persentase')
                                            -{{ rtrim(rtrim(number_format($aturan->nilai_diskon, 2), '0'), '.') }}%
                                        @else
                                            -Rp {{ number_format($aturan->nilai_diskon, 0, ',', '.') }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <!-- Rule Name & Notes -->
                            <div class="mb-2">
                                <div class="fw-bold text-dark">{{ $aturan->nama }}</div>
                                @if ($aturan->keterangan)
                                    <small class="text-muted d-block">{{ $aturan->keterangan }}</small>
                                @endif
                            </div>

                            <!-- Targets & Conditions Box -->
                            <div class="bg-light rounded-2 p-2 mb-2 border small">
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <!-- Target Pelanggan Badge -->
                                    @if ($aturan->target_pelanggan === 'umum')
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="bi bi-person me-1"></i>Umum
                                        </span>
                                    @elseif ($aturan->target_pelanggan === 'pemborong')
                                        <span class="badge bg-info-subtle text-info border">
                                            <i class="bi bi-tools me-1"></i>Aplikator
                                        </span>
                                    @elseif ($aturan->target_pelanggan === 'langganan')
                                        <span class="badge bg-primary-subtle text-primary border">
                                            <i class="bi bi-shop me-1"></i>Toko
                                        </span>
                                    @elseif ($aturan->target_pelanggan === 'spesifik')
                                        <span class="badge bg-white text-dark border">
                                            <i class="bi bi-person-check me-1"></i>{{ $aturan->pelanggan?->nama ?? 'Pelanggan Terpilih' }}
                                        </span>
                                    @else
                                        <span class="badge bg-white text-muted border">
                                            Semua Tipe Pelanggan
                                        </span>
                                    @endif

                                    <!-- Target Produk Badge -->
                                    @if ($aturan->target_produk === 'kategori')
                                        @php
                                            $katCount = $aturan->kategoris->count();
                                            $katList = $katCount > 0 ? $aturan->kategoris->pluck('nama_kategori')->join(', ') : ($aturan->kategori?->nama_kategori ?? '-');
                                        @endphp
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" title="{{ $katList }}">
                                            <i class="bi bi-folder2-open me-1"></i>{{ $katCount > 1 ? $katCount . ' Kategori' : $katList }}
                                        </span>
                                    @elseif ($aturan->target_produk === 'merek')
                                        @php
                                            $mrkCount = $aturan->mereks->count();
                                            $mrkList = $mrkCount > 0 ? $aturan->mereks->pluck('nama_merek')->join(', ') : ($aturan->merek?->nama_merek ?? '-');
                                        @endphp
                                        <span class="badge bg-secondary-subtle text-dark border" title="{{ $mrkList }}">
                                            <i class="bi bi-tag me-1"></i>{{ $mrkCount > 1 ? $mrkCount . ' Merek' : $mrkList }}
                                        </span>
                                    @elseif ($aturan->target_produk === 'spesifik')
                                        @php
                                            $prodCount = $aturan->produks->count();
                                            $prodList = $prodCount > 0 ? $aturan->produks->pluck('nama')->join(', ') : ($aturan->produk?->nama ?? '-');
                                        @endphp
                                        <span class="badge bg-white text-dark border" title="{{ $prodList }}">
                                            <i class="bi bi-box me-1"></i>
                                            @if ($prodCount > 1)
                                                {{ $prodCount }} Produk Spesifik
                                            @else
                                                SKU: {{ $aturan->produk?->kode_katalog ?? ($aturan->produks->first()?->kode_katalog ?? '-') }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="badge bg-white text-secondary border">Semua Produk</span>
                                    @endif
                                </div>

                                @if (! $showTrashed)
                                    <div class="d-flex justify-content-between align-items-center text-muted pt-1 border-top mt-1" style="font-size: 0.75rem;">
                                        <span>
                                            <strong>Min. Beli:</strong>
                                            @if ($aturan->min_kuantiti > 0)
                                                &ge; {{ rtrim(rtrim(number_format($aturan->min_kuantiti, 2), '0'), '.') }}
                                            @else
                                                Tanpa Min
                                            @endif
                                        </span>
                                        <span>
                                            <i class="bi bi-calendar3 me-1"></i>
                                            @if ($isExpired)
                                                Selesai {{ $aturan->tanggal_selesai->format('d M Y') }}
                                            @elseif ($isNotStarted)
                                                Mulai {{ $aturan->tanggal_mulai->format('d M Y') }}
                                            @elseif ($aturan->tanggal_selesai)
                                                Sampai {{ $aturan->tanggal_selesai->format('d M Y') }}
                                            @else
                                                Permanen
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Bottom Action Row -->
                            @if (! $showTrashed)
                                <div class="d-flex justify-content-between align-items-center gap-2 pt-1">
                                    <div class="form-check form-switch mb-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            wire:click="confirmToggleActive({{ $aturan->id }})"
                                            {{ $aturan->is_active ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label small {{ $aturan->is_active ? 'text-success fw-semibold' : 'text-muted' }}">
                                            {{ $aturan->is_active ? 'Switch ON' : 'Switch OFF' }}
                                        </label>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button wire:click="openEditModal({{ $aturan->id }})" class="btn btn-sm btn-outline-primary px-3" title="Edit Aturan Diskon">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                        <button wire:click="confirmDelete({{ $aturan->id }})" class="btn btn-sm btn-outline-danger" title="Hapus ke Tempat Sampah">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex gap-2 pt-1">
                                    <button wire:click="confirmRestore({{ $aturan->id }})" class="btn btn-sm btn-outline-success flex-fill">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                    </button>
                                    <button wire:click="confirmForceDelete({{ $aturan->id }})" class="btn btn-sm btn-outline-danger flex-fill">
                                        <i class="bi bi-trash3 me-1"></i>Hapus Permanen
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-5 px-3">
                            <div class="text-muted">
                                <i class="bi {{ $showTrashed ? 'bi-trash3' : 'bi-tags' }} display-4 d-block mb-2 text-secondary"></i>
                                <p class="mb-0">
                                    {{ $showTrashed ? 'Tempat sampah kosong. Tidak ada aturan diskon yang terhapus.' : 'Belum ada aturan diskon yang sesuai filter.' }}
                                </p>
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
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Nama Aturan Diskon</th>
                            <th>Target Pelanggan</th>
                            <th>Target Produk</th>
                            <th>Nilai Diskon</th>
                            @if (! $showTrashed)
                                <th>Min. Beli</th>
                                <th>Masa Berlaku</th>
                                <th>Status Promo</th>
                                <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                            @else
                                <th>Tanggal Dihapus</th>
                                <th class="text-end pe-4" style="width: 170px;">Aksi Pemulihan</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aturans as $index => $aturan)
                            @php
                                $today = \Carbon\Carbon::today();
                                $isExpired = $aturan->tanggal_selesai && $today->gt($aturan->tanggal_selesai);
                                $isNotStarted = $aturan->tanggal_mulai && $today->lt($aturan->tanggal_mulai);
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $aturans->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $aturan->nama }}</div>
                                    @if ($aturan->keterangan)
                                        <small class="text-muted d-block text-truncate" style="max-width: 250px;">{{ $aturan->keterangan }}</small>
                                    @endif
                                    @if ($aturan->priority > 0)
                                        <small class="badge bg-light text-primary border mt-1">
                                            <i class="bi bi-arrow-up-circle me-1"></i>Prioritas: {{ $aturan->priority }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if ($aturan->target_pelanggan === 'umum')
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="bi bi-person me-1"></i>Umum
                                        </span>
                                    @elseif ($aturan->target_pelanggan === 'pemborong')
                                        <span class="badge bg-info-subtle text-info border">
                                            <i class="bi bi-tools me-1"></i>Aplikator
                                        </span>
                                    @elseif ($aturan->target_pelanggan === 'langganan')
                                        <span class="badge bg-primary-subtle text-primary border">
                                            <i class="bi bi-shop me-1"></i>Toko
                                        </span>
                                    @elseif ($aturan->target_pelanggan === 'spesifik')
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person-check me-1"></i>{{ $aturan->pelanggan?->nama ?? 'Pelanggan Terpilih' }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border">
                                            Semua Tipe Pelanggan
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($aturan->target_produk === 'kategori')
                                        @php
                                            $katCount = $aturan->kategoris->count();
                                            $katList = $katCount > 0 ? $aturan->kategoris->pluck('nama_kategori')->join(', ') : ($aturan->kategori?->nama_kategori ?? '-');
                                        @endphp
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" title="{{ $katList }}">
                                            <i class="bi bi-folder2-open me-1"></i>
                                            @if ($katCount > 1)
                                                {{ $katCount }} Kategori
                                            @else
                                                {{ $katList }}
                                            @endif
                                        </span>
                                    @elseif ($aturan->target_produk === 'merek')
                                        @php
                                            $mrkCount = $aturan->mereks->count();
                                            $mrkList = $mrkCount > 0 ? $aturan->mereks->pluck('nama_merek')->join(', ') : ($aturan->merek?->nama_merek ?? '-');
                                        @endphp
                                        <span class="badge bg-secondary-subtle text-dark border" title="{{ $mrkList }}">
                                            <i class="bi bi-tag me-1"></i>
                                            @if ($mrkCount > 1)
                                                {{ $mrkCount }} Merek
                                            @else
                                                {{ $mrkList }}
                                            @endif
                                        </span>
                                    @elseif ($aturan->target_produk === 'spesifik')
                                        @php
                                            $prodCount = $aturan->produks->count();
                                            $prodList = $prodCount > 0 ? $aturan->produks->pluck('nama')->join(', ') : ($aturan->produk?->nama ?? '-');
                                        @endphp
                                        <span class="badge bg-light text-dark border" title="{{ $prodList }}">
                                            <i class="bi bi-box me-1"></i>
                                            @if ($prodCount > 1)
                                                {{ $prodCount }} Produk Spesifik
                                            @else
                                                SKU: {{ $aturan->produk?->kode_katalog ?? ($aturan->produks->first()?->kode_katalog ?? '-') }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border">Semua Produk</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($aturan->jenis_diskon === 'persentase')
                                        <span class="fw-bold text-success font-monospace fs-6">
                                            {{ rtrim(rtrim(number_format($aturan->nilai_diskon, 2), '0'), '.') }}%
                                        </span>
                                    @else
                                        <span class="fw-bold text-success font-monospace">
                                            Rp {{ number_format($aturan->nilai_diskon, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                @if (! $showTrashed)
                                    <td>
                                        @if ($aturan->min_kuantiti > 0)
                                            <span class="badge bg-light text-dark border">
                                                &ge; {{ rtrim(rtrim(number_format($aturan->min_kuantiti, 2), '0'), '.') }}
                                            </span>
                                        @else
                                            <span class="text-muted small">Tanpa Min</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($isExpired)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Kadaluarsa</span>
                                            <small class="d-block text-muted" style="font-size: 0.72rem;">Selesai {{ $aturan->tanggal_selesai->format('d M Y') }}</small>
                                        @elseif ($isNotStarted)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Mulai Nanti</span>
                                            <small class="d-block text-muted" style="font-size: 0.72rem;">{{ $aturan->tanggal_mulai->format('d M Y') }}</small>
                                        @elseif ($aturan->tanggal_selesai)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">Sampai {{ $aturan->tanggal_selesai->format('d M Y') }}</span>
                                        @else
                                            <span class="badge bg-light text-muted border">Permanen</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @if (! $aturan->is_active)
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle d-inline-flex align-items-center" style="width: fit-content;">
                                                    <i class="bi bi-pause-circle me-1"></i>Nonaktif
                                                </span>
                                            @elseif ($isExpired)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center" style="width: fit-content;">
                                                    <i class="bi bi-x-circle me-1"></i>Kedaluwarsa
                                                </span>
                                            @elseif ($isNotStarted)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-inline-flex align-items-center" style="width: fit-content;">
                                                    <i class="bi bi-clock me-1"></i>Akan Datang
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center" style="width: fit-content;">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Aktif Berjalan
                                                </span>
                                            @endif
                                            <div class="form-check form-switch mb-0">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    role="switch"
                                                    wire:click="confirmToggleActive({{ $aturan->id }})"
                                                    {{ $aturan->is_active ? 'checked' : '' }}
                                                    title="Klik untuk mengubah status aktif / nonaktif aturan diskon ini"
                                                >
                                                <label class="form-check-label small {{ $aturan->is_active ? 'text-success fw-semibold' : 'text-muted' }}" style="font-size: 0.72rem;">
                                                    {{ $aturan->is_active ? 'Switch ON' : 'Switch OFF' }}
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="openEditModal({{ $aturan->id }})" class="btn btn-outline-primary" title="Edit Aturan Diskon">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $aturan->id }})" class="btn btn-outline-danger" title="Hapus ke Tempat Sampah">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                @else
                                    <td>
                                        <div class="text-danger small fw-semibold">
                                            <i class="bi bi-trash3 me-1"></i>{{ $aturan->deleted_at->format('d/m/Y H:i') }}
                                        </div>
                                        <small class="text-muted">{{ $aturan->deleted_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="confirmRestore({{ $aturan->id }})" class="btn btn-outline-success" title="Pulihkan Aturan Diskon">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                            <button wire:click="confirmForceDelete({{ $aturan->id }})" class="btn btn-outline-danger" title="Hapus Permanen">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showTrashed ? 7 : 9 }}" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi {{ $showTrashed ? 'bi-trash3' : 'bi-tags' }} display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">
                                            {{ $showTrashed ? 'Tempat sampah kosong. Tidak ada aturan diskon yang terhapus.' : 'Belum ada aturan diskon yang sesuai filter.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($aturans->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $aturans->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if ($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-fullscreen-sm-down modal-dialog-scrollable my-0 my-sm-3 my-md-4">
                <form wire:submit="save" class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-light border-bottom py-2 py-sm-3 px-3 px-sm-4 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="bg-primary-subtle text-primary rounded p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                <i class="bi bi-percent fs-5"></i>
                            </div>
                            <div class="min-w-0">
                                <h5 class="modal-title fw-bold text-dark mb-0 text-truncate">
                                    {{ $aturanId ? 'Edit Aturan Diskon' : 'Tambah Aturan Diskon Baru' }}
                                </h5>
                                <small class="text-muted d-none d-sm-inline">Tentukan target pelanggan, cakupan produk, dan besaran potongan harga.</small>
                            </div>
                        </div>
                        <button type="button" wire:click="closeFormModal" class="btn-close" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-3 p-sm-4 bg-light-subtle">
                        <div class="row g-3">
                            <!-- Nama Aturan -->
                            <div class="col-12">
                                <label for="nama_aturan" class="form-label fw-semibold small mb-1">
                                    Nama Aturan Diskon / Promo <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_aturan"
                                    wire:model="nama"
                                    class="form-control form-control-sm @error('nama') is-invalid @enderror"
                                    placeholder="Contoh: Diskon Khusus Pemborong Kategori Hollow"
                                    required
                                >
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Target Pelanggan -->
                            <div class="col-sm-6">
                                <label for="target_pelanggan" class="form-label fw-semibold small mb-1">
                                    Target Pelanggan <span class="text-danger">*</span>
                                </label>
                                <select id="target_pelanggan" wire:model.live="target_pelanggan" class="form-select form-select-sm" required>
                                    <option value="semua">Semua Tipe Pelanggan</option>
                                    <option value="umum">Khusus Umum</option>
                                    <option value="pemborong">Khusus Aplikator</option>
                                    <option value="langganan">Khusus Toko</option>
                                    <option value="spesifik">Pilih 1 Pelanggan Spesifik</option>
                                </select>
                            </div>

                            @if ($target_pelanggan === 'spesifik')
                                <div class="col-sm-6">
                                    <label for="pelanggan_id" class="form-label fw-semibold small mb-1">
                                        Pilih Pelanggan <span class="text-danger">*</span>
                                    </label>
                                    <select id="pelanggan_id" wire:model="pelanggan_id" class="form-select form-select-sm @error('pelanggan_id') is-invalid @enderror" required>
                                        <option value="">-- Pilih Pelanggan --</option>
                                        @foreach ($pelanggans as $pelanggan)
                                            <option value="{{ $pelanggan->id }}">{{ $pelanggan->nama }} ({{ $pelanggan->tipe->label() }})</option>
                                        @endforeach
                                    </select>
                                    @error('pelanggan_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            <!-- Target Produk -->
                            <div class="col-sm-6">
                                <label for="target_produk" class="form-label fw-semibold small mb-1">
                                    Penerapan Produk <span class="text-danger">*</span>
                                </label>
                                <select id="target_produk" wire:model.live="target_produk" class="form-select form-select-sm" required>
                                    <option value="semua">Semua Produk Katalog</option>
                                    <option value="kategori">Berdasarkan Kategori (Bisa Banyak)</option>
                                    <option value="merek">Berdasarkan Merek (Bisa Banyak)</option>
                                    <option value="spesifik">Pilih Produk Tertentu (Bisa Banyak)</option>
                                </select>
                            </div>

                            @if ($target_produk === 'kategori')
                                <div class="col-12">
                                    <div class="card border border-primary-subtle bg-light-subtle rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <div>
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="bi bi-folder2-open text-primary me-1"></i>Pilih Kategori Produk <span class="text-danger">*</span>
                                                </label>
                                                <div class="text-muted" style="font-size: 0.73rem;">
                                                    Klik kategori di bawah untuk memilih. Kategori aktif berwarna biru.
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" wire:click="selectAllKategoris" class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold" style="font-size: 0.73rem;">
                                                    <i class="bi bi-check-all me-1"></i>{{ filled($searchKategoriQuery) ? 'Pilih Semua Hasil (' . count($availableKategoris) . ')' : 'Pilih Semua (' . count($availableKategoris) . ')' }}
                                                </button>
                                                @if (count($selected_kategori_ids) > 0)
                                                    <button type="button" wire:click="clearSelectedKategoris" class="btn btn-xs btn-outline-danger py-1 px-2 fw-semibold" style="font-size: 0.73rem;">
                                                        <i class="bi bi-trash3 me-1"></i>Reset ({{ count($selected_kategori_ids) }})
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Live Search Filter -->
                                        <div class="input-group input-group-sm mb-2 shadow-xs">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                            <input
                                                type="text"
                                                wire:model.live.debounce.200ms="searchKategoriQuery"
                                                class="form-control"
                                                placeholder="Saring nama kategori..."
                                            >
                                            @if (filled($searchKategoriQuery))
                                                <button type="button" wire:click="$set('searchKategoriQuery', '')" class="btn btn-outline-secondary btn-sm" title="Bersihkan saringan">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Clickable Category Pills -->
                                        <div class="p-2 border rounded bg-white d-flex flex-wrap gap-2 align-items-center" style="max-height: 180px; overflow-y: auto;">
                                            @forelse ($availableKategoris as $kat)
                                                @php $isSelected = in_array($kat->id, $selected_kategori_ids, true); @endphp
                                                <button
                                                    type="button"
                                                    wire:click="toggleSelectedKategori({{ $kat->id }})"
                                                    class="btn btn-sm d-inline-flex align-items-center py-1 px-2 rounded-pill transition-all {{ $isSelected ? 'btn-primary shadow-xs fw-semibold' : ($kat->parent_id ? 'btn-outline-secondary bg-white text-dark' : 'btn-outline-dark bg-light fw-semibold') }}"
                                                    style="font-size: 0.8rem;"
                                                >
                                                    <i class="bi {{ $isSelected ? 'bi-check-circle-fill text-white' : 'bi-plus-circle text-muted' }} me-1"></i>
                                                    @if ($kat->parent_id)
                                                        <span class="text-muted me-1">↳</span>{{ $kat->nama_kategori }}
                                                    @else
                                                        <span class="me-1">📁</span>{{ $kat->nama_kategori }} <span class="badge bg-secondary-subtle text-dark ms-1 py-0 px-1" style="font-size: 0.65rem;">Induk</span>
                                                    @endif
                                                    @if(isset($kat->produks_count) && $kat->produks_count > 0)
                                                        <span class="badge {{ $isSelected ? 'bg-white text-primary' : 'bg-light text-secondary border' }} ms-1 rounded-pill" style="font-size: 0.68rem;">
                                                            {{ $kat->produks_count }}
                                                        </span>
                                                    @endif
                                                </button>
                                            @empty
                                                <div class="text-muted small py-2 px-1">
                                                    <i class="bi bi-info-circle me-1"></i>Tidak ada kategori yang cocok dengan saringan <em>"{{ $searchKategoriQuery }}"</em>.
                                                </div>
                                            @endforelse
                                        </div>

                                        <!-- Selected Summary Indicator -->
                                        <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                                            <div class="small" style="font-size: 0.75rem;">
                                                @if (count($selected_kategori_ids) > 0)
                                                    <span class="text-success fw-semibold">
                                                        <i class="bi bi-check-circle-fill me-1"></i>{{ count($selected_kategori_ids) }} kategori terpilih:
                                                    </span>
                                                    <span class="text-muted">
                                                        {{ $selectedKategoris->pluck('nama_kategori')->join(', ') }}
                                                    </span>
                                                @else
                                                    <span class="text-danger small">
                                                        <i class="bi bi-exclamation-circle me-1"></i>Wajib memilih minimal satu kategori produk target diskon.
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        @error('selected_kategori_ids')
                                            <div class="text-danger small mt-1 px-1 fw-semibold">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @elseif ($target_produk === 'merek')
                                <div class="col-12">
                                    <div class="card border border-secondary-subtle bg-light-subtle rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <div>
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="bi bi-tag text-secondary me-1"></i>Pilih Merek Produk <span class="text-danger">*</span>
                                                </label>
                                                <div class="text-muted" style="font-size: 0.73rem;">
                                                    Klik merek di bawah untuk memilih. Merek aktif berwarna gelap/biru.
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" wire:click="selectAllMereks" class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold" style="font-size: 0.73rem;">
                                                    <i class="bi bi-check-all me-1"></i>{{ filled($searchMerekQuery) ? 'Pilih Semua Hasil (' . count($availableMereks) . ')' : 'Pilih Semua (' . count($availableMereks) . ')' }}
                                                </button>
                                                @if (count($selected_merek_ids) > 0)
                                                    <button type="button" wire:click="clearSelectedMereks" class="btn btn-xs btn-outline-danger py-1 px-2 fw-semibold" style="font-size: 0.73rem;">
                                                        <i class="bi bi-trash3 me-1"></i>Reset ({{ count($selected_merek_ids) }})
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Live Search Filter -->
                                        <div class="input-group input-group-sm mb-2 shadow-xs">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                            <input
                                                type="text"
                                                wire:model.live.debounce.200ms="searchMerekQuery"
                                                class="form-control"
                                                placeholder="Saring nama merek..."
                                            >
                                            @if (filled($searchMerekQuery))
                                                <button type="button" wire:click="$set('searchMerekQuery', '')" class="btn btn-outline-secondary btn-sm" title="Bersihkan saringan">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Clickable Brand Pills -->
                                        <div class="p-2 border rounded bg-white d-flex flex-wrap gap-2 align-items-center" style="max-height: 180px; overflow-y: auto;">
                                            @forelse ($availableMereks as $mrk)
                                                @php $isSelected = in_array($mrk->id, $selected_merek_ids, true); @endphp
                                                <button
                                                    type="button"
                                                    wire:click="toggleSelectedMerek({{ $mrk->id }})"
                                                    class="btn btn-sm d-inline-flex align-items-center py-1 px-2 rounded-pill transition-all {{ $isSelected ? 'btn-dark shadow-xs fw-semibold' : 'btn-outline-secondary bg-white text-dark' }}"
                                                    style="font-size: 0.8rem;"
                                                >
                                                    <i class="bi {{ $isSelected ? 'bi-check-circle-fill text-white' : 'bi-plus-circle text-muted' }} me-1"></i>
                                                    {{ $mrk->nama_merek }}
                                                    @if(isset($mrk->produks_count) && $mrk->produks_count > 0)
                                                        <span class="badge {{ $isSelected ? 'bg-white text-dark' : 'bg-light text-secondary border' }} ms-1 rounded-pill" style="font-size: 0.68rem;">
                                                            {{ $mrk->produks_count }}
                                                        </span>
                                                    @endif
                                                </button>
                                            @empty
                                                <div class="text-muted small py-2 px-1">
                                                    <i class="bi bi-info-circle me-1"></i>Tidak ada merek yang cocok dengan saringan <em>"{{ $searchMerekQuery }}"</em>.
                                                </div>
                                            @endforelse
                                        </div>

                                        <!-- Selected Summary Indicator -->
                                        <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                                            <div class="small" style="font-size: 0.75rem;">
                                                @if (count($selected_merek_ids) > 0)
                                                    <span class="text-success fw-semibold">
                                                        <i class="bi bi-check-circle-fill me-1"></i>{{ count($selected_merek_ids) }} merek terpilih:
                                                    </span>
                                                    <span class="text-muted">
                                                        {{ $selectedMereks->pluck('nama_merek')->join(', ') }}
                                                    </span>
                                                @else
                                                    <span class="text-danger small">
                                                        <i class="bi bi-exclamation-circle me-1"></i>Wajib memilih minimal satu merek produk target diskon.
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        @error('selected_merek_ids')
                                            <div class="text-danger small mt-1 px-1 fw-semibold">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @elseif ($target_produk === 'spesifik')
                                <div class="col-12">
                                    <div class="card border border-info-subtle bg-light-subtle rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <div>
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="bi bi-box-seam text-info me-1"></i>Pilih Produk Spesifik <span class="text-danger">*</span>
                                                </label>
                                                <div class="text-muted" style="font-size: 0.73rem;">
                                                    Cari berdasarkan nama produk atau kode katalog (SKU).
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                @if (count($searchProdukResults) > 0)
                                                    <button type="button" wire:click="selectAllFoundProduks" class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold" style="font-size: 0.73rem;">
                                                        <i class="bi bi-check-all me-1"></i>Pilih Semua Hasil Ini ({{ count($searchProdukResults) }})
                                                    </button>
                                                @endif
                                                @if (count($selected_produk_ids) > 0)
                                                    <button type="button" wire:click="clearSelectedProduks" class="btn btn-xs btn-outline-danger py-1 px-2 fw-semibold" style="font-size: 0.73rem;">
                                                        <i class="bi bi-trash3 me-1"></i>Reset ({{ count($selected_produk_ids) }})
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Live Search Bar -->
                                        <div class="input-group input-group-sm mb-2 shadow-xs">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                            <input
                                                type="text"
                                                wire:model.live.debounce.250ms="searchProdukQuery"
                                                class="form-control"
                                                placeholder="Ketik nama produk atau kode SKU katalog..."
                                            >
                                            @if (filled($searchProdukQuery))
                                                <button type="button" wire:click="$set('searchProdukQuery', '')" class="btn btn-outline-secondary btn-sm" title="Bersihkan pencarian">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Direct Scrollable Product Results List -->
                                        <div class="border rounded bg-white shadow-xs mb-2" style="max-height: 220px; overflow-y: auto;">
                                            <div class="bg-light py-1 px-3 border-bottom d-flex justify-content-between align-items-center">
                                                <span class="text-muted" style="font-size: 0.73rem;">
                                                    @if (filled($searchProdukQuery))
                                                        <i class="bi bi-search me-1"></i>Ditemukan {{ count($searchProdukResults) }} produk cocok
                                                    @else
                                                        <i class="bi bi-clock-history me-1"></i>Menampilkan {{ count($searchProdukResults) }} Produk Katalog Terbaru
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="list-group list-group-flush">
                                                @forelse ($searchProdukResults as $p)
                                                    @php $isSelected = in_array($p->id, $selected_produk_ids, true); @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="toggleSelectedProduk({{ $p->id }})"
                                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small {{ $isSelected ? 'bg-primary-subtle' : '' }}"
                                                    >
                                                        <div class="text-start">
                                                            <strong class="text-dark">{{ $p->nama }}</strong>
                                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                                <span class="badge bg-light text-dark border me-1"><code>{{ $p->kode_katalog }}</code></span>
                                                                {{ $p->kategori?->nama_kategori ?? '-' }} &bull; Rp {{ number_format($p->harga_jual, 0, ',', '.') }}
                                                            </div>
                                                        </div>
                                                        @if ($isSelected)
                                                            <span class="badge bg-success rounded-pill px-2 py-1"><i class="bi bi-check-lg me-1"></i>Terpilih</span>
                                                        @else
                                                            <span class="badge bg-light text-primary border rounded-pill px-2 py-1"><i class="bi bi-plus-lg me-1"></i>Pilih</span>
                                                        @endif
                                                    </button>
                                                @empty
                                                    <div class="list-group-item text-muted small py-3 text-center">
                                                        <i class="bi bi-info-circle me-1"></i>Produk tidak ditemukan dengan kata kunci <em>"{{ $searchProdukQuery }}"</em>.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>

                                        <!-- Selected Produk Chips -->
                                        <div class="p-2 border rounded bg-light-subtle d-flex flex-wrap gap-1 align-items-center" style="max-height: 120px; overflow-y: auto;">
                                            @forelse ($selectedProduks as $item)
                                                <span class="badge bg-white text-dark border d-inline-flex align-items-center py-1 px-2 shadow-xs">
                                                    <span class="text-primary font-monospace me-1">[{{ $item->kode_katalog }}]</span>
                                                    {{ Str::limit($item->nama, 35) }}
                                                    <button type="button" wire:click="removeSelectedProduk({{ $item->id }})" class="btn-close btn-close-xs ms-2" style="font-size: 0.55rem;" aria-label="Hapus"></button>
                                                </span>
                                            @empty
                                                <span class="text-muted small ps-1" style="font-size: 0.74rem;">
                                                    <i class="bi bi-info-circle me-1"></i>Belum ada produk dipilih. Klik salah satu produk di atas untuk memilihnya.
                                                </span>
                                            @endforelse
                                        </div>

                                        @error('selected_produk_ids')
                                            <div class="text-danger small mt-1 px-1 fw-semibold">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <!-- Jenis & Nilai Diskon -->
                            <div class="col-6 col-sm-4">
                                <label for="jenis_diskon" class="form-label fw-semibold small mb-1">Jenis Diskon <span class="text-danger">*</span></label>
                                <select id="jenis_diskon" wire:model.live="jenis_diskon" class="form-select form-select-sm" required>
                                    <option value="persentase">Persentase (%)</option>
                                    <option value="nominal">Nominal Rupiah (Rp)</option>
                                </select>
                            </div>

                            <div class="col-6 col-sm-4">
                                <label for="nilai_diskon" class="form-label fw-semibold small mb-1">
                                    Nilai Diskon <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">{{ $jenis_diskon === 'persentase' ? '%' : 'Rp' }}</span>
                                    <input
                                        type="number"
                                        @if ($jenis_diskon === 'persentase')
                                            step="0.01"
                                            min="0.01"
                                            max="100"
                                            placeholder="Contoh: 5"
                                        @else
                                            step="1"
                                            min="1"
                                            placeholder="Contoh: 10000"
                                        @endif
                                        id="nilai_diskon"
                                        wire:model="nilai_diskon"
                                        class="form-control @error('nilai_diskon') is-invalid @enderror"
                                        required
                                    >
                                </div>
                                @if ($jenis_diskon === 'nominal' && (float)$nilai_diskon > 0)
                                    <small class="text-success fw-semibold mt-1 d-block" style="font-size: 0.73rem;">
                                        &bull; Potongan: Rp {{ number_format((float)$nilai_diskon, 0, ',', '.') }} per unit
                                    </small>
                                @endif
                                @error('nilai_diskon')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-sm-4">
                                <label for="min_kuantiti" class="form-label fw-semibold small mb-1">
                                    Minimal Pembelian (Qty)
                                </label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    id="min_kuantiti"
                                    wire:model="min_kuantiti"
                                    class="form-control form-control-sm @error('min_kuantiti') is-invalid @enderror"
                                    placeholder="0 = tanpa syarat"
                                >
                                <small class="text-muted" style="font-size: 0.72rem;">Isi 0 jika berlaku dari 1 item.</small>
                            </div>

                            <!-- Periode Berlaku & Prioritas -->
                            <div class="col-6 col-sm-4">
                                <label for="tanggal_mulai" class="form-label fw-semibold small mb-1">Tanggal Mulai (Opsional)</label>
                                <input
                                    type="date"
                                    id="tanggal_mulai"
                                    wire:model="tanggal_mulai"
                                    class="form-control form-control-sm @error('tanggal_mulai') is-invalid @enderror"
                                >
                                <small class="text-muted" style="font-size: 0.72rem;">Kosongkan jika langsung aktif.</small>
                            </div>

                            <div class="col-6 col-sm-4">
                                <label for="tanggal_selesai" class="form-label fw-semibold small mb-1">Tanggal Berakhir (Opsional)</label>
                                <input
                                    type="date"
                                    id="tanggal_selesai"
                                    wire:model="tanggal_selesai"
                                    class="form-control form-control-sm @error('tanggal_selesai') is-invalid @enderror"
                                >
                                <small class="text-muted" style="font-size: 0.72rem;">Kosongkan jika aktif selamanya.</small>
                                @error('tanggal_selesai')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-sm-4">
                                <label for="priority" class="form-label fw-semibold small mb-1">
                                    Tingkat Prioritas <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    max="999"
                                    id="priority"
                                    wire:model="priority"
                                    class="form-control form-control-sm @error('priority') is-invalid @enderror"
                                    placeholder="0"
                                    required
                                >
                                <small class="text-muted" style="font-size: 0.72rem;">Semakin besar angka, semakin didahulukan.</small>
                                @error('priority')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Keterangan -->
                            <div class="col-12">
                                <label for="keterangan" class="form-label fw-semibold small mb-1">Catatan / Keterangan</label>
                                <textarea
                                    id="keterangan"
                                    wire:model="keterangan"
                                    rows="2"
                                    class="form-control form-control-sm"
                                    placeholder="Contoh: Kesepakatan harga untuk proyek perumahan atau promo cuci gudang akhir bulan."
                                ></textarea>
                            </div>

                            <!-- Status Aktif -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="is_active"
                                        wire:model="is_active"
                                    >
                                    <label class="form-check-label small" for="is_active">
                                        <strong>Aktifkan aturan diskon ini sekarang</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top py-2 px-3 px-sm-4 flex-shrink-0 d-flex justify-content-between gap-2">
                        <button type="button" wire:click="closeFormModal" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">Batal</button>
                        <button type="submit" class="btn btn-primary px-3 px-sm-4 fw-semibold shadow-sm flex-fill flex-sm-grow-0" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">
                                <i class="bi bi-check-lg me-1"></i>Simpan Aturan Diskon
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
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($isDeleteModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>Hapus Aturan Diskon
                        </h5>
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin menghapus aturan diskon <strong>{{ $aturanToDeleteName }}</strong> ke tempat sampah?
                        </p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="$set('isDeleteModalOpen', false)" class="btn btn-secondary">Batal</button>
                        <button type="button" wire:click="delete" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Restore Confirmation Modal -->
    @if ($isRestoreModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Pulihkan Aturan Diskon
                        </h5>
                        <button type="button" wire:click="$set('isRestoreModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0">
                            Apakah Anda yakin ingin memulihkan aturan diskon <strong>{{ $aturanToRestoreName }}</strong> dari tempat sampah?
                        </p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="$set('isRestoreModalOpen', false)" class="btn btn-secondary">Batal</button>
                        <button type="button" wire:click="restore" class="btn btn-success">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Ya, Pulihkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Force Delete Confirmation Modal -->
    @if ($isForceDeleteModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="bi bi-exclamation-octagon me-2"></i>Hapus Permanen Aturan Diskon
                        </h5>
                        <button type="button" wire:click="$set('isForceDeleteModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-danger py-2 small mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong>Tindakan ini tidak dapat dibatalkan!</strong> Data akan dihapus selamanya dari database.
                        </div>
                        <p class="mb-0">
                            Apakah Anda yakin ingin menghapus permanen aturan diskon <strong>{{ $aturanToForceDeleteName }}</strong>?
                        </p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="$set('isForceDeleteModalOpen', false)" class="btn btn-secondary">Batal</button>
                        <button type="button" wire:click="forceDelete" class="btn btn-danger">
                            <i class="bi bi-trash3 me-1"></i>Ya, Hapus Permanen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Toggle Active Confirmation Modal -->
    @if ($isToggleModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        @if ($aturanToToggleTargetActive)
                            <h5 class="modal-title fw-bold text-success">
                                <i class="bi bi-lightning-charge me-2"></i>Aktifkan Aturan Diskon
                            </h5>
                        @else
                            <h5 class="modal-title fw-bold text-warning-emphasis">
                                <i class="bi bi-pause-circle me-2"></i>Nonaktifkan Aturan Diskon
                            </h5>
                        @endif
                        <button type="button" wire:click="$set('isToggleModalOpen', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        @if ($aturanToToggleTargetActive)
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success-subtle p-2 rounded-circle me-3">
                                    <i class="bi bi-check-circle fs-3 text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">Mengaktifkan Promo</div>
                                    <small class="text-muted">Transaksi penjualan kasir akan mulai mendapatkan potongan otomatis dari aturan ini.</small>
                                </div>
                            </div>
                            <p class="mb-0">
                                Apakah Anda yakin ingin <strong>mengaktifkan</strong> aturan diskon <strong>{{ $aturanToToggleName }}</strong>?
                            </p>
                        @else
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning-subtle p-2 rounded-circle me-3">
                                    <i class="bi bi-exclamation-triangle fs-3 text-warning"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">Menonaktifkan Promo</div>
                                    <small class="text-muted">Penjualan kasir tidak akan lagi mendapatkan potongan harga otomatis dari aturan ini.</small>
                                </div>
                            </div>
                            <p class="mb-0">
                                Apakah Anda yakin ingin <strong>menonaktifkan</strong> aturan diskon <strong>{{ $aturanToToggleName }}</strong>?
                            </p>
                        @endif
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="$set('isToggleModalOpen', false)" class="btn btn-secondary">Batal</button>
                        @if ($aturanToToggleTargetActive)
                            <button type="button" wire:click="toggleActive" class="btn btn-success">
                                <i class="bi bi-lightning-charge me-1"></i>Ya, Aktifkan
                            </button>
                        @else
                            <button type="button" wire:click="toggleActive" class="btn btn-warning text-dark">
                                <i class="bi bi-pause-circle me-1"></i>Ya, Nonaktifkan
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
