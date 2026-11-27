<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Catat Hasil Produksi</h4>
            <p class="text-muted small mb-0">Tambahkan stok barang jadi hasil cetak/produksi workshop ke gudang penyimpanan (beserta pemotongan bahan baku opsional).</p>
        </div>
        <a href="{{ route('produksi.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @error('general')
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @enderror

    @if ($errors->any() && !$errors->has('general'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Terdapat kesalahan pengisian:</h6>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $totalOutputUnits = 0.0;
        $totalEstimasiOutputRp = 0.0;
        foreach ($items as $it) {
            $pId = (int) ($it['produk_id'] ?? 0);
            $qty = (float) ($it['jumlah'] ?? 0);
            if ($pId > 0 && $qty > 0) {
                $totalOutputUnits += $qty;
                $hpp = $selectedProductsMap[$pId]['hpp'] ?? 0;
                $totalEstimasiOutputRp += ($qty * $hpp);
            }
        }

        $totalMaterialUnits = 0.0;
        $totalEstimasiMaterialRp = 0.0;
        if ($useMaterials) {
            foreach ($materials as $mat) {
                $mId = (int) ($mat['produk_id'] ?? 0);
                $mQty = (float) ($mat['jumlah'] ?? 0);
                if ($mId > 0 && $mQty > 0) {
                    $totalMaterialUnits += $mQty;
                    $mHpp = $selectedProductsMap[$mId]['hpp'] ?? 0;
                    $totalEstimasiMaterialRp += ($mQty * $mHpp);
                }
            }
        }
    @endphp

    <form wire:submit="save">
        <!-- Header Information Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-info-circle me-1"></i> Informasi Batch & Gudang Produksi
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Selesai Produksi <span class="text-danger">*</span></label>
                        <input type="date" wire:model="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Gudang Tujuan Masuk <span class="text-danger">*</span></label>
                        <select wire:model.live="gudang_id" class="form-select @error('gudang_id') is-invalid @enderror">
                            <option value="">-- Pilih Gudang Penerima --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        @error('gudang_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Shift / Sesi Produksi</label>
                        <select wire:model="shift" class="form-select">
                            <option value="">-- Pilih Shift (Opsional) --</option>
                            <option value="Shift Pagi">Shift Pagi</option>
                            <option value="Shift Siang">Shift Siang</option>
                            <option value="Shift Malam / Lembur">Shift Malam / Lembur</option>
                            <option value="Borongan Harian">Borongan Harian</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kepala Tukang / Pelaksana</label>
                        <input type="text" wire:model="penanggung_jawab" class="form-control" placeholder="Contoh: Tim Cetak Pak Budi">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Catatan / Keterangan Batch</label>
                        <input type="text" wire:model="catatan" class="form-control" placeholder="Catatan kualitas cetakan / nomor batch / keterangan tambahan...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Output Barang Jadi (Quick Search Bar + Multi-Add Catalog) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="card-title fw-bold mb-0 text-primary">
                            <i class="bi bi-box-seam me-1"></i> 1. Rincian Barang Jadi yang Dihasilkan (Stok Masuk)
                        </h6>
                        <small class="text-muted">Ketik nama barang di bawah atau gunakan tombol Pilih Banyak dari Katalog.</small>
                    </div>
                    <div class="d-flex gap-2 w-100 w-sm-auto">
                        @if (count($items) > 0)
                            <button type="button" wire:click="clearAllItems" wire:confirm="Yakin ingin mengosongkan semua baris barang?" class="btn btn-sm btn-outline-danger flex-fill flex-sm-grow-0">
                                <i class="bi bi-trash3 me-1"></i>Kosongkan Semua
                            </button>
                        @endif
                        <button type="button" wire:click="openCatalogModal('output')" class="btn btn-sm btn-primary flex-fill flex-sm-grow-0">
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
                            wire:model.live.debounce.250ms="quickSearchOutput"
                            class="form-control form-control-lg fs-6"
                            placeholder="Ketik nama produk atau kode katalog (contoh: 'Vifon', 'Lis', 'Wallpanel')... tekan untuk tambah!"
                        >
                        @if (filled($quickSearchOutput))
                            <button type="button" wire:click="$set('quickSearchOutput', '')" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>

                    <!-- Quick Search Results Dropdown -->
                    @if (filled($quickSearchOutput) && count($outputSearchResults) > 0)
                        <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border border-primary" style="max-height: 280px; overflow-y: auto;">
                            @foreach ($outputSearchResults as $sp)
                                <button
                                    type="button"
                                    wire:click="quickAddOutputProduct({{ $sp->id }})"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small"
                                >
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        @if ($sp->foto_url)
                                            <img src="{{ $sp->foto_url }}" alt="{{ $sp->nama }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;" loading="lazy">
                                        @else
                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <strong class="text-dark fs-6 text-truncate d-block">{{ $sp->nama }}</strong>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <span class="badge bg-light text-secondary border font-monospace me-1">{{ $sp->kode_katalog ?? 'Tanpa SKU' }}</span>
                                                {{ $sp->kategori?->nama_kategori ?? '-' }} &bull; {{ $sp->merek?->nama_merek ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <span class="badge bg-light text-dark border me-2">{{ $sp->satuan->label() }}</span>
                                        <span class="fw-bold text-primary">Rp {{ number_format((float) $sp->harga_beli, 0, ',', '.') }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif (filled($quickSearchOutput))
                        <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3">
                            <div class="list-group-item py-3 px-3 text-center text-muted small">
                                Produk dengan kata kunci '<strong>{{ $quickSearchOutput }}</strong>' tidak ditemukan.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Table of Line Items -->
            <div class="card-body p-0">
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table table-hover align-middle mb-0 table-mobile-cards">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center text-muted">#</th>
                                <th style="min-width: 320px;">Produk / Motif Barang Jadi</th>
                                <th style="min-width: 240px;">Kuantitas Output <span class="text-danger">*</span></th>
                                <th style="min-width: 150px;" class="text-end">HPP Satuan</th>
                                <th style="min-width: 160px;" class="text-end">Estimasi Nilai</th>
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
                                        <h6 class="fw-semibold text-dark">Belum ada barang jadi dalam daftar hasil produksi</h6>
                                        <p class="small text-muted mb-3">
                                            Ketik nama produk di kolom pencarian di atas atau klik tombol katalog untuk memilih produk sekaligus.
                                        </p>
                                        <button type="button" wire:click="openCatalogModal('output')" class="btn btn-sm btn-primary">
                                            <i class="bi bi-grid-3x3-gap me-1"></i>Buka Katalog Produk
                                        </button>
                                    </td>
                                </tr>
                            @else
                                @foreach ($items as $index => $item)
                                    @php
                                        $pId = (int) ($item['produk_id'] ?? 0);
                                        $pInfo = $selectedProductsMap[$pId] ?? null;
                                        $pModel = $pInfo['model'] ?? null;
                                        $qty = (float) ($item['jumlah'] ?? 0);
                                        $konv = ($pModel && $qty > 0) ? $pModel->getKonversiFisik($qty) : null;
                                        $rowHpp = $pInfo['hpp'] ?? 0;
                                        $rowSubtotal = $qty * $rowHpp;
                                    @endphp
                                    <tr wire:key="prod-output-row-{{ $index }}-{{ $pId }}">
                                        <!-- Col 1: # -->
                                        <td class="text-center text-muted fw-semibold d-none d-md-table-cell">{{ $index + 1 }}</td>

                                        <!-- Col 2: Nama Produk & Metadata (Identical style to PembelianCreate) -->
                                        <td data-label="Baris #{{ $index + 1 }} — Produk">
                                            @if ($pModel)
                                                <div class="d-flex align-items-start gap-2">
                                                    <!-- Image Thumbnail with Right-Side Hover Preview & Lightbox (Never clipped by table header!) -->
                                                    <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                        @if ($pModel->foto_url)
                                                            <img
                                                                src="{{ $pModel->foto_url }}"
                                                                alt="{{ $pModel->nama }}"
                                                                class="rounded border object-fit-cover"
                                                                style="width: 38px; height: 38px; cursor: pointer;"
                                                                loading="lazy"
                                                                @mouseenter="showPreview = true"
                                                                @mouseleave="showPreview = false"
                                                                wire:click.stop="openImageModal({{ $pModel->id }})"
                                                            >
                                                            <div
                                                                x-show="showPreview"
                                                                class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                                style="left: 46px; top: 50%; transform: translateY(-50%); z-index: 1085; width: 200px; pointer-events: none;"
                                                                x-cloak
                                                            >
                                                                <img src="{{ $pModel->foto_url }}" alt="{{ $pModel->nama }}" class="img-fluid rounded" style="max-height: 170px; object-fit: contain;">
                                                                <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $pModel->nama }}</div>
                                                            </div>
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;" title="Belum ada foto">
                                                                <i class="bi bi-image" style="font-size: 1rem;"></i>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="min-w-0 flex-grow-1">
                                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                                            <div class="fw-bold text-dark fs-6">{{ $pModel->nama }}</div>
                                                            <button
                                                                type="button"
                                                                wire:click="openCatalogModal('output', {{ $index }})"
                                                                class="btn btn-link btn-sm p-0 text-secondary text-decoration-none flex-shrink-0"
                                                                style="font-size: 0.74rem;"
                                                                title="Ganti produk pada baris ini"
                                                            >
                                                                <i class="bi bi-arrow-repeat me-1"></i>Ganti
                                                            </button>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap" style="font-size: 0.78rem;">
                                                            @if (!empty($pModel->kode_katalog))
                                                                <span class="badge bg-light text-secondary border font-monospace">{{ $pModel->kode_katalog }}</span>
                                                            @endif
                                                            @if ($pModel->kategori)
                                                                <span class="badge bg-light text-dark border">{{ $pModel->kategori->nama_kategori }}</span>
                                                            @endif
                                                            <span class="badge bg-light text-dark border">{{ $pModel->satuan->label() }}</span>
                                                            <span class="text-muted">
                                                                <i class="bi bi-box me-1"></i>Stok Gudang: <strong>{{ number_format((float) ($pInfo['stok'] ?? 0), 0, ',', '.') }}</strong>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="openCatalogModal('output', {{ $index }})"
                                                    class="btn btn-outline-primary btn-sm"
                                                >
                                                    <i class="bi bi-grid-3x3-gap me-1"></i> Pilih Produk dari Katalog...
                                                </button>
                                            @endif
                                            @error('items.' . $index . '.produk_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <!-- Col 3: Kuantitas Output -->
                                        <td data-label="Kuantitas Output">
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="input-group input-group-sm" style="max-width: 200px;">
                                                        <input
                                                            type="number"
                                                            step="any"
                                                            min="0.001"
                                                            wire:model.live.debounce.300ms="items.{{ $index }}.jumlah"
                                                            class="form-control text-end fw-bold px-2 @error('items.' . $index . '.jumlah') is-invalid @enderror"
                                                            placeholder="0"
                                                            style="min-width: 95px;"
                                                        >
                                                        <span class="input-group-text bg-light text-muted px-2" style="font-size: 0.75rem;">
                                                            {{ $pModel?->satuan?->label() ?? 'Unit' }}
                                                        </span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        wire:click="openRollConverter({{ $index }})"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        title="Kalkulator Konversi Kemasan ({{ $pModel?->kemasan ?: 'Dus/Roll' }})"
                                                    >
                                                        <i class="bi bi-calculator"></i>
                                                    </button>
                                                </div>
                                                @if ($konv && !empty($konv['has_conversion']))
                                                    <div class="mt-1">
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace" style="font-size: 0.72rem;">
                                                            {{ $konv['badge_text'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            @error('items.' . $index . '.jumlah')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <!-- Col 4: HPP Satuan -->
                                        <td class="text-end text-muted font-monospace" data-label="HPP Satuan">
                                            Rp {{ number_format($rowHpp, 0, ',', '.') }}
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                per {{ $pModel?->satuan?->value ?? 'unit' }}
                                            </div>
                                        </td>

                                        <!-- Col 5: Estimasi Nilai -->
                                        <td class="text-end fw-bold text-dark font-monospace" data-label="Estimasi Nilai">
                                            Rp {{ number_format($rowSubtotal, 0, ',', '.') }}
                                        </td>

                                        <!-- Col 6: Aksi -->
                                        <td class="text-center">
                                            <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-sm btn-outline-danger" title="Hapus baris">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        @if (!empty($items))
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total Hasil Produksi (Barang Jadi Masuk):</td>
                                    <td class="fw-bold text-success">{{ number_format($totalOutputUnits, 2, ',', '.') }} Unit</td>
                                    <td></td>
                                    <td class="text-end fw-bold text-success font-monospace fs-6">Rp {{ number_format($totalEstimasiOutputRp, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 2: Optional Raw Materials Consumed (Pemakaian Internal) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-warning-emphasis">
                        <i class="bi bi-layers-half me-1"></i> 2. Pemakaian Bahan Baku dari Gudang (Opsional)
                    </h6>
                    <small class="text-muted">Aktifkan jika produksi ini mengambil bahan baku dari gudang yang ingin langsung dipotong stoknya (`Pemakaian Internal`).</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if ($useMaterials)
                        <button type="button" wire:click="openCatalogModal('material')" class="btn btn-sm btn-warning text-dark fw-semibold">
                            <i class="bi bi-grid-3x3-gap me-1"></i>+ Pilih Bahan dari Katalog
                        </button>
                    @endif
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="useMaterialsSwitch" wire:click="toggleUseMaterials" @checked($useMaterials)>
                        <label class="form-check-label fw-semibold small" for="useMaterialsSwitch">
                            {{ $useMaterials ? 'Aktif' : 'Catat Bahan Baku' }}
                        </label>
                    </div>
                </div>
            </div>

            @if ($useMaterials)
                <div class="card-body border-top pt-3">
                    <!-- Quick Search for Raw Materials -->
                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-warning text-dark"><i class="bi bi-search"></i></span>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="quickSearchMaterial"
                                class="form-control"
                                placeholder="Ketik nama bahan baku atau SKU untuk tambah cepat, atau klik '+ Pilih Bahan dari Katalog'..."
                            >
                            @if (filled($quickSearchMaterial))
                                <button type="button" wire:click="$set('quickSearchMaterial', '')" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @endif
                        </div>

                        @if (filled($quickSearchMaterial) && count($materialSearchResults) > 0)
                            <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border border-warning" style="max-height: 260px; overflow-y: auto;">
                                @foreach ($materialSearchResults as $mp)
                                    <button
                                        type="button"
                                        wire:click="quickAddMaterialProduct({{ $mp->id }})"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small"
                                    >
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            @if ($mp->foto_url)
                                                <img src="{{ $mp->foto_url }}" alt="{{ $mp->nama }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;">
                                            @else
                                                <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                    <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <strong class="text-dark fs-6 text-truncate d-block">{{ $mp->nama }}</strong>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    <span class="badge bg-light text-secondary border font-monospace me-1">{{ $mp->kode_katalog ?? 'No SKU' }}</span>
                                                    {{ $mp->kategori?->nama_kategori ?? '-' }}
                                                </div>
                                            </div>
                                        </div>
                                        <span class="btn btn-sm btn-warning py-0 px-2">
                                            <i class="bi bi-plus-lg me-1"></i>Pakai Bahan
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive" style="overflow: visible;">
                        <table class="table table-hover align-middle mb-0 table-mobile-cards">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th style="min-width: 300px;">Bahan Baku yang Dipakai</th>
                                    <th style="width: 220px;">Jumlah Terpakai</th>
                                    <th class="text-end" style="width: 150px;">HPP Satuan</th>
                                    <th class="text-end" style="width: 160px;">Biaya Bahan (HPP)</th>
                                    <th style="width: 70px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($materials as $mIdx => $mat)
                                    @php
                                        $mId = (int) ($mat['produk_id'] ?? 0);
                                        $mInfo = $selectedProductsMap[$mId] ?? null;
                                        $mModel = $mInfo['model'] ?? null;
                                        $mStok = (float) ($mInfo['stok'] ?? 0);
                                        $mQty = (float) ($mat['jumlah'] ?? 0);
                                        $mHpp = (float) ($mInfo['hpp'] ?? 0);
                                        $mCost = $mQty * $mHpp;
                                        $isInsufficient = $mId > 0 && $mQty > $mStok;
                                    @endphp
                                    <tr wire:key="prod-mat-row-{{ $mIdx }}-{{ $mId }}">
                                        <td class="text-center text-muted fw-semibold d-none d-md-table-cell">{{ $mIdx + 1 }}</td>
                                        <td data-label="Bahan #{{ $mIdx + 1 }}">
                                            @if ($mModel)
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                        @if ($mModel->foto_url)
                                                            <img
                                                                src="{{ $mModel->foto_url }}"
                                                                alt="{{ $mModel->nama }}"
                                                                class="rounded border object-fit-cover"
                                                                style="width: 38px; height: 38px; cursor: pointer;"
                                                                @mouseenter="showPreview = true"
                                                                @mouseleave="showPreview = false"
                                                                wire:click.stop="openImageModal({{ $mModel->id }})"
                                                            >
                                                            <div
                                                                x-show="showPreview"
                                                                class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                                style="left: 46px; top: 50%; transform: translateY(-50%); z-index: 1085; width: 200px; pointer-events: none;"
                                                                x-cloak
                                                            >
                                                                <img src="{{ $mModel->foto_url }}" alt="{{ $mModel->nama }}" class="img-fluid rounded" style="max-height: 170px; object-fit: contain;">
                                                                <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $mModel->nama }}</div>
                                                            </div>
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;">
                                                                <i class="bi bi-image"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-grow-1">
                                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                                            <div class="fw-bold text-dark fs-6">{{ $mModel->nama }}</div>
                                                            <button
                                                                type="button"
                                                                wire:click="openCatalogModal('material', {{ $mIdx }})"
                                                                class="btn btn-link btn-sm p-0 text-secondary text-decoration-none"
                                                                style="font-size: 0.74rem;"
                                                            >
                                                                <i class="bi bi-arrow-repeat me-1"></i>Ganti
                                                            </button>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap" style="font-size: 0.78rem;">
                                                            <span class="badge bg-light text-secondary border font-monospace">{{ $mModel->kode_katalog ?? 'No SKU' }}</span>
                                                            <span class="badge bg-light text-dark border">{{ $mModel->satuan->label() }}</span>
                                                            <span class="{{ $isInsufficient ? 'text-danger fw-bold' : 'text-muted' }}">
                                                                <i class="bi bi-box me-1"></i>Stok: <strong>{{ number_format($mStok, 0, ',', '.') }}</strong>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="openCatalogModal('material', {{ $mIdx }})"
                                                    class="btn btn-outline-warning text-dark btn-sm"
                                                >
                                                    <i class="bi bi-grid-3x3-gap me-1"></i> Pilih Bahan Baku dari Katalog...
                                                </button>
                                            @endif
                                        </td>
                                        <td data-label="Jumlah Terpakai">
                                            <div class="input-group input-group-sm" style="max-width: 190px;">
                                                <input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    wire:model.live.debounce.250ms="materials.{{ $mIdx }}.jumlah"
                                                    class="form-control text-end fw-bold text-danger px-2"
                                                    placeholder="0"
                                                >
                                                <span class="input-group-text bg-light text-muted px-2" style="font-size: 0.75rem;">
                                                    {{ $mModel?->satuan?->label() ?? 'Unit' }}
                                                </span>
                                            </div>
                                            @if ($isInsufficient)
                                                <small class="text-danger d-block mt-1" style="font-size: 0.72rem;">
                                                    <i class="bi bi-exclamation-circle me-1"></i>Melebihi stok gudang saat ini
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-end text-muted font-monospace" data-label="HPP Satuan">
                                            Rp {{ number_format($mHpp, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end fw-bold text-danger font-monospace" data-label="Biaya Bahan (HPP)">
                                            Rp {{ number_format($mCost, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" wire:click="removeMaterial({{ $mIdx }})" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <p class="small mb-2">Belum ada bahan baku yang dipilih.</p>
                                            <button type="button" wire:click="openCatalogModal('material')" class="btn btn-sm btn-outline-warning text-dark">
                                                <i class="bi bi-grid-3x3-gap me-1"></i>Pilih Bahan Baku dari Katalog
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (!empty($materials))
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Total Bahan Baku Dipotong (`Pemakaian Internal`):</td>
                                        <td class="fw-bold text-danger">{{ number_format($totalMaterialUnits, 2, ',', '.') }} Unit</td>
                                        <td></td>
                                        <td class="text-end fw-bold text-danger font-monospace fs-6">Rp {{ number_format($totalEstimasiMaterialRp, 0, ',', '.') }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('produksi.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-circle me-1"></i> Simpan Output Produksi
            </button>
        </div>
    </form>

    <!-- ===================================================================== -->
    <!-- MODAL MULTI-PILIH KATALOG (Identik dengan PembelianCreate)            -->
    <!-- ===================================================================== -->
    @if ($isCatalogModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2 py-sm-3 px-3">
                        <div class="min-w-0">
                            <h6 class="modal-title fw-bold mb-0 text-truncate">
                                <i class="bi bi-grid-3x3-gap me-2"></i>
                                @if ($replaceRowIndex !== null)
                                    Ganti Produk pada Baris #{{ $replaceRowIndex + 1 }}
                                @elseif ($catalogTarget === 'material')
                                    Pilih Bahan Baku Produksi dari Katalog
                                @else
                                    Pilih Produk dari Katalog
                                @endif
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

                        @if ($replaceRowIndex === null)
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
                        @endif
                    </div>

                    <!-- Scrollable Product List Body (Single Native Touch Scroll) -->
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse ($catalogProducts as $prod)
                                @php
                                    $isSelected = in_array($prod->id, $catalogSelectedProductIds, true);
                                    $currentStok = (float) ($prod->stok_gudang_aktif ?? 0);
                                    $minStok = (float) $prod->stok_minimum;
                                    $statusStok = \App\Domains\Inventory\Enums\StatusStok::determine($currentStok, $minStok);
                                @endphp
                                <div
                                    wire:key="prod-create-cat-{{ $prod->id }}"
                                    wire:click="toggleCatalogProduct({{ $prod->id }})"
                                    class="list-group-item list-group-item-action py-2.5 px-3 {{ $isSelected ? 'bg-primary-subtle border-start border-4 border-primary' : '' }}"
                                    style="cursor: pointer;"
                                >
                                    <div class="d-flex align-items-start gap-2.5">
                                        @if ($replaceRowIndex === null)
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
                                        @endif

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
                                                     style="left: 52px; top: 50%; transform: translateY(-50%); z-index: 1085; width: 200px; pointer-events: none;"
                                                     x-cloak>
                                                    <img src="{{ $prod->foto_url }}" alt="{{ $prod->nama }}" class="img-fluid rounded" style="max-height: 170px; object-fit: contain;">
                                                    <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $prod->nama }}</div>
                                                </div>
                                            @else
                                                <div class="bg-light rounded-3 border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                                    <i class="bi bi-image" style="font-size: 1.1rem;"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Full-Width Product Details -->
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
                            @if ($replaceRowIndex !== null)
                                Klik salah satu produk di atas untuk memilih.
                            @else
                                Produk terpilih akan dimasukkan ke daftar hasil produksi.
                            @endif
                        </span>
                        <div class="d-flex gap-2 w-100 w-sm-auto ms-sm-auto">
                            <button type="button" class="btn btn-outline-secondary bg-white flex-fill flex-sm-grow-0" wire:click="closeCatalogModal">Tutup</button>
                            @if ($replaceRowIndex === null)
                                <button
                                    type="button"
                                    class="btn btn-primary fw-bold flex-fill flex-sm-grow-0"
                                    wire:click="addSelectedCatalogProducts"
                                    {{ count($catalogSelectedProductIds) === 0 ? 'disabled' : '' }}
                                >
                                    <i class="bi bi-check2-circle me-1"></i>Tambahkan ({{ count($catalogSelectedProductIds) }}) Produk
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================== -->
    <!-- MODAL LIGHTBOX PRATINJAU FOTO PRODUK (Identik dengan PembelianCreate) -->
    <!-- ===================================================================== -->
    @if ($isImageModalOpen && $imageModalProduk)
        @php $modalProd = $imageModalProduk; @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 1080;" aria-modal="true" role="dialog" wire:click.self="closeImageModal">
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

    <!-- ===================================================================== -->
    <!-- MODAL KALKULATOR KEMASAN CERDAS (Identik dengan PembelianCreate)      -->
    <!-- ===================================================================== -->
    @if ($isRollModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1070;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white py-3">
                        <h6 class="modal-title fw-bold">
                            <i class="bi bi-calculator me-1"></i> Kalkulator Kemasan Fisik Produksi ({{ $converterUnitLabel }})
                        </h6>
                        <button type="button" wire:click="closeRollConverter" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="card bg-light border-0 p-3 mb-3 rounded-3">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-dark">
                                        Jumlah {{ $converterUnitLabel }} Utuh
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        wire:model.live.debounce.150ms="rollCount"
                                        class="form-control form-control-lg text-center fw-bold"
                                        placeholder="0"
                                        autofocus
                                    >
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-dark">
                                        Isi / Panjang per {{ $converterUnitLabel }}
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        wire:model.live.debounce.150ms="rollLength"
                                        class="form-control form-control-lg text-center fw-bold"
                                        placeholder="0"
                                    >
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-dark">
                                        Tambahan Eceran / Lepasan (Opsional)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        wire:model.live.debounce.150ms="eceranCount"
                                        class="form-control text-center fw-bold"
                                        placeholder="0"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="card border border-primary-subtle bg-primary bg-opacity-10 p-3 rounded-3 mb-3">
                            <div class="small text-muted mb-1">Hasil Konversi ke Satuan Sistem:</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small text-dark font-monospace">
                                    ({{ (float) $rollCount }} × {{ (float) $rollLength }}) + {{ (float) ($eceranCount ?: 0) }} =
                                </div>
                                <div class="fs-4 fw-bold text-primary">
                                    {{ number_format(((float) $rollCount * (float) $rollLength) + (float) ($eceranCount ?: 0), 2, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" wire:click="closeRollConverter" class="btn btn-outline-secondary">
                                Batal
                            </button>
                            <button type="button" wire:click="applyRollConversion" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-check-lg me-1"></i> Terapkan ke Baris
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
