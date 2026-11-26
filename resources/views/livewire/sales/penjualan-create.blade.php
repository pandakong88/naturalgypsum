<div>
    <style>
        /* Hilangkan spinner panah atas/bawah pada input angka di tabel kasir */
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .no-spinner[type=number] {
            -moz-appearance: textfield;
        }
    </style>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Catat Penjualan Baru</h4>
            <p class="text-muted small mb-0">Input faktur penjualan barang keluar kepada pelanggan dan kurangi stok otomatis.</p>
        </div>
        <div>
            <a href="{{ route('penjualan.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    @if (session()->has('info_duplikat'))
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-copy me-2"></i><strong>Duplikat Pesanan:</strong> {{ session('info_duplikat') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Error Summary if any -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Terdapat kesalahan pengisian:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form wire:submit="save">
        <!-- Sales Header Card (Tata Letak Ramah Kasir Non-IT) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold mb-0 text-primary">
                    <i class="bi bi-person-badge me-2"></i>Informasi Transaksi & Pelanggan
                </h6>
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Pilih pelanggan dan pastikan gudang sumber barang benar.
                </span>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 g-md-4">
                    <!-- KOLOM KIRI: Data Pelanggan -->
                    <div class="col-lg-6 border-end-lg">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1.5 mb-2">
                            <label class="form-label fw-bold mb-0 text-dark">
                                <i class="bi bi-person me-1 text-primary"></i>Pilih Pelanggan <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-center gap-1.5">
                                <button type="button" wire:click="openPelangganModal" class="btn btn-sm btn-outline-primary py-1 px-2 fw-semibold" style="font-size: 0.78rem;" title="Lihat dan cari di seluruh daftar pelanggan">
                                    <i class="bi bi-person-lines-fill me-1"></i>Daftar Pelanggan
                                </button>
                                <button type="button" wire:click="openQuickPelangganModal" class="btn btn-sm btn-outline-success py-1 px-2 fw-semibold" style="font-size: 0.78rem;" title="Tambah pelanggan baru secara cepat">
                                    <i class="bi bi-person-plus-fill me-1"></i>+ Pelanggan Baru
                                </button>
                            </div>
                        </div>

                        <!-- Flash notice if quick customer just created -->
                        @if (session()->has('pelanggan_created'))
                            <div class="alert alert-success alert-dismissible py-2 px-3 small fade show mb-2" role="alert">
                                <i class="bi bi-check-circle-fill me-1"></i>{{ session('pelanggan_created') }}
                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Flash notice if limit updated -->
                        @if (session()->has('limit_updated'))
                            <div class="alert alert-success alert-dismissible py-2 px-3 small fade show mb-2" role="alert">
                                <i class="bi bi-shield-check me-1"></i>{{ session('limit_updated') }}
                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($selectedPelanggan)
                            <!-- Kartu Pelanggan Terpilih (Sangat Jelas & Bersih untuk Orang Non-IT) -->
                            <div class="card border-primary-subtle bg-light shadow-sm p-3 position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h5 class="fw-bold mb-0 text-dark">{{ $selectedPelanggan->nama }}</h5>
                                            @php
                                                $badgeColor = match($selectedPelanggan->tipe) {
                                                    \App\Domains\Catalog\Enums\TipePelanggan::Umum => 'bg-secondary-subtle text-secondary',
                                                    \App\Domains\Catalog\Enums\TipePelanggan::Pemborong => 'bg-info-subtle text-info',
                                                    \App\Domains\Catalog\Enums\TipePelanggan::Langganan => 'bg-primary-subtle text-primary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeColor }} border">
                                                {{ $selectedPelanggan->tipe->label() }}
                                            </span>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-telephone me-1"></i>{{ $selectedPelanggan->kontak ?? 'Tidak ada nomor telp' }}
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-1.5">
                                            @php $custLimit = (float) $selectedPelanggan->limit_piutang; @endphp
                                            <span class="badge {{ $custLimit > 0 ? 'bg-info-subtle text-info border' : 'bg-warning-subtle text-dark border' }}" style="font-size: 0.72rem;">
                                                <i class="bi bi-shield-lock me-1"></i>Limit: {{ $custLimit > 0 ? 'Rp ' . number_format($custLimit, 0, ',', '.') : 'Rp 0 (Wajib Cash)' }}
                                            </span>
                                            @can('update', $selectedPelanggan)
                                                <button type="button" wire:click="openQuickLimitModal" class="btn btn-xs btn-outline-warning py-0 px-2 fw-semibold" style="font-size: 0.72rem;" title="Ubah Limit Piutang Pelanggan">
                                                    <i class="bi bi-pencil-square me-1"></i>Ubah Limit
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                    <button type="button" wire:click="clearPelanggan" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Ganti Pelanggan">
                                        <i class="bi bi-arrow-repeat me-1"></i>Ganti
                                    </button>
                                </div>
                                <div class="mt-2 pt-2 border-top small text-success d-flex align-items-center gap-1">
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Harga produk otomatis disesuaikan ke tier: <strong>{{ $selectedPelanggan->tipe->label() }}</strong></span>
                                </div>
                            </div>
                            <input type="hidden" wire:model="pelanggan_id" value="{{ $selectedPelanggan->id }}">
                        @else
                            <!-- Input Pencarian Autocomplete Tunggal (Tanpa Dropdown Dobel) -->
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input
                                        type="text"
                                        wire:model.live.debounce.250ms="pelangganSearchQuery"
                                        class="form-control @error('pelanggan_id') is-invalid @enderror"
                                        placeholder="Ketik nama pelanggan atau no. telp..."
                                    >
                                    @if (filled($pelangganSearchQuery))
                                        <button type="button" wire:click="$set('pelangganSearchQuery', '')" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                </div>

                                <!-- Floating Autocomplete Dropdown List -->
                                @if (filled($pelangganSearchQuery) && count($pelanggans) > 0)
                                    <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border border-primary" style="max-height: 240px; overflow-y: auto;">
                                        @foreach ($pelanggans as $p)
                                            <button
                                                type="button"
                                                wire:click="selectPelanggan({{ $p->id }})"
                                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small"
                                            >
                                                <div>
                                                    <strong class="text-dark fs-6">{{ $p->nama }}</strong>
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        <i class="bi bi-telephone me-1"></i>{{ $p->kontak ?? 'Tanpa kontak' }}
                                                    </div>
                                                </div>
                                                <span class="badge bg-light text-primary border rounded-pill">{{ $p->tipe->label() }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif (filled($pelangganSearchQuery))
                                    <div class="list-group position-absolute w-100 shadow mt-1 z-3">
                                        <div class="list-group-item py-3 px-3 text-center text-muted small">
                                            <div>Pelanggan '<strong>{{ $pelangganSearchQuery }}</strong>' tidak ditemukan.</div>
                                            <div class="d-flex justify-content-center align-items-center gap-2 mt-2">
                                                <button type="button" wire:click="openPelangganModal" class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-person-lines-fill me-1"></i>Buka Daftar Lengkap
                                                </button>
                                                <button type="button" wire:click="openQuickPelangganModal" class="btn btn-xs btn-success py-1 px-2 fw-semibold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-plus-circle me-1"></i>+ Pelanggan Baru
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.78rem;">
                                Ketik nama / no. telp di atas, atau klik <strong>Daftar Pelanggan</strong> untuk melihat dan memfilter seluruh pelanggan.
                            </small>
                            @error('pelanggan_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <!-- KOLOM KANAN: Detail Faktur, Gudang, & Keterangan -->
                    <div class="col-lg-6">
                        <div class="row g-2 g-sm-3">
                            <div class="col-6">
                                <label class="form-label fw-bold mb-1 text-dark small">
                                    <i class="bi bi-calendar-event me-1 text-primary"></i>Tanggal Penjualan <span class="text-danger">*</span>
                                </label>
                                <input type="date" wire:model="tanggal" class="form-control form-control-sm @error('tanggal') is-invalid @enderror">
                                @error('tanggal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-bold mb-1 text-dark small">
                                    <i class="bi bi-buildings me-1 text-primary"></i>Gudang Sumber <span class="text-danger">*</span>
                                </label>
                                <select wire:model.live="gudang_id" class="form-select form-select-sm @error('gudang_id') is-invalid @enderror">
                                    <option value="">-- Pilih Gudang Sumber --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                    @endforeach
                                </select>
                                @error('gudang_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold mb-1 text-dark small">
                                    <i class="bi bi-journal-text me-1 text-primary"></i>Catatan / Keterangan Faktur
                                </label>
                                <input
                                    type="text"
                                    wire:model="keterangan"
                                    class="form-control form-control-sm @error('keterangan') is-invalid @enderror"
                                    placeholder="Contoh: Kirim via armada toko, pesanan proyek ruko, dll..."
                                >
                                @error('keterangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Informasi Pengiriman / Sopir (Opsional untuk Surat Jalan) -->
                            <div class="col-12">
                                <div class="border rounded p-2 bg-light-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                                        <span class="fw-bold small text-dark d-flex align-items-center gap-1" style="font-size: 0.8rem;">
                                            <i class="bi bi-truck text-primary"></i>
                                            <span>Informasi Pengiriman & Sopir</span>
                                            <span class="badge bg-secondary-subtle text-secondary fw-normal ms-1" style="font-size: 0.68rem;">Opsional (Surat Jalan)</span>
                                        </span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input
                                                type="text"
                                                wire:model="nama_sopir"
                                                class="form-control form-control-sm @error('nama_sopir') is-invalid @enderror"
                                                placeholder="Nama Sopir / Pengemudi"
                                            >
                                            @error('nama_sopir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-6">
                                            <input
                                                type="text"
                                                wire:model="plat_nomor"
                                                class="form-control form-control-sm @error('plat_nomor') is-invalid @enderror"
                                                placeholder="No. Polisi / Plat (B 1234 CD)"
                                            >
                                            @error('plat_nomor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12">
                                            <input
                                                type="text"
                                                wire:model="alamat_pengiriman"
                                                class="form-control form-control-sm @error('alamat_pengiriman') is-invalid @enderror"
                                                placeholder="Alamat / Lokasi Kirim Proyek (jika dikirim ke lokasi proyek)"
                                            >
                                            @error('alamat_pengiriman') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Card (Gaya B: Quick Search Bar + Multi-Add Catalog) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="card-title fw-bold mb-0 text-primary">
                            <i class="bi bi-cart-check me-1"></i> Rincian Barang Penjualan
                        </h6>
                        <small class="text-muted">Ketik nama barang di bawah atau gunakan tombol Pilih dari Katalog.</small>
                    </div>
                    <div class="d-flex gap-2 w-100 w-sm-auto">
                        <button type="button" wire:click="openCatalogModal" class="btn btn-sm btn-primary flex-fill flex-sm-grow-0">
                            <i class="bi bi-grid-3x3-gap me-1"></i>+ Pilih Banyak dari Katalog
                        </button>
                    </div>
                </div>

                @if (session()->has('info_split'))
                    <div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-1"></i> {{ session('info_split') }}
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Quick Product Search Bar (Input keyboard langsung masuk tabel) -->
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="quickSearchQuery"
                            class="form-control form-control-lg fs-6"
                            placeholder="Ketik nama produk atau kode katalog (contoh: 'hollow', 'gypsum', 'aplus')... tekan untuk tambah!"
                            autofocus
                        >
                        @if (filled($quickSearchQuery))
                            <button type="button" wire:click="$set('quickSearchQuery', '')" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>

                    <!-- Floating Search Results Dropdown -->
                    @if (filled($quickSearchQuery) && count($quickSearchResults) > 0)
                        <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border border-primary" style="max-height: 280px; overflow-y: auto;">
                            @foreach ($quickSearchResults as $res)
                                <button
                                    type="button"
                                    wire:click="addQuickProduct({{ $res['id'] }})"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3"
                                >
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        @if (!empty($res['foto_url']))
                                            <img src="{{ $res['foto_url'] }}" alt="{{ $res['nama'] }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;" loading="lazy">
                                        @else
                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <strong class="text-dark text-truncate d-block">{{ $res['nama'] }}</strong>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <span class="badge bg-light text-dark border me-1">{{ $res['kode_katalog'] }}</span>
                                                {{ $res['kategori'] }} &bull; Satuan: <strong>{{ $res['satuan'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success font-monospace">Rp {{ number_format($res['harga_jual'], 0, ',', '.') }}</div>
                                        <span class="badge {{ $res['stok'] <= 0 ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info' }} border" style="font-size: 0.7rem;">
                                            Stok: {{ number_format($res['stok'], 2, ',', '.') }} {{ $res['satuan'] }}
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif (filled($quickSearchQuery))
                        <div class="list-group position-absolute w-100 shadow mt-1 z-3">
                            <div class="list-group-item text-muted small py-3 px-3 text-center">
                                <i class="bi bi-question-circle me-1"></i>Produk '{{ $quickSearchQuery }}' tidak ditemukan.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 table-mobile-cards">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="min-width: 300px;">Produk & Gudang Asal <span class="text-danger">*</span></th>
                                <th style="min-width: 140px;" class="text-end">Harga Satuan (Rp) <span class="text-danger">*</span></th>
                                <th style="min-width: 170px;" class="text-center">Jumlah Keluar <span class="text-danger">*</span></th>
                                <th style="min-width: 130px;" class="text-end">Diskon</th>
                                <th style="min-width: 150px;" class="text-end">Subtotal (Rp)</th>
                                <th style="width: 50px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $index => $item)
                                @php
                                    $prodId = (int) ($item['produk_id'] ?? 0);
                                    $prodModel = $prodId > 0 ? $produks->firstWhere('id', $prodId) : null;
                                    $rowGudangId = (int) ($item['gudang_id'] ?? $gudang_id);
                                    $rowGudang = $gudangs->firstWhere('id', $rowGudangId);
                                    $diskonNominal = (float) ($item['nilai_diskon'] ?? 0);
                                    if (($item['jenis_diskon'] ?? '') === 'persentase' && $diskonNominal > 0) {
                                        $grossRow = (float)($item['jumlah'] ?? 0) * (float)($item['harga_satuan'] ?? 0);
                                        $diskonNominal = round($grossRow * ($diskonNominal / 100), 2);
                                    }
                                    $qtyKeluar = (float) ($item['jumlah'] ?? 0);
                                    $currentStock = (float) ($item['current_stock'] ?? 0);
                                    $isDeficit = $prodModel && $qtyKeluar > $currentStock;
                                    $deficitQty = max(0.0, $qtyKeluar - $currentStock);
                                @endphp
                                <tr class="{{ $isDeficit ? 'table-warning-subtle' : '' }}" wire:key="item-row-{{ $index }}-{{ $prodId }}-{{ $rowGudangId }}">
                                    <td class="text-center text-muted fw-semibold d-none d-md-table-cell">{{ $index + 1 }}</td>
                                    <td data-label="Baris #{{ $index + 1 }} — Produk & Gudang">
                                        @if ($prodModel)
                                            <input type="hidden" wire:model="items.{{ $index }}.produk_id" value="{{ $prodModel->id }}">
                                            <div>
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                        @if ($prodModel->foto_url)
                                                            <img src="{{ $prodModel->foto_url }}" alt="{{ $prodModel->nama }}"
                                                                 class="rounded border object-fit-cover"
                                                                 style="width: 38px; height: 38px; cursor: pointer;"
                                                                 loading="lazy"
                                                                 @mouseenter="showPreview = true"
                                                                 @mouseleave="showPreview = false"
                                                                 wire:click.stop="openImageModal({{ $prodModel->id }})">
                                                            <div x-show="showPreview"
                                                                 class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                                 style="bottom: 100%; left: 0; z-index: 1085; min-width: 180px; max-width: 240px; pointer-events: none; margin-bottom: 6px;"
                                                                 x-cloak>
                                                                <img src="{{ $prodModel->foto_url }}" alt="{{ $prodModel->nama }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                                                <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $prodModel->nama }}</div>
                                                            </div>
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;" title="Belum ada foto">
                                                                <i class="bi bi-image" style="font-size: 1rem;"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-grow-1">
                                                        <div class="fw-bold text-dark fs-6">{{ $prodModel->nama }}</div>
                                                        <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                                            <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                                                SKU: {{ $prodModel->kode_katalog ?? '-' }}
                                                            </span>
                                                            <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">
                                                                Satuan: {{ $prodModel->satuan->label() }}
                                                            </span>
                                                            <!-- Badge Gudang -->
                                                            <span class="badge {{ $rowGudangId !== (int)$gudang_id ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary' }} border" style="font-size: 0.72rem;">
                                                                <i class="bi bi-building me-1"></i>{{ $rowGudang->nama_gudang ?? 'Gudang Utama' }}
                                                            </span>
                                                            <!-- Badge Stok -->
                                                            <span class="badge {{ $currentStock <= 0 ? 'bg-danger text-white' : ($isDeficit ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info') }} border" style="font-size: 0.72rem;">
                                                                Tersedia: {{ number_format($currentStock, 2, ',', '.') }} {{ $prodModel->satuan->label() }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Peringatan & Bantuan Stok Kurang (Multi-Gudang Split / Auto Transfer) -->
                                                @if ($isDeficit)
                                                    <div class="mt-2 p-2 bg-danger-subtle border border-danger-subtle rounded small">
                                                        <div class="text-danger fw-bold mb-1">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Stok di {{ $rowGudang->nama_gudang ?? 'gudang ini' }} kurang <strong>{{ number_format($deficitQty, 2, ',', '.') }} {{ $prodModel->satuan->label() }}</strong>!
                                                        </div>
                                                        @php
                                                            $stockService = app(\App\Domains\Inventory\Services\StockService::class);
                                                            $otherGudangsWithStock = [];
                                                            foreach ($gudangs as $g) {
                                                                if ($g->id !== $rowGudangId) {
                                                                    $stk = $stockService->getStock($prodModel, $g);
                                                                    if ($stk > 0) {
                                                                        $otherGudangsWithStock[] = ['gudang' => $g, 'stok' => $stk];
                                                                    }
                                                                }
                                                            }
                                                        @endphp

                                                        @if (count($otherGudangsWithStock) > 0)
                                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                                Tersedia di gudang lain:
                                                                @foreach ($otherGudangsWithStock as $other)
                                                                    <span class="badge bg-white text-dark border me-1">
                                                                        {{ $other['gudang']->nama_gudang }}: {{ number_format($other['stok'], 2, ',', '.') }} {{ $prodModel->satuan->label() }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                                                @foreach ($otherGudangsWithStock as $other)
                                                                    <!-- Tombol Opsi 1: Pecah Pengambilan -->
                                                                    <button
                                                                        type="button"
                                                                        wire:click="splitItemToWarehouse({{ $index }}, {{ $other['gudang']->id }})"
                                                                        class="btn btn-xs btn-outline-primary py-0 px-2"
                                                                        style="font-size: 0.72rem;"
                                                                        title="Pecah pengambilan agar sisa diambil dari {{ $other['gudang']->nama_gudang }}"
                                                                    >
                                                                        <i class="bi bi-arrow-split me-1"></i>Pecah ke {{ $other['gudang']->nama_gudang }}
                                                                    </button>
                                                                    <!-- Tombol Opsi 2: Tarik / Transfer Instan -->
                                                                    <button
                                                                        type="button"
                                                                        wire:click="transferStockForDeficit({{ $index }}, {{ $other['gudang']->id }})"
                                                                        class="btn btn-xs btn-outline-success py-0 px-2"
                                                                        style="font-size: 0.72rem;"
                                                                        title="Tarik stok langsung dari {{ $other['gudang']->nama_gudang }}"
                                                                    >
                                                                        <i class="bi bi-box-arrow-in-right me-1"></i>Tarik dari {{ $other['gudang']->nama_gudang }}
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="text-danger small" style="font-size: 0.72rem;">
                                                                Tidak ada stok di gudang lain yang mencukupi. Penjualan ini akan membuat stok minus jika dilanjutkan.
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="input-group input-group-sm">
                                                <select
                                                    wire:model.live="items.{{ $index }}.produk_id"
                                                    class="form-select form-select-sm @error("items.{$index}.produk_id") is-invalid @enderror"
                                                >
                                                    <option value="">-- Pilih Produk --</option>
                                                    @foreach ($produks as $produk)
                                                        <option value="{{ $produk->id }}">
                                                            {{ $produk->nama }} ({{ $produk->kode_katalog ?? 'No SKU' }}) [{{ $produk->satuan->label() }}]
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                        @error("items.{$index}.produk_id")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td data-label="Harga Satuan (Rp)">
                                        @php
                                            $hargaNormalRow = isset($item['harga_normal']) && $item['harga_normal'] !== null ? (float) $item['harga_normal'] : null;
                                            $isHargaDiubah = $prodModel && $hargaNormalRow !== null && abs((float) ($item['harga_satuan'] ?? 0) - $hargaNormalRow) > 0.001;
                                        @endphp
                                        <div class="input-group input-group-sm ms-md-auto" style="max-width: 185px;">
                                            <span class="input-group-text text-muted bg-light" style="font-size: 0.75rem;">Rp</span>
                                            <input
                                                type="number"
                                                step="any"
                                                min="0"
                                                wire:model.live.debounce.300ms="items.{{ $index }}.harga_satuan"
                                                class="form-control form-control-sm text-end font-monospace no-spinner {{ $isHargaDiubah ? 'border-warning bg-warning-subtle' : '' }} @error("items.{$index}.harga_satuan") is-invalid @enderror"
                                                placeholder="0"
                                                onwheel="this.blur()"
                                                {{ !auth()->user()->can('override_sales_price') ? 'readonly' : '' }}
                                            >
                                            @if ($isHargaDiubah)
                                                <button
                                                    type="button"
                                                    wire:click="resetHargaNormal({{ $index }})"
                                                    class="btn btn-outline-warning btn-sm"
                                                    title="Kembalikan ke harga normal"
                                                >
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            @endif
                                        </div>
                                        @if ($isHargaDiubah)
                                            <div class="text-md-end mt-1">
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.65rem;">
                                                    <i class="bi bi-pencil-square me-1"></i>Harga diubah
                                                </span>
                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                    Normal: Rp {{ number_format($hargaNormalRow, 0, ',', '.') }}
                                                </div>
                                            </div>
                                        @elseif ($prodModel && $selectedPelanggan)
                                            <div class="text-md-end mt-1">
                                                @if ($selectedPelanggan->tipe === \App\Domains\Catalog\Enums\TipePelanggan::Pemborong)
                                                    <span class="badge bg-info-subtle text-info border" style="font-size: 0.65rem;">Harga Aplikator</span>
                                                @elseif ($selectedPelanggan->tipe === \App\Domains\Catalog\Enums\TipePelanggan::Langganan)
                                                    <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.65rem;">Harga Toko</span>
                                                @else
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">Harga Ecer/Umum</span>
                                                @endif
                                            </div>
                                        @endif
                                        @error("items.{$index}.harga_satuan")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td data-label="Jumlah Keluar">
                                        <div class="d-flex align-items-center justify-content-start justify-content-md-center gap-1">
                                            <button
                                                type="button"
                                                wire:click="decrementQty({{ $index }})"
                                                class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-none"
                                                title="Kurangi 1"
                                                {{ (float)($item['jumlah'] ?? 0) <= 1 ? 'disabled' : '' }}
                                            >
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <div style="width: 80px;">
                                                <input
                                                    type="number"
                                                    step="any"
                                                    min="0.001"
                                                    id="qty-item-{{ $index }}"
                                                    wire:model.live.debounce.300ms="items.{{ $index }}.jumlah"
                                                    class="form-control form-control-sm text-center fw-bolder font-monospace no-spinner {{ $isDeficit ? 'border-danger text-danger' : 'border-secondary-subtle' }} @error("items.{$index}.jumlah") is-invalid @enderror"
                                                    placeholder="1"
                                                    onwheel="this.blur()"
                                                >
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="incrementQty({{ $index }})"
                                                class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-none"
                                                title="Tambah 1"
                                            >
                                                <i class="bi bi-plus"></i>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openCalculator({{ $index }})"
                                                class="btn btn-outline-primary btn-sm px-2 py-1 shadow-none"
                                                title="Buka Kalkulator Kemasan (Dus / Ikat / Roll)"
                                            >
                                                <i class="bi bi-calculator"></i>
                                            </button>
                                        </div>
                                        @php
                                            $physicalBadge = !empty($item['kemasan_deskripsi']) ? $item['kemasan_deskripsi'] : $this->getItemPhysicalBadge($item);
                                        @endphp
                                        @if ($physicalBadge)
                                            <div class="text-md-center mt-1">
                                                <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.68rem;" title="Rincian Fisik Kemasan">
                                                    <i class="bi bi-box-seam me-1"></i>{{ $physicalBadge }}
                                                </span>
                                            </div>
                                        @elseif ($prodModel)
                                            <div class="text-md-center text-muted mt-1 small" style="font-size: 0.72rem;">
                                                {{ $prodModel->satuan->label() }}
                                            </div>
                                        @endif
                                        @error("items.{$index}.jumlah")
                                            <div class="invalid-feedback d-block text-md-center">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-md-end" data-label="Diskon">
                                        @if ($diskonNominal > 0)
                                            <div class="fw-semibold text-danger">
                                                - Rp {{ number_format($diskonNominal, 0, ',', '.') }}
                                            </div>
                                            <small class="badge bg-success-subtle text-success border" style="font-size: 0.68rem;" title="{{ $item['promo_nama'] ?? 'Aturan Diskon Aktif' }}">
                                                <i class="bi bi-tag-fill me-1"></i>{{ Str::limit($item['promo_nama'] ?? 'Promo Aktif', 14) }}
                                            </small>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-md-end fw-bold" data-label="Subtotal (Rp)">
                                        Rp {{ number_format((float) ($item['subtotal'] ?? 0), 2, ',', '.') }}
                                    </td>
                                    <td class="text-end text-md-center">
                                        <button
                                            type="button"
                                            wire:click="removeItem({{ $index }})"
                                            class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                            title="Hapus baris"
                                            {{ count($items) <= 1 ? 'disabled' : '' }}
                                        >
                                            <i class="bi bi-trash"></i>
                                            <span class="d-md-none ms-1 small">Hapus Baris</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Belum ada barang di daftar penjualan. Ketik nama barang di kotak pencarian atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Summary & Ongkir Block (Responsive on Desktop & Mobile) -->
                <div class="bg-light border-top p-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-semibold"><i class="bi bi-box-seam me-1 text-primary"></i>Total Kuantitas Fisik:</span>
                                <span class="badge bg-white text-dark border font-monospace px-2 py-1">
                                    {{ number_format($this->totalKuantitasFisik, 2, ',', '.') }} Unit
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-md-7">
                            <div class="d-flex flex-column gap-2 ms-md-auto" style="max-width: 420px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted fw-bold small">Subtotal Produk:</span>
                                    <span class="fw-bold font-monospace text-dark">
                                        Rp {{ number_format($this->subtotalPenjualan, 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="text-muted fw-bold small text-nowrap">
                                        <i class="bi bi-truck me-1"></i>Biaya Ongkir:
                                    </span>
                                    <div class="input-group input-group-sm" style="max-width: 190px;">
                                        <span class="input-group-text text-muted bg-white" style="font-size: 0.75rem;">Rp</span>
                                        <input
                                            type="number"
                                            step="any"
                                            min="0"
                                            wire:model.live.debounce.300ms="biaya_ongkir"
                                            class="form-control form-control-sm text-end font-monospace no-spinner @error('biaya_ongkir') is-invalid @enderror"
                                            placeholder="0"
                                            onwheel="this.blur()"
                                        >
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="fw-bold text-dark fs-6">Grand Total Penjualan:</span>
                                    <span class="fw-bold fs-5 text-primary font-monospace">
                                        Rp {{ number_format($this->totalAkhir, 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Pilih barang melalui kotak pencarian di atas atau klik tombol <strong>+ Pilih Banyak dari Katalog</strong>.
                </span>
                <span class="text-muted small">
                    <i class="bi bi-box-seam me-1"></i>Jumlah muatan: <strong>{{ number_format($this->totalKuantitasFisik, 2, ',', '.') }} unit</strong>
                </span>
            </div>
        </div>

        <!-- Card 3: Informasi Pembayaran & Penyelesaian Transaksi (Atas-Bawah, Plong & Bersih) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="card-title fw-bold mb-0 text-primary">
                    <i class="bi bi-wallet2 me-2"></i>Pembayaran & Penyelesaian Transaksi
                </h6>
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Pilih metode pembayaran dan pastikan nominal pembayaran sesuai.
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- KOLOM KIRI (col-lg-7): Metode Bayar, Uang Diterima, Kembalian & Pasca Simpan -->
                    <div class="col-lg-7 border-end-lg">
                        <!-- Metode Pembayaran: Large Touch Pill / Radio Buttons -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark d-flex justify-content-between align-items-center mb-2">
                                <span><i class="bi bi-credit-card-2-front me-1 text-primary"></i>Metode Pembayaran <span class="text-danger">*</span></span>
                            </label>
                            <div class="row g-2">
                                <!-- Tunai -->
                                <div class="col-4">
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        wire:model.live="tipe_pembayaran"
                                        id="pay_tunai"
                                        value="tunai"
                                        name="tipe_pembayaran"
                                    >
                                    <label
                                        class="btn {{ $tipe_pembayaran === 'tunai' ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' }} w-100 py-2.5 d-flex flex-column align-items-center justify-content-center"
                                        for="pay_tunai"
                                        style="cursor: pointer; min-height: 58px;"
                                    >
                                        <i class="bi bi-cash-stack fs-5 mb-0.5"></i>
                                        <span style="font-size: 0.85rem;">Tunai (Cash)</span>
                                    </label>
                                </div>
                                <!-- Transfer -->
                                <div class="col-4">
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        wire:model.live="tipe_pembayaran"
                                        id="pay_transfer"
                                        value="transfer"
                                        name="tipe_pembayaran"
                                    >
                                    <label
                                        class="btn {{ $tipe_pembayaran === 'transfer' ? 'btn-primary text-white fw-bold shadow-sm' : 'btn-outline-secondary' }} w-100 py-2.5 d-flex flex-column align-items-center justify-content-center"
                                        for="pay_transfer"
                                        style="cursor: pointer; min-height: 58px;"
                                    >
                                        <i class="bi bi-bank fs-5 mb-0.5"></i>
                                        <span style="font-size: 0.85rem;">Transfer Bank</span>
                                    </label>
                                </div>
                                <!-- Tempo -->
                                <div class="col-4">
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        wire:model.live="tipe_pembayaran"
                                        id="pay_tempo"
                                        value="belum_bayar"
                                        name="tipe_pembayaran"
                                    >
                                    <label
                                        class="btn {{ $tipe_pembayaran === 'belum_bayar' ? 'btn-warning text-dark fw-bold shadow-sm' : 'btn-outline-secondary' }} w-100 py-2.5 d-flex flex-column align-items-center justify-content-center"
                                        for="pay_tempo"
                                        style="cursor: pointer; min-height: 58px;"
                                    >
                                        <i class="bi bi-clock-history fs-5 mb-0.5"></i>
                                        <span style="font-size: 0.85rem;">Tempo (Piutang)</span>
                                    </label>
                                </div>
                            </div>
                            @error('tipe_pembayaran')
                                <div class="alert alert-danger py-2 px-3 small mt-2 mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                                    <div>{{ $message }}</div>
                                </div>
                            @enderror
                        </div>

                        @if ($tipe_pembayaran !== 'belum_bayar')
                            <!-- Input Uang Diterima & Quick Cash Presets -->
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1.5">
                                    <label class="form-label fw-bold mb-0 small text-dark">
                                        <i class="bi bi-wallet2 me-1 text-success"></i>Uang Diterima / Bayar <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" wire:click="setUangPas" class="btn btn-sm btn-success py-1 px-2.5 fw-bold" style="font-size: 0.78rem;">
                                        <i class="bi bi-check-all me-1"></i>Uang Pas
                                    </button>
                                </div>

                                <div class="input-group input-group-lg mb-2">
                                    <span class="input-group-text fw-bold bg-white text-muted">Rp</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.live.debounce.250ms="jumlah_bayar"
                                        class="form-control fw-bold font-monospace fs-4 text-end bg-white @error('jumlah_bayar') is-invalid @enderror"
                                        placeholder="0"
                                    >
                                </div>
                                @error('jumlah_bayar')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <!-- Quick Cash Presets Buttons (Tablet-friendly, min-height 38px) -->
                                <div class="mt-2">
                                    <div class="d-flex gap-1.5 flex-wrap">
                                        <button type="button" wire:click="addNominalBayar(50000)" class="btn btn-outline-secondary btn-sm py-1.5 px-2 fw-semibold flex-fill" style="min-height: 38px;">
                                            +50rb
                                        </button>
                                        <button type="button" wire:click="addNominalBayar(100000)" class="btn btn-outline-secondary btn-sm py-1.5 px-2 fw-semibold flex-fill" style="min-height: 38px;">
                                            +100rb
                                        </button>
                                        <button type="button" wire:click="addNominalBayar(200000)" class="btn btn-outline-secondary btn-sm py-1.5 px-2 fw-semibold flex-fill" style="min-height: 38px;">
                                            +200rb
                                        </button>
                                        <button type="button" wire:click="clearNominalBayar" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 fw-semibold" style="min-height: 38px;" title="Reset uang bayar">
                                            <i class="bi bi-backspace"></i> C
                                        </button>
                                    </div>
                                    <div class="d-flex gap-1.5 flex-wrap mt-1.5">
                                        <button type="button" wire:click="setExactNominalBayar(50000)" class="btn btn-light border btn-sm py-1 px-1.5 text-muted font-monospace flex-fill" style="font-size: 0.75rem;">
                                            50k
                                        </button>
                                        <button type="button" wire:click="setExactNominalBayar(100000)" class="btn btn-light border btn-sm py-1 px-1.5 text-muted font-monospace flex-fill" style="font-size: 0.75rem;">
                                            100k
                                        </button>
                                        <button type="button" wire:click="setExactNominalBayar(200000)" class="btn btn-light border btn-sm py-1 px-1.5 text-muted font-monospace flex-fill" style="font-size: 0.75rem;">
                                            200k
                                        </button>
                                        <button type="button" wire:click="setExactNominalBayar(500000)" class="btn btn-light border btn-sm py-1 px-1.5 text-muted font-monospace flex-fill" style="font-size: 0.75rem;">
                                            500k
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Kembalian / Status Lunas / Kurang Bayar -->
                            @if ($this->kembalian > 0)
                                <div class="p-3 bg-success-subtle border border-success rounded-3 text-center mb-3">
                                    <small class="text-success fw-bold text-uppercase d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Uang Kembalian</small>
                                    <span class="fs-2 fw-bolder text-success font-monospace d-block mt-0.5">
                                        Rp {{ number_format($this->kembalian, 0, ',', '.') }}
                                    </span>
                                </div>
                            @elseif ($this->sisaPiutangBaru > 0)
                                <div class="p-3 bg-warning-subtle border border-warning rounded-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-warning-emphasis fw-bold text-uppercase" style="font-size: 0.72rem;">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                                            {{ $tipe_pembayaran === 'tunai' ? 'Uang Pembayaran Kurang' : 'Sisa Piutang (Kurang Bayar)' }}
                                        </small>
                                        <button type="button" wire:click="setUangPas" class="btn btn-xs btn-outline-warning py-0 px-1 text-dark fw-bold" style="font-size: 0.7rem;">
                                            Set Uang Pas
                                        </button>
                                    </div>
                                    <div class="text-center">
                                        <span class="fs-4 fw-bold text-danger font-monospace">
                                            Rp {{ number_format($this->sisaPiutangBaru, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mt-1 text-center" style="font-size: 0.72rem; line-height: 1.2;">
                                        Sisa kekurangan akan dicatat sebagai piutang pelanggan.
                                    </small>
                                </div>
                            @elseif ($this->kembalian == 0 && (float)$jumlah_bayar >= $this->totalAkhir && $this->totalAkhir > 0)
                                <div class="p-2.5 bg-success-subtle border border-success rounded-3 text-center mb-3">
                                    <span class="fw-bold text-success fs-6"><i class="bi bi-check-circle-fill me-1.5"></i>Lunas (Uang Pas)</span>
                                </div>
                            @endif
                        @else
                            <!-- Tempo Warning -->
                            <div class="p-3 bg-warning-subtle border border-warning rounded-3 text-center mb-3">
                                <small class="text-warning-emphasis fw-bold text-uppercase d-block" style="font-size: 0.75rem;">Total Masuk Piutang Dagang</small>
                                <span class="fs-3 fw-bolder text-danger font-monospace d-block mt-1">
                                    Rp {{ number_format($this->totalAkhir, 0, ',', '.') }}
                                </span>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                    Pelanggan dapat melunasinya melalui menu Pelunasan Piutang.
                                </small>
                            </div>
                        @endif

                        <!-- Customer Credit Limit & Debt Warning -->
                        @if ($selectedPelanggan)
                            @php
                                $limit = (float) $selectedPelanggan->limit_piutang;
                                $hutangLama = $this->piutangPelangganSaatIni;
                                $hutangBaru = $this->sisaPiutangBaru;
                                $totalProyeksi = $hutangLama + $hutangBaru;
                                $isZeroLimit = $limit <= 0;
                                $isOverLimit = $isZeroLimit || ($limit > 0 && $totalProyeksi > $limit);
                            @endphp
                            @if ($tipe_pembayaran === 'belum_bayar' || $hutangBaru > 0)
                                <div class="border rounded p-3 mb-3 {{ $isOverLimit ? 'bg-danger-subtle border-danger' : 'bg-light' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold small">
                                            <i class="bi bi-shield-lock me-1"></i>Informasi Piutang Pelanggan:
                                        </span>
                                        @if ($isZeroLimit)
                                            <span class="badge bg-danger">LIMIT RP 0 (DILARANG KASBON)</span>
                                        @elseif ($isOverLimit)
                                            <span class="badge bg-danger">MELEBIHI LIMIT!</span>
                                        @endif
                                    </div>
                                    <div class="row g-2 small">
                                        <div class="col-4">
                                            <span class="text-muted d-block" style="font-size: 0.72rem;">Limit:</span>
                                            <strong class="{{ $isZeroLimit ? 'text-danger' : '' }}" style="font-size: 0.78rem;">{{ $limit > 0 ? 'Rp ' . number_format($limit, 0, ',', '.') : 'Rp 0' }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted d-block" style="font-size: 0.72rem;">Piutang:</span>
                                            <strong class="{{ $hutangLama > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 0.78rem;">
                                                Rp {{ number_format($hutangLama, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted d-block" style="font-size: 0.72rem;">Proyeksi:</span>
                                            <strong class="{{ $isOverLimit ? 'text-danger' : 'text-primary' }}" style="font-size: 0.78rem;">
                                                Rp {{ number_format($totalProyeksi, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>
                                    @if ($isOverLimit)
                                        <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div class="text-danger small fw-semibold" style="font-size: 0.72rem;">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                @if ($isZeroLimit)
                                                    Pelanggan tidak memiliki limit kasbon!
                                                @else
                                                    Total piutang melampaui limit!
                                                @endif
                                            </div>
                                            @can('update', $selectedPelanggan)
                                                <button type="button" wire:click="openQuickLimitModal" class="btn btn-sm btn-danger py-0 px-2 fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-lightning-charge-fill me-1"></i>Ubah Limit
                                                </button>
                                            @endcan
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif

                        <!-- Pilihan Tindakan Setelah Simpan -->
                        <div class="pt-2 border-top">
                            <label class="form-label fw-bold small mb-2 text-dark">Tindakan Setelah Simpan:</label>
                            <div class="d-flex flex-column gap-1.5">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="pasca_simpan" id="pasca_cetak_kilat" value="cetak_kilat">
                                    <label class="form-check-label cursor-pointer small fw-semibold text-primary" for="pasca_cetak_kilat">
                                        <i class="bi bi-printer me-1"></i>Dialog Cetak Kilat (Rekomendasi POS)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="pasca_simpan" id="pasca_baru" value="baru">
                                    <label class="form-check-label cursor-pointer small" for="pasca_baru">
                                        <i class="bi bi-plus-circle me-1"></i>Transaksi Baru Langsung (Kasir Cepat)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="pasca_simpan" id="pasca_lihat" value="lihat">
                                    <label class="form-check-label cursor-pointer small" for="pasca_lihat">
                                        <i class="bi bi-file-text me-1"></i>Lihat Rincian Faktur
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div><!-- /col-lg-7 -->

                    <!-- KOLOM KANAN (col-lg-5): Ringkasan Tagihan & Tombol Selesaikan -->
                    <div class="col-lg-5 d-flex flex-column justify-content-between">
                        <!-- Ringkasan Grand Total Box -->
                        <div class="card border-primary-subtle bg-light shadow-sm p-4 rounded-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-bold small text-uppercase">Subtotal Barang</span>
                                <span class="fw-bold font-monospace text-dark fs-6">
                                    Rp {{ number_format($this->subtotalPenjualan, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-bold small text-uppercase">Biaya Ongkir</span>
                                <span class="fw-bold font-monospace text-dark fs-6">
                                    Rp {{ number_format((float)$biaya_ongkir, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Total Fisik Muatan</span>
                                <span class="badge bg-primary-subtle text-primary border font-monospace">
                                    {{ number_format($this->totalKuantitasFisik, 0) }} unit / koli
                                </span>
                            </div>

                            <div class="border-top pt-3 text-center bg-white p-3 rounded border">
                                <span class="text-uppercase small fw-bold text-muted d-block mb-1">Total Tagihan (Grand Total)</span>
                                <div class="fs-1 fw-bolder font-monospace text-primary">
                                    Rp {{ number_format($this->totalAkhir, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Simpan & Batal -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-3 fw-bold fs-5 shadow-sm" wire:loading.attr="disabled" style="min-height: 54px;">
                                <span wire:loading.remove wire:target="save,confirmShortageAndSave,saveAndPrint">
                                    <i class="bi bi-check2-circle me-2"></i>Simpan & Selesaikan
                                </span>
                                <span wire:loading wire:target="save,confirmShortageAndSave,saveAndPrint">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Menyimpan Faktur...
                                </span>
                            </button>

                            <a href="{{ route('penjualan.index') }}" class="btn btn-outline-secondary py-2 small">
                                <i class="bi bi-x-circle me-1"></i>Batal Transaksi
                            </a>
                        </div>
                    </div><!-- /col-lg-5 -->
                </div><!-- /row g-4 -->
            </div>
        </div>
    </form>

    <!-- Modal Dialog Cetak Kilat Pasca Simpan (Tablet / POS Quick Print) -->
    @if ($isPrintModalOpen && $lastSavedPenjualanId)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            style="background-color: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 1075;"
            aria-modal="true"
            role="dialog"
            @keydown.window.enter="$wire.startNewTransaction()"
        >
            <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
                <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
                    <div class="modal-header bg-success text-white py-3 px-4 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">Transaksi Berhasil Disimpan!</h5>
                                <div class="small text-white-50">Faktur Penjualan: <strong class="text-white font-monospace">{{ $lastSavedNoPenjualan }}</strong></div>
                            </div>
                        </div>
                        <button type="button" wire:click="closePrintModal" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 bg-white">
                        <!-- Invoice Summary Details -->
                        <div class="d-flex justify-content-between align-items-center p-3 rounded bg-light border mb-3">
                            <div>
                                <span class="text-muted small d-block">Metode Bayar:</span>
                                <span class="badge {{ $lastSavedMetode === 'tunai' ? 'bg-success' : ($lastSavedMetode === 'transfer' ? 'bg-primary' : 'bg-warning text-dark') }} text-uppercase fw-bold">
                                    {{ $lastSavedMetode === 'belum_bayar' ? 'TEMPO (PIUTANG)' : $lastSavedMetode }}
                                </span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block">Total Transaksi:</span>
                                <strong class="fs-5 font-monospace text-dark">
                                    Rp {{ number_format($lastSavedTotal, 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>

                        @if ($lastSavedKembalian > 0)
                            <div class="p-3 bg-success-subtle border border-success rounded-3 text-center mb-4">
                                <span class="text-success small fw-bold text-uppercase d-block" style="letter-spacing: 0.5px;">Uang Kembalian Kasir</span>
                                <span class="fs-1 fw-bolder font-monospace text-success d-block mt-1">
                                    Rp {{ number_format($lastSavedKembalian, 0, ',', '.') }}
                                </span>
                            </div>
                        @endif

                        <h6 class="fw-bold text-dark mb-2.5 d-flex align-items-center gap-1.5" style="font-size: 0.9rem;">
                            <i class="bi bi-printer text-primary"></i>
                            <span>Pilih Dokumen untuk Dicetak (1-Klik):</span>
                        </h6>

                        <!-- 3 Big 1-Click Print Buttons -->
                        <div class="d-grid gap-2 mb-3">
                            <a
                                href="{{ route('penjualan.print.thermal', $lastSavedPenjualanId) }}"
                                target="_blank"
                                class="btn btn-outline-dark py-2.5 px-3 d-flex justify-content-between align-items-center rounded-3 text-decoration-none shadow-sm"
                                style="min-height: 48px;"
                            >
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-receipt-cutoff fs-5 text-secondary"></i>
                                    <div class="text-start">
                                        <div class="fw-bold text-dark" style="font-size: 0.88rem;">Cetak Struk Kasir (Thermal)</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Ukuran kertas 58mm / 80mm printer kasir</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary border">Buka &bull; 🖨️</span>
                            </a>

                            <a
                                href="{{ route('penjualan.print.faktur', $lastSavedPenjualanId) }}"
                                target="_blank"
                                class="btn btn-outline-primary py-2.5 px-3 d-flex justify-content-between align-items-center rounded-3 text-decoration-none shadow-sm"
                                style="min-height: 48px;"
                            >
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-text fs-5 text-primary"></i>
                                    <div class="text-start">
                                        <div class="fw-bold text-primary" style="font-size: 0.88rem;">Cetak Faktur Penjualan (A4 / A5)</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Faktur resmi invoice dengan harga & rincian pembayaran</div>
                                    </div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border">Buka &bull; 🖨️</span>
                            </a>

                            <a
                                href="{{ route('penjualan.print.surat-jalan', $lastSavedPenjualanId) }}"
                                target="_blank"
                                class="btn btn-outline-success py-2.5 px-3 d-flex justify-content-between align-items-center rounded-3 text-decoration-none shadow-sm"
                                style="min-height: 48px;"
                            >
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-truck fs-5 text-success"></i>
                                    <div class="text-start">
                                        <div class="fw-bold text-success" style="font-size: 0.88rem;">Cetak Surat Jalan & Muatan</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Dokumen sopir/gudang tanpa harga, rincian per gudang</div>
                                    </div>
                                </div>
                                <span class="badge bg-success-subtle text-success border">Buka &bull; 🖨️</span>
                            </a>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-3 px-4 border-top d-flex justify-content-between align-items-center">
                        <button type="button" wire:click="closePrintModal" class="btn btn-outline-secondary">
                            Tutup
                        </button>
                        <button
                            type="button"
                            wire:click="startNewTransaction"
                            class="btn btn-primary py-2.5 px-4 fw-bold fs-6 shadow-sm"
                            autofocus
                        >
                            <i class="bi bi-plus-circle-fill me-1.5"></i>Transaksi Baru (Enter)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Warning Stok Kurang (Negative Stock Warning) -->
    @if ($isShortageModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-warning">
                    <div class="modal-header bg-warning-subtle text-warning-emphasis">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Peringatan Stok Tidak Mencukupi
                        </h5>
                        <button type="button" class="btn-close" wire:click="cancelShortage"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Jumlah penjualan melebihi stok yang tersedia di gudang terpilih. Jika dilanjutkan, stok barang akan menjadi <strong>negatif</strong>:
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end">Stok Saat Ini</th>
                                        <th class="text-end">Penjualan</th>
                                        <th class="text-end">Stok Setelahnya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($shortageWarnings as $warn)
                                        <tr>
                                            <td class="fw-semibold">{{ $warn['produk_nama'] }}</td>
                                            <td class="text-end">{{ number_format($warn['stok_saat_ini'], 2, ',', '.') }} {{ $warn['satuan'] }}</td>
                                            <td class="text-end text-primary fw-bold">{{ number_format($warn['jumlah_jual'], 2, ',', '.') }} {{ $warn['satuan'] }}</td>
                                            <td class="text-end text-danger fw-bold">{{ number_format($warn['stok_setelah'], 2, ',', '.') }} {{ $warn['satuan'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mb-0 text-muted small">
                            Apakah Anda yakin ingin tetap menyimpan transaksi penjualan ini?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelShortage">Batal</button>
                        <button type="button" class="btn btn-warning fw-bold" wire:click="confirmShortageAndSave" wire:loading.attr="disabled">
                            <i class="bi bi-check2-circle me-1"></i>Tetap Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Pilih Banyak Barang dari Katalog (Catalog Modal) -->
    @if ($isCatalogModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2 py-sm-3 px-3">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="bi bi-grid-3x3-gap-fill fs-5 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <h6 class="modal-title fw-bold mb-0 text-truncate">Pilih Barang dari Katalog</h6>
                                <small class="text-white-50 d-none d-sm-inline" style="font-size: 0.75rem;">Centang beberapa barang sekaligus untuk pesanan borongan / cepat.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCatalogModal"></button>
                    </div>

                    <!-- Sticky Search & Collapsible Filters -->
                    <div class="p-2 p-sm-3 bg-light border-bottom flex-shrink-0" x-data="{ showFilters: false }">
                        <!-- Search Bar + Mobile Filter Toggle -->
                        <div class="d-flex gap-2 mb-2">
                            <div class="input-group input-group-sm flex-grow-1">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input
                                    type="text"
                                    wire:model.live.debounce.250ms="catalogSearchQuery"
                                    class="form-control border-start-0"
                                    placeholder="Cari nama motif, ukuran, atau kode SKU..."
                                    autofocus
                                >
                                @if (filled($catalogSearchQuery))
                                    <button type="button" wire:click="$set('catalogSearchQuery', '')" class="btn btn-outline-secondary">
                                        <i class="bi bi-x"></i>
                                    </button>
                                @endif
                            </div>
                            @php
                                $hasActivePosCatFilter = filled($catalogKategoriId) || filled($catalogMerekId) || ($catalogStockFilter !== 'all');
                            @endphp
                            <button
                                type="button"
                                @click="showFilters = !showFilters"
                                class="btn btn-sm d-md-none flex-shrink-0 d-inline-flex align-items-center gap-1 {{ $hasActivePosCatFilter ? 'btn-primary' : 'btn-outline-secondary bg-white' }}"
                            >
                                <i class="bi bi-sliders"></i>
                                <span>Filter</span>
                            </button>
                        </div>

                        <!-- Collapsible Dropdowns on Mobile, Always Visible on Desktop -->
                        <div class="row g-2 mb-2 d-md-flex" :class="showFilters ? 'd-flex' : 'd-none'">
                            <!-- Filter Kategori -->
                            <div class="col-6 col-md-4">
                                <select wire:model.live="catalogKategoriId" class="form-select form-select-sm">
                                    <option value="">-- Semua Kategori --</option>
                                    @foreach ($rootKategoris as $root)
                                        @if ($root->children->isNotEmpty())
                                            <optgroup label="📁 {{ $root->nama_kategori }}">
                                                <option value="{{ $root->id }}">&bull; Semua {{ $root->nama_kategori }}</option>
                                                @foreach ($root->children as $child)
                                                    <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;↳ {{ $child->nama_kategori }}</option>
                                                @endforeach
                                            </optgroup>
                                        @else
                                            <option value="{{ $root->id }}">📁 {{ $root->nama_kategori }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Merek -->
                            <div class="col-6 col-md-4">
                                <select wire:model.live="catalogMerekId" class="form-select form-select-sm">
                                    <option value="">-- Semua Merek --</option>
                                    @foreach ($mereks as $merek)
                                        <option value="{{ $merek->id }}">{{ $merek->nama_merek }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Ketersediaan Stok -->
                            <div class="col-12 col-md-4">
                                <select wire:model.live="catalogStockFilter" class="form-select form-select-sm">
                                    <option value="all">Semua Stok</option>
                                    <option value="ready">Hanya Ready (&gt; 0)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Quick Brand Pills (Horizontal Swipe) -->
                        @if ($mereks->isNotEmpty())
                            <div class="d-flex align-items-center gap-1 nav-pills-scrollable flex-nowrap mb-2 pb-1">
                                <button
                                    type="button"
                                    wire:click="$set('catalogMerekId', '')"
                                    class="btn btn-xs py-1 px-2.5 rounded-pill text-nowrap flex-shrink-0 {{ empty($catalogMerekId) ? 'btn-primary fw-semibold' : 'btn-outline-secondary bg-white' }}"
                                    style="font-size: 0.74rem;"
                                >
                                    Semua Merek
                                </button>
                                @foreach ($mereks as $mrk)
                                    <button
                                        type="button"
                                        wire:click="$set('catalogMerekId', {{ $mrk->id }})"
                                        class="btn btn-xs py-1 px-2.5 rounded-pill text-nowrap flex-shrink-0 {{ (int)$catalogMerekId === $mrk->id ? 'btn-primary fw-semibold' : 'btn-outline-secondary bg-white' }}"
                                        style="font-size: 0.74rem;"
                                    >
                                        {{ $mrk->nama_merek }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <!-- Action Toolbar -->
                        <div class="d-flex justify-content-between align-items-center pt-1 border-top flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-1.5 small text-muted" style="font-size: 0.75rem;">
                                <span><strong>{{ count($catalogProducts) }}</strong> barang</span>
                                <span>&bull;</span>
                                <span>Terpilih: <strong class="text-primary">{{ count($catalogSelectedProductIds) }}</strong></span>
                                @if ($selectedPelanggan)
                                    <span class="badge bg-white text-primary border ms-1">
                                        Tier: {{ $selectedPelanggan->tipe->label() }}
                                    </span>
                                @endif
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" wire:click="selectAllFoundCatalogProducts" class="btn btn-outline-primary bg-white btn-xs py-1 px-2 fw-semibold" style="font-size: 0.74rem;">
                                    <i class="bi bi-check-all me-1"></i>Pilih Semua
                                </button>
                                @if (count($catalogSelectedProductIds) > 0)
                                    <button type="button" wire:click="clearSelectedCatalogProducts" class="btn btn-outline-danger bg-white btn-xs py-1 px-2 fw-semibold" style="font-size: 0.74rem;">
                                        <i class="bi bi-x-circle me-1"></i>Reset ({{ count($catalogSelectedProductIds) }})
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-body p-0 bg-white">
                        <!-- Mobile Product Card List (< 768px) — Full Width Product Name -->
                        <div class="d-md-none list-group list-group-flush">
                            @forelse ($catalogProducts as $cp)
                                @php $checked = in_array($cp['id'], $catalogSelectedProductIds, true); @endphp
                                <div
                                    wire:key="pos-cat-mob-{{ $cp['id'] }}"
                                    wire:click="toggleCatalogProduct({{ $cp['id'] }})"
                                    style="cursor: pointer;"
                                    class="list-group-item list-group-item-action py-2.5 px-3 {{ $checked ? 'bg-primary-subtle border-start border-4 border-primary' : ($cp['is_in_cart'] ? 'bg-light' : '') }}"
                                >
                                    <div class="d-flex align-items-start gap-2.5">
                                        <div onclick="event.stopPropagation();" class="pt-1 flex-shrink-0">
                                            <input
                                                type="checkbox"
                                                class="form-check-input m-0"
                                                style="width: 1.25rem; height: 1.25rem; cursor: pointer;"
                                                wire:click="toggleCatalogProduct({{ $cp['id'] }})"
                                                {{ $checked ? 'checked' : '' }}
                                            >
                                        </div>
                                        <div onclick="event.stopPropagation();" class="flex-shrink-0">
                                            @if (!empty($cp['foto_url']))
                                                <img src="{{ $cp['foto_url'] }}" alt="{{ $cp['nama'] }}"
                                                     class="rounded-3 border object-fit-cover"
                                                     style="width: 44px; height: 44px;"
                                                     loading="lazy"
                                                     wire:click="openImageModal({{ $cp['id'] }})">
                                            @else
                                                <div class="bg-light rounded-3 border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                                    <i class="bi bi-image" style="font-size: 1.1rem;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="d-flex align-items-start justify-content-between gap-1">
                                                <div class="fw-bold {{ $checked ? 'text-primary' : 'text-dark' }}" style="font-size: 0.9rem; line-height: 1.3;">
                                                    {{ $cp['nama'] }}
                                                </div>
                                                @if ($cp['is_in_cart'])
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle flex-shrink-0" style="font-size: 0.65rem;">
                                                        <i class="bi bi-cart-check"></i> Di Faktur
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size: 0.72rem;">
                                                @if (!empty($cp['kode_katalog']))
                                                    <span class="badge bg-light text-dark border font-monospace">{{ $cp['kode_katalog'] }}</span>
                                                @endif
                                                <span class="badge bg-secondary-subtle text-secondary border">{{ $cp['kategori'] }}</span>
                                                @if (!empty($cp['merek']))
                                                    <span class="badge bg-info-subtle text-info border">{{ $cp['merek'] }}</span>
                                                @endif
                                                @if (!empty($cp['ukuran']))
                                                    <span class="badge bg-light text-dark border">{{ $cp['ukuran'] }}</span>
                                                @endif
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mt-2 pt-1 border-top border-light-subtle">
                                                <div>
                                                    <span class="fw-bold text-success font-monospace" style="font-size: 0.86rem;">
                                                        Rp {{ number_format($cp['harga_jual'], 0, ',', '.') }}
                                                    </span>
                                                    <span class="text-muted" style="font-size: 0.74rem;">/ {{ $cp['satuan'] }}</span>
                                                    @if ($cp['is_tier_discounted'])
                                                        <span class="text-muted text-decoration-line-through ms-1" style="font-size: 0.7rem;">
                                                            Rp {{ number_format($cp['harga_normal'], 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="badge {{ $cp['stok'] <= 0 ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info' }} border font-monospace" style="font-size: 0.72rem;">
                                                    Stok: {{ number_format($cp['stok'], 2, ',', '.') }} {{ $cp['satuan'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 px-3 text-muted">
                                    <i class="bi bi-inbox d-block fs-2 mb-1 text-secondary"></i>
                                    Tidak ada produk yang cocok dengan pencarian / filter yang dipilih.
                                </div>
                            @endforelse
                        </div>

                        <!-- Desktop Product List Table (>= 768px) -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 35px;" class="text-center">#</th>
                                        <th>Produk & Spesifikasi</th>
                                        <th style="width: 90px;" class="text-center">Satuan</th>
                                        <th style="width: 170px;" class="text-end">Harga Satuan</th>
                                        <th style="width: 140px;" class="text-end">Stok Gudang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($catalogProducts as $cp)
                                        @php $checked = in_array($cp['id'], $catalogSelectedProductIds, true); @endphp
                                        <tr
                                            wire:click="toggleCatalogProduct({{ $cp['id'] }})"
                                            style="cursor: pointer;"
                                            class="{{ $checked ? 'table-primary' : ($cp['is_in_cart'] ? 'table-light' : '') }}"
                                        >
                                            <td class="text-center" onclick="event.stopPropagation();">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    wire:click="toggleCatalogProduct({{ $cp['id'] }})"
                                                    {{ $checked ? 'checked' : '' }}
                                                >
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                        @if (!empty($cp['foto_url']))
                                                            <img src="{{ $cp['foto_url'] }}" alt="{{ $cp['nama'] }}"
                                                                 class="rounded border object-fit-cover"
                                                                 style="width: 38px; height: 38px; cursor: pointer;"
                                                                 loading="lazy"
                                                                 @mouseenter="showPreview = true"
                                                                 @mouseleave="showPreview = false"
                                                                 wire:click.stop="openImageModal({{ $cp['id'] }})">
                                                            <div x-show="showPreview"
                                                                 class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                                 style="bottom: 100%; left: 0; z-index: 1085; min-width: 180px; max-width: 240px; pointer-events: none; margin-bottom: 6px;"
                                                                 x-cloak>
                                                                <img src="{{ $cp['foto_url'] }}" alt="{{ $cp['nama'] }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                                                <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $cp['nama'] }}</div>
                                                            </div>
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;" title="Belum ada foto">
                                                                <i class="bi bi-image" style="font-size: 1rem;"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                            <strong class="text-dark">{{ $cp['nama'] }}</strong>
                                                            @if ($cp['is_in_cart'])
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size: 0.68rem;" title="Barang ini sudah ada di faktur">
                                                                    <i class="bi bi-cart-check me-1"></i>Sudah di Faktur
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1 mt-1 flex-wrap text-muted" style="font-size: 0.72rem;">
                                                            @if (!empty($cp['kode_katalog']))
                                                                <span class="badge bg-light text-dark border">{{ $cp['kode_katalog'] }}</span>
                                                            @endif
                                                            <span class="badge bg-secondary-subtle text-secondary border">{{ $cp['kategori'] }}</span>
                                                            @if (!empty($cp['merek']))
                                                                <span class="badge bg-info-subtle text-info border">
                                                                    <i class="bi bi-tag me-1"></i>{{ $cp['merek'] }}
                                                                </span>
                                                            @endif
                                                            @if (!empty($cp['ukuran']))
                                                                <span class="badge bg-light text-dark border">
                                                                    <i class="bi bi-rulers me-1"></i>{{ $cp['ukuran'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-secondary border">{{ $cp['satuan'] }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="font-monospace fw-bold text-success">
                                                    Rp {{ number_format($cp['harga_jual'], 0, ',', '.') }}
                                                </div>
                                                @if ($cp['is_tier_discounted'])
                                                    <div class="d-flex align-items-center justify-content-end gap-1 mt-0.5">
                                                        <span class="text-muted text-decoration-line-through small" style="font-size: 0.7rem;">
                                                            Rp {{ number_format($cp['harga_normal'], 0, ',', '.') }}
                                                        </span>
                                                        <span class="badge bg-info-subtle text-info border" style="font-size: 0.65rem;">
                                                            {{ $cp['tier_label'] }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">{{ $cp['tier_label'] }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $cp['stok'] <= 0 ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info' }} border">
                                                    {{ number_format($cp['stok'], 2, ',', '.') }} {{ $cp['satuan'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox d-block fs-3 mb-1 text-secondary"></i>
                                                Tidak ada produk yang cocok dengan pencarian / filter yang dipilih.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-outline-secondary bg-white btn-sm flex-fill flex-sm-grow-0" wire:click="closeCatalogModal">Batal</button>
                        <button
                            type="button"
                            class="btn btn-primary btn-sm px-3 px-sm-4 fw-bold flex-fill flex-sm-grow-0"
                            wire:click="addSelectedCatalogProducts"
                            {{ count($catalogSelectedProductIds) === 0 ? 'disabled' : '' }}
                        >
                            <i class="bi bi-cart-plus me-1"></i>Tambahkan ({{ count($catalogSelectedProductIds) }}) Barang ke Faktur
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Daftar Lengkap Pelanggan (Customer Directory Modal) -->
    @if ($isPelangganModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2.5 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-lines-fill fs-5"></i>
                            <div>
                                <h6 class="modal-title fw-bold mb-0">Daftar Lengkap Pelanggan</h6>
                                <small class="text-white-50 d-none d-sm-inline" style="font-size: 0.75rem;">Cari dan pilih pelanggan untuk transaksi faktur penjualan.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closePelangganModal"></button>
                    </div>

                    <div class="p-2.5 p-sm-3 bg-light border-bottom flex-shrink-0">
                        <!-- Baris Filter & Pencarian -->
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-7">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input
                                        type="text"
                                        wire:model.live.debounce.250ms="pelangganModalSearch"
                                        class="form-control"
                                        placeholder="Cari nama pelanggan, no. telp/kontak..."
                                        autofocus
                                    >
                                    @if (filled($pelangganModalSearch))
                                        <button type="button" wire:click="$set('pelangganModalSearch', '')" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 col-md-5">
                                <select wire:model.live="pelangganModalTipe" class="form-select form-select-sm">
                                    <option value="">Semua Tipe Pelanggan</option>
                                    <option value="umum">Umum (Ecer)</option>
                                    <option value="pemborong">Pemborong / Aplikator</option>
                                    <option value="langganan">Langganan / Toko</option>
                                </select>
                            </div>
                        </div>

                        <!-- Info Bar & Tombol Tambah Cepat -->
                        <div class="d-flex justify-content-between align-items-center px-1">
                            <span class="text-muted small" style="font-size: 0.76rem;">
                                Menampilkan <strong>{{ count($modalPelanggans) }}</strong> pelanggan
                            </span>
                            <button type="button" wire:click="openQuickPelangganModalFromDirectory" class="btn btn-xs btn-success py-1 px-2.5 fw-semibold" style="font-size: 0.75rem;">
                                <i class="bi bi-person-plus-fill me-1"></i>+ Pelanggan Baru
                            </button>
                        </div>
                    </div>

                    <div class="modal-body p-0 bg-white">
                        <!-- Mobile Customer Card List (< 768px) -->
                        <div class="d-md-none list-group list-group-flush">
                            @forelse ($modalPelanggans as $cust)
                                @php
                                    $cLimit = (float) $cust->limit_piutang;
                                    $bClass = match($cust->tipe) {
                                        \App\Domains\Catalog\Enums\TipePelanggan::Umum => 'bg-secondary-subtle text-secondary',
                                        \App\Domains\Catalog\Enums\TipePelanggan::Pemborong => 'bg-info-subtle text-info',
                                        \App\Domains\Catalog\Enums\TipePelanggan::Langganan => 'bg-primary-subtle text-primary',
                                    };
                                @endphp
                                <div wire:click="selectPelangganFromModal({{ $cust->id }})"
                                     style="cursor: pointer;"
                                     class="list-group-item list-group-item-action py-2.5 px-3 d-flex justify-content-between align-items-center gap-2">
                                    <div class="min-w-0">
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="fw-bold text-dark">{{ $cust->nama }}</span>
                                            <span class="badge {{ $bClass }} border rounded-pill" style="font-size: 0.68rem;">
                                                {{ $cust->tipe->label() }}
                                            </span>
                                        </div>
                                        <div class="text-muted small mt-0.5" style="font-size: 0.74rem;">
                                            <i class="bi bi-telephone me-1"></i>{{ filled($cust->kontak) ? $cust->kontak : 'Tanpa kontak' }}
                                            &bull; Limit: <strong>{{ $cLimit > 0 ? 'Rp ' . number_format($cLimit, 0, ',', '.') : 'Cash only' }}</strong>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-2.5 fw-semibold flex-shrink-0">
                                        Pilih
                                    </button>
                                </div>
                            @empty
                                <div class="text-center py-5 px-3 text-muted">
                                    <i class="bi bi-people d-block fs-2 text-muted mb-2"></i>
                                    <div>Tidak ada pelanggan yang cocok dengan pencarian.</div>
                                    <button type="button" wire:click="openQuickPelangganModalFromDirectory" class="btn btn-sm btn-success mt-2 py-1 px-3">
                                        <i class="bi bi-plus-circle me-1"></i>Tambah '{{ $pelangganModalSearch ?: 'Pelanggan Baru' }}' Sekarang
                                    </button>
                                </div>
                            @endforelse
                        </div>

                        <!-- Desktop Tabel Pelanggan (>= 768px) -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="py-2 ps-3" style="width: 38%;">Nama Pelanggan</th>
                                        <th class="py-2" style="width: 24%;">No. HP / Kontak</th>
                                        <th class="py-2 text-center" style="width: 18%;">Tipe / Tier</th>
                                        <th class="py-2 text-end pe-3" style="width: 20%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($modalPelanggans as $cust)
                                        <tr>
                                            <td class="ps-3 py-2">
                                                <div class="fw-bold text-dark">{{ $cust->nama }}</div>
                                                @php $cLimit = (float) $cust->limit_piutang; @endphp
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    <i class="bi bi-shield-lock me-1"></i>Limit: {{ $cLimit > 0 ? 'Rp ' . number_format($cLimit, 0, ',', '.') : 'Rp 0 (Cash only)' }}
                                                </div>
                                            </td>
                                            <td class="py-2">
                                                @if(filled($cust->kontak))
                                                    <span class="text-dark"><i class="bi bi-telephone me-1 text-muted"></i>{{ $cust->kontak }}</span>
                                                @else
                                                    <span class="text-muted fst-italic">-</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-center">
                                                @php
                                                    $bClass = match($cust->tipe) {
                                                        \App\Domains\Catalog\Enums\TipePelanggan::Umum => 'bg-secondary-subtle text-secondary',
                                                        \App\Domains\Catalog\Enums\TipePelanggan::Pemborong => 'bg-info-subtle text-info',
                                                        \App\Domains\Catalog\Enums\TipePelanggan::Langganan => 'bg-primary-subtle text-primary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $bClass }} border rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                                    {{ $cust->tipe->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-3 py-2">
                                                <button
                                                    type="button"
                                                    wire:click="selectPelangganFromModal({{ $cust->id }})"
                                                    class="btn btn-sm btn-primary py-1 px-3 fw-semibold shadow-sm"
                                                    style="font-size: 0.78rem;"
                                                >
                                                    <i class="bi bi-check2-circle me-1"></i>Pilih
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="bi bi-people d-block fs-2 text-muted mb-2"></i>
                                                <div>Tidak ada pelanggan yang cocok dengan kata kunci atau filter.</div>
                                                <button type="button" wire:click="openQuickPelangganModalFromDirectory" class="btn btn-sm btn-success mt-2 py-1 px-3">
                                                    <i class="bi bi-plus-circle me-1"></i>Tambah '{{ $pelangganModalSearch ?: 'Pelanggan Baru' }}' Sekarang
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-info-circle me-1"></i>Klik tombol <strong>Pilih</strong> untuk langsung menggunakan pelanggan ini di faktur.
                        </small>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="closePelangganModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Tambah Pelanggan Baru Cepat (Quick Add Pelanggan) -->
    @if ($isQuickPelangganModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-plus-fill fs-5"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">Tambah Pelanggan Baru</h5>
                                <small class="text-white-50" style="font-size: 0.75rem;">Input cepat data pembeli agar langsung masuk transaksi.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeQuickPelangganModal"></button>
                    </div>

                    <form wire:submit="saveQuickPelanggan">
                        <div class="modal-body p-4 bg-light-subtle">
                            <div class="mb-3">
                                <label for="new_pelanggan_nama" class="form-label fw-bold small text-dark mb-1">
                                    Nama Pelanggan / Toko / Tukang <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="new_pelanggan_nama"
                                    wire:model="new_pelanggan_nama"
                                    class="form-control form-control-sm @error('new_pelanggan_nama') is-invalid @enderror"
                                    placeholder="Contoh: Pak Bambang, Toko Berkah, dll..."
                                    autofocus
                                    required
                                >
                                @error('new_pelanggan_nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="new_pelanggan_kontak" class="form-label fw-bold small text-dark mb-1">
                                    No. HP / WhatsApp (Opsional)
                                </label>
                                <input
                                    type="text"
                                    id="new_pelanggan_kontak"
                                    wire:model="new_pelanggan_kontak"
                                    class="form-control form-control-sm @error('new_pelanggan_kontak') is-invalid @enderror"
                                    placeholder="Contoh: 081234567890"
                                >
                                @error('new_pelanggan_kontak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    Kategori / Tipe Pelanggan <span class="text-danger">*</span>
                                </label>
                                <div class="btn-group w-100 shadow-sm" role="group">
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        wire:model.live="new_pelanggan_tipe"
                                        id="q_tipe_umum"
                                        value="umum"
                                        name="new_pelanggan_tipe"
                                    >
                                    <label
                                        class="btn {{ $new_pelanggan_tipe === 'umum' ? 'btn-secondary text-white fw-bold' : 'btn-outline-secondary' }} py-2"
                                        for="q_tipe_umum"
                                        style="cursor: pointer;"
                                    >
                                        <i class="bi bi-person me-1"></i>Umum (Ecer)
                                    </label>

                                    <input
                                        type="radio"
                                        class="btn-check"
                                        wire:model.live="new_pelanggan_tipe"
                                        id="q_tipe_pemborong"
                                        value="pemborong"
                                        name="new_pelanggan_tipe"
                                    >
                                    <label
                                        class="btn {{ $new_pelanggan_tipe === 'pemborong' ? 'btn-info text-white fw-bold' : 'btn-outline-secondary' }} py-2"
                                        for="q_tipe_pemborong"
                                        style="cursor: pointer;"
                                    >
                                        <i class="bi bi-tools me-1"></i>Aplikator
                                    </label>

                                    <input
                                        type="radio"
                                        class="btn-check"
                                        wire:model.live="new_pelanggan_tipe"
                                        id="q_tipe_langganan"
                                        value="langganan"
                                        name="new_pelanggan_tipe"
                                    >
                                    <label
                                        class="btn {{ $new_pelanggan_tipe === 'langganan' ? 'btn-primary text-white fw-bold' : 'btn-outline-secondary' }} py-2"
                                        for="q_tipe_langganan"
                                        style="cursor: pointer;"
                                    >
                                        <i class="bi bi-shop me-1"></i>Toko (Grosir)
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.73rem;">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Tipe otomatis menentukan harga produk:
                                    @if ($new_pelanggan_tipe === 'pemborong')
                                        <strong class="text-info">Harga Aplikator</strong>
                                    @elseif ($new_pelanggan_tipe === 'langganan')
                                        <strong class="text-primary">Harga Grosir</strong>
                                    @else
                                        <strong class="text-secondary">Harga Eceran Normal</strong>
                                    @endif
                                </small>
                            </div>

                            <div class="mb-0">
                                <label for="new_pelanggan_limit" class="form-label fw-bold small text-dark mb-1">
                                    Batas Limit Piutang (Rp)
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input
                                        type="number"
                                        step="any"
                                        min="0"
                                        id="new_pelanggan_limit"
                                        wire:model="new_pelanggan_limit"
                                        class="form-control text-end @error('new_pelanggan_limit') is-invalid @enderror"
                                        placeholder="0"
                                    >
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">Isi 0 jika pembelian tempo tidak diizinkan.</small>
                                @error('new_pelanggan_limit')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeQuickPelangganModal">Batal</button>
                            <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveQuickPelanggan">
                                    <i class="bi bi-check-lg me-1"></i>Simpan & Pilih Pelanggan
                                </span>
                                <span wire:loading wire:target="saveQuickPelanggan">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Ubah Limit Piutang Pelanggan Cepat (Quick Edit Limit) -->
    @if ($isQuickLimitModalOpen && $selectedPelanggan)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-warning text-dark py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">Ubah Limit Piutang Pelanggan</h5>
                                <small class="text-dark" style="font-size: 0.75rem;">Sesuaikan batas plafon kasbon/tempo untuk pelanggan ini.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeQuickLimitModal"></button>
                    </div>

                    <form wire:submit="saveQuickLimit">
                        <div class="modal-body p-4 bg-light-subtle">
                            <!-- Info Pelanggan -->
                            <div class="p-3 bg-white rounded border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Nama Pelanggan:</span>
                                    <strong class="text-dark">{{ $selectedPelanggan->nama }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Tipe / Tier:</span>
                                    <span class="badge bg-secondary-subtle text-secondary border">{{ $selectedPelanggan->tipe->label() }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Piutang Berjalan:</span>
                                    <strong class="{{ $this->piutangPelangganSaatIni > 0 ? 'text-danger' : 'text-success' }}">
                                        Rp {{ number_format($this->piutangPelangganSaatIni, 0, ',', '.') }}
                                    </strong>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="quickLimitAmount" class="form-label fw-bold small text-dark mb-1">
                                    Batas Plafon Limit Piutang Baru (Rp) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text fw-bold">Rp</span>
                                    <input
                                        type="number"
                                        step="1000"
                                        min="0"
                                        id="quickLimitAmount"
                                        wire:model="quickLimitAmount"
                                        class="form-control fw-bold font-monospace text-end @error('quickLimitAmount') is-invalid @enderror"
                                        placeholder="0"
                                        autofocus
                                        required
                                    >
                                </div>
                                <div class="d-flex gap-1 mt-2 flex-wrap">
                                    @foreach ([0, 5000000, 10000000, 20000000, 50000000] as $nom)
                                        <button
                                            type="button"
                                            wire:click="$set('quickLimitAmount', {{ $nom }})"
                                            class="btn btn-outline-secondary btn-sm py-0 px-2"
                                            style="font-size: 0.72rem;"
                                        >
                                            {{ $nom === 0 ? 'Rp 0 (Blokir Utang)' : 'Rp ' . number_format($nom, 0, ',', '.') }}
                                        </button>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">
                                    *Isi <strong>Rp 0</strong> jika pelanggan dilarang kasbon sama sekali (wajib tunai/lunas).
                                </small>
                                @error('quickLimitAmount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeQuickLimitModal">Batal</button>
                            <button type="submit" class="btn btn-warning btn-sm px-4 fw-semibold text-dark" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveQuickLimit">
                                    <i class="bi bi-check-lg me-1"></i>Simpan Limit Baru
                                </span>
                                <span wire:loading wire:target="saveQuickLimit">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Peringatan Stok Kurang / Minus (Shortage Warning Modal) -->
    @if ($isShortageModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">Peringatan: Stok di Gudang Tidak Mencukupi!</h5>
                                <small class="text-white-50" style="font-size: 0.75rem;">Beberapa barang yang dijual melebihi stok fisik yang tercatat di sistem.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="cancelShortage"></button>
                    </div>

                    <div class="modal-body p-4 bg-light-subtle">
                        <div class="alert alert-warning border-warning d-flex align-items-center gap-2 py-2 px-3 small mb-3">
                            <i class="bi bi-info-circle-fill text-warning fs-5"></i>
                            <div>
                                Jika Anda melanjutkan, stok produk di gudang terkait akan tercatat <strong>minus (negatif)</strong>. Pastikan fisik barang benar-benar ada di toko.
                            </div>
                        </div>

                        <!-- Tabel Barang Kurang -->
                        <div class="table-responsive bg-white rounded border mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk & Gudang</th>
                                        <th class="text-center" style="width: 80px;">Satuan</th>
                                        <th class="text-end" style="width: 110px;">Stok Sistem</th>
                                        <th class="text-end" style="width: 110px;">Dijual</th>
                                        <th class="text-end text-danger" style="width: 120px;">Defisit (Minus)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($shortageWarnings as $warn)
                                        <tr>
                                            <td class="fw-semibold text-dark">{{ $warn['produk_nama'] }}</td>
                                            <td class="text-center text-muted">{{ $warn['satuan'] }}</td>
                                            <td class="text-end font-monospace">{{ number_format($warn['stok_saat_ini'], 2, ',', '.') }}</td>
                                            <td class="text-end font-monospace fw-bold">{{ number_format($warn['jumlah_jual'], 2, ',', '.') }}</td>
                                            <td class="text-end font-monospace text-danger fw-bold bg-danger-subtle">
                                                {{ number_format($warn['stok_setelah'], 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pilihan Alasan Kasir -->
                        <div class="card border p-3 bg-white">
                            <label class="form-label fw-bold text-dark small mb-2">
                                <i class="bi bi-question-circle me-1 text-primary"></i>Pilih Alasan Stok Minus:
                            </label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model.live="shortageAlasan" id="alasanFisikTiba" value="barang_fisik_tiba">
                                    <label class="form-check-label small" for="alasanFisikTiba">
                                        <strong>Barang fisik sudah tiba di toko</strong> (Surat jalan / Faktur PO supplier belum sempat diinput admin).
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model.live="shortageAlasan" id="alasanIzinSupervisor" value="izin_supervisor">
                                    <label class="form-check-label small" for="alasanIzinSupervisor">
                                        <strong>Telah disetujui Supervisor / Pemilik Toko</strong>.
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model.live="shortageAlasan" id="alasanLainnya" value="lainnya">
                                    <label class="form-check-label small" for="alasanLainnya">
                                        <strong>Alasan lainnya</strong> (tulis catatan singkat di bawah).
                                    </label>
                                </div>
                            </div>

                            @if ($shortageAlasan === 'lainnya')
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        wire:model="shortageCatatanLainnya"
                                        class="form-control form-control-sm"
                                        placeholder="Ketik keterangan alasan stok minus..."
                                        autofocus
                                    >
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancelShortage">
                            <i class="bi bi-x-circle me-1"></i>Batal & Periksa Ulang
                        </button>
                        <button type="button" class="btn btn-danger btn-sm px-4 fw-bold shadow-sm" wire:click="confirmShortageAndSave">
                            <i class="bi bi-check-circle-fill me-1"></i>Tetap Simpan Penjualan (Stok Minus)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Kalkulator Kemasan Fisik Kasir (Packaging Calculator Modal v2) -->
    @if ($isCalculatorModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1070;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-calculator-fill fs-5"></i>
                            <div>
                                <h6 class="modal-title fw-bold mb-0">Kalkulator Kemasan Fisik Kasir</h6>
                                <small class="text-white-50" style="font-size: 0.75rem;">Konversi hitung Dus / Ikat / Roll otomatis ke satuan faktur.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCalculator" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <div class="fw-bold text-dark fs-6">{{ $calcItemName ?: $calcProductName }}</div>
                            @php
                                $isRollModal = ($calcMode === 'roll') || strcasecmp($calcKemasanLabel ?? '', 'roll') === 0 || str_contains(strtolower($calcSatuan ?? ''), 'roll');
                                $isPvcModal = !$isRollModal && ($calcMode === 'dus_pvc' || (str_contains(strtolower($calcSatuan ?? ''), 'meter') && !empty($calcPanjangMeter) && !empty($calcIsiPerKemasan)));
                                $firstWordSatuan = strtolower(strtok($calcSatuan ?? '', " (/"));
                                $firstWordKemasan = strtolower(strtok($calcKemasanLabel ?? '', " (/"));
                                $isUnitSameAsKemasan = !$isRollModal && (($calcMode === 'kemasan_dus') || ($firstWordKemasan !== '' && (
                                    $firstWordSatuan === $firstWordKemasan
                                    || (str_contains(strtolower($calcSatuan ?? ''), 'dus') && str_contains(strtolower($calcKemasanLabel ?? ''), 'dus'))
                                    || (str_contains(strtolower($calcSatuan ?? ''), 'box') && str_contains(strtolower($calcKemasanLabel ?? ''), 'box'))
                                    || (str_contains(strtolower($calcSatuan ?? ''), 'pack') && str_contains(strtolower($calcKemasanLabel ?? ''), 'pack'))
                                )));
                                $isiPck = (float)($calcIsiPerKemasan ?: 0);
                                $pjgMtr = (float)($calcPanjangMeter ?: 0);
                                $jmlKms = (float)($calcJmlKemasan ?: 0);
                                $jmlEcr = (float)(is_string($calcJmlEceran) ? str_replace(',', '.', $calcJmlEceran) : ($calcJmlEceran ?: 0));
                                $lembarKms = $jmlKms * $isiPck;
                                $totalLembar = $lembarKms + $jmlEcr;
                                $eceranPecahan = ($isUnitSameAsKemasan && $isiPck > 0) ? ($jmlEcr / $isiPck) : 0;
                            @endphp
                            <div class="d-flex gap-2 flex-wrap mt-1">
                                <span class="badge bg-light text-dark border">
                                    Kemasan: <strong>{{ $calcKemasanLabel ?: 'Dus' }}</strong>
                                </span>
                                @if($isRollModal)
                                    <span class="badge bg-light text-primary border">
                                        Panjang per Roll: <strong>{{ $isiPck }} Meter</strong>
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        1 Roll = <strong>{{ $isiPck }} Meter</strong>
                                    </span>
                                @elseif($isPvcModal)
                                    <span class="badge bg-light text-primary border">
                                        Isi: <strong>{{ $isiPck }} Lembar</strong>
                                    </span>
                                    <span class="badge bg-light text-success border">
                                        Panjang: <strong>{{ $pjgMtr }} Meter/lembar</strong>
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        1 {{ $calcKemasanLabel ?: 'Dus' }} = <strong>{{ $isiPck * $pjgMtr }} Meter</strong>
                                    </span>
                                @elseif($isUnitSameAsKemasan && $isiPck > 0)
                                    <span class="badge bg-light text-primary border">
                                        Isi per Dus: <strong>{{ number_format($isiPck, 0, ',', '.') }} Pcs (biji)</strong>
                                    </span>
                                @else
                                    @if($calcIsiPerKemasan)
                                        <span class="badge bg-light text-primary border">
                                            Isi: <strong>{{ number_format($isiPck, 0, ',', '.') }}</strong>
                                        </span>
                                    @endif
                                    @if($calcPanjangMeter)
                                        <span class="badge bg-light text-success border">
                                            Panjang: <strong>{{ $pjgMtr }} Meter</strong>
                                        </span>
                                    @endif
                                @endif
                                <span class="badge bg-secondary-subtle text-secondary">
                                    Satuan Faktur: <strong>{{ $calcSatuan ?: $calcTargetUnit }}</strong>
                                </span>
                            </div>
                        </div>

                        <div class="card bg-light border-0 p-3 mb-3 rounded-3">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-dark">
                                        @if($isRollModal)
                                            Jumlah Roll Utuh
                                        @elseif($isUnitSameAsKemasan)
                                            Jumlah {{ $calcKemasanLabel ?: 'Dus' }} Utuh
                                        @else
                                            Jumlah {{ $calcKemasanLabel ?: 'Kemasan Penuh' }}
                                        @endif
                                    </label>
                                    <input type="number"
                                           min="0"
                                           step="1"
                                           wire:model.live.debounce.150ms="calcJmlKemasan"
                                           class="form-control form-control-lg text-center fw-bold"
                                           placeholder="0"
                                           autofocus>
                                    @if($isRollModal)
                                        <div class="small text-muted text-center mt-1">
                                            {{ $jmlKms }} Roll = <strong>{{ number_format($jmlKms * $isiPck, 2, ',', '.') }} m</strong>
                                        </div>
                                    @elseif($isPvcModal)
                                        <div class="small text-muted text-center mt-1">
                                            {{ $jmlKms }} {{ $calcKemasanLabel ?: 'Dus' }} = <strong>{{ $lembarKms }} Lembar</strong>
                                            <div class="text-primary fw-semibold fs-7">(= {{ number_format($lembarKms * $pjgMtr, 2, ',', '.') }} m)</div>
                                        </div>
                                    @elseif($isUnitSameAsKemasan)
                                        <div class="small text-muted text-center mt-1">
                                            {{ $jmlKms }} {{ $calcKemasanLabel ?: 'Dus' }} = <strong>{{ number_format($jmlKms, 2, ',', '.') }} {{ $calcSatuan }}</strong>
                                        </div>
                                    @elseif($calcIsiPerKemasan)
                                        <div class="small text-muted text-center mt-1">
                                            {{ $jmlKms }} {{ $calcKemasanLabel ?: 'Dus' }} = <strong>{{ $jmlKms * $isiPck }} {{ $calcSatuan }}</strong>
                                        </div>
                                    @else
                                        <small class="text-muted d-block text-center mt-1">Dus / Ikat / Sak</small>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-dark">
                                        @if($isRollModal)
                                            Tambahan Eceran (Meter)
                                        @elseif($isUnitSameAsKemasan)
                                            Tambahan Biji / Pcs Eceran
                                        @elseif($isPvcModal)
                                            Tambahan Lembar Lepasan
                                        @else
                                            Tambahan Eceran / Lepasan
                                        @endif
                                    </label>
                                    <input type="text"
                                           inputmode="decimal"
                                           wire:model.live.debounce.400ms="calcJmlEceran"
                                           class="form-control form-control-lg text-center fw-bold"
                                           placeholder="0">
                                    @if($isRollModal)
                                        <div class="small text-muted text-center mt-1">
                                            {{ $jmlEcr }} Meter Lepasan
                                        </div>
                                    @elseif($isPvcModal)
                                        <div class="small text-muted text-center mt-1">
                                            {{ $jmlEcr }} Lembar Lepasan
                                            <div class="text-primary fw-semibold fs-7">(= {{ number_format($jmlEcr * $pjgMtr, 2, ',', '.') }} m)</div>
                                            @if(fmod($jmlEcr, 1) != 0 && $pjgMtr > 0)
                                                @php
                                                    $whEcr = (int) floor($jmlEcr);
                                                    $sisaMtrPotongan = round(($jmlEcr - $whEcr) * $pjgMtr, 2);
                                                @endphp
                                                <div class="text-success small fw-medium mt-1">
                                                    <i class="bi bi-scissors me-1"></i>
                                                    @if($whEcr > 0)
                                                        {{ $whEcr }} lbr utuh + {{ $sisaMtrPotongan }}m potongan
                                                    @else
                                                        Potongan {{ $sisaMtrPotongan }}m
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($isUnitSameAsKemasan && $isiPck > 0)
                                        <div class="small text-muted text-center mt-1">
                                            {{ $jmlEcr }} Biji Eceran = <strong>{{ number_format($eceranPecahan, 2, ',', '.') }} {{ $calcKemasanLabel ?: 'Dus' }}</strong>
                                        </div>
                                    @else
                                        <small class="text-muted d-block text-center mt-1">Lembar / Batang / Pcs</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Live Calculation Preview -->
                        <div class="card border border-primary-subtle bg-primary bg-opacity-10 p-3 rounded-3 mb-3">
                            @if($isRollModal)
                                <div class="small fw-semibold text-primary mb-2">
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Roll ke Satuan Meter Faktur:
                                </div>
                                <div class="bg-white rounded p-2 mb-2 border border-primary-subtle small font-monospace">
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• {{ $jmlKms }} Roll × {{ $isiPck }}m:</span>
                                        <span class="fw-bold text-dark">{{ number_format($jmlKms * $isiPck, 2, ',', '.') }} m</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• {{ $jmlEcr }} Meter Eceran Lepas:</span>
                                        <span class="fw-bold text-dark">{{ number_format($jmlEcr, 2, ',', '.') }} m</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-dark border-top pt-1">
                                        <span><strong>Total Masuk Faktur:</strong></span>
                                        <span class="fw-bold text-primary">{{ number_format($this->computedPhysicalStock, 2, ',', '.') }} m</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="small text-muted font-monospace">
                                        Rumus: ({{ $jmlKms }} × {{ $isiPck }}m) + {{ $jmlEcr }}m =
                                    </div>
                                    <div class="fs-4 fw-bold text-primary">
                                        {{ number_format($this->computedPhysicalStock, 2, ',', '.') }} {{ $calcSatuan }}
                                    </div>
                                </div>
                            @elseif($isPvcModal)
                                <div class="small fw-semibold text-primary mb-2">
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Plafon PVC ke Meter Faktur:
                                </div>
                                <div class="bg-white rounded p-2 mb-2 border border-primary-subtle small font-monospace">
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• {{ $jmlKms }} {{ $calcKemasanLabel ?: 'Dus' }} ({{ $lembarKms }} Lbr) × {{ $pjgMtr }}m:</span>
                                        <span class="fw-bold text-dark">{{ number_format($lembarKms * $pjgMtr, 2, ',', '.') }} m</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• {{ $jmlEcr }} Lbr Lepasan × {{ $pjgMtr }}m:</span>
                                        <span class="fw-bold text-dark">{{ number_format($jmlEcr * $pjgMtr, 2, ',', '.') }} m</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-dark border-top pt-1">
                                        <span><strong>Total Lembar:</strong> {{ $totalLembar }} Lbr × {{ $pjgMtr }}m</span>
                                        <span class="fw-bold text-primary">{{ number_format($this->computedPhysicalStock, 2, ',', '.') }} m</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="small text-muted font-monospace">
                                        Rumus: (({{ $jmlKms }} × {{ $isiPck }}) + {{ $jmlEcr }}) × {{ $pjgMtr }}m =
                                    </div>
                                    <div class="fs-4 fw-bold text-primary">
                                        {{ number_format($this->computedPhysicalStock, 2, ',', '.') }} {{ $calcSatuan }}
                                    </div>
                                </div>
                            @elseif($isUnitSameAsKemasan && $isiPck > 0)
                                <div class="small fw-semibold text-primary mb-2">
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Eceran Biji ke Satuan Dus:
                                </div>
                                <div class="bg-white rounded p-2 mb-2 border border-primary-subtle small font-monospace">
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• Kemasan Utuh: {{ $jmlKms }} {{ $calcKemasanLabel ?: 'Dus' }}:</span>
                                        <span class="fw-bold text-dark">{{ number_format($jmlKms, 2, ',', '.') }} {{ $calcSatuan }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• Eceran Lepas: {{ $jmlEcr }} Pcs (@ {{ number_format($isiPck, 0, ',', '.') }} pcs/dus):</span>
                                        <span class="fw-bold text-dark">{{ number_format($eceranPecahan, 2, ',', '.') }} {{ $calcSatuan }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-dark border-top pt-1">
                                        <span><strong>Total Kuantitas Masuk Faktur:</strong></span>
                                        <span class="fw-bold text-primary">{{ number_format($this->computedPhysicalStock, 2, ',', '.') }} {{ $calcSatuan }}</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="small text-muted font-monospace">
                                        Rumus: {{ $jmlKms }} + ({{ $jmlEcr }} / {{ number_format($isiPck, 0, ',', '.') }}) =
                                    </div>
                                    <div class="fs-4 fw-bold text-primary">
                                        {{ number_format($this->computedPhysicalStock, 2, ',', '.') }} {{ $calcSatuan }}
                                    </div>
                                </div>
                            @else
                                <div class="small text-muted mb-1">Total Kuantitas Masuk Faktur:</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small text-dark font-monospace">
                                        @if($calcIsiPerKemasan)
                                            ({{ $jmlKms }} × {{ $isiPck }}) + {{ $jmlEcr }}
                                        @else
                                            {{ $jmlKms }} + {{ $jmlEcr }}
                                        @endif
                                    </div>
                                    <div class="fs-4 fw-bold text-primary">
                                        {{ number_format($this->computedPhysicalStock, 2, ',', '.') }} {{ $calcSatuan }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeCalculator">Batal</button>
                        <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" wire:click="applyCalculation">
                            <i class="bi bi-check-lg me-1"></i>Terapkan ke Faktur
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Lightbox Pratinjau Foto Produk -->
    @if ($isImageModalOpen && $this->imageModalProduk)
        @php $modalProd = $this->imageModalProduk; @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 1080;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
                <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
                    <div class="modal-header bg-dark text-white py-2 px-3 border-0 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2 text-truncate">
                            <i class="bi bi-image text-warning"></i>
                            <span class="fw-semibold small text-truncate">{{ $modalProd->nama }}</span>
                        </div>
                        <button type="button" wire:click="closeImageModal" class="btn-close btn-close-white btn-sm" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 bg-dark text-center position-relative d-flex align-items-center justify-content-center" style="min-height: 320px; max-height: 480px;">
                        @if ($modalProd->foto_url)
                            <img src="{{ $modalProd->foto_url }}" alt="{{ $modalProd->nama }}" class="img-fluid" style="max-height: 460px; width: auto; object-fit: contain;">
                        @else
                            <div class="py-5 text-white-50">
                                <i class="bi bi-image display-4 d-block mb-2"></i>
                                Tidak ada foto untuk produk ini.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light py-2 px-3 border-0 d-flex justify-content-between align-items-center">
                        <div class="small text-muted font-monospace text-truncate">
                            {{ $modalProd->kode_katalog ?? '-' }} &bull; {{ $modalProd->kategori->nama_kategori ?? '-' }} &bull; {{ $modalProd->merek->nama_merek ?? '-' }}
                        </div>
                        <div class="d-flex gap-2">
                            @if ($modalProd->foto_url)
                                <a href="{{ $modalProd->foto_url }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Buka gambar ukuran penuh di tab baru">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Ukuran Asli
                                </a>
                            @endif
                            <button type="button" wire:click="closeImageModal" class="btn btn-secondary btn-sm px-3">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        $wire.on('focus-qty-row', (event) => {
            setTimeout(() => {
                const input = document.getElementById('qty-item-' + event.index);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 100);
        });
    </script>
    @endscript
</div>

