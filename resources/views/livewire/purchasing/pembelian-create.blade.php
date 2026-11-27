<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Catat Pembelian Baru</h4>
            <p class="text-muted small mb-0">Input faktur penerimaan barang masuk dari supplier ke gudang.</p>
        </div>
        <div>
            <a href="{{ route('pembelian.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
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
        <!-- Purchase Header Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title fw-bold mb-0 text-primary">
                        <i class="bi bi-info-circle me-1"></i> Informasi Transaksi & Dokumen Pabrik
                    </h6>
                    <!-- Workflow Status Toggle (Diterima vs PO) -->
                    <div class="d-flex flex-column flex-sm-row gap-1 w-100 w-md-auto" role="group" aria-label="Status Transaksi">
                        <input type="radio" class="btn-check" name="statusOption" id="statusReceived" value="received" wire:model.live="status" autocomplete="off">
                        <label class="btn btn-sm btn-outline-success fw-semibold px-3 flex-fill" for="statusReceived">
                            <i class="bi bi-box-seam me-1"></i>Langsung Terima Barang (Stok Masuk)
                        </label>

                        <input type="radio" class="btn-check" name="statusOption" id="statusOrdered" value="ordered" wire:model.live="status" autocomplete="off">
                        <label class="btn btn-sm btn-outline-warning fw-semibold px-3 flex-fill" for="statusOrdered">
                            <i class="bi bi-file-earmark-text me-1"></i>Surat Pesanan / PO (Menunggu Truk)
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if ($status === 'ordered')
                    <div class="alert alert-warning-subtle text-warning-emphasis border border-warning-subtle py-2 px-3 mb-3 small d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div>
                            <strong>Mode Purchase Order (PO):</strong> Transaksi akan dicatat sebagai pesanan. Stok gudang <u>tidak akan bertambah</u> sampai barang fisik tiba dan diverifikasi lewat tombol <em>"Terima Barang"</em>.
                        </div>
                    </div>
                @endif

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

                        @if ($selectedSup = $suppliers->firstWhere('id', $supplier_id))
                            <div class="small text-muted mt-1">
                                <i class="bi bi-telephone me-1"></i>Kontak: {{ $selectedSup->kontak ?? 'Tidak ada kontak' }}
                            </div>
                        @endif
                    </div>

                    <!-- Tanggal Pembelian / PO -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" wire:model="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if ($status === 'received')
                        <!-- No Faktur Supplier (Asli Pabrik) -->
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-semibold">No. Faktur / Invoice Supplier</label>
                            <input type="text" wire:model="no_faktur_supplier" class="form-control @error('no_faktur_supplier') is-invalid @enderror" placeholder="Contoh: IT124280-130726-3">
                            @error('no_faktur_supplier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-none d-sm-inline">Nomor faktur resmi dari nota supplier.</small>
                        </div>

                        <!-- No Surat Jalan Supplier -->
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-semibold">No. Surat Jalan Pengiriman</label>
                            <input type="text" wire:model="no_surat_jalan" class="form-control @error('no_surat_jalan') is-invalid @enderror" placeholder="Contoh: SJ128402-031126-1">
                            @error('no_surat_jalan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- No Kontainer -->
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-semibold">No. Kontainer</label>
                            <input type="text" wire:model="no_kontainer" class="form-control @error('no_kontainer') is-invalid @enderror" placeholder="Contoh: CONT-884912">
                            @error('no_kontainer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- No Polisi Truk / Ekspedisi -->
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-semibold">No. Plat Truk / Ekspedisi</label>
                            <input type="text" wire:model="no_polisi" class="form-control @error('no_polisi') is-invalid @enderror" placeholder="Contoh: AD 8123 CD">
                            @error('no_polisi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @else
                        <!-- Info Box for PO Mode: No need to fill documents yet -->
                        <div class="col-md-8">
                            <div class="p-3 bg-light rounded-3 border h-100 d-flex align-items-center">
                                <div class="small text-muted">
                                    <i class="bi bi-info-circle text-primary me-1 fs-6"></i>
                                    <strong>Dokumen Logistik (No. Faktur, Surat Jalan, Plat Truk):</strong> Tidak perlu diisi saat pembuatan PO. Kolom ini akan otomatis diminta nanti saat armada tiba membawa fisik barang melalui tombol <strong>"Terima Barang"</strong>.
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Skema Pembayaran & Hutang Supplier -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold mb-1">Status Pembayaran</label>
                                    <select wire:model.live="status_pembayaran" class="form-select @error('status_pembayaran') is-invalid @enderror">
                                        @if ($status === 'ordered')
                                            <option value="tempo">Tempo / Kredit (Belum Bayar - Default PO)</option>
                                            <option value="sebagian">Uang Muka / DP ke Supplier</option>
                                            <option value="lunas">Lunas di Depan (Bayar Sebelum Kirim)</option>
                                        @else
                                            <option value="lunas">Lunas (Tunai / Transfer)</option>
                                            <option value="tempo">Tempo / Kredit (Hutang)</option>
                                            <option value="sebagian">Bayar Sebagian (DP / Termin)</option>
                                        @endif
                                    </select>
                                    @error('status_pembayaran')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if ($status_pembayaran === 'tempo' || $status_pembayaran === 'sebagian')
                                    <!-- Tanggal Jatuh Tempo -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold mb-1">
                                            Jatuh Tempo (TOP) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="date" wire:model="tanggal_jatuh_tempo" class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror">
                                            <button type="button" wire:click="setTempoDays(7)" class="btn btn-outline-secondary btn-sm" title="Jatuh tempo 7 hari">+7 hr</button>
                                            <button type="button" wire:click="setTempoDays(14)" class="btn btn-outline-secondary btn-sm" title="Jatuh tempo 14 hari">+14 hr</button>
                                            <button type="button" wire:click="setTempoDays(30)" class="btn btn-outline-secondary btn-sm" title="Jatuh tempo 30 hari">+30 hr</button>
                                        </div>
                                        @error('tanggal_jatuh_tempo')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Uang Muka / Bayar Awal -->
                                    <div class="col-md-3" x-data="{
                                        dpVal: '{{ $nominal_bayar_awal }}',
                                        formatRupiah(num) {
                                            if (!num) return '';
                                            let n = parseFloat(num);
                                            if (isNaN(n) || n <= 0) return '';
                                            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
                                        }
                                    }" x-effect="dpVal = $wire.nominal_bayar_awal">
                                        <label class="form-label fw-semibold mb-1">
                                            {{ $status_pembayaran === 'sebagian' ? 'Nominal DP / Bayar Awal' : 'Uang Muka (Opsional)' }}
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number"
                                                   step="1000"
                                                   min="0"
                                                   @input="dpVal = $event.target.value"
                                                   wire:model.live.debounce.300ms="nominal_bayar_awal"
                                                   class="form-control text-end fw-bold @error('nominal_bayar_awal') is-invalid @enderror"
                                                   placeholder="0">
                                        </div>
                                        <div x-show="formatRupiah(dpVal) !== ''" class="mt-1" x-cloak>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 0.8rem;">
                                                <i class="bi bi-cash-stack me-1"></i><span x-text="formatRupiah(dpVal)"></span>
                                            </span>
                                        </div>
                                        @error('nominal_bayar_awal')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Metode Pembayaran Awal -->
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold mb-1">Metode Bayar</label>
                                        <select wire:model="metode_pembayaran_awal" class="form-select">
                                            <option value="transfer">Transfer Bank</option>
                                            <option value="tunai">Tunai (Cash / COD)</option>
                                            <option value="giro">Giro / Cek</option>
                                        </select>
                                    </div>
                                @else
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold mb-1">Metode Pembayaran</label>
                                        <select wire:model="metode_pembayaran_awal" class="form-select">
                                            <option value="transfer">Transfer Bank (Lunas)</option>
                                            <option value="tunai">Tunai / COD (Lunas)</option>
                                            <option value="giro">Giro / Cek</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <small class="text-muted d-block mt-3">
                                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                                            Faktur ini akan langsung dicatat <strong>Lunas</strong> dan tidak menimbulkan kewajiban hutang supplier.
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Default Warehouse Option -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Gudang Penerima Default</label>
                        <div class="input-group">
                            <select wire:model.live="default_gudang_id" class="form-select">
                                @foreach ($gudangs as $g)
                                    <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="applyDefaultGudangToAll" class="btn btn-outline-primary text-nowrap" title="Terapkan gudang ini ke semua baris di bawah">
                                <i class="bi bi-arrow-down-square me-1"></i><span class="d-none d-sm-inline">Terapkan ke Semua Baris</span><span class="d-sm-none">Set Semua</span>
                            </button>
                        </div>
                        <small class="text-muted">Barang baru yang ditambahkan otomatis masuk ke gudang ini.</small>
                    </div>

                    <!-- Update Master HPP Option -->
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="updateMasterHargaBeli" wire:model="update_master_harga_beli">
                            <label class="form-check-label fw-semibold text-dark" for="updateMasterHargaBeli">
                                Perbarui Harga Beli Master Produk (HPP)
                            </label>
                            <small class="text-muted d-block">
                                Jika aktif, harga beli di master data katalog produk akan otomatis dimutakhirkan sesuai harga faktur ini.
                            </small>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Catatan / Keterangan</label>
                        <textarea wire:model="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror" placeholder="Catatan opsional untuk faktur pembelian ini..."></textarea>
                        @error('keterangan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Card (Quick Search Bar + Multi-Add Catalog) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="card-title fw-bold mb-0 text-primary">
                            <i class="bi bi-box-seam me-1"></i> Rincian Barang Masuk
                        </h6>
                        <small class="text-muted">Ketik nama barang di bawah atau gunakan tombol Pilih Banyak dari Katalog.</small>
                    </div>
                    <div class="d-flex gap-2 w-100 w-sm-auto">
                        @if (count($items) > 0)
                            <button type="button" wire:click="clearAllItems" wire:confirm="Yakin ingin mengosongkan semua baris barang?" class="btn btn-sm btn-outline-danger flex-fill flex-sm-grow-0">
                                <i class="bi bi-trash3 me-1"></i>Kosongkan Semua
                            </button>
                        @endif
                        <button type="button" wire:click="openCatalogModal" class="btn btn-sm btn-primary flex-fill flex-sm-grow-0">
                            <i class="bi bi-grid-3x3-gap me-1"></i>+ Pilih Banyak dari Katalog
                        </button>
                    </div>
                </div>

                <!-- Quick Product Search Bar -->
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="quickSearchQuery"
                            class="form-control form-control-lg fs-6"
                            placeholder="Ketik nama produk atau kode katalog (contoh: 'hollow', 'gypsum', 'aplus')... tekan untuk tambah!"
                        >
                        @if (filled($quickSearchQuery))
                            <button type="button" wire:click="$set('quickSearchQuery', '')" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>

                    <!-- Quick Search Results Dropdown -->
                    @if (filled($quickSearchQuery) && count($quickSearchResults) > 0)
                        <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border border-primary" style="max-height: 280px; overflow-y: auto;">
                            @foreach ($quickSearchResults as $res)
                                <button
                                    type="button"
                                    wire:click="addQuickProduct({{ $res->id }})"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small"
                                >
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        @if ($res->foto_url)
                                            <img src="{{ $res->foto_url }}" alt="{{ $res->nama }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;" loading="lazy">
                                        @else
                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <strong class="text-dark fs-6 text-truncate d-block">{{ $res->nama }}</strong>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <span class="badge bg-light text-secondary border font-monospace me-1">{{ $res->kode_katalog ?? 'Tanpa SKU' }}</span>
                                                {{ $res->kategori->nama_kategori ?? '-' }} &bull; {{ $res->merek->nama_merek ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <span class="badge bg-light text-dark border me-2">{{ $res->satuan->label() }}</span>
                                        <span class="fw-bold text-primary">Rp {{ number_format((float) $res->harga_beli, 0, ',', '.') }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif (filled($quickSearchQuery))
                        <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3">
                            <div class="list-group-item py-3 px-3 text-center text-muted small">
                                Produk dengan kata kunci '<strong>{{ $quickSearchQuery }}</strong>' tidak ditemukan.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Table of Line Items -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-mobile-cards">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center text-muted">#</th>
                                <th style="min-width: 320px;">Produk / Motif Barang</th>
                                <th style="min-width: 300px;">Kuantitas (Lembar & Meter)</th>
                                <th style="min-width: 170px;" class="text-end">Harga Beli Satuan</th>
                                <th style="min-width: 160px;" class="text-end">Subtotal</th>
                                <th style="width: 80px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (empty($items))
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="mb-2">
                                            <i class="bi bi-box-seam display-6 text-secondary opacity-50"></i>
                                        </div>
                                        <h6 class="fw-semibold text-dark">Belum ada barang masuk dalam daftar</h6>
                                        <p class="small text-muted mb-3">
                                            Ketik nama produk di kolom pencarian di atas atau klik tombol katalog untuk memilih produk sekaligus.
                                        </p>
                                        <button type="button" wire:click="openCatalogModal" class="btn btn-sm btn-primary">
                                            <i class="bi bi-grid-3x3-gap me-1"></i>Buka Katalog Produk
                                        </button>
                                    </td>
                                </tr>
                            @else
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

                                                            @if ($this->hasDuplicateRow($index))
                                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Produk dan gudang tujuan ini sama dengan baris lain">
                                                                    <i class="bi bi-exclamation-triangle me-1"></i>Gudang & Produk Dobel
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="fw-semibold text-muted">Pilih produk...</div>
                                            @endif
                                            @error("items.{$index}.produk_id")
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                            @error("items.{$index}.gudang_id")
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <!-- Col 3: Kuantitas Pemesanan -->
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
                                                                   title="Ketik jumlah lembar fisik nota pabrik"
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
                                            @error("items.{$index}.jumlah")
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <!-- Col 4: Harga Beli Satuan -->
                                        <td data-label="Harga Beli Satuan">
                                            <div class="input-group input-group-sm justify-content-end ms-md-auto" style="max-width: 180px;">
                                                <span class="input-group-text bg-light text-muted">Rp</span>
                                                <input type="number" step="any" min="0"
                                                       wire:model.live.debounce.300ms="items.{{ $index }}.harga_beli_satuan"
                                                       class="form-control text-end fw-semibold @error("items.{$index}.harga_beli_satuan") is-invalid @enderror">
                                            </div>
                                            <div class="text-muted text-md-end mt-1" style="font-size: 0.72rem;">
                                                per {{ $item['satuan'] ?? 'unit' }}
                                            </div>
                                            @php
                                                $currentPrice = (float) ($item['harga_beli_satuan'] ?? 0);
                                                $masterPrice = (float) ($item['master_harga_beli'] ?? 0);
                                                $diff = $currentPrice - $masterPrice;
                                                $pct = $masterPrice > 0 ? round(($diff / $masterPrice) * 100, 1) : 0;
                                            @endphp
                                            @if ($masterPrice > 0 && $currentPrice > 0 && abs($diff) > 0.01)
                                                <div class="text-md-end mt-1">
                                                    @if ($diff > 0)
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;" title="Harga beli faktur ini lebih tinggi dari master HPP">
                                                            <i class="bi bi-arrow-up-right"></i> +Rp {{ number_format($diff, 0, ',', '.') }} (+{{ $pct }}%)
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;" title="Harga beli faktur ini lebih murah dari master HPP">
                                                            <i class="bi bi-arrow-down-right"></i> -Rp {{ number_format(abs($diff), 0, ',', '.') }} ({{ $pct }}%)
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                            @error("items.{$index}.harga_beli_satuan")
                                                <div class="text-danger small mt-1 text-md-end">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <!-- Col 5: Subtotal -->
                                        <td class="text-md-end" data-label="Subtotal">
                                            <span class="fw-bold fs-6 text-dark font-monospace">
                                                Rp {{ number_format((float) ($item['subtotal'] ?? 0), 0, ',', '.') }}
                                            </span>
                                        </td>

                                        <!-- Col 6: Aksi (Duplikasi & Hapus) -->
                                        <td class="text-end text-md-center">
                                            <div class="d-flex align-items-center justify-content-end justify-content-md-center gap-2 gap-md-1">
                                                <button type="button" wire:click="duplicateItem({{ $index }})"
                                                        class="btn btn-sm btn-outline-secondary px-2 py-1 d-inline-flex align-items-center justify-content-center"
                                                        title="Duplikasi baris ini (alokasi ke gudang lain)">
                                                    <i class="bi bi-copy" style="font-size: 0.8rem;"></i>
                                                    <span class="d-md-none ms-1 small">Duplikat</span>
                                                </button>
                                                <button type="button" wire:click="removeItem({{ $index }})"
                                                        class="btn btn-sm btn-outline-danger px-2 py-1 d-inline-flex align-items-center justify-content-center"
                                                        title="Hapus baris barang">
                                                    <i class="bi bi-trash" style="font-size: 0.85rem;"></i>
                                                    <span class="d-md-none ms-1 small">Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Summary Footer Block (Responsive on Desktop & Mobile) -->
                <div class="bg-light border-top p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-semibold text-secondary small">
                                <i class="bi bi-boxes me-1 text-primary"></i>Ringkasan Fisik:
                            </span>
                            @forelse ($this->physicalSummary as $unit => $totalQty)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <strong>{{ number_format($totalQty, 2, ',', '.') }}</strong> {{ $unit }}
                                </span>
                            @empty
                                <span class="text-muted small">0 item</span>
                            @endforelse
                        </div>
                        <div class="text-end ms-auto">
                            <span class="fw-bold fs-6 me-2">Grand Total Nilai PO:</span>
                            <span class="fw-bold fs-5 text-primary font-monospace">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($status_pembayaran === 'tempo' || $status_pembayaran === 'sebagian')
                        @php
                            $dp = filled($nominal_bayar_awal) ? min((float) $nominal_bayar_awal, $this->total) : 0.0;
                            $sisa = max(0.0, $this->total - $dp);
                        @endphp
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 pt-2 border-top">
                            <div class="text-muted small">
                                <i class="bi bi-calendar-event me-1"></i>Jatuh Tempo: <strong>{{ filled($tanggal_jatuh_tempo) ? Carbon\Carbon::parse($tanggal_jatuh_tempo)->format('d/m/Y') : '-' }}</strong>
                            </div>
                            <div class="text-end ms-auto small">
                                <div>Uang Muka / Bayar Awal: <strong class="text-success font-monospace">Rp {{ number_format($dp, 0, ',', '.') }}</strong></div>
                                <div class="mt-1">
                                    <span class="fw-bold text-danger me-2">Sisa Hutang Toko:</span>
                                    <span class="fw-bold text-danger font-monospace">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    @if ($status === 'ordered')
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-1">
                            <i class="bi bi-file-earmark-text me-1"></i>Surat Pesanan (PO)
                        </span>
                        Stok gudang belum bertambah sampai barang fisik tiba & diterima.
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle me-1">
                            <i class="bi bi-box-seam me-1"></i>Penerimaan Barang Masuk
                        </span>
                        Stok gudang otomatis bertambah saat faktur ini disimpan.
                    @endif
                </div>
                <div class="d-flex gap-2 w-100 w-sm-auto">
                    <a href="{{ route('pembelian.index') }}" class="btn btn-light border flex-fill flex-sm-grow-0">Batal</a>
                    <button type="submit" class="btn {{ $status === 'ordered' ? 'btn-warning text-dark fw-semibold' : 'btn-primary' }} flex-fill flex-sm-grow-0" wire:loading.attr="disabled" {{ count($items) === 0 ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="save">
                            @if ($status === 'ordered')
                                <i class="bi bi-file-earmark-plus me-1"></i>Simpan Surat Pesanan (PO)
                            @else
                                <i class="bi bi-check2-circle me-1"></i>Simpan & Terima Masuk Gudang
                            @endif
                        </span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal Multi-Pilih Katalog -->
    @if ($isCatalogModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2 py-sm-3 px-3">
                        <div class="min-w-0">
                            <h6 class="modal-title fw-bold mb-0 text-truncate">
                                <i class="bi bi-grid-3x3-gap me-2"></i>Pilih Produk dari Katalog
                            </h6>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCatalogModal"></button>
                    </div>

                    <!-- Sticky Top Search & Collapsible Filters -->
                    <div class="p-2 p-sm-3 bg-light border-bottom flex-shrink-0" x-data="{ showFilters: false }">
                        <!-- Search Bar + Mobile Filter Toggle Button -->
                        <div class="d-flex gap-2 mb-2">
                            <div class="input-group input-group-sm flex-grow-1">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input
                                    type="text"
                                    wire:model.live.debounce.250ms="catalogSearchQuery"
                                    class="form-control border-start-0"
                                    placeholder="Cari nama motif, ketebalan, panjang, atau SKU..."
                                >
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

                        <!-- Collapsible Dropdown Filters on Mobile, Always Visible on Desktop -->
                        <div class="row g-2 mb-2 d-md-flex" :class="showFilters ? 'd-flex' : 'd-none'">
                            <div class="col-6 col-md-4">
                                <select wire:model.live="catalogKategoriId" class="form-select form-select-sm">
                                    <option value="">Semua Kategori</option>
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
                            <div class="col-6 col-md-4">
                                <select wire:model.live="catalogMerekId" class="form-select form-select-sm">
                                    <option value="">Semua Merek / Pabrik</option>
                                    @foreach ($mereks as $mrk)
                                        <option value="{{ $mrk->id }}">{{ $mrk->nama_merek }}</option>
                                    @endforeach
                                </select>
                            </div>
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

                        <!-- Quick Brand Pills (Horizontal Swipe) -->
                        <div class="d-flex align-items-center gap-1 nav-pills-scrollable flex-nowrap mb-2 pb-1">
                            @if ($supplier_id)
                                <button
                                    type="button"
                                    wire:click="filterByCurrentSupplierBrands"
                                    class="btn btn-xs py-1 px-2.5 rounded-pill btn-warning text-dark fw-semibold text-nowrap flex-shrink-0"
                                    style="font-size: 0.74rem;"
                                    title="Tampilkan hanya produk merek supplier yang sedang dipilih"
                                >
                                    <i class="bi bi-star-fill me-1"></i>Produk Supplier Ini
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="setCatalogMerek(null)"
                                class="btn btn-xs py-1 px-2.5 rounded-pill text-nowrap flex-shrink-0 {{ $catalogMerekId === null ? 'btn-primary fw-semibold' : 'btn-outline-secondary bg-white' }}"
                                style="font-size: 0.74rem;"
                            >
                                Semua Merek
                            </button>
                            @foreach ($mereks as $mrk)
                                <button
                                    type="button"
                                    wire:click="setCatalogMerek({{ $mrk->id }})"
                                    class="btn btn-xs py-1 px-2.5 rounded-pill text-nowrap flex-shrink-0 {{ $catalogMerekId === $mrk->id ? 'btn-primary fw-semibold' : 'btn-outline-secondary bg-white' }}"
                                    style="font-size: 0.74rem;"
                                >
                                    {{ $mrk->nama_merek }}
                                </button>
                            @endforeach
                        </div>

                        <!-- View Tab (Semua vs Terpilih) & Bulk Action -->
                        <div class="d-flex justify-content-between align-items-center gap-2 pt-1 border-top">
                            <div class="btn-group btn-group-sm" role="group">
                                <button
                                    type="button"
                                    wire:click="setCatalogViewTab('all')"
                                    class="btn py-1 px-2 px-sm-3 {{ $catalogViewTab === 'all' ? 'btn-primary fw-semibold' : 'btn-outline-primary bg-white' }}"
                                    style="font-size: 0.78rem;"
                                >
                                    Semua ({{ $catalogTotalCount }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="setCatalogViewTab('selected')"
                                    class="btn py-1 px-2 px-sm-3 {{ $catalogViewTab === 'selected' ? 'btn-success fw-semibold' : 'btn-outline-success bg-white' }}"
                                    style="font-size: 0.78rem;"
                                >
                                    <i class="bi bi-check2-circle me-1"></i>Terpilih ({{ count($catalogSelectedProductIds) }})
                                </button>
                            </div>

                            <div class="btn-group btn-group-sm">
                                <button type="button" wire:click="selectAllFoundCatalogProducts" class="btn btn-outline-secondary bg-white py-1 px-2" style="font-size: 0.76rem;" title="Centang semua produk hasil filter yang sedang tampil">
                                    <i class="bi bi-check-all me-1"></i>Pilih Semua
                                </button>
                                @if (count($catalogSelectedProductIds) > 0)
                                    <button type="button" wire:click="clearSelectedCatalogProducts" class="btn btn-outline-danger bg-white py-1 px-2" style="font-size: 0.76rem;" title="Hapus semua centang">
                                        <i class="bi bi-x-circle me-1"></i>Reset
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Scrollable Product List Body (Single Native Touch Scroll) -->
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse ($catalogProducts as $prod)
                                @php
                                    $isSelected = in_array($prod->id, $catalogSelectedProductIds, true);
                                    $currentStok = (float) ($prod->stok_sum_jumlah ?? 0);
                                    $minStok = (float) $prod->stok_minimum;
                                    $statusStok = \App\Domains\Inventory\Enums\StatusStok::determine($currentStok, $minStok);
                                @endphp
                                <div
                                    wire:key="po-create-cat-{{ $prod->id }}"
                                    wire:click="toggleCatalogProduct({{ $prod->id }})"
                                    class="list-group-item list-group-item-action py-2.5 px-3 {{ $isSelected ? 'bg-primary-subtle border-start border-4 border-primary' : '' }}"
                                    style="cursor: pointer;"
                                >
                                    <div class="d-flex align-items-start gap-2.5">
                                        <!-- Touch-friendly Checkbox -->
                                        <div class="pt-1 flex-shrink-0" onclick="event.stopPropagation();">
                                            <input
                                                type="checkbox"
                                                class="form-check-input m-0"
                                                style="width: 1.25rem; height: 1.25rem; cursor: pointer;"
                                                wire:click="toggleCatalogProduct({{ $prod->id }})"
                                                {{ $isSelected ? 'checked' : '' }}
                                            >
                                        </div>

                                        <!-- Product Photo Thumbnail -->
                                        <div class="position-relative flex-shrink-0" x-data="{ showPreview: false }" onclick="event.stopPropagation();">
                                            @if ($prod->foto_url)
                                                <img src="{{ $prod->foto_url }}" alt="{{ $prod->nama }}"
                                                     class="rounded-3 border object-fit-cover"
                                                     style="width: 44px; height: 44px; cursor: pointer;"
                                                     loading="lazy"
                                                     @mouseenter="showPreview = true"
                                                     @mouseleave="showPreview = false"
                                                     wire:click="openImageModal({{ $prod->id }})">
                                                <div x-show="showPreview"
                                                     class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                     style="bottom: 100%; left: 0; z-index: 1085; min-width: 180px; max-width: 240px; pointer-events: none; margin-bottom: 6px;"
                                                     x-cloak>
                                                    <img src="{{ $prod->foto_url }}" alt="{{ $prod->nama }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                                    <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $prod->nama }}</div>
                                                </div>
                                            @else
                                                <div class="bg-light rounded-3 border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                                    <i class="bi bi-image" style="font-size: 1.1rem;"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Full-Width Product Details (No Truncation on Name!) -->
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div class="fw-bold {{ $isSelected ? 'text-primary' : 'text-dark' }}" style="font-size: 0.9rem; line-height: 1.3;">
                                                    {{ $prod->nama }}
                                                </div>
                                                <!-- Desktop Price & Stock Inline -->
                                                <div class="d-none d-md-flex align-items-center gap-2 flex-shrink-0">
                                                    <span class="badge {{ $statusStok->badgeClass() }}">
                                                        Stok: {{ number_format($currentStok, 0, ',', '.') }} / Min: {{ number_format($minStok, 0, ',', '.') }}
                                                    </span>
                                                    <span class="badge bg-light text-dark border">{{ $prod->satuan->label() }}</span>
                                                    <span class="fw-bold text-primary font-monospace">Rp {{ number_format((float) $prod->harga_beli, 0, ',', '.') }}</span>
                                                </div>
                                            </div>

                                            <!-- Badges Row: SKU, Kategori, Merek -->
                                            <div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size: 0.74rem;">
                                                <span class="badge bg-light text-secondary border font-monospace">{{ $prod->kode_katalog ?? 'No SKU' }}</span>
                                                <span class="badge bg-light text-dark border">{{ $prod->kategori->nama_kategori ?? '-' }}</span>
                                                @if ($prod->merek)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $prod->merek->nama_merek }}</span>
                                                @endif
                                            </div>

                                            <!-- Mobile Price & Stock Footer Row -->
                                            <div class="d-flex d-md-none justify-content-between align-items-center flex-wrap gap-1 mt-2 pt-1 border-top border-light-subtle">
                                                <div>
                                                    <span class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;">
                                                        Rp {{ number_format((float) $prod->harga_beli, 0, ',', '.') }}
                                                    </span>
                                                    <span class="text-muted" style="font-size: 0.75rem;">/ {{ $prod->satuan->label() }}</span>
                                                </div>
                                                <span class="badge {{ $statusStok->badgeClass() }}" style="font-size: 0.72rem;">
                                                    Stok: {{ number_format($currentStok, 0, ',', '.') }} (Min: {{ number_format($minStok, 0, ',', '.') }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 px-3 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    @if ($catalogViewTab === 'selected')
                                        <div class="fw-semibold">Belum ada produk yang dicentang</div>
                                        <small>Silakan pilih produk di tab "Semua ({{ $catalogTotalCount }})".</small>
                                    @else
                                        <div class="fw-semibold">Tidak ada produk yang cocok</div>
                                        <small>Coba ubah kata kunci pencarian atau reset filter kategori/merek.</small>
                                    @endif
                                </div>
                            @endforelse
                        </div>

                        <!-- Load More Button for performance -->
                        @if ($catalogViewTab === 'all' && count($catalogProducts) < $catalogTotalCount)
                            <div class="p-3 text-center bg-light border-top">
                                <button type="button" wire:click="loadMoreCatalog" class="btn btn-sm btn-outline-primary px-4 rounded-pill">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Muat 50 Produk Berikutnya (Tersisa: {{ $catalogTotalCount - count($catalogProducts) }})
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 flex-shrink-0">
                        <span class="text-muted small d-none d-sm-inline">
                            Produk terpilih akan dimasukkan ke tabel dengan gudang default.
                        </span>
                        <div class="d-flex gap-2 w-100 w-sm-auto ms-sm-auto">
                            <button type="button" class="btn btn-outline-secondary bg-white flex-fill flex-sm-grow-0" wire:click="closeCatalogModal">Tutup</button>
                            <button
                                type="button"
                                class="btn btn-primary fw-bold flex-fill flex-sm-grow-0"
                                wire:click="addSelectedCatalogProducts"
                                {{ count($catalogSelectedProductIds) === 0 ? 'disabled' : '' }}
                            >
                                <i class="bi bi-check2-circle me-1"></i>Tambahkan ({{ count($catalogSelectedProductIds) }}) Produk
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Cepat Tambah Supplier Baru -->
    @if ($isQuickSupplierModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-building-add me-2 text-success"></i>Tambah Supplier Baru
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeQuickSupplierModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Supplier / Pabrik <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                wire:model="new_nama_supplier"
                                class="form-control @error('new_nama_supplier') is-invalid @enderror"
                                placeholder="Contoh: PT Semen Gypsum Utama"
                                autofocus
                            >
                            @error('new_nama_supplier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kontak / No. Telepon / Sales</label>
                            <input
                                type="text"
                                wire:model="new_kontak_supplier"
                                class="form-control @error('new_kontak_supplier') is-invalid @enderror"
                                placeholder="Contoh: 0812-3456-7890 (Bpk. Joko)"
                            >
                            @error('new_kontak_supplier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeQuickSupplierModal">Batal</button>
                        <button type="button" class="btn btn-success" wire:click="saveQuickSupplier">
                            <i class="bi bi-check2-circle me-1"></i>Simpan & Pilih Supplier
                        </button>
                    </div>
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
</div>
