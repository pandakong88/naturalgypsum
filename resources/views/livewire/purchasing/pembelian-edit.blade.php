<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">Edit Pesanan Pembelian (PO) #{{ $pembelian->no_pembelian }}</h4>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6">
                    <i class="bi bi-file-earmark-text me-1"></i>Status: Dipesan (PO)
                </span>
            </div>
            <p class="text-muted small mb-0">Perbarui kuantitas, harga kesepakatan, atau daftar item PO sebelum barang dikirim dan diterima di gudang.</p>
        </div>
        <div>
            <a href="{{ route('pembelian.show', $pembelian->id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail PO
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session()->has('message_supplier'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('message_supplier') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('info_gudang'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-1"></i> {{ session('info_gudang') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('info_item'))
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <i class="bi bi-cart-plus me-1"></i> {{ session('info_item') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Error Summary if any -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
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
        <!-- Purchase Header Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title fw-bold mb-0 text-primary">
                        <i class="bi bi-info-circle me-1"></i> Informasi Pesanan & Supplier
                    </h6>
                    <span class="badge bg-light text-dark border">
                        No. Ref: {{ $pembelian->no_pembelian }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-warning-subtle text-warning-emphasis border border-warning-subtle py-2 px-3 mb-3 small d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                    <div>
                        <strong>Pengeditan PO:</strong> Mengubah item atau nominal PO ini tidak akan mempengaruhi stok fisik gudang saat ini. Dokumen logistik fisik (Surat Jalan pabrik, Plat Truk, Faktur Supplier) akan diinput saat armada tiba melalui tombol <em>"Terima Barang"</em>.
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Supplier -->
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Supplier <span class="text-danger">*</span></label>
                            <button type="button" wire:click="openQuickSupplierModal" class="btn btn-link btn-sm p-0 text-decoration-none text-success fw-semibold">
                                <i class="bi bi-plus-circle me-1"></i>+ Supplier Baru
                            </button>
                        </div>
                        <select wire:model.live="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->nama_supplier }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tanggal PO -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">Tanggal Pemesanan (PO) <span class="text-danger">*</span></label>
                        <input type="date" wire:model="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tanggal Jatuh Tempo -->
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">Perkiraan Jatuh Tempo</label>
                        <input type="date" wire:model="tanggal_jatuh_tempo" class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror">
                        <div class="form-text small d-none d-sm-block">Opsional jika ada kesepakatan TOP (Term of Payment) dengan supplier.</div>
                        @error('tanggal_jatuh_tempo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Default Gudang Tujuan Penerimaan -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Rencana Gudang Bongkar / Penerimaan</label>
                        <div class="input-group">
                            <select wire:model="default_gudang_id" class="form-select">
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }} ({{ $gudang->kode_gudang }})</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="applyDefaultGudangToAll" class="btn btn-outline-secondary" title="Terapkan gudang ini ke seluruh baris barang di bawah">
                                <i class="bi bi-arrow-repeat me-1"></i>Set Semua
                            </button>
                        </div>
                        <div class="form-text small">Gudang tujuan di mana barang akan dibongkar saat truk ekspedisi tiba.</div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Catatan / Keterangan Pemesanan</label>
                        <input type="text" wire:model="keterangan" class="form-control" placeholder="Contoh: PO Vifon motif kayu pesanan PO-01, estimasi muat pabrik hari Kamis">
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Line Items Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title fw-bold mb-0 text-primary">
                        <i class="bi bi-box-seam me-1"></i> Daftar Barang Pesanan (PO)
                    </h6>
                    <div class="d-flex gap-2 w-100 w-sm-auto">
                        <button type="button" wire:click="openCatalogModal" class="btn btn-primary btn-sm shadow-sm flex-fill flex-sm-grow-0">
                            <i class="bi bi-grid-3x3-gap me-1"></i>Pilih Banyak Produk dari Katalog
                        </button>
                        <button type="button" wire:click="addItem" class="btn btn-outline-primary btn-sm flex-fill flex-sm-grow-0">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Baris Kosong
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Quick Search Bar -->
                <div class="p-3 bg-light border-bottom">
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text"
                                   wire:model.live.debounce.300ms="quickSearchQuery"
                                   class="form-control border-start-0"
                                   placeholder="Ketik nama atau kode motif produk untuk menambah cepat ke tabel pesanan...">
                        </div>

                        <!-- Dropdown quick results -->
                        @if ($quickResults->isNotEmpty())
                            <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3" style="max-height: 280px; overflow-y: auto;">
                                @foreach ($quickResults as $qr)
                                    <button type="button"
                                            wire:click="addQuickProduct({{ $qr->id }})"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            @if ($qr->foto_url)
                                                <img src="{{ $qr->foto_url }}" alt="{{ $qr->nama }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;" loading="lazy">
                                            @else
                                                <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                    <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <span class="fw-bold text-dark text-truncate d-block">{{ $qr->nama }}</span>
                                                <div class="text-muted small" style="font-size: 0.75rem;">
                                                    @if ($qr->kode_katalog)
                                                        <span class="badge bg-secondary-subtle text-secondary me-1">{{ $qr->kode_katalog }}</span>
                                                    @endif
                                                    @if ($qr->merek)
                                                        <span class="badge bg-info-subtle text-info me-1">{{ $qr->merek->nama_merek }}</span>
                                                    @endif
                                                    {{ $qr->kategori?->nama_kategori ?? '-' }} | Satuan: {{ $qr->satuan?->label() ?? 'Pcs' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end flex-shrink-0 ms-2">
                                            <span class="fw-bold text-success">Rp {{ number_format((float) $qr->harga_beli, 0, ',', '.') }}</span>
                                            <div class="small text-muted"><i class="bi bi-plus-circle me-1"></i>Klik Tambah</div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-mobile-cards">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center text-muted">#</th>
                                <th style="min-width: 320px;">Produk / Motif Barang</th>
                                <th style="min-width: 300px;">Kuantitas (Lembar & Meter)</th>
                                <th style="min-width: 170px;" class="text-end">Harga Beli Satuan</th>
                                <th style="min-width: 160px;" class="text-end">Subtotal</th>
                                <th style="width: 50px;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                                <tr wire:key="item-row-{{ $index }}">
                                    <!-- Col 1: # -->
                                    <td class="text-center text-muted fw-semibold d-none d-md-table-cell">{{ $index + 1 }}</td>

                                    <!-- Col 2: Nama Produk & Metadata -->
                                    <td data-label="Baris #{{ $index + 1 }} — Produk">
                                        @if (!empty($item['produk_id']))
                                            <div class="d-flex align-items-start gap-2">
                                                <!-- Image Thumbnail with Hover Preview & Lightbox -->
                                                <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                    @if (!empty($item['foto_url']))
                                                        <img src="{{ $item['foto_url'] }}" alt="{{ $item['nama_produk'] ?? '' }}"
                                                             class="rounded border object-fit-cover"
                                                             style="width: 38px; height: 38px; cursor: pointer;"
                                                             loading="lazy"
                                                             @mouseenter="showPreview = true"
                                                             @mouseleave="showPreview = false"
                                                             wire:click.stop="openImageModal({{ (int) $item['produk_id'] }})">
                                                        <div x-show="showPreview"
                                                             class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                             style="bottom: 100%; left: 0; z-index: 1085; min-width: 180px; max-width: 240px; pointer-events: none; margin-bottom: 6px;"
                                                             x-cloak>
                                                            <img src="{{ $item['foto_url'] }}" alt="{{ $item['nama_produk'] ?? '' }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                                            <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $item['nama_produk'] ?? '' }}</div>
                                                        </div>
                                                    @else
                                                        <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;" title="Belum ada foto">
                                                            <i class="bi bi-image" style="font-size: 1rem;"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0 flex-grow-1">
                                                    <div class="fw-bold text-dark fs-6">{{ $item['nama_produk'] ?? ('Produk #'.($item['produk_id'] ?? '')) }}</div>
                                                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap" style="font-size: 0.78rem;">
                                                        @if (!empty($item['kode_katalog']))
                                                            <span class="badge bg-light text-secondary border font-monospace">{{ $item['kode_katalog'] }}</span>
                                                        @endif
                                                        <span class="badge bg-light text-dark border">{{ $item['satuan'] ?? 'Unit' }}</span>
                                                        <span class="text-muted"><i class="bi bi-box me-1"></i>Stok: <strong>{{ number_format((float) ($item['current_stock'] ?? 0), 1) }}</strong></span>

                                                        <!-- Gudang Tujuan inline dropdown -->
                                                        <span class="text-muted ms-1"><i class="bi bi-geo-alt me-1 text-primary"></i>Gudang:</span>
                                                        <select wire:model.live="items.{{ $index }}.gudang_id"
                                                                class="form-select form-select-sm d-inline-block border-0 bg-light py-0 ps-1 pe-4 text-secondary fw-semibold"
                                                                style="width: auto; font-size: 0.78rem; height: auto;">
                                                            @foreach ($gudangs as $g)
                                                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Empty row fallback -->
                                            <div class="d-flex gap-2 align-items-center">
                                                <select wire:model.live="items.{{ $index }}.produk_id" class="form-select form-select-sm">
                                                    <option value="">-- Pilih Produk --</option>
                                                    @foreach ($produks as $p)
                                                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" wire:click="openCatalogModal" class="btn btn-sm btn-outline-primary text-nowrap">
                                                    <i class="bi bi-grid-3x3-gap me-1"></i>Katalog
                                                </button>
                                            </div>
                                        @endif
                                        @error('items.'.$index.'.produk_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <td data-label="Kuantitas">
                                        @php
                                            $satVal = $item['satuan_value'] ?? '';
                                            $kemVal = strtolower($item['kemasan'] ?? '');
                                            $isRollRow = ($satVal === 'roll') || ($kemVal === 'roll') || (!empty($item['panjang_per_roll']) && (float)$item['panjang_per_roll'] > 0);
                                            $isPvcRow = !$isRollRow && $satVal === 'meter' && !empty($item['panjang_meter']) && (float)$item['panjang_meter'] > 0;
                                        @endphp
                                        @if ($isPvcRow)
                                            <!-- Format Khusus Plafon PVC: [Lembar] x [Panjang m] = [Total Meter] -->
                                            <div>
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    <div class="input-group input-group-sm" style="width: 115px;">
                                                        <input type="number" step="1" min="1"
                                                               wire:model.live.debounce.300ms="items.{{ $index }}.qty_lembar"
                                                               class="form-control text-end fw-bold px-2"
                                                               placeholder="Lbr"
                                                               title="Ketik jumlah lembar fisik nota"
                                                               style="min-width: 65px;">
                                                        <span class="input-group-text bg-light text-muted px-1" style="font-size: 0.75rem;">lbr</span>
                                                    </div>

                                                    <span class="text-muted fw-bold px-1">&times;</span>

                                                    <div class="input-group input-group-sm" style="width: 78px;">
                                                        <input type="number" step="0.1" min="0.5"
                                                               wire:model.live.debounce.300ms="items.{{ $index }}.panjang_meter"
                                                               class="form-control text-end px-1"
                                                               placeholder="Pjg"
                                                               title="Panjang per lembar (meter)">
                                                        <span class="input-group-text bg-light text-muted px-1" style="font-size: 0.75rem;">m</span>
                                                    </div>

                                                    <span class="text-muted fw-bold px-1">=</span>

                                                    <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-2 fs-6 fw-bold text-nowrap" style="min-width: 65px; text-align: right;">
                                                        {{ number_format((float) ($item['jumlah'] ?? 0), 1) }} <small style="font-size: 0.7rem;">m</small>
                                                    </div>

                                                    <button type="button" wire:click="openCalculator({{ $index }})"
                                                            class="btn btn-outline-secondary btn-sm"
                                                            title="Kalkulator Konversi Dus & Lembar">
                                                        <i class="bi bi-calculator"></i>
                                                    </button>
                                                </div>
                                                @php
                                                    $badge = $this->getItemPhysicalBadge($item);
                                                @endphp
                                                @if ($badge)
                                                    <div class="mt-1">
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace" style="font-size: 0.72rem;">
                                                            {{ $badge }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                                        <i class="bi bi-info-circle me-1"></i>Input lembar &times; panjang = total meter
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <!-- Format Standar Non-PVC (Batang, Lembar, Dus, Pcs) -->
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="input-group input-group-sm" style="max-width: 200px;">
                                                        <input type="number" step="any" min="0.001"
                                                               wire:model.live.debounce.300ms="items.{{ $index }}.jumlah"
                                                               class="form-control text-end fw-bold px-2 @error('items.'.$index.'.jumlah') is-invalid @enderror"
                                                               placeholder="0"
                                                               style="min-width: 95px;">
                                                        <span class="input-group-text bg-light text-muted px-2" style="font-size: 0.75rem;">{{ $item['satuan'] ?? 'Unit' }}</span>
                                                    </div>
                                                    <button type="button" wire:click="openCalculator({{ $index }})"
                                                            class="btn btn-outline-secondary btn-sm"
                                                            title="Kalkulator Konversi Kemasan ({{ $item['kemasan'] ?? 'Dus' }})">
                                                        <i class="bi bi-calculator"></i>
                                                    </button>
                                                </div>
                                                @php
                                                    $badge = $this->getItemPhysicalBadge($item);
                                                @endphp
                                                @if ($badge)
                                                    <div class="mt-1">
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace" style="font-size: 0.72rem;">
                                                            {{ $badge }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        @error('items.'.$index.'.jumlah')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Col 4: Harga Beli Satuan -->
                                    <td data-label="Harga Beli Satuan">
                                        <div class="input-group input-group-sm justify-content-end ms-md-auto" style="max-width: 180px;">
                                            <span class="input-group-text bg-light text-muted">Rp</span>
                                            <input type="number" step="any" min="0"
                                                   wire:model.live.debounce.300ms="items.{{ $index }}.harga_beli_satuan"
                                                   class="form-control text-end fw-semibold @error('items.'.$index.'.harga_beli_satuan') is-invalid @enderror">
                                        </div>
                                        <div class="text-muted text-md-end mt-1" style="font-size: 0.72rem;">
                                            per {{ $item['satuan'] ?? 'unit' }}
                                        </div>
                                        @error('items.'.$index.'.harga_beli_satuan')
                                            <div class="text-danger small mt-1 text-md-end">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Col 5: Subtotal -->
                                    <td class="text-md-end" data-label="Subtotal">
                                        <span class="fw-bold fs-6 text-dark font-monospace">
                                            Rp {{ number_format((float) ($item['subtotal'] ?? 0), 0, ',', '.') }}
                                        </span>
                                    </td>

                                    <!-- Col 6: Aksi Hapus -->
                                    <td class="text-end text-md-center">
                                        @if (count($items) > 1)
                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                    class="btn btn-sm btn-outline-danger px-2 py-1 d-inline-flex align-items-center justify-content-center"
                                                    title="Hapus baris barang">
                                                <i class="bi bi-trash" style="font-size: 0.85rem;"></i>
                                                <span class="d-md-none ms-1 small">Hapus Baris</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted small">Total Baris Barang: <strong>{{ $totalItems }}</strong> item</span>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small me-2">Grand Total Nilai PO:</span>
                        <span class="fs-4 fw-bold text-primary">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button Section -->
        <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-2">
            <a href="{{ route('pembelian.show', $pembelian->id) }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">
                <i class="bi bi-x-circle me-1"></i>Batal & Kembali
            </a>
            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow flex-fill flex-sm-grow-0">
                <span wire:loading.remove wire:target="save">
                    <i class="bi bi-check-circle me-1"></i>Simpan Perubahan Pesanan (PO)
                </span>
                <span wire:loading wire:target="save">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan Perubahan...
                </span>
            </button>
        </div>
    </form>

    <!-- Multi-Select Catalog Modal -->
    @if ($isCatalogModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2 py-sm-3 px-3">
                        <div class="min-w-0">
                            <h6 class="modal-title fw-bold mb-0 text-truncate">
                                <i class="bi bi-grid-3x3-gap me-2"></i>Pilih Produk dari Katalog Pabrik
                            </h6>
                            <small class="opacity-75 d-none d-sm-inline">Cari berdasarkan nama motif, kategori, atau merek pabrikan (Vifon, Gemafon, Jayaboard, dll).</small>
                        </div>
                        <button type="button" wire:click="closeCatalogModal" class="btn-close btn-close-white"></button>
                    </div>

                    <!-- Sticky Search & Collapsible Filters -->
                    <div class="p-2 p-sm-3 bg-light border-bottom flex-shrink-0" x-data="{ showFilters: false }">
                        <!-- Search Bar + Mobile Filter Toggle -->
                        <div class="d-flex gap-2 mb-2">
                            <div class="input-group input-group-sm flex-grow-1">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text"
                                       wire:model.live.debounce.300ms="catalogSearchQuery"
                                       class="form-control border-start-0"
                                       placeholder="Cari nama motif PVC / ketebalan / kode SKU...">
                                @if (filled($catalogSearchQuery))
                                    <button type="button" wire:click="$set('catalogSearchQuery', '')" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @endif
                            </div>
                            @php
                                $hasActiveCatalogFilter = filled($catalogKategoriId) || filled($catalogMerekId) || filled($catalogStatusStok);
                            @endphp
                            <button
                                type="button"
                                @click="showFilters = !showFilters"
                                class="btn btn-sm d-md-none flex-shrink-0 d-inline-flex align-items-center gap-1 {{ $hasActiveCatalogFilter ? 'btn-primary' : 'btn-outline-secondary bg-white' }}"
                            >
                                <i class="bi bi-sliders"></i>
                                <span>Filter</span>
                                @if ($hasActiveCatalogFilter)
                                    <span class="badge bg-white text-primary rounded-pill" style="font-size: 0.65rem;">!</span>
                                @endif
                            </button>
                        </div>

                        <!-- Collapsible Filters on Mobile, Always Visible on Desktop -->
                        <div class="row g-2 mb-2 align-items-center d-md-flex" :class="showFilters ? 'd-flex' : 'd-none'">
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
                                    <option value="">-- Semua Merek Pabrik --</option>
                                    @foreach ($mereks as $mrk)
                                        <option value="{{ $mrk->id }}">{{ $mrk->nama_merek }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Status Stok -->
                            <div class="col-12 col-md-4">
                                <select wire:model.live="catalogStatusStok" class="form-select form-select-sm">
                                    <option value="">Semua Status Stok</option>
                                    <option value="kritis">⚠️ Di Bawah Min</option>
                                    <option value="menipis">🟡 Menipis (&gt; 0 s/d Min)</option>
                                    <option value="habis">❌ Stok Habis (0)</option>
                                    <option value="aman">✅ Stok Aman</option>
                                </select>
                            </div>
                        </div>

                        <!-- Brand Pills Quick Buttons -->
                        @if ($mereks->isNotEmpty())
                            <div class="d-flex align-items-center gap-1 nav-pills-scrollable flex-nowrap mb-2 pb-1">
                                @if ($supplier_id)
                                    <button type="button"
                                            wire:click="filterByCurrentSupplierBrands"
                                            class="btn btn-xs py-1 px-2.5 rounded-pill btn-warning text-dark fw-semibold text-nowrap flex-shrink-0"
                                            style="font-size: 0.74rem;"
                                            title="Filter langsung merek yang cocok dengan nama supplier ini">
                                        <i class="bi bi-star-fill me-1"></i>Produk Supplier Ini
                                    </button>
                                @endif
                                <button type="button"
                                        wire:click="setCatalogMerek(null)"
                                        class="btn btn-xs py-1 px-2.5 rounded-pill text-nowrap flex-shrink-0 {{ is_null($catalogMerekId) ? 'btn-primary fw-semibold' : 'btn-outline-secondary bg-white' }}"
                                        style="font-size: 0.74rem;">
                                    Semua Merek
                                </button>
                                @foreach ($mereks as $m)
                                    <button type="button"
                                            wire:click="setCatalogMerek({{ $m->id }})"
                                            class="btn btn-xs py-1 px-2.5 rounded-pill text-nowrap flex-shrink-0 {{ $catalogMerekId === $m->id ? 'btn-primary fw-semibold' : 'btn-outline-secondary bg-white' }}"
                                            style="font-size: 0.74rem;">
                                        {{ $m->nama_merek }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <!-- Tab View (Semua vs Terpilih) & Bulk Action Toolbar -->
                        <div class="d-flex justify-content-between align-items-center gap-2 pt-1 border-top">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button"
                                        wire:click="setCatalogViewTab('all')"
                                        class="btn py-1 px-2 px-sm-3 {{ $catalogViewTab === 'all' ? 'btn-primary fw-semibold' : 'btn-outline-primary bg-white' }}"
                                        style="font-size: 0.78rem;">
                                    Semua ({{ $catalogTotalCount }})
                                </button>
                                <button type="button"
                                        wire:click="setCatalogViewTab('selected')"
                                        class="btn py-1 px-2 px-sm-3 {{ $catalogViewTab === 'selected' ? 'btn-success fw-semibold' : 'btn-outline-success bg-white' }}"
                                        style="font-size: 0.78rem;">
                                    <i class="bi bi-check2-circle me-1"></i>Terpilih ({{ count($catalogSelectedProductIds) }})
                                </button>
                            </div>

                            <div class="btn-group btn-group-sm">
                                @if ($catalogViewTab === 'all' && $catalogProducts->isNotEmpty())
                                    <button type="button" wire:click="selectAllFoundCatalogProducts" class="btn btn-outline-secondary bg-white py-1 px-2" style="font-size: 0.76rem;">
                                        <i class="bi bi-check-all me-1"></i>Pilih Semua
                                    </button>
                                @endif
                                @if (!empty($catalogSelectedProductIds))
                                    <button type="button" wire:click="clearSelectedCatalogProducts" class="btn btn-outline-danger bg-white py-1 px-2" style="font-size: 0.76rem;">
                                        <i class="bi bi-x-circle me-1"></i>Reset
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modal Body / Products Grid/Table -->
                    <div class="modal-body p-0">
                        @if ($catalogProducts->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                                @if ($catalogViewTab === 'selected')
                                    <p class="mb-0 fw-semibold">Belum ada produk yang dicentang.</p>
                                    <small>Silakan klik tab "Semua" lalu centang produk yang ingin ditambahkan.</small>
                                @else
                                    <p class="mb-0 fw-semibold">Tidak ada produk yang cocok dengan pencarian.</p>
                                    <small>Coba gunakan kata kunci lain atau ubah filter merek / kategori.</small>
                                @endif
                            </div>
                        @else
                            <!-- Mobile Product Card List (< 768px) — Full Width Product Name without Truncation -->
                            <div class="d-md-none list-group list-group-flush">
                                @foreach ($catalogProducts as $cp)
                                    @php
                                        $isSelected = in_array($cp->id, $catalogSelectedProductIds, true);
                                        $currentStok = (float) ($cp->stok_sum_jumlah ?? 0);
                                        $minStok = (float) $cp->stok_minimum;
                                        $statusStok = \App\Domains\Inventory\Enums\StatusStok::determine($currentStok, $minStok);
                                    @endphp
                                    <div wire:key="cat-prod-mobile-{{ $cp->id }}"
                                         wire:click="toggleCatalogProduct({{ $cp->id }})"
                                         style="cursor: pointer;"
                                         class="list-group-item list-group-item-action py-2.5 px-3 {{ $isSelected ? 'bg-primary-subtle border-start border-4 border-primary' : '' }}">
                                        <div class="d-flex align-items-start gap-2.5">
                                            <div onclick="event.stopPropagation();" class="pt-1 flex-shrink-0">
                                                <input type="checkbox"
                                                       class="form-check-input m-0"
                                                       style="width: 1.25rem; height: 1.25rem; cursor: pointer;"
                                                       value="{{ $cp->id }}"
                                                       wire:click="toggleCatalogProduct({{ $cp->id }})"
                                                       {{ $isSelected ? 'checked' : '' }}>
                                            </div>
                                            <div onclick="event.stopPropagation();" class="flex-shrink-0">
                                                @if ($cp->foto_url)
                                                    <img src="{{ $cp->foto_url }}" alt="{{ $cp->nama }}"
                                                         class="rounded-3 border object-fit-cover"
                                                         style="width: 44px; height: 44px;"
                                                         loading="lazy"
                                                         wire:click="openImageModal({{ $cp->id }})">
                                                @else
                                                    <div class="bg-light rounded-3 border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                                        <i class="bi bi-image" style="font-size: 1.1rem;"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="fw-bold {{ $isSelected ? 'text-primary' : 'text-dark' }}" style="font-size: 0.9rem; line-height: 1.3;">
                                                    {{ $cp->nama }}
                                                </div>
                                                <div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size: 0.74rem;">
                                                    @if ($cp->kode_katalog)
                                                        <span class="badge bg-light text-secondary border font-monospace">{{ $cp->kode_katalog }}</span>
                                                    @endif
                                                    <span class="badge bg-light text-dark border">{{ $cp->kategori?->nama_kategori ?? '-' }}</span>
                                                    <span class="badge bg-info-subtle text-info">{{ $cp->merek?->nama_merek ?? '-' }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mt-2 pt-1 border-top border-light-subtle">
                                                    <div>
                                                        <span class="fw-bold text-success font-monospace" style="font-size: 0.85rem;">
                                                            Rp {{ number_format((float) $cp->harga_beli, 0, ',', '.') }}
                                                        </span>
                                                        <span class="text-muted" style="font-size: 0.75rem;">/ {{ $cp->satuan?->label() ?? 'Pcs' }}</span>
                                                    </div>
                                                    <span class="badge {{ $statusStok->badgeClass() }}" style="font-size: 0.72rem;">
                                                        Stok: {{ number_format($currentStok, 0, ',', '.') }} (Min: {{ number_format($minStok, 0, ',', '.') }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Desktop Table (>= 768px) -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 45px;" class="text-center">Pilih</th>
                                            <th>Nama Produk / Motif</th>
                                            <th>Kategori</th>
                                            <th>Merek</th>
                                            <th class="text-center">Stok / Min</th>
                                            <th>Satuan</th>
                                            <th class="text-end">Harga Beli Master</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($catalogProducts as $cp)
                                            @php
                                                $isSelected = in_array($cp->id, $catalogSelectedProductIds, true);
                                            @endphp
                                            <tr wire:key="cat-prod-{{ $cp->id }}"
                                                wire:click="toggleCatalogProduct({{ $cp->id }})"
                                                style="cursor: pointer;"
                                                class="{{ $isSelected ? 'table-primary bg-opacity-10' : '' }}">
                                                <td class="text-center" onclick="event.stopPropagation();">
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           value="{{ $cp->id }}"
                                                           wire:click="toggleCatalogProduct({{ $cp->id }})"
                                                           {{ $isSelected ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                            @if ($cp->foto_url)
                                                                <img src="{{ $cp->foto_url }}" alt="{{ $cp->nama }}"
                                                                     class="rounded border object-fit-cover"
                                                                     style="width: 38px; height: 38px; cursor: pointer;"
                                                                     loading="lazy"
                                                                     @mouseenter="showPreview = true"
                                                                     @mouseleave="showPreview = false"
                                                                     wire:click.stop="openImageModal({{ $cp->id }})">
                                                                <div x-show="showPreview"
                                                                     class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                                     style="bottom: 100%; left: 0; z-index: 1085; min-width: 180px; max-width: 240px; pointer-events: none; margin-bottom: 6px;"
                                                                     x-cloak>
                                                                    <img src="{{ $cp->foto_url }}" alt="{{ $cp->nama }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                                                    <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $cp->nama }}</div>
                                                                </div>
                                                            @else
                                                                <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;" title="Belum ada foto">
                                                                    <i class="bi bi-image" style="font-size: 1rem;"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="fw-bold {{ $isSelected ? 'text-primary' : 'text-dark' }}">{{ $cp->nama }}</div>
                                                            @if ($cp->kode_katalog)
                                                                <span class="badge bg-secondary-subtle text-secondary small">{{ $cp->kode_katalog }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">{{ $cp->kategori?->nama_kategori ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info">{{ $cp->merek?->nama_merek ?? '-' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $currentStok = (float) ($cp->stok_sum_jumlah ?? 0);
                                                        $minStok = (float) $cp->stok_minimum;
                                                        $statusStok = \App\Domains\Inventory\Enums\StatusStok::determine($currentStok, $minStok);
                                                    @endphp
                                                    <span class="badge {{ $statusStok->badgeClass() }}" title="Stok Saat Ini: {{ number_format($currentStok, 0, ',', '.') }} | Stok Min: {{ number_format($minStok, 0, ',', '.') }}">
                                                        {{ number_format($currentStok, 0, ',', '.') }} / {{ number_format($minStok, 0, ',', '.') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-muted small">{{ $cp->satuan?->label() ?? 'Pcs' }}</span>
                                                </td>
                                                <td class="text-end fw-semibold text-success">
                                                    Rp {{ number_format((float) $cp->harga_beli, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Lazy Loading / Pagination Footer -->
                            @if ($catalogViewTab === 'all' && $catalogTotalCount > $catalogLimit)
                                <div class="p-3 text-center bg-light border-top">
                                    <button type="button" wire:click="loadMoreCatalog" class="btn btn-outline-primary btn-sm px-4">
                                        <i class="bi bi-chevron-down me-1"></i>Muat 50 Produk Berikutnya (Menampilkan {{ min($catalogLimit, $catalogTotalCount) }} dari {{ $catalogTotalCount }})
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between flex-wrap gap-2">
                        <div class="d-none d-sm-block">
                            <span class="text-muted small">
                                <strong>{{ count($catalogSelectedProductIds) }}</strong> produk terpilih
                            </span>
                        </div>
                        <div class="d-flex gap-2 w-100 w-sm-auto ms-sm-auto">
                            <button type="button" wire:click="closeCatalogModal" class="btn btn-secondary btn-sm flex-fill flex-sm-grow-0">Batal</button>
                            <button type="button" wire:click="addSelectedCatalogProducts" class="btn btn-primary btn-sm fw-bold flex-fill flex-sm-grow-0">
                                <i class="bi bi-check2-circle me-1"></i>Tambahkan Produk Terpilih ({{ count($catalogSelectedProductIds) }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Supplier Modal -->
    @if ($isQuickSupplierModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Supplier Cepat</h5>
                        <button type="button" wire:click="closeQuickSupplierModal" class="btn-close btn-close-white"></button>
                    </div>
                    <form wire:submit.prevent="saveQuickSupplier">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nama Supplier <span class="text-danger">*</span></label>
                                <input type="text" wire:model="new_nama_supplier" class="form-control @error('new_nama_supplier') is-invalid @enderror" placeholder="Contoh: PT. Sumber Makmur PVC">
                                @error('new_nama_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                                <input type="text" wire:model="new_kontak_supplier" class="form-control" placeholder="Contoh: 08123456789">
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeQuickSupplierModal" class="btn btn-secondary btn-sm">Batal</button>
                            <button type="submit" class="btn btn-success btn-sm fw-bold">Simpan Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Kalkulator Kemasan Cerdas Lapangan -->
    @if ($isCalculatorModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1070;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white py-3">
                        <h6 class="modal-title fw-bold">
                            <i class="bi bi-calculator me-1"></i> Kalkulator Kemasan Fisik Pembelian (PO)
                        </h6>
                        <button type="button" wire:click="closeCalculator" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <div class="fw-bold text-dark fs-6">{{ $calcItemName ?: $calcProductName }}</div>
                            @php
                                $isRollModal = ($calcMode === 'roll') || strcasecmp($calcKemasanLabel ?? '', 'roll') === 0 || str_contains(strtolower($calcSatuan ?? ''), 'roll');
                                $isPvcModal = !$isRollModal && ($calcMode === 'dus_pvc' || (str_contains(strtolower($calcSatuan ?? ''), 'meter') && !empty($calcPanjangMeter) && !empty($calcIsiPerKemasan)));
                                $firstWordSatuan = strtolower(strtok($calcSatuan ?? '', " (/"));
                                $firstWordKemasan = strtolower(strtok($calcKemasanLabel ?? '', " (/"));
                                $isUnitSameAsKemasan = !$isRollModal && ($firstWordKemasan !== '' && (
                                    $firstWordSatuan === $firstWordKemasan
                                    || (str_contains(strtolower($calcSatuan ?? ''), 'dus') && str_contains(strtolower($calcKemasanLabel ?? ''), 'dus'))
                                    || (str_contains(strtolower($calcSatuan ?? ''), 'box') && str_contains(strtolower($calcKemasanLabel ?? ''), 'box'))
                                    || (str_contains(strtolower($calcSatuan ?? ''), 'pack') && str_contains(strtolower($calcKemasanLabel ?? ''), 'pack'))
                                ));
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
                                    Satuan Sistem: <strong>{{ $calcSatuan ?: $calcTargetUnit }}</strong>
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
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Roll ke Satuan Meter Sistem:
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
                                        <span><strong>Total Masuk ke PO:</strong></span>
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
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Masuk ke Pesanan Pembelian:
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
                                        <span><strong>Total Kuantitas:</strong></span>
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
                                <div class="small text-muted mb-1">Hasil Konversi ke Satuan Sistem:</div>
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

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" wire:click="closeCalculator" class="btn btn-outline-secondary">
                                Batal
                            </button>
                            <button
                                type="button"
                                wire:click="applyCalculation"
                                class="btn btn-primary px-4 fw-semibold"
                                @disabled($this->computedPhysicalStock <= 0)
                            >
                                <i class="bi bi-check-lg me-1"></i> Terapkan ke Baris
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Lightbox Zoom Foto Produk -->
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
</div>

