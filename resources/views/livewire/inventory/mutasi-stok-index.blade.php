<div>
    <style>
        @media print {
            .no-print, .btn, .pagination, .card-header, .alert, .modal, .modal-backdrop {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .table {
                font-size: 11px !important;
                width: 100% !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background: #fff !important;
                font-size: 12px !important;
            }
        }
        .print-only {
            display: none;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .kpi-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
    </style>

    <!-- Print Only Header -->
    <div class="print-only mb-4 pb-2 border-bottom">
        <h3 class="fw-bold mb-1">LAPORAN MUTASI STOK</h3>
        <p class="text-muted small mb-0">Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }} WIB | Operator: {{ auth()->user()->name ?? 'Staff' }}</p>
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
        <div>
            <h4 class="fw-bold mb-1">Riwayat Mutasi Stok</h4>
            <p class="text-muted small mb-0">Audit trail seluruh pergerakan barang masuk, keluar, transfer, dan penyesuaian stok secara kronologis.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(request()->query('from') === 'stok')
                <a href="{{ route('stok.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Stok Saat Ini
                </a>
            @else
                <a href="{{ route('stok.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-layers me-1"></i>Lihat Stok Saat Ini
                </a>
            @endif

            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Cetak
            </button>

            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" class="btn btn-success text-white">
                <span wire:loading.remove wire:target="exportExcel"><i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel</span>
                <span wire:loading wire:target="exportExcel">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Menyiapkan File...
                </span>
            </button>
        </div>
    </div>

    <!-- Movement KPI Metric Cards -->
    <div class="row g-3 mb-4 no-print">
        <!-- Total Mutasi -->
        <div class="col-sm-6 col-xl-3">
            <div
                wire:click="filterByArah('')"
                class="card border-0 shadow-sm rounded-3 kpi-card cursor-pointer {{ $filterArah === '' ? 'ring-2 border-primary border' : '' }}"
                title="Klik untuk tampilkan semua pergerakan"
            >
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium">Total Mutasi Transaksi</span>
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-2">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($metrics['totalMutasi'], 0, ',', '.') }}</h3>
                    <small class="text-muted">Baris pergerakan stok tercatat</small>
                </div>
            </div>
        </div>

        <!-- Total Masuk (+) -->
        <div class="col-sm-6 col-xl-3">
            <div
                wire:click="filterByArah('masuk')"
                class="card border-0 shadow-sm rounded-3 kpi-card cursor-pointer {{ $filterArah === 'masuk' ? 'border border-success bg-success bg-opacity-10' : '' }}"
                title="Klik untuk filter hanya barang masuk (+)"
            >
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium">Total Barang Masuk (+)</span>
                        <div class="p-2 bg-success bg-opacity-10 text-success rounded-2">
                            <i class="bi bi-arrow-down-left-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-success">+{{ number_format($metrics['totalMasuk'], 2, ',', '.') }}</h3>
                    <small class="text-muted">Unit barang masuk bertambah</small>
                </div>
            </div>
        </div>

        <!-- Total Keluar (-) -->
        <div class="col-sm-6 col-xl-3">
            <div
                wire:click="filterByArah('keluar')"
                class="card border-0 shadow-sm rounded-3 kpi-card cursor-pointer {{ $filterArah === 'keluar' ? 'border border-danger bg-danger bg-opacity-10' : '' }}"
                title="Klik untuk filter hanya barang keluar (-)"
            >
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium">Total Barang Keluar (-)</span>
                        <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-2">
                            <i class="bi bi-arrow-up-right-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-danger">-{{ number_format($metrics['totalKeluar'], 2, ',', '.') }}</h3>
                    <small class="text-muted">Unit barang keluar berkurang</small>
                </div>
            </div>
        </div>

        <!-- Net Pergerakan -->
        @php
            $isNetPositive = $metrics['netPergerakan'] >= 0;
        @endphp
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium">Net Pergerakan Bersih</span>
                        <div class="p-2 {{ $isNetPositive ? 'bg-primary bg-opacity-10 text-primary' : 'bg-warning bg-opacity-10 text-warning' }} rounded-2">
                            <i class="bi {{ $isNetPositive ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 {{ $isNetPositive ? 'text-primary' : 'text-warning' }}">
                        {{ $isNetPositive ? '+' : '' }}{{ number_format($metrics['netPergerakan'], 2, ',', '.') }}
                    </h3>
                    <small class="text-muted">Selisih arus stok keluar/masuk</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4 border-0 shadow-sm no-print">
        <div class="card-body p-3">
            <!-- Top Row: Search, Per Page, Reset -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Cari nomor referensi, nama produk, kode katalog..."
                        >
                        @if(filled($search))
                            <button class="btn btn-outline-secondary border-start-0 border" type="button" wire:click="$set('search', '')" title="Bersihkan pencarian">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-3">
                    <div class="d-flex align-items-center justify-content-md-end gap-2">
                        <label class="small text-muted text-nowrap mb-0 d-none d-sm-inline">Tampil:</label>
                        <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                            <option value="15">15 Baris</option>
                            <option value="25">25 Baris</option>
                            <option value="50">50 Baris</option>
                            <option value="100">100 Baris</option>
                        </select>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2 text-end">
                    @if($this->hasActiveFilters)
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                        </button>
                    @endif
                </div>
            </div>

            <!-- Second Row: Multi-Criteria Filter Dropdowns -->
            <div class="row g-2">
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Arah Mutasi</label>
                    <select wire:model.live="filterArah" class="form-select form-select-sm">
                        <option value="">Semua Arah</option>
                        <option value="masuk">Barang Masuk (+)</option>
                        <option value="keluar">Barang Keluar (-)</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small text-muted mb-1">Gudang</label>
                    <select wire:model.live="filterGudang" class="form-select form-select-sm">
                        <option value="">Semua Gudang</option>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small text-muted mb-1">Jenis Mutasi</label>
                    <select wire:model.live="filterJenis" class="form-select form-select-sm">
                        <option value="">Semua Jenis Mutasi</option>
                        @foreach ($jenisOptions as $jenis)
                            <option value="{{ $jenis->value }}">{{ $jenis->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Active Filter Chips Bar -->
            @if($this->hasActiveFilters)
                <div class="d-flex flex-wrap align-items-center gap-1 mt-3 pt-2 border-top">
                    <span class="small text-muted me-1"><i class="bi bi-funnel me-1"></i>Filter Aktif:</span>

                    @if(filled($search))
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Pencarian: "<strong>{{ $search }}</strong>"</span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('search', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($startDate) || filled($endDate))
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Periode: {{ $startDate ?: 'Awal' }} s/d {{ $endDate ?: 'Sekarang' }}</span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('startDate', ''); $set('endDate', '');" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($filterArah))
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Arah: <strong>{{ $filterArah === 'masuk' ? 'Barang Masuk (+)' : 'Barang Keluar (-)' }}</strong></span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('filterArah', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($filterGudang))
                        @php
                            $gudangAktif = $gudangs->firstWhere('id', $filterGudang);
                        @endphp
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Gudang: <strong>{{ $gudangAktif->nama_gudang ?? '-' }}</strong></span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('filterGudang', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($filterJenis))
                        @php
                            $jenisEnum = \App\Domains\Inventory\Enums\JenisMutasiStok::tryFrom($filterJenis);
                        @endphp
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Jenis: <strong>{{ $jenisEnum ? $jenisEnum->label() : $filterJenis }}</strong></span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('filterJenis', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    @if(filled($filterProduk))
                        @php
                            $produkAktif = $produks->firstWhere('id', $filterProduk);
                        @endphp
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2 fw-normal">
                            <span>Produk: <strong>{{ $produkAktif->nama ?? '-' }}</strong></span>
                            <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" wire:click="$set('filterProduk', '')" aria-label="Close"></button>
                        </span>
                    @endif

                    <a href="javascript:void(0)" wire:click="resetFilters" class="small text-danger ms-2 text-decoration-none">
                        Reset Semua
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Tanggal & Waktu</th>
                            <th>Jenis Pergerakan</th>
                            <th>Produk</th>
                            <th>Gudang</th>
                            <th class="text-end">Jumlah Mutasi</th>
                            <th>No. Referensi</th>
                            <th>Operator</th>
                            <th class="text-end pe-4 no-print" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutasis as $index => $mutasi)
                            @php
                                $qty = (float) $mutasi->jumlah;
                                $isPositive = $qty > 0;
                                $docUrl = $this->getDocumentUrl($mutasi);
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $mutasis->firstItem() + $index }}</td>
                                <td>
                                    <span class="d-block fw-medium text-dark">{{ $mutasi->tanggal ? $mutasi->tanggal->translatedFormat('d/m/Y') : '-' }}</span>
                                    <small class="text-muted">{{ $mutasi->tanggal ? $mutasi->tanggal->format('H:i') : '' }} WIB</small>
                                </td>
                                <td>
                                    <span class="badge {{ $mutasi->jenis->badgeClass() }} px-2 py-1">
                                        {{ $mutasi->jenis->label() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $mutasi->produk->nama ?? '-' }}</span>
                                    <small class="d-block text-muted font-monospace">{{ $mutasi->produk->kode_katalog ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="text-secondary"><i class="bi bi-building me-1"></i>{{ $mutasi->gudang->nama_gudang ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold font-monospace fs-6 {{ $isPositive ? 'text-success' : 'text-danger' }}">
                                        {{ $isPositive ? '+' : '' }}{{ number_format($qty, 2, ',', '.') }}
                                    </span>
                                    <small class="text-muted d-block">{{ $mutasi->produk->satuan->value ?? '' }}</small>
                                </td>
                                <td>
                                    @if ($docUrl)
                                        <a
                                            href="{{ $docUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="badge bg-light text-primary border text-decoration-none font-monospace py-1 px-2"
                                            title="Buka Dokumen Transaksi di Tab Baru"
                                        >
                                            {{ $mutasi->referensi }}
                                            <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                        </a>
                                    @else
                                        <span class="badge bg-light text-dark border font-monospace py-1 px-2">
                                            {{ $mutasi->referensi }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-secondary">{{ $mutasi->user->name ?? 'Sistem' }}</span>
                                </td>
                                <td class="text-end pe-4 no-print">
                                    <button
                                        type="button"
                                        wire:click="openDetailModal({{ $mutasi->id }})"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Detail Mutasi"
                                    >
                                        <i class="bi bi-info-circle me-1"></i>Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-clock-history display-4 d-block mb-2 text-secondary"></i>
                                        <p class="mb-0">Tidak ada data riwayat mutasi yang sesuai filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($mutasis->hasPages())
            <div class="card-footer bg-white py-3 no-print">
                {{ $mutasis->links() }}
            </div>
        @endif
    </div>

    <!-- Mutation Detail Modal -->
    @if ($isDetailModalOpen && $selectedMutasi)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Detail Mutasi Stok</h5>
                        <button type="button" wire:click="closeDetailModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <span class="badge {{ $selectedMutasi->jenis->badgeClass() }} fs-6 mb-2">
                                {{ $selectedMutasi->jenis->label() }}
                            </span>
                            @php
                                $mQty = (float) $selectedMutasi->jumlah;
                                $modalDocUrl = $this->getDocumentUrl($selectedMutasi);
                            @endphp
                            <h2 class="fw-bold font-monospace mb-0 {{ $mQty > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $mQty > 0 ? '+' : '' }}{{ number_format($mQty, 2, ',', '.') }} {{ $selectedMutasi->produk->satuan->value ?? '' }}
                            </h2>
                        </div>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">No. Referensi Dokumen</span>
                                <div>
                                    @if ($modalDocUrl)
                                        <a href="{{ $modalDocUrl }}" target="_blank" rel="noopener noreferrer" class="fw-bold font-monospace text-primary text-decoration-none">
                                            {{ $selectedMutasi->referensi }}
                                            <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                        </a>
                                    @else
                                        <span class="fw-bold font-monospace text-dark">{{ $selectedMutasi->referensi }}</span>
                                    @endif
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Tipe Sumber (Ref Type)</span>
                                <span class="text-secondary">{{ $selectedMutasi->referensi_type ?: 'Manual / Penyesuaian' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">ID Sumber (Ref ID)</span>
                                <span class="font-monospace text-secondary">{{ $selectedMutasi->referensi_id ?: '-' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Produk</span>
                                <span class="fw-semibold text-dark text-end">{{ $selectedMutasi->produk->nama ?? '-' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Gudang</span>
                                <span class="fw-medium text-dark">{{ $selectedMutasi->gudang->nama_gudang ?? '-' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Tanggal Efektif Mutasi</span>
                                <span class="text-dark">{{ $selectedMutasi->tanggal ? $selectedMutasi->tanggal->translatedFormat('d F Y, H:i') : '-' }} WIB</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Operator Penanggung Jawab</span>
                                <span class="fw-medium text-dark">{{ $selectedMutasi->user->name ?? 'Sistem Otomatis' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Tercatat di Sistem</span>
                                <span class="text-muted small">{{ $selectedMutasi->created_at ? $selectedMutasi->created_at->translatedFormat('d F Y, H:i:s') : '-' }} WIB</span>
                            </li>
                        </ul>
                    </div>
                    <div class="modal-footer bg-light">
                        @if ($modalDocUrl)
                            <a href="{{ $modalDocUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary me-auto">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka Dokumen Transaksi
                            </a>
                        @endif
                        <button type="button" wire:click="closeDetailModal" class="btn btn-secondary">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
