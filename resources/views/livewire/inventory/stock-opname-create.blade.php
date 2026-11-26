<div>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 8mm 10mm 8mm;
            }
            #sidebar,
            aside,
            #topbar,
            .sidebar-overlay,
            .no-print,
            .btn,
            .pagination,
            .alert,
            .modal,
            .modal-backdrop,
            header,
            footer {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            #app-wrapper,
            #main-content,
            .content-body {
                display: block !important;
                position: static !important;
                width: 100% !important;
                min-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            body {
                background: #fff !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
                font-size: 8.5pt !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .print-count-sheet-only {
                display: block !important;
                width: 100% !important;
            }
            .count-sheet-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 6px !important;
            }
            .count-sheet-table thead {
                display: table-header-group !important;
            }
            .count-sheet-table tr {
                page-break-inside: avoid !important;
            }
            .count-sheet-table th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 700 !important;
                font-size: 7.5pt !important;
                text-transform: uppercase !important;
                border: 1px solid #334155 !important;
                padding: 4px 3px !important;
                text-align: center !important;
                vertical-align: middle !important;
            }
            .count-sheet-table td {
                border: 1px solid #475569 !important;
                padding: 3px 4px !important;
                vertical-align: middle !important;
                font-size: 8pt !important;
                line-height: 1.15 !important;
            }
            .count-sheet-write-box {
                height: 22px !important;
                min-height: 22px !important;
                background-color: #fbfbfb !important;
            }
            .count-sheet-signature {
                page-break-inside: avoid !important;
                margin-top: 15px !important;
                padding-top: 8px !important;
                border-top: 1px solid #64748b !important;
            }
        }
        .print-count-sheet-only {
            display: none;
        }
    </style>

    <!-- Printable Official Count Sheet (Visible ONLY on print) -->
    <div class="print-count-sheet-only">
        <div style="border-bottom: 2px solid #0f172a; padding-bottom: 6px; margin-bottom: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-weight: 800; margin: 0; font-size: 13pt; letter-spacing: 0.5px; text-transform: uppercase; color: #0f172a;">
                        LEMBAR KERJA HITUNG FISIK GUDANG (COUNT SHEET)
                    </h3>
                    <div style="font-size: 8.5pt; font-weight: 600; color: #475569;">
                        NATURAL GYPSUM & PLAFON PVC BY PANDAKONG
                    </div>
                </div>
                <div style="text-align: right;">
                    <span style="display: inline-block; padding: 2px 7px; border: 1.5px solid #0f172a; font-weight: 800; font-size: 8pt; text-transform: uppercase; border-radius: 4px; background-color: {{ $countSheetMode === 'blind' ? '#f8fafc' : '#e0f2fe' }};">
                        {{ $countSheetMode === 'blind' ? 'BLIND COUNT (HITUNG BUTA)' : 'GUIDED COUNT (DENGAN STOK SISTEM)' }}
                    </span>
                    <div style="font-size: 8pt; color: #334155; margin-top: 2px;">
                        Tanggal: <strong>{{ Carbon\Carbon::parse($tanggal)->format('d F Y') }}</strong>
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 8pt; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #cbd5e1;">
                <div><strong>Lokasi Gudang:</strong> {{ $gudangs->firstWhere('id', $gudang_id)?->nama_gudang ?? '-' }}</div>
                <div><strong>Petugas Lapangan:</strong> .....................................................................</div>
                <div><strong>Total Item:</strong> {{ count($items) }} Produk</div>
            </div>
        </div>

        <table class="count-sheet-table">
            <thead>
                <tr>
                    <th style="width: 3%; text-align: center;">NO</th>
                    <th style="width: 12%;">KODE BARANG</th>
                    <th style="width: 33%;">NAMA PRODUK & SPESIFIKASI</th>
                    <th style="width: 14%;">KEMASAN PABRIK</th>
                    <th style="width: 7%; text-align: center;">SATUAN</th>
                    @if($countSheetMode === 'guided')
                        <th style="width: 9%; text-align: center;">STOK SISTEM</th>
                    @endif
                    <th style="width: 10%; text-align: center;">JML DUS / IKAT</th>
                    <th style="width: 9%; text-align: center;">ECERAN</th>
                    <th style="width: 11%; text-align: center;">TOTAL FISIK</th>
                    <th style="width: 11%;">CATATAN KONDISI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $it)
                    @php
                        $isPvc = str_contains(strtolower($it['satuan'] ?? ''), 'meter') && !empty($it['panjang_meter']) && !empty($it['isi_per_kemasan']);
                        $firstWordS = strtolower(strtok($it['satuan'] ?? '', " (/"));
                        $firstWordK = strtolower(strtok($it['kemasan'] ?? '', " (/"));
                        $isSameUnitK = ($firstWordK !== '' && (
                            $firstWordS === $firstWordK
                            || (str_contains(strtolower($it['satuan'] ?? ''), 'dus') && str_contains(strtolower($it['kemasan'] ?? ''), 'dus'))
                        ));
                        $isiVal = (float)($it['isi_per_kemasan'] ?? 0);
                        $pjgVal = (float)($it['panjang_meter'] ?? 0);
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td style="font-family: monospace; font-weight: 700;">{{ $it['kode_produk'] ?? '-' }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $it['nama_produk'] }}</div>
                            <div style="font-size: 7pt; color: #475569;">{{ $it['kategori'] ?? '' }} &bull; {{ $it['merek'] ?? '' }}</div>
                        </td>
                        <td>
                            @if(!empty($it['kemasan']) || !empty($it['isi_per_kemasan']))
                                <strong>{{ $it['kemasan'] ?? 'Kemasan' }}</strong>
                                @if($isPvc)
                                    <span style="font-size: 7.5pt; color: #1e3a8a;">({{ $isiVal }} Lbr @ {{ $pjgVal }}m = {{ $isiVal * $pjgVal }}m)</span>
                                @elseif($isSameUnitK)
                                    <span style="font-size: 7.5pt; color: #1e3a8a;">(isi {{ number_format($isiVal, 0, ',', '.') }} pcs)</span>
                                @elseif(!empty($it['isi_per_kemasan']))
                                    <span style="font-size: 7.5pt; color: #1e3a8a;">(isi {{ number_format($isiVal, 0, ',', '.') }})</span>
                                @endif
                                @if(!$isPvc && !empty($it['panjang_meter']))
                                    <span style="font-size: 7.5pt;">@ {{ $pjgVal }}m</span>
                                @endif
                            @else
                                <span style="color: #94a3b8;">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $it['satuan'] }}</td>
                        @if($countSheetMode === 'guided')
                            <td style="text-align: center; font-weight: bold; background-color: #f8fafc;">
                                {{ number_format((float)($it['stok_sistem'] ?? 0), 0, ',', '.') }}
                            </td>
                        @endif
                        <td class="count-sheet-write-box">&nbsp;</td>
                        <td class="count-sheet-write-box">&nbsp;</td>
                        <td class="count-sheet-write-box">&nbsp;</td>
                        <td class="count-sheet-write-box">&nbsp;</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $countSheetMode === 'guided' ? 10 : 9 }}" style="text-align: center; padding: 15px; color: #64748b;">
                            Belum ada produk yang dimuat ke dalam lembar hitung.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signature Block -->
        <div class="count-sheet-signature">
            <div style="display: flex; justify-content: space-between; text-align: center; font-size: 8pt;">
                <div style="width: 30%;">
                    <div>Staf Pemeriksa Lapangan,</div>
                    <div style="height: 38px;"></div>
                    <div style="font-weight: bold; text-decoration: underline;">................................................</div>
                    <div style="font-size: 7pt; color: #64748b;">Tgl: .......................................</div>
                </div>
                <div style="width: 30%;">
                    <div>Petugas Input Sistem (Operator),</div>
                    <div style="height: 38px;"></div>
                    <div style="font-weight: bold; text-decoration: underline;">{{ auth()->user()->name ?? 'Operator' }}</div>
                    <div style="font-size: 7pt; color: #64748b;">Tgl: {{ Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}</div>
                </div>
                <div style="width: 30%;">
                    <div>Kepala Gudang / Owner,</div>
                    <div style="height: 38px;"></div>
                    <div style="font-weight: bold; text-decoration: underline;">................................................</div>
                    <div style="font-size: 7pt; color: #64748b;">Tgl: .......................................</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Screen-Only Application UI -->
    <div class="no-print">
        <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="{{ route('inventory.stock-opname.index') }}" class="btn btn-outline-secondary btn-sm py-0 px-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h4 class="mb-0 font-weight-bold text-dark">Mulai Stok Opname</h4>
                </div>
                <p class="text-muted mb-0 small">Hitung fisik inventaris gudang dan lakukan penyesuaian otomatis ke mutasi sistem.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
                @if(count($items) > 0)
                    <button type="button" wire:click="openPrintCountSheetModal" class="btn btn-outline-secondary btn-sm shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-printer me-1"></i> Cetak Lembar Hitung
                    </button>
                    <button type="button" wire:click="exportTemplate" class="btn btn-outline-success btn-sm shadow-sm flex-fill flex-md-grow-0" title="Download template Excel terisi barang">
                        <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
                    </button>
                    <button type="button" wire:click="openImportExcelModal" class="btn btn-success btn-sm shadow-sm flex-fill flex-md-grow-0" title="Unggah file Excel hasil hitung fisik">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Import Excel
                    </button>
                @endif
            </div>
        </div>

        @if(session()->has('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session()->has('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Left: Form Info & Summary -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-1 text-primary"></i> Informasi Dokumen</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2 mb-2">
                            <div class="col-6 col-lg-12">
                                <label class="form-label small fw-semibold mb-1">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                <input type="date" wire:model="tanggal" class="form-control form-control-sm @error('tanggal') is-invalid @enderror">
                                @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-6 col-lg-12">
                                <label class="form-label small fw-semibold mb-1">Lokasi Gudang <span class="text-danger">*</span></label>
                                <select wire:model.live="gudang_id" class="form-select form-select-sm @error('gudang_id') is-invalid @enderror">
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach($gudangs as $g)
                                        <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                                    @endforeach
                                </select>
                                @error('gudang_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Catatan / Keterangan</label>
                            <textarea wire:model="catatan" rows="2" class="form-control form-control-sm" placeholder="Contoh: Audit fisik berkala kuartal..."></textarea>
                        </div>

                        @if($gudang_id)
                            <div class="d-flex flex-column gap-2 mt-3 pt-2 border-top">
                                <button type="button" wire:click="openBulkLoadModal" class="btn btn-primary btn-sm shadow-sm text-start">
                                    <i class="bi bi-funnel-fill me-1"></i> Muat Cepat (Filter Kategori / Stok > 0)
                                </button>
                                <button type="button" wire:click="openCatalogModal" class="btn btn-outline-primary btn-sm text-start">
                                    <i class="bi bi-grid-3x3-gap me-1"></i> Buka Katalog & Pilih Manual
                                </button>
                                <button type="button" wire:click="loadAllProductsFromWarehouse" class="btn btn-outline-secondary btn-sm text-start" wire:loading.attr="disabled">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Muat Semua 104 Katalog Produk
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="card border-0 shadow-sm bg-white mb-4">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calculator me-1 text-primary"></i> Ringkasan Selisih Fisik</h6>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Total Item Terdaftar:</span>
                            <span class="fw-bold">{{ count($items) }} Produk</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-success"><i class="bi bi-arrow-up-circle me-1"></i>Selisih Lebih (+):</span>
                            <span class="fw-bold text-success">+{{ number_format($this->totalSelisihPlus, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-danger"><i class="bi bi-arrow-down-circle me-1"></i>Selisih Kurang (-):</span>
                            <span class="fw-bold text-danger">{{ number_format($this->totalSelisihMinus, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Net Selisih:</span>
                            <span class="{{ $this->totalSelisihNet > 0 ? 'text-primary' : ($this->totalSelisihNet < 0 ? 'text-danger' : 'text-dark') }}">
                                {{ ($this->totalSelisihNet > 0 ? '+' : '') . number_format($this->totalSelisihNet, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Items Table & Quick Entry -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-boxes me-1 text-primary"></i> Rincian Hitung Fisik</h6>

                            @if($gudang_id)
                                <div class="d-flex gap-2">
                                    <button type="button" wire:click="openBulkLoadModal" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-funnel me-1"></i> Muat Filter
                                    </button>
                                    <button type="button" wire:click="openCatalogModal" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah dari Katalog
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if($gudang_id)
                            <!-- Quick Search Bar with Instant Results Dropdown -->
                            <div class="position-relative mb-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text"
                                           wire:model.live.debounce.250ms="quickSearchQuery"
                                           class="form-control border-start-0"
                                           placeholder="Ketik nama atau kode katalog produk untuk menambah cepat ke opname...">
                                    @if(filled($quickSearchQuery))
                                        <button type="button" wire:click="$set('quickSearchQuery', '')" class="btn btn-outline-secondary">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    @endif
                                </div>

                                @if(filled($quickSearchQuery))
                                    <div class="position-absolute w-100 bg-white border rounded-3 shadow-lg mt-1 p-1" style="z-index: 1040; max-height: 280px; overflow-y: auto;">
                                        @forelse($this->quickSearchResults as $p)
                                            <div wire:click="addQuickProduct({{ $p->id }})"
                                                 class="d-flex justify-content-between align-items-center p-2 rounded hover-bg-light cursor-pointer border-bottom-last-0"
                                                 style="cursor: pointer;">
                                                <div class="d-flex align-items-center gap-2 min-w-0">
                                                    @if ($p->foto_url)
                                                        <img src="{{ $p->foto_url }}" alt="{{ $p->nama }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;" loading="lazy">
                                                    @else
                                                        <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 34px; height: 34px;">
                                                            <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                                        </div>
                                                    @endif
                                                    <div class="min-w-0">
                                                        <div class="fw-semibold text-dark text-truncate">{{ $p->nama }}</div>
                                                        <small class="text-muted font-monospace">{{ $p->kode_katalog ?? '-' }} &bull; {{ $p->kategori->nama_kategori ?? '-' }} &bull; Kemasan: {{ $p->kemasan ?? '-' }}</small>
                                                    </div>
                                                </div>
                                                <div class="flex-shrink-0 ms-2">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        <i class="bi bi-plus-lg me-1"></i> Tambah
                                                    </span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">
                                                Tidak ada produk yang cocok dengan "<strong>{{ $quickSearchQuery }}</strong>".
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Batch Helpers Toolbar & Filter Tabs -->
                        @if(count($items) > 0)
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top mt-2">
                                <!-- Filter Tabs -->
                                <div class="btn-group btn-group-sm w-100 w-sm-auto" role="group">
                                    <button type="button"
                                            wire:click="$set('tableFilter', 'all')"
                                            class="btn {{ $tableFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        Semua ({{ count($items) }})
                                    </button>
                                    <button type="button"
                                            wire:click="$set('tableFilter', 'diff')"
                                            class="btn {{ $tableFilter === 'diff' ? 'btn-danger' : 'btn-outline-danger' }}">
                                        Selisih ({{ $this->countDiffItems }})
                                    </button>
                                    <button type="button"
                                            wire:click="$set('tableFilter', 'match')"
                                            class="btn {{ $tableFilter === 'match' ? 'btn-success' : 'btn-outline-success' }}">
                                        Cocok ({{ $this->countMatchedItems }})
                                    </button>
                                </div>

                                <!-- Batch Actions -->
                                <div class="d-flex gap-1 flex-wrap w-100 w-sm-auto">
                                    <button type="button" wire:click="setAllMatched" class="btn btn-outline-success btn-sm flex-fill flex-sm-grow-0" title="Salin semua angka stok sistem ke stok fisik">
                                        <i class="bi bi-check2-all me-1"></i> Samakan Fisik
                                    </button>
                                    <button type="button" wire:click="clearAllPhysicalStock" class="btn btn-outline-warning btn-sm flex-fill flex-sm-grow-0" title="Nolkan semua input fisik">
                                        <i class="bi bi-eraser me-1"></i> Nolkan
                                    </button>
                                    <button type="button" wire:click="clearAllItems" class="btn btn-outline-secondary btn-sm flex-fill flex-sm-grow-0" onclick="return confirm('Kosongkan semua produk dari daftar opname?') || event.stopImmediatePropagation()">
                                        <i class="bi bi-trash me-1"></i> Kosongkan
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="card-body p-0">
                        @error('items') <div class="alert alert-danger m-3 py-2 small">{{ $message }}</div> @enderror

                        <div class="table-responsive">
                            <table class="table table-hover table-mobile-cards align-middle mb-0">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-3 py-3" style="width: 35%;">Produk & Kemasan</th>
                                        <th class="py-3 text-center" style="width: 15%;">Stok Sistem</th>
                                        <th class="py-3 text-center" style="width: 25%;">Stok Fisik & Hitung</th>
                                        <th class="py-3 text-center" style="width: 15%;">Selisih</th>
                                        <th class="pe-3 py-3 text-end" style="width: 10%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse($this->filteredItems as $entry)
                                        @php
                                            $index = $entry['index'];
                                            $item = $entry['item'];
                                            $selisih = (float)($item['stok_fisik'] ?? 0) - (float)$item['stok_sistem'];
                                        @endphp
                                        <tr wire:key="so-item-{{ $item['produk_id'] }}">
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                        @if (!empty($item['foto_url']))
                                                            <img src="{{ $item['foto_url'] }}" alt="{{ $item['nama_produk'] }}"
                                                                 class="rounded border object-fit-cover"
                                                                 style="width: 38px; height: 38px; cursor: pointer;"
                                                                 loading="lazy"
                                                                 @mouseenter="showPreview = true"
                                                                 @mouseleave="showPreview = false"
                                                                 wire:click.stop="openImageModal({{ $item['produk_id'] }})">
                                                            <div x-show="showPreview"
                                                                 class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                                 style="bottom: 100%; left: 0; z-index: 1085; min-width: 180px; max-width: 240px; pointer-events: none; margin-bottom: 6px;"
                                                                 x-cloak>
                                                                <img src="{{ $item['foto_url'] }}" alt="{{ $item['nama_produk'] }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                                                <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $item['nama_produk'] }}</div>
                                                            </div>
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;" title="Belum ada foto">
                                                                <i class="bi bi-image" style="font-size: 1rem;"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="fw-semibold text-dark">{{ $item['nama_produk'] }}</div>
                                                        <div class="small text-muted font-monospace">
                                                            {{ $item['kode_produk'] }} &bull; Satuan: {{ $item['satuan'] }}
                                                        </div>
                                                    </div>
                                                </div>
                                                @if(!empty($item['kemasan']) || !empty($item['isi_per_kemasan']))
                                                    @php
                                                        $isPvcBadge = str_contains(strtolower($item['satuan'] ?? ''), 'meter') && !empty($item['panjang_meter']) && !empty($item['isi_per_kemasan']);
                                                        $firstWordSat = strtolower(strtok($item['satuan'] ?? '', " (/"));
                                                        $firstWordKem = strtolower(strtok($item['kemasan'] ?? '', " (/"));
                                                        $isSameUnitKem = ($firstWordKem !== '' && (
                                                            $firstWordSat === $firstWordKem
                                                            || (str_contains(strtolower($item['satuan'] ?? ''), 'dus') && str_contains(strtolower($item['kemasan'] ?? ''), 'dus'))
                                                        ));
                                                        $isiVal = (float)($item['isi_per_kemasan'] ?? 0);
                                                        $pjgVal = (float)($item['panjang_meter'] ?? 0);
                                                    @endphp
                                                    <div class="badge bg-light text-primary border border-primary-subtle mt-1" style="font-size: 0.72rem;">
                                                        <i class="bi bi-box-seam me-1"></i>{{ $item['kemasan'] ?? 'Kemasan' }}
                                                        @if($isPvcBadge)
                                                            ({{ $isiVal }} Lembar @ {{ $pjgVal }}m = {{ $isiVal * $pjgVal }}m/{{ strtolower($item['kemasan'] ?? 'dus') }})
                                                        @elseif($isSameUnitKem)
                                                            (isi {{ number_format($isiVal, 0, ',', '.') }} pcs/{{ strtolower($item['kemasan'] ?? 'dus') }})
                                                        @elseif(!empty($item['isi_per_kemasan']))
                                                            (isi {{ number_format($isiVal, 0, ',', '.') }} {{ $item['satuan'] }})
                                                        @endif
                                                        @if(!$isPvcBadge && !empty($item['panjang_meter']))
                                                            @ {{ $pjgVal }}m
                                                        @endif
                                                    </div>
                                                @endif
                                                <input type="text"
                                                       wire:model="items.{{ $index }}.catatan"
                                                       class="form-control form-control-sm mt-1 py-0 fs-7"
                                                       placeholder="Catatan item (opsional)">
                                            </td>
                                            <td class="text-center" data-label="Stok Sistem">
                                                <span class="badge bg-light text-dark border fs-7 px-2 py-1">
                                                    {{ number_format((float) $item['stok_sistem'], 0, ',', '.') }} {{ $item['satuan'] }}
                                                </span>
                                            </td>
                                            <td class="text-center" data-label="Stok Fisik & Hitung">
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           min="0"
                                                           step="0.01"
                                                           wire:model.live.debounce.300ms="items.{{ $index }}.stok_fisik"
                                                           class="form-control form-control-sm text-center fw-bold @error('items.'.$index.'.stok_fisik') is-invalid @enderror">
                                                    <button type="button"
                                                            wire:click="openPackagingCalculator({{ $index }})"
                                                            class="btn btn-outline-primary"
                                                            title="Kalkulator Kemasan (Dus/Ikat/Lembar)">
                                                        <i class="bi bi-calculator"></i>
                                                    </button>
                                                    <button type="button"
                                                            wire:click="$set('items.{{ $index }}.stok_fisik', {{ (float) $item['stok_sistem'] }})"
                                                            class="btn btn-outline-secondary"
                                                            title="Samakan dengan stok sistem">
                                                        =
                                                    </button>
                                                </div>
                                                @error('items.'.$index.'.stok_fisik') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                                            </td>
                                            <td class="text-center" data-label="Selisih">
                                                @if($selisih > 0)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                        +{{ number_format($selisih, 0, ',', '.') }}
                                                    </span>
                                                @elseif($selisih < 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                        {{ number_format($selisih, 0, ',', '.') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border px-2 py-1">
                                                        0 (Cocok)
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-end">
                                                <button type="button"
                                                        wire:click="removeProduct({{ $index }})"
                                                        class="btn btn-outline-danger btn-sm py-1 px-2 w-100 w-md-auto"
                                                        title="Hapus dari daftar">
                                                    <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus Item</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox display-6 d-block mb-2 text-secondary"></i>
                                                @if(!$gudang_id)
                                                    Pilih Gudang terlebih dahulu untuk mulai memasukkan item produk.
                                                @elseif(count($items) === 0)
                                                    Belum ada produk yang ditambahkan. Gunakan <strong>Muat Cepat (Filter)</strong> atau <strong>Pilih dari Katalog</strong>.
                                                @else
                                                    Tidak ada produk dalam tab filter ini.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons Toolbar -->
                <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-2">
                    <a href="{{ route('inventory.stock-opname.index') }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <div class="d-flex gap-2 flex-wrap flex-fill flex-sm-grow-0 justify-content-end">
                        <button type="button"
                                wire:click="saveDraft"
                                class="btn btn-outline-primary flex-fill flex-sm-grow-0"
                                wire:loading.attr="disabled"
                                @if(empty($items)) disabled @endif>
                            <i class="bi bi-save me-1"></i> Simpan Draft
                        </button>
                        <button type="button"
                                wire:click="promptConfirmation"
                                class="btn btn-success flex-fill flex-sm-grow-0"
                                wire:loading.attr="disabled"
                                @if(empty($items)) disabled @endif>
                            <i class="bi bi-check2-circle me-1"></i> Selesaikan Stok Opname
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Poin 1: Modal Kalkulator Kemasan Cerdas (Smart Packaging Helper) -->
    @if($isPackagingCalcOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1070;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white py-3">
                        <h6 class="modal-title fw-bold">
                            <i class="bi bi-calculator me-1"></i> Kalkulator Kemasan Fisik Lapangan
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
                                        <span><strong>Total Stok Fisik:</strong></span>
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
                                    <i class="bi bi-info-circle me-1"></i> Rincian Konversi Lapangan ke Satuan Sistem:
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
                                        <span><strong>Total Stok Fisik:</strong></span>
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
                            <button type="button" wire:click="closePackagingCalculator" class="btn btn-outline-secondary">
                                Batal
                            </button>
                            <button type="button" wire:click="applyPackagingCalculation" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Terapkan ke Stok Fisik
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Poin 2: Modal Smart Bulk Load (Filter Kategori & Stok Aktif) -->
    @if($isBulkLoadModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-white py-3 border-0">
                        <h6 class="modal-title fw-bold text-dark">
                            <i class="bi bi-funnel-fill text-primary me-1"></i> Muat Cepat Barang Opname
                        </h6>
                        <button type="button" wire:click="closeBulkLoadModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <p class="text-muted small mb-3">
                            Pilih kriteria produk yang ingin dimuat ke dalam tabel opname gudang <strong>{{ $gudangs->firstWhere('id', $gudang_id)?->nama_gudang }}</strong>.
                        </p>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Berdasarkan Kategori</label>
                            <select wire:model="bulkLoadKategoriId" class="form-select">
                                <option value="">Semua Kategori (Seluruh Katalog)</option>
                                @foreach($rootKategoris as $root)
                                    @if ($root->children->isNotEmpty())
                                        <optgroup label="📁 {{ $root->nama_kategori }}">
                                            <option value="{{ $root->id }}">&bull; Semua {{ $root->nama_kategori }}</option>
                                            @foreach($root->children as $child)
                                                <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;↳ {{ $child->nama_kategori }}</option>
                                            @endforeach
                                        </optgroup>
                                    @else
                                        <option value="{{ $root->id }}">📁 {{ $root->nama_kategori }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" wire:model="bulkLoadOnlyWithStock" class="form-check-input" id="onlyWithStockCheck">
                            <label class="form-check-label small fw-semibold text-dark" for="onlyWithStockCheck">
                                Hanya muat produk yang memiliki saldo stok di gudang ini (Stok > 0)
                            </label>
                            <div class="form-text text-muted small">Membantu mempercepat audit berkala dengan menyembunyikan barang bersaldo 0.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold d-block">Metode Pemuatan ke Tabel</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" wire:model="bulkLoadMode" id="modeReplace" value="replace">
                                <label class="form-check-label small" for="modeReplace">Gantikan daftar saat ini (Kosongkan lalu isi baru)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" wire:model="bulkLoadMode" id="modeAppend" value="append">
                                <label class="form-check-label small" for="modeAppend">Tambahkan ke daftar yang sudah ada</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" wire:click="closeBulkLoadModal" class="btn btn-outline-secondary">Batal</button>
                            <button type="button" wire:click="executeBulkLoad" class="btn btn-primary px-4" wire:loading.attr="disabled">
                                <i class="bi bi-box-arrow-in-down me-1"></i> Muat Barang Sekarang
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Poin 3: Modal Opsi Cetak Lembar Hitung Lapangan -->
    @if($isPrintCountSheetModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-white py-3 border-0">
                        <h6 class="modal-title fw-bold text-dark">
                            <i class="bi bi-printer text-primary me-1"></i> Cetak Lembar Hitung Lapangan
                        </h6>
                        <button type="button" wire:click="closePrintCountSheetModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <p class="text-muted small mb-3">
                            Pilih mode lembar kerja fisik yang akan dibawa oleh petugas gudang menggunakan papan jalan (clipboard):
                        </p>

                        <div class="list-group mb-4">
                            <label class="list-group-item list-group-item-action d-flex gap-3 align-items-center cursor-pointer p-3 rounded-3 mb-2 border">
                                <input class="form-check-input flex-shrink-0" type="radio" wire:model.live="countSheetMode" value="blind">
                                <div>
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-shield-check text-success me-1"></i> Mode Blind Count (Hitung Buta) — Direkomendasikan
                                    </div>
                                    <small class="text-muted">Kolom stok sistem disembunyikan. Staf gudang wajib menghitung fisik asli tanpa bias atau contek angka sistem.</small>
                                </div>
                            </label>
                            <label class="list-group-item list-group-item-action d-flex gap-3 align-items-center cursor-pointer p-3 rounded-3 border">
                                <input class="form-check-input flex-shrink-0" type="radio" wire:model.live="countSheetMode" value="guided">
                                <div>
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-eye text-primary me-1"></i> Mode Guided Count (Dengan Angka Sistem)
                                    </div>
                                    <small class="text-muted">Menampilkan kolom stok sistem sebagai referensi pembanding saat pemeriksaan.</small>
                                </div>
                            </label>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" wire:click="closePrintCountSheetModal" class="btn btn-outline-secondary">Batal</button>
                            <button type="button" onclick="window.print()" class="btn btn-primary px-4">
                                <i class="bi bi-printer-fill me-1"></i> Cetak Sekarang (Print A4)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Poin 5: Modal Import Excel Hasil Opname -->
    @if($isImportExcelModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-white py-3 border-0">
                        <h6 class="modal-title fw-bold text-dark">
                            <i class="bi bi-cloud-arrow-up-fill text-success me-1"></i> Import Hasil Hitung Fisik dari Excel
                        </h6>
                        <button type="button" wire:click="closeImportExcelModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <p class="text-muted small mb-3">
                            Unggah file spreadsheet Excel (.xlsx) yang telah diisi kolom <code>STOK FISIK</code> oleh staf pemeriksa. Sistem akan otomatis mencocokkan kode barang dan memperbarui baris opname.
                        </p>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih File Excel (.xlsx) <span class="text-danger">*</span></label>
                            <input type="file" wire:model="excelCountFile" class="form-control form-control-sm @error('excelCountFile') is-invalid @enderror" accept=".xlsx">
                            @error('excelCountFile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div wire:loading wire:target="excelCountFile" class="text-primary small mt-1">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengunggah file...
                            </div>
                        </div>

                        @if($importSummary)
                            <div class="alert alert-info py-2 small mb-3">
                                <div><strong>Hasil Import:</strong> {{ $importSummary['updated'] }} item berhasil diperbarui.</div>
                                @if(!empty($importSummary['unmatched']))
                                    <div class="text-warning mt-1">
                                        {{ count($importSummary['unmatched']) }} kode barang tidak cocok dengan tabel.
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <button type="button" wire:click="exportTemplate" class="btn btn-link text-decoration-none p-0 small">
                                <i class="bi bi-download me-1"></i> Unduh Template Excel Lagi
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="closeImportExcelModal" class="btn btn-outline-secondary btn-sm">Batal</button>
                                <button type="button" wire:click="importExcelCountSheet" class="btn btn-success btn-sm px-3" wire:loading.attr="disabled" @if(!$excelCountFile) disabled @endif>
                                    <i class="bi bi-check2-circle me-1"></i> Terapkan ke Tabel Opname
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Katalog Buka & Pilih Manual (Existing) -->
    @if ($isCatalogModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1055;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white py-2 py-sm-3 px-3 px-sm-4 border-0 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="bi bi-grid-3x3-gap-fill fs-5 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <h5 class="modal-title fw-bold mb-0 text-truncate">Pilih Produk dari Katalog</h5>
                                <small class="text-white-50 d-none d-sm-inline">Centang produk yang ingin dimasukkan ke dalam daftar pemeriksaan fisik gudang.</small>
                            </div>
                        </div>
                        <button type="button" wire:click="closeCatalogModal" class="btn-close btn-close-white flex-shrink-0" aria-label="Close"></button>
                    </div>

                    <div class="p-2 p-sm-3 bg-light border-bottom flex-shrink-0" x-data="{ showFilters: false }">
                        <!-- Filter & Search Toolbar -->
                        <div class="d-flex gap-2 mb-2">
                            <div class="input-group input-group-sm flex-grow-1">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text"
                                       wire:model.live.debounce.250ms="catalogSearch"
                                       class="form-control border-start-0"
                                       placeholder="Cari nama barang atau kode SKU..."
                                       autofocus>
                                @if (filled($catalogSearch))
                                    <button type="button" wire:click="$set('catalogSearch', '')" class="btn btn-outline-secondary">
                                        <i class="bi bi-x"></i>
                                    </button>
                                @endif
                            </div>
                            @php
                                $hasActiveOpnameFilter = filled($catalogKategoriId) || filled($catalogMerekId);
                            @endphp
                            <button
                                type="button"
                                @click="showFilters = !showFilters"
                                class="btn btn-sm d-md-none flex-shrink-0 d-inline-flex align-items-center gap-1 {{ $hasActiveOpnameFilter ? 'btn-primary' : 'btn-outline-secondary bg-white' }}"
                            >
                                <i class="bi bi-sliders"></i>
                                <span>Filter</span>
                            </button>
                        </div>

                        <div class="row g-2 mb-2 d-md-flex" :class="showFilters ? 'd-flex' : 'd-none'">
                            <div class="col-6">
                                <select wire:model.live="catalogKategoriId" class="form-select form-select-sm">
                                    <option value="">Semua Kategori</option>
                                    @foreach($rootKategoris as $root)
                                        @if ($root->children->isNotEmpty())
                                            <optgroup label="📁 {{ $root->nama_kategori }}">
                                                <option value="{{ $root->id }}">&bull; Semua {{ $root->nama_kategori }}</option>
                                                @foreach($root->children as $child)
                                                    <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;↳ {{ $child->nama_kategori }}</option>
                                                @endforeach
                                            </optgroup>
                                        @else
                                            <option value="{{ $root->id }}">📁 {{ $root->nama_kategori }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <select wire:model.live="catalogMerekId" class="form-select form-select-sm">
                                    <option value="">Semua Merek</option>
                                    @foreach($mereks as $m)
                                        <option value="{{ $m->id }}">{{ $m->nama_merek }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Quick Brand Pills (Horizontal Scrollable on Mobile) -->
                        @if($mereks->isNotEmpty())
                            <div class="d-flex align-items-center gap-1 nav-pills-scrollable flex-nowrap mb-2 pb-1">
                                <button
                                    type="button"
                                    wire:click="setCatalogMerek(null)"
                                    class="btn btn-xs py-1 px-2.5 rounded-pill text-nowrap flex-shrink-0 {{ $catalogMerekId === null ? 'btn-primary fw-semibold' : 'btn-outline-secondary bg-white' }}"
                                    style="font-size: 0.74rem;"
                                >
                                    Semua Merek
                                </button>
                                @foreach($mereks as $mrk)
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
                        @endif

                        <!-- Action Toolbar -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-1 border-top">
                            <span class="small text-muted" style="font-size: 0.75rem;">
                                Menampilkan {{ count($this->catalogProducts) }} produk &bull; Tercentang: <strong class="text-primary">{{ count($catalogSelectedIds) }}</strong> item
                            </span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" wire:click="selectAllFoundCatalogProducts" class="btn btn-outline-primary bg-white btn-xs py-1 px-2" style="font-size: 0.75rem;">
                                    <i class="bi bi-check-all me-1"></i>Pilih Semua
                                </button>
                                @if (count($catalogSelectedIds) > 0)
                                    <button type="button" wire:click="$set('catalogSelectedIds', [])" class="btn btn-outline-danger bg-white btn-xs py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-x-circle me-1"></i>Reset ({{ count($catalogSelectedIds) }})
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-body p-0 bg-white">
                        @php
                            $activeGudang = $gudang_id ? $gudangs->firstWhere('id', $gudang_id) : null;
                            $stockService = app(\App\Domains\Inventory\Services\StockService::class);
                        @endphp

                        <!-- Mobile Card List View (< 768px) — Full Width Product Name -->
                        <div class="d-md-none">
                            <div class="list-group list-group-flush">
                                @forelse($this->catalogProducts as $prod)
                                    @php
                                        $isSelected = in_array($prod->id, $catalogSelectedIds, true);
                                        $stokSistem = $activeGudang ? (float) $stockService->getStock($prod, $activeGudang) : 0;
                                    @endphp
                                    <div
                                        wire:key="so-cat-mob-{{ $prod->id }}"
                                        wire:click="toggleCatalogProduct({{ $prod->id }})"
                                        style="cursor: pointer;"
                                        class="list-group-item list-group-item-action py-2.5 px-3 {{ $isSelected ? 'bg-primary-subtle border-start border-4 border-primary' : '' }}"
                                    >
                                        <div class="d-flex align-items-start gap-2.5">
                                            <div onclick="event.stopPropagation();" class="pt-1 flex-shrink-0">
                                                <input type="checkbox"
                                                       class="form-check-input m-0"
                                                       style="width: 1.25rem; height: 1.25rem;"
                                                       value="{{ $prod->id }}"
                                                       @if($isSelected) checked @endif
                                                       wire:click.stop="toggleCatalogProduct({{ $prod->id }})">
                                            </div>
                                            <div onclick="event.stopPropagation();" class="flex-shrink-0">
                                                @if ($prod->foto_url)
                                                    <img src="{{ $prod->foto_url }}" alt="{{ $prod->nama }}"
                                                         class="rounded-3 border object-fit-cover"
                                                         style="width: 44px; height: 44px;"
                                                         loading="lazy"
                                                         wire:click="openImageModal({{ $prod->id }})">
                                                @else
                                                    <div class="bg-light rounded-3 border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="fw-bold {{ $isSelected ? 'text-primary' : 'text-dark' }}" style="font-size: 0.9rem; line-height: 1.3;">
                                                    {{ $prod->nama }}
                                                </div>
                                                <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">{{ $prod->kode_katalog ?? '-' }}</span>
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">{{ $prod->kategori->nama_kategori ?? '-' }}</span>
                                                    @if($prod->merek)
                                                        <span class="badge bg-white text-dark border" style="font-size: 0.7rem;">{{ $prod->merek->nama_merek }}</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-light-subtle">
                                                    <span class="text-muted" style="font-size: 0.74rem;">Satuan: <strong>{{ $prod->satuan?->label() ?? 'Pcs' }}</strong></span>
                                                    <span class="badge {{ $stokSistem <= 0 ? 'bg-secondary-subtle text-secondary' : 'bg-info-subtle text-info' }} border font-monospace" style="font-size: 0.74rem;">
                                                        Stok Sistem: {{ number_format($stokSistem, 2, ',', '.') }} {{ $prod->satuan?->label() ?? 'Pcs' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5 px-3 text-muted small">
                                        <i class="bi bi-inbox d-block fs-2 text-muted mb-2"></i>
                                        Tidak ada produk katalog yang sesuai atau semua sudah ada di tabel opname.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Desktop & Tablet Product List Table (>= 768px) -->
                        <div class="table-responsive bg-white d-none d-md-block">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;" class="text-center ps-3">#</th>
                                        <th>Nama Produk & Kode SKU</th>
                                        <th style="width: 140px;">Kategori</th>
                                        <th style="width: 120px;">Merek</th>
                                        <th style="width: 90px;">Satuan</th>
                                        <th style="width: 140px;" class="text-end pe-3">Stok Sistem Gudang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($this->catalogProducts as $prod)
                                        @php
                                            $isSelected = in_array($prod->id, $catalogSelectedIds, true);
                                            $stokSistem = $activeGudang ? (float) $stockService->getStock($prod, $activeGudang) : 0;
                                        @endphp
                                        <tr class="{{ $isSelected ? 'table-primary bg-opacity-10' : '' }}"
                                            wire:click="toggleCatalogProduct({{ $prod->id }})"
                                            style="cursor: pointer;">
                                            <td class="text-center ps-3" onclick="event.stopPropagation();">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       value="{{ $prod->id }}"
                                                       @if($isSelected) checked @endif
                                                       wire:click.stop="toggleCatalogProduct({{ $prod->id }})">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="position-relative d-inline-block flex-shrink-0" x-data="{ showPreview: false }">
                                                        @if ($prod->foto_url)
                                                            <img src="{{ $prod->foto_url }}" alt="{{ $prod->nama }}"
                                                                 class="rounded border object-fit-cover"
                                                                 style="width: 38px; height: 38px; cursor: pointer;"
                                                                 loading="lazy"
                                                                 @mouseenter="showPreview = true"
                                                                 @mouseleave="showPreview = false"
                                                                 wire:click.stop="openImageModal({{ $prod->id }})">
                                                            <div x-show="showPreview"
                                                                 class="position-absolute shadow-lg rounded-3 border bg-white p-1 text-center"
                                                                 style="bottom: 100%; left: 0; z-index: 1085; min-width: 180px; max-width: 240px; pointer-events: none; margin-bottom: 6px;"
                                                                 x-cloak>
                                                                <img src="{{ $prod->foto_url }}" alt="{{ $prod->nama }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                                                <div class="small fw-bold text-dark text-truncate mt-1 px-1" style="font-size: 0.72rem;">{{ $prod->nama }}</div>
                                                            </div>
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;" title="Belum ada foto">
                                                                <i class="bi bi-image" style="font-size: 1rem;"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="fw-semibold text-dark">{{ $prod->nama }}</div>
                                                        <small class="text-muted font-monospace">{{ $prod->kode_katalog ?? '-' }} &bull; Kemasan: {{ $prod->kemasan ?? '-' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-secondary">{{ $prod->kategori->nama_kategori ?? '-' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $prod->merek->nama_merek ?? '-' }}</span>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $prod->satuan?->label() ?? 'Pcs' }}
                                            </td>
                                            <td class="text-end pe-3">
                                                <span class="badge {{ $stokSistem <= 0 ? 'bg-secondary-subtle text-secondary' : 'bg-info-subtle text-info' }} border">
                                                    {{ number_format($stokSistem, 2, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted small">
                                                <i class="bi bi-inbox d-block fs-2 text-muted mb-2"></i>
                                                Tidak ada produk katalog yang sesuai atau semua sudah ada di tabel opname.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 flex-shrink-0 d-flex justify-content-between align-items-center gap-2">
                        <button type="button" wire:click="closeCatalogModal" class="btn btn-outline-secondary btn-sm flex-fill flex-sm-grow-0">Batal</button>
                        <button type="button"
                                wire:click="addSelectedCatalogProducts"
                                class="btn btn-primary btn-sm px-3 px-sm-4 fw-semibold shadow-sm flex-fill flex-sm-grow-0"
                                @if(empty($catalogSelectedIds)) disabled @endif>
                            <i class="bi bi-plus-lg me-1"></i> Tambahkan ({{ count($catalogSelectedIds) }}) Produk
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modern Finalize Confirmation Modal (Existing) -->
    @if ($isConfirmModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1065;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-body p-4 text-center">
                        <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-3 shadow-sm">
                            <i class="bi bi-clipboard-check-fill display-5"></i>
                        </div>

                        <h4 class="fw-bold text-dark mb-1">Selesaikan Stok Opname?</h4>
                        <p class="text-muted small mb-3">
                            Sistem akan secara permanen menyesuaikan saldo stok gudang sesuai hasil hitung fisik yang Anda masukkan.
                        </p>

                        <div class="card bg-light border-0 rounded-3 text-start mb-3 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted small">Gudang Audit:</span>
                                <span class="fw-bold text-dark">{{ $gudangs->firstWhere('id', $gudang_id)?->nama_gudang ?? '-' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted small">Total Produk Diaudit:</span>
                                <span class="fw-bold text-dark">{{ count($items) }} Produk</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted small">Item Ada Selisih Fisik:</span>
                                <span class="fw-bold text-danger">{{ $this->countDiffItems }} Produk</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Net Selisih Kuantitas:</span>
                                <span class="fw-bold {{ $this->totalSelisihNet >= 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ ($this->totalSelisihNet > 0 ? '+' : '') . number_format($this->totalSelisihNet, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <div class="alert alert-info text-start py-2 px-3 small border-0 mb-4 rounded-3 d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill text-primary fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                Mutasi penyesuaian opname akan dibuat secara otomatis untuk item yang berselisih.
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" wire:click="closeConfirmationModal" class="btn btn-outline-secondary px-4 py-2">
                                Periksa Kembali
                            </button>
                            <button type="button" wire:click="saveComplete" class="btn btn-success px-4 py-2" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveComplete"><i class="bi bi-check2-circle me-1"></i> Ya, Selesaikan Sekarang</span>
                                <span wire:loading wire:target="saveComplete">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Menyesuaikan Stok...
                                </span>
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
