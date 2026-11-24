<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Buat Transfer Antar Gudang</h4>
            <p class="text-muted small mb-0">Pindahkan stok produk antar lokasi gudang fisik</p>
        </div>
        <a href="{{ route('transfer.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @error('general')
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @enderror

    <form wire:submit="save">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-1"></i> Informasi Transfer</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" wire:model="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Gudang Asal (Sumber Stok) <span class="text-danger">*</span></label>
                        <select wire:model.live="gudang_asal_id" class="form-select @error('gudang_asal_id') is-invalid @enderror">
                            <option value="">-- Pilih Gudang Asal --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        @error('gudang_asal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Gudang Tujuan (Penerima) <span class="text-danger">*</span></label>
                        <select wire:model="gudang_tujuan_id" class="form-select @error('gudang_tujuan_id') is-invalid @enderror">
                            <option value="">-- Pilih Gudang Tujuan --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}" @disabled((int) $g->id === (int) $gudang_asal_id)>
                                    {{ $g->nama_gudang }} {{ (int) $g->id === (int) $gudang_asal_id ? '(Gudang Asal)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('gudang_tujuan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Keterangan / Catatan Pengiriman</label>
                        <textarea wire:model="keterangan" class="form-control" rows="2" placeholder="Contoh: No. Polisi armada, nama driver, kebutuhan restock proyek..."></textarea>
                    </div>
                </div>

                <!-- Visual Route Indicator -->
                @if ($gudang_asal_id || $gudang_tujuan_id)
                    @php
                        $gAsalSelected = $gudangs->firstWhere('id', $gudang_asal_id);
                        $gTujuanSelected = $gudangs->firstWhere('id', $gudang_tujuan_id);
                    @endphp
                    <div class="p-3 bg-light rounded-3 border mt-3">
                        <div class="row align-items-center g-2 text-center text-md-start">
                            <div class="col-md-5">
                                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle p-2 rounded-circle fs-6">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </span>
                                    <div>
                                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Gudang Asal (Pemotongan Stok)</small>
                                        <strong class="text-dark fs-6">{{ $gAsalSelected->nama_gudang ?? 'Belum Dipilih' }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center my-1 my-md-0">
                                <span class="badge bg-white text-primary border shadow-sm px-3 py-1 fs-6">
                                    <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex align-items-center justify-content-center justify-content-md-end gap-2 text-md-end">
                                    <div class="order-2 order-md-1">
                                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Gudang Tujuan (Penambahan Stok)</small>
                                        <strong class="text-dark fs-6">{{ $gTujuanSelected->nama_gudang ?? 'Belum Dipilih' }}</strong>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle p-2 rounded-circle fs-6 order-1 order-md-2">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Daftar Barang yang Ditransfer -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-1"></i> Daftar Barang yang Ditransfer</h6>
                        <small class="text-muted">Ketik nama barang di bawah atau gunakan tombol Pilih dari Katalog.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($gudang_asal_id)
                            <div class="form-check form-switch mb-0 me-2">
                                <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="onlyAvailableStockSwitch" wire:model.live="onlyAvailableStock">
                                <label class="form-check-label small text-secondary cursor-pointer" for="onlyAvailableStockSwitch">
                                    Hanya stok tersedia
                                </label>
                            </div>
                        @endif
                        <button type="button" wire:click="openCatalogModal" class="btn btn-sm btn-primary">
                            <i class="bi bi-grid-3x3-gap me-1"></i>+ Buka Katalog & Pilih Banyak
                        </button>
                        @if(count(array_filter($items, fn($i) => !empty($i['produk_id']))) > 0)
                            <button type="button" wire:click="clearAllItems" class="btn btn-sm btn-outline-danger" title="Kosongkan semua daftar barang">
                                <i class="bi bi-trash me-1"></i>Kosongkan
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Quick Product Search Bar -->
                <div class="position-relative">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-primary text-white"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="quickSearchQuery"
                            class="form-control fs-6"
                            placeholder="{{ $gudang_asal_id ? 'Ketik nama produk atau kode SKU untuk menambahkan ke transfer...' : 'Pilih Gudang Asal terlebih dahulu...' }}"
                            @disabled(!$gudang_asal_id)
                        >
                        @if (filled($quickSearchQuery))
                            <button type="button" wire:click="$set('quickSearchQuery', '')" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>

                    <!-- Floating Search Results Dropdown -->
                    @if (filled($quickSearchQuery) && count($quickSearchResults) > 0)
                        <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border border-primary rounded-3 overflow-hidden" style="max-height: 280px; overflow-y: auto;">
                            @foreach ($quickSearchResults as $res)
                                <button
                                    type="button"
                                    wire:click="addQuickProduct({{ $res['id'] }})"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3"
                                >
                                    <div class="d-flex align-items-center gap-2">
                                        @if (!empty($res['foto_url']))
                                            <img src="{{ $res['foto_url'] }}" alt="{{ $res['nama'] }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;">
                                        @else
                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <strong class="text-dark">{{ $res['nama'] }}</strong>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <span class="badge bg-light text-dark border me-1">{{ $res['kode_katalog'] }}</span>
                                                {{ $res['kategori'] }} &bull; Satuan: <strong>{{ $res['satuan'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $res['stok'] <= 0 ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-success-subtle text-success border-success-subtle' }} border px-2 py-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-box me-1"></i>Stok Asal: {{ number_format($res['stok'], 2, ',', '.') }} {{ $res['satuan'] }}
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif (filled($quickSearchQuery))
                        <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border rounded-3">
                            <div class="list-group-item text-muted small py-3 px-3 text-center">
                                <i class="bi bi-question-circle me-1"></i>Produk '{{ $quickSearchQuery }}' tidak ditemukan.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-body p-0" x-data="{
                allProducts: {{ Js::from($select2Products) }},
                getSearchFiltered(keyword) {
                    if (!keyword || keyword.trim() === '') {
                        return this.allProducts.slice(0, 50);
                    }
                    const term = keyword.toLowerCase().trim();
                    return this.allProducts.filter(p =>
                        (p.nama && p.nama.toLowerCase().includes(term)) ||
                        (p.kode && p.kode.toLowerCase().includes(term)) ||
                        (p.kategori && p.kategori.toLowerCase().includes(term)) ||
                        (p.merek && p.merek.toLowerCase().includes(term))
                    ).slice(0, 50);
                }
            }">
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th style="min-width: 320px;">Produk <span class="text-danger">*</span></th>
                                <th style="min-width: 260px;">Jumlah Transfer <span class="text-danger">*</span></th>
                                <th style="width: 80px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $validItemCount = 0;
                            @endphp
                            @foreach ($items as $index => $item)
                                @php
                                    $hasProduk = !empty($item['produk_id']);
                                    if ($hasProduk) {
                                        $validItemCount++;
                                    }
                                    $prodModel = $hasProduk ? $produks->firstWhere('id', (int) $item['produk_id']) : null;
                                    $availStock = ($gudang_asal_id && $hasProduk) ? $this->getAvailableStock($item['produk_id']) : null;
                                    $destStock = ($gudang_tujuan_id && $hasProduk) ? $this->getDestinationStock($item['produk_id']) : null;
                                    $inputQty = (float) ($item['jumlah'] ?? 0);
                                    $isOverflow = ($availStock !== null && $inputQty > $availStock);
                                    $isDuplicate = in_array((int)($item['produk_id'] ?? 0), $this->duplicateProductIds);
                                @endphp
                                <tr wire:key="transfer-row-{{ $index }}-{{ $item['produk_id'] ?? 'empty' }}">
                                    <td class="ps-4 text-muted fw-semibold">{{ $index + 1 }}</td>
                                    <td style="position: relative;">
                                        @if ($prodModel)
                                            <input type="hidden" wire:model="items.{{ $index }}.produk_id" value="{{ $prodModel->id }}">
                                            <div class="p-2 border rounded-3 bg-light d-flex justify-content-between align-items-center" style="min-height: 48px;">
                                                <div class="d-flex align-items-center gap-2 flex-wrap pe-2">
                                                    <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                        @if ($prodModel->foto_url)
                                                            <img src="{{ $prodModel->foto_url }}" alt="{{ $prodModel->nama }}"
                                                                 class="rounded border object-fit-cover"
                                                                 style="width: 38px; height: 38px; cursor: pointer;"
                                                                 loading="lazy"
                                                                 @mouseenter="showPreview = true"
                                                                 @mouseleave="showPreview = false"
                                                                 wire:click="openImageModal({{ $prodModel->id }})">
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
                                                    <div>
                                                        <span class="fw-bold text-dark fs-6 me-1">{{ $prodModel->nama }}</span>
                                                        <div class="d-flex align-items-center gap-1 flex-wrap mt-0.5">
                                                            <span class="badge bg-white text-dark border font-monospace" style="font-size: 0.72rem;">
                                                                SKU: {{ $prodModel->kode_katalog ?? '-' }}
                                                            </span>
                                                            @if ($prodModel->kategori)
                                                                <span class="badge bg-white text-secondary border" style="font-size: 0.72rem;">
                                                                    {{ $prodModel->kategori->nama_kategori }}
                                                                </span>
                                                            @endif
                                                            <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;">
                                                                {{ $prodModel->satuan->label() }}
                                                            </span>

                                                            @if ($gudang_asal_id)
                                                                <span class="badge {{ $availStock > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} font-monospace" style="font-size: 0.72rem;">
                                                                    <i class="bi bi-box me-1"></i>Stok Asal: {{ number_format($availStock, 2, ',', '.') }}
                                                                </span>
                                                            @endif
                                                            @if ($gudang_tujuan_id)
                                                                <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">
                                                                    <i class="bi bi-box-arrow-in-right me-1"></i>Stok Tujuan: {{ number_format($destStock, 2, ',', '.') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    wire:click="$set('items.{{ $index }}.produk_id', null)"
                                                    class="btn btn-outline-secondary btn-xs py-1 px-2.5 text-nowrap rounded-2"
                                                    style="font-size: 0.72rem;"
                                                    title="Ganti produk pada baris ini"
                                                >
                                                    <i class="bi bi-arrow-repeat me-1"></i>Ganti
                                                </button>
                                            </div>
                                        @else
                                            <!-- In-Row Seamless Select2 Searchable Dropdown -->
                                            <div
                                                x-data="{
                                                    isOpen: false,
                                                    searchQuery: '',
                                                    get results() {
                                                        return getSearchFiltered(this.searchQuery);
                                                    },
                                                    select(id) {
                                                        $wire.selectProductForRow({{ $index }}, id);
                                                        this.isOpen = false;
                                                        this.searchQuery = '';
                                                    }
                                                }"
                                                @click.outside="isOpen = false"
                                                class="position-relative"
                                            >
                                                <!-- Unified Single Input Box -->
                                                <div class="position-relative d-flex align-items-center">
                                                    <i class="bi bi-search position-absolute text-muted" style="left: 12px; font-size: 0.85rem; pointer-events: none; z-index: 3;"></i>
                                                    <input
                                                        type="text"
                                                        x-model="searchQuery"
                                                        @focus="isOpen = true"
                                                        @click="isOpen = true"
                                                        @keydown.escape="isOpen = false"
                                                        @keydown.enter.prevent="if (results.length > 0) select(results[0].id)"
                                                        class="form-control bg-white shadow-none @error('items.' . $index . '.produk_id') is-invalid border-danger @enderror"
                                                        style="padding-left: 34px; padding-right: 34px; height: 38px; border-radius: 8px; border: 1px solid #ced4da;"
                                                        placeholder="{{ $gudang_asal_id ? 'Ketik nama produk / SKU / kategori...' : 'Pilih Gudang Asal dahulu...' }}"
                                                        @disabled(!$gudang_asal_id)
                                                    >
                                                    <button
                                                        type="button"
                                                        @click="isOpen = !isOpen"
                                                        class="btn btn-link position-absolute end-0 text-muted p-0 me-3 border-0 shadow-none text-decoration-none"
                                                        style="z-index: 3;"
                                                        @disabled(!$gudang_asal_id)
                                                        title="Buka pilihan produk"
                                                    >
                                                        <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                                    </button>
                                                </div>
                                                @error('items.' . $index . '.produk_id')
                                                    <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                                                @enderror

                                                <!-- Floating Select2 Dropdown -->
                                                <div
                                                    x-show="isOpen"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 transform -translate-y-1"
                                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 transform translate-y-0"
                                                    x-transition:leave-end="opacity-0 transform -translate-y-1"
                                                    class="position-absolute start-0 end-0 bg-white border border-primary-subtle shadow-lg rounded-3 mt-1 overflow-hidden"
                                                    style="max-height: 290px; overflow-y: auto; display: none; z-index: 1050; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15), 0 8px 10px -6px rgba(0,0,0,0.1);"
                                                >
                                                    <div class="p-1">
                                                        <div class="px-2 py-1.5 bg-light rounded-top small text-muted d-flex justify-content-between align-items-center border-bottom">
                                                            <span class="fw-semibold">Pilih Produk (<span x-text="results.length"></span>)</span>
                                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Tekan Enter untuk pilih pertama</span>
                                                        </div>
                                                        <template x-for="p in results" :key="p.id">
                                                            <div
                                                                @click="select(p.id)"
                                                                class="d-flex justify-content-between align-items-center p-2 rounded-2 border-bottom border-light"
                                                                style="cursor: pointer; transition: background-color 0.15s ease;"
                                                                onmouseover="this.style.backgroundColor='#e0f2fe'"
                                                                onmouseout="this.style.backgroundColor='transparent'"
                                                            >
                                                                <div class="d-flex align-items-center gap-2 pe-2">
                                                                    <template x-if="p.foto_url">
                                                                        <img :src="p.foto_url" :alt="p.nama" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;">
                                                                    </template>
                                                                    <template x-if="!p.foto_url">
                                                                        <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                                            <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                                                        </div>
                                                                    </template>
                                                                    <div>
                                                                        <div class="fw-bold text-dark" style="font-size: 0.88rem;" x-text="p.nama"></div>
                                                                        <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                                                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.68rem;" x-text="'SKU: ' + p.kode"></span>
                                                                            <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;" x-text="p.kategori"></span>
                                                                            <span class="badge bg-light text-primary border" style="font-size: 0.68rem;" x-text="p.satuan"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="text-end text-nowrap">
                                                                    <span
                                                                        class="badge border font-monospace"
                                                                        :class="p.stok > 0 ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
                                                                        style="font-size: 0.72rem;"
                                                                        x-text="'Stok Asal: ' + Number(p.stok).toLocaleString('id-ID', {minimumFractionDigits: 2})"
                                                                    ></span>
                                                                </div>
                                                            </div>
                                                        </template>

                                                        <div x-show="results.length === 0" class="p-3 text-center text-muted small">
                                                            <i class="bi bi-question-circle me-1"></i>Tidak ada produk yang cocok dengan pencarian.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($isDuplicate)
                                            <div class="small text-warning mt-1">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Produk ini dipilih lebih dari 1 baris (jumlah akan terakumulasi).
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <button
                                                type="button"
                                                wire:click="decrementQuantity({{ $index }})"
                                                class="btn btn-outline-secondary"
                                                title="Kurangi 1"
                                                @disabled(!$hasProduk || $inputQty <= 1)
                                            >
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input
                                                type="number"
                                                step="0.001"
                                                wire:model.live.debounce.300ms="items.{{ $index }}.jumlah"
                                                class="form-control text-center fw-bold {{ $isOverflow ? 'border-danger bg-danger bg-opacity-10 text-danger' : '' }} @error('items.' . $index . '.jumlah') is-invalid @enderror"
                                                placeholder="0.00"
                                            >
                                            <button
                                                type="button"
                                                wire:click="incrementQuantity({{ $index }})"
                                                class="btn btn-outline-secondary"
                                                title="Tambah 1"
                                                @disabled(!$hasProduk)
                                            >
                                                <i class="bi bi-plus"></i>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openPackagingCalculator({{ $index }})"
                                                class="btn btn-outline-info"
                                                title="Hitung Kemasan Fisik (Kalkulator Lapangan)"
                                                @disabled(!$hasProduk)
                                            >
                                                <i class="bi bi-calculator"></i>
                                            </button>
                                            @if ($availStock !== null && $availStock > 0)
                                                <button
                                                    type="button"
                                                    wire:click="fillMaxQuantity({{ $index }})"
                                                    class="btn btn-outline-primary btn-sm fw-semibold"
                                                    title="Isi dengan seluruh stok gudang asal ({{ number_format($availStock, 2, ',', '.') }})"
                                                >
                                                    Maks
                                                </button>
                                            @endif
                                        </div>
                                        @error('items.' . $index . '.jumlah') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                                        @php
                                            $konv = ($prodModel && $inputQty > 0) ? $prodModel->getKonversiFisik((float) $inputQty) : null;
                                        @endphp
                                        @if ($konv && $konv['has_conversion'])
                                            <div class="mt-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-1.5 rounded-2 font-monospace" style="font-size: 0.68rem;">
                                                    <i class="bi bi-boxes me-1"></i>{{ $konv['badge_text'] }}
                                                </span>
                                            </div>
                                        @endif

                                        @if ($isOverflow)
                                            <div class="small text-danger mt-1">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Melebihi sisa fisik di gudang asal ({{ number_format($availStock, 2, ',', '.') }})!
                                            </div>
                                        @elseif ($gudang_asal_id && $hasProduk && $inputQty > 0)
                                            @php
                                                $newSource = max(0, $availStock - $inputQty);
                                                $newDest = $destStock !== null ? ($destStock + $inputQty) : null;
                                            @endphp
                                            <div class="mt-1 small text-muted" style="font-size: 0.78rem;">
                                                <span class="d-inline-block me-2">
                                                    Sisa Asal: <strong class="{{ $newSource <= 0 ? 'text-danger' : 'text-dark' }}">{{ number_format($newSource, 2, ',', '.') }}</strong>
                                                </span>
                                                @if ($newDest !== null)
                                                    <span class="d-inline-block">
                                                        Jadi Tujuan: <strong class="text-success">{{ number_format($newDest, 2, ',', '.') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-sm btn-outline-danger" @disabled(count($items) === 1 && empty($item['produk_id']))>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach

                            @if ($validItemCount === 0 && count($items) === 1 && empty($items[0]['produk_id']))
                                <tr>
                                    <td colspan="4" class="text-center py-4 bg-light text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                                        <div class="fw-semibold text-dark">Belum ada barang yang dipilih untuk ditransfer.</div>
                                        <small class="text-muted d-block mb-2">Gunakan pencarian instan di atas atau buka katalog untuk memilih beberapa barang sekaligus.</small>
                                        <button type="button" wire:click="openCatalogModal" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-grid-3x3-gap me-1"></i>Buka Katalog Produk
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-white border-top d-flex justify-content-between align-items-center">
                    <button type="button" wire:click="addItem" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Baris Kosong
                    </button>
                    <small class="text-muted">Total {{ count($items) }} baris input</small>
                </div>
            </div>

            <!-- Live Summary Bar -->
            <div class="card-footer bg-light p-3">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-4 flex-wrap">
                            <div>
                                <span class="text-muted small d-block">Total Variasi Item:</span>
                                <strong class="fs-6 text-dark">{{ count(array_filter($items, fn($i) => !empty($i['produk_id']))) }} SKU Produk</strong>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Total Kuantitas Ditransfer:</span>
                                <strong class="fs-6 text-primary">{{ number_format($this->totalTransferredQuantity, 2, ',', '.') }} Unit Fisik</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0">
                        @if ($this->hasOverflowItems)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle p-2">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Ada kuantitas melebihi stok asal!
                            </span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle p-2">
                                <i class="bi bi-check-circle-fill me-1"></i>Stok Valid & Siap Transfer
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('transfer.index') }}" class="btn btn-secondary">Batal</a>
            <button
                type="button"
                wire:click="promptConfirmation"
                class="btn btn-primary px-4 fw-semibold"
                @disabled($this->hasOverflowItems || empty($gudang_asal_id) || empty($gudang_tujuan_id))
            >
                <i class="bi bi-check-circle me-1"></i> Review & Simpan Transfer
            </button>
        </div>
    </form>

    <!-- Modal Katalog Produk & Multi-Add -->
    @if ($isCatalogModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1055;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">Katalog Produk & Pilih Sekaligus</h5>
                                <small class="text-white-50">Centang produk yang ingin dipindahkan dari Gudang Asal, lalu klik Tambahkan.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCatalogModal"></button>
                    </div>

                    <div class="modal-body p-3 bg-light-subtle">
                        <!-- Filter & Search Toolbar -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input
                                        type="text"
                                        wire:model.live.debounce.250ms="catalogSearchQuery"
                                        class="form-control"
                                        placeholder="Ketik nama atau kode SKU barang..."
                                        autofocus
                                    >
                                    @if (filled($catalogSearchQuery))
                                        <button type="button" wire:click="$set('catalogSearchQuery', '')" class="btn btn-outline-secondary">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="catalogKategoriId" class="form-select">
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
                            <div class="col-md-3">
                                <select wire:model.live="catalogMerekId" class="form-select">
                                    <option value="">-- Semua Merek --</option>
                                    @foreach ($mereks as $m)
                                        <option value="{{ $m->id }}">{{ $m->nama_merek }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Action Toolbar -->
                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                            <span class="small text-muted" style="font-size: 0.75rem;">
                                Menampilkan {{ count($catalogProducts) }} barang &bull; Tercentang: <strong class="text-primary">{{ count($catalogSelectedProductIds) }}</strong> item
                            </span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" wire:click="selectAllFoundCatalogProducts" class="btn btn-outline-primary btn-xs py-1 px-2" style="font-size: 0.75rem;">
                                    <i class="bi bi-check-all me-1"></i>Centang Semua
                                </button>
                                @if (count($catalogSelectedProductIds) > 0)
                                    <button type="button" wire:click="clearSelectedCatalogProducts" class="btn btn-outline-danger btn-xs py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-x-circle me-1"></i>Hapus Centang
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Product List Table -->
                        <div class="table-responsive border rounded bg-white" style="max-height: 380px; overflow-y: auto;">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th>Nama Produk & SKU</th>
                                        <th style="width: 140px;">Kategori</th>
                                        <th style="width: 120px;">Merek</th>
                                        <th style="width: 90px;">Satuan</th>
                                        <th style="width: 150px;" class="text-end pe-3">Stok Gudang Asal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($catalogProducts as $cp)
                                        @php $checked = in_array($cp['id'], $catalogSelectedProductIds, true); @endphp
                                        <tr
                                            wire:click="toggleCatalogProduct({{ $cp['id'] }})"
                                            style="cursor: pointer;"
                                            class="{{ $checked ? 'table-primary' : '' }}"
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
                                                    <div>
                                                        <strong class="text-dark d-block">{{ $cp['nama'] }}</strong>
                                                        <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                                            SKU: {{ $cp['kode_katalog'] }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border">{{ $cp['kategori'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-white text-dark border">{{ $cp['merek'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border">{{ $cp['satuan'] }}</span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <span class="badge {{ $cp['stok'] <= 0 ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-success-subtle text-success border-success-subtle' }} border font-monospace">
                                                    {{ number_format($cp['stok'], 2, ',', '.') }} {{ $cp['satuan'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox d-block fs-3 mb-1"></i>
                                                Tidak ada produk yang cocok dengan pencarian / kategori / merek.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeCatalogModal">Batal</button>
                        <button
                            type="button"
                            class="btn btn-primary btn-sm px-4 fw-semibold"
                            wire:click="addSelectedCatalogProducts"
                            @disabled(count($catalogSelectedProductIds) === 0)
                        >
                            <i class="bi bi-plus-circle me-1"></i>Tambahkan ({{ count($catalogSelectedProductIds) }}) Produk Terpilih ke Transfer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modern Confirmation Modal Before Execution -->
    @if ($isConfirmModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-dark">Konfirmasi Perpindahan Stok</h5>
                            <small class="text-muted">Periksa kembali rute dan daftar barang sebelum mutasi dicatat ke sistem.</small>
                        </div>
                        <button type="button" wire:click="closeConfirmationModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Route Banner -->
                        @php
                            $gAsal = $gudangs->firstWhere('id', $gudang_asal_id);
                            $gTujuan = $gudangs->firstWhere('id', $gudang_tujuan_id);
                        @endphp
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light">
                                    <small class="text-muted d-block mb-1">Gudang Asal (Sumber Pemotongan):</small>
                                    <h6 class="fw-bold mb-0 text-danger">
                                        <i class="bi bi-box-arrow-right me-1"></i>{{ $gAsal->nama_gudang ?? '-' }}
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light">
                                    <small class="text-muted d-block mb-1">Gudang Tujuan (Penerima Penambahan):</small>
                                    <h6 class="fw-bold mb-0 text-success">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>{{ $gTujuan->nama_gudang ?? '-' }}
                                    </h6>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="small text-muted d-block">Tanggal Efektif Mutasi: <strong>{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</strong></span>
                            @if(filled($keterangan))
                                <span class="small text-muted d-block">Keterangan: <em>{{ $keterangan }}</em></span>
                            @endif
                        </div>

                        <!-- Items Table Preview -->
                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-boxes me-1"></i>Rincian Barang yang Akan Dipindahkan:</h6>
                        <div class="table-responsive border rounded-3 mb-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;">#</th>
                                        <th>Nama Produk</th>
                                        <th class="text-end pe-3">Jumlah Transfer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $idx => $it)
                                        @php
                                            $prod = $produks->firstWhere('id', $it['produk_id']);
                                        @endphp
                                        @if ($prod)
                                            <tr>
                                                <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if ($prod->foto_url)
                                                            <img src="{{ $prod->foto_url }}" alt="{{ $prod->nama }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 32px; height: 32px;">
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 32px; height: 32px;">
                                                                <i class="bi bi-image" style="font-size: 0.8rem;"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-semibold text-dark">{{ $prod->nama }}</div>
                                                            <small class="text-muted font-monospace">{{ $prod->kode_katalog ?? '-' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end pe-3 fw-bold text-primary font-monospace">
                                                    {{ number_format((float) $it['jumlah'], 2, ',', '.') }} {{ $prod->satuan->label() }}
                                                    @php
                                                        $konvConfirm = $prod->getKonversiFisik((float) $it['jumlah']);
                                                    @endphp
                                                    @if ($konvConfirm && $konvConfirm['has_conversion'])
                                                        <div class="small text-muted font-monospace fw-normal" style="font-size: 0.72rem;">
                                                            ({{ $konvConfirm['badge_text'] }})
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="ps-3 fw-bold text-end">Total Volume Kuantitas:</td>
                                        <td class="text-end pe-3 fw-bold text-primary font-monospace fs-6">
                                            {{ number_format($this->totalTransferredQuantity, 2, ',', '.') }} Unit
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="alert alert-info py-2 px-3 small border-0 mb-0 rounded-3">
                            <i class="bi bi-info-circle me-1"></i>Stok akan langsung dipotong di gudang asal dan ditambahkan ke gudang tujuan secara otomatis dan aman (transaksional).
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="closeConfirmationModal" class="btn btn-secondary">
                            Periksa Kembali
                        </button>
                        <button
                            type="button"
                            wire:click="save"
                            wire:loading.attr="disabled"
                            class="btn btn-primary px-4 fw-bold"
                        >
                            <span wire:loading.remove wire:target="save">
                                <i class="bi bi-check-circle me-1"></i> Ya, Eksekusi Transfer Sekarang
                            </span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Memproses Mutasi...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Kalkulator Kemasan Cerdas Lapangan -->
    @if($isPackagingCalcOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1070;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white py-3">
                        <h6 class="modal-title fw-bold">
                            <i class="bi bi-calculator me-1"></i> Kalkulator Kemasan Fisik Transfer Gudang
                        </h6>
                        <button type="button" wire:click="closePackagingCalculator" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <div class="fw-bold text-dark fs-6">{{ $calcItemName }}</div>
                            @php
                                $isRollModal = strcasecmp($calcKemasanLabel ?? '', 'roll') === 0 || str_contains(strtolower($calcSatuan ?? ''), 'roll');
                                $isPvcModal = !$isRollModal && str_contains(strtolower($calcSatuan ?? ''), 'meter') && !empty($calcPanjangMeter) && !empty($calcIsiPerKemasan);
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
                                    Satuan Sistem: <strong>{{ $calcSatuan }}</strong>
                                </span>
                            </div>

                            <!-- Info Stok Gudang Asal & Tombol Pindahkan Dus Utuh -->
                            @if($calcStokAsal !== null)
                                @php
                                    $pIdCalc = (int) ($items[$calcItemIndex]['produk_id'] ?? 0);
                                    $pCalc = $produks->firstWhere('id', $pIdCalc);
                                    $konvAsal = $pCalc ? $pCalc->getKonversiFisik((float) $calcStokAsal) : null;
                                @endphp
                                <div class="alert alert-secondary py-2 px-3 mt-2 mb-0 d-flex justify-content-between align-items-center flex-wrap gap-2 rounded-3 border">
                                    <div class="small">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>Stok Gudang Asal:
                                        <strong class="text-dark">{{ number_format($calcStokAsal, 2, ',', '.') }} {{ $calcSatuan }}</strong>
                                        @if($konvAsal && $konvAsal['has_conversion'])
                                            <span class="text-muted ms-1">({{ $konvAsal['badge_text'] }})</span>
                                        @endif
                                    </div>
                                    @if($konvAsal && !empty($konvAsal['jumlah_kemasan_utuh']) && $konvAsal['jumlah_kemasan_utuh'] > 0)
                                        <button
                                            type="button"
                                            wire:click="fillAllFullBoxesFromSource"
                                            class="btn btn-outline-primary btn-sm py-0 px-2 fw-semibold"
                                            style="font-size: 0.75rem;"
                                            title="Pindahkan {{ $konvAsal['jumlah_kemasan_utuh'] }} {{ $calcKemasanLabel ?: 'Dus' }} utuh"
                                        >
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Pindahkan {{ $konvAsal['jumlah_kemasan_utuh'] }} {{ $calcKemasanLabel ?: 'Dus' }} Utuh
                                        </button>
                                    @endif
                                </div>
                            @endif
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
                        <div class="card border {{ $this->isCalcQtyOverStock ? 'border-danger bg-danger bg-opacity-10' : 'border-primary-subtle bg-primary bg-opacity-10' }} p-3 rounded-3 mb-3">
                            @if($isRollModal)
                                <div class="small fw-semibold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }} mb-2">
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Roll ke Satuan Meter Sistem:
                                </div>
                                <div class="bg-white rounded p-2 mb-2 border {{ $this->isCalcQtyOverStock ? 'border-danger' : 'border-primary-subtle' }} small font-monospace">
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• {{ $jmlKms }} Roll × {{ $isiPck }}m:</span>
                                        <span class="fw-bold text-dark">{{ number_format($jmlKms * $isiPck, 2, ',', '.') }} m</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>• {{ $jmlEcr }} Meter Eceran Lepas:</span>
                                        <span class="fw-bold text-dark">{{ number_format($jmlEcr, 2, ',', '.') }} m</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-dark border-top pt-1">
                                        <span><strong>Total Masuk ke Transfer:</strong></span>
                                        <span class="fw-bold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }}">{{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} m</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="small text-muted font-monospace">
                                        Rumus: ({{ $jmlKms }} × {{ $isiPck }}m) + {{ $jmlEcr }}m =
                                    </div>
                                    <div class="fs-4 fw-bold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }}">
                                        {{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} {{ $calcSatuan }}
                                    </div>
                                </div>
                            @elseif($isPvcModal)
                                <div class="small fw-semibold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }} mb-2">
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Lapangan ke Satuan Sistem:
                                </div>
                                <div class="bg-white rounded p-2 mb-2 border {{ $this->isCalcQtyOverStock ? 'border-danger' : 'border-primary-subtle' }} small font-monospace">
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
                                        <span class="fw-bold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }}">{{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} m</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="small text-muted font-monospace">
                                        Rumus: (({{ $jmlKms }} × {{ $isiPck }}) + {{ $jmlEcr }}) × {{ $pjgMtr }}m =
                                    </div>
                                    <div class="fs-4 fw-bold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }}">
                                        {{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} {{ $calcSatuan }}
                                    </div>
                                </div>
                            @elseif($isUnitSameAsKemasan && $isiPck > 0)
                                <div class="small fw-semibold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }} mb-2">
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Eceran Biji ke Satuan Dus:
                                </div>
                                <div class="bg-white rounded p-2 mb-2 border {{ $this->isCalcQtyOverStock ? 'border-danger' : 'border-primary-subtle' }} small font-monospace">
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
                                        <span class="fw-bold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }}">{{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} {{ $calcSatuan }}</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="small text-muted font-monospace">
                                        Rumus: {{ $jmlKms }} + ({{ $jmlEcr }} / {{ number_format($isiPck, 0, ',', '.') }}) =
                                    </div>
                                    <div class="fs-4 fw-bold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }}">
                                        {{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} {{ $calcSatuan }}
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
                                    <div class="fs-4 fw-bold {{ $this->isCalcQtyOverStock ? 'text-danger' : 'text-primary' }}">
                                        {{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} {{ $calcSatuan }}
                                    </div>
                                </div>
                            @endif


                            @if($this->isCalcQtyOverStock)
                                <div class="small text-danger fw-semibold mt-2 pt-2 border-top border-danger border-opacity-25">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    Kuantitas transfer ({{ number_format($this->computedPhysicalTransferQty, 2, ',', '.') }} {{ $calcSatuan }}) melebihi stok tersedia di gudang asal ({{ number_format($calcStokAsal, 2, ',', '.') }} {{ $calcSatuan }})!
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" wire:click="closePackagingCalculator" class="btn btn-outline-secondary">
                                Batal
                            </button>
                            <button
                                type="button"
                                wire:click="applyPackagingCalculation"
                                class="btn btn-primary px-4 fw-semibold"
                                @disabled($this->isCalcQtyOverStock || $this->computedPhysicalTransferQty <= 0)
                            >
                                <i class="bi bi-check-lg me-1"></i> Terapkan ke Kuantitas
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

