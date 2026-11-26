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
            .print-document-only {
                display: block !important;
                width: 100% !important;
            }
            .report-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 6px !important;
            }
            .report-table thead {
                display: table-header-group !important;
            }
            .report-table tr {
                page-break-inside: avoid !important;
            }
            .report-table th {
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
            .report-table td {
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
            .report-signature {
                page-break-inside: avoid !important;
                margin-top: 15px !important;
                padding-top: 8px !important;
                border-top: 1px solid #64748b !important;
            }
        }
        .print-document-only {
            display: none;
        }
    </style>

    <!-- Dedicated Printable Document Container (Visible ONLY on print) -->
    <div class="print-document-only">
        @if($isDraft)
            <!-- Printable Official Count Sheet (Draft Mode) -->
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
                            No: <strong style="font-family: monospace;">{{ $stockOpname->no_stock_opname }}</strong> &bull;
                            Tanggal: <strong>{{ $stockOpname->tanggal->format('d F Y') }}</strong>
                        </div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 8pt; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #cbd5e1;">
                    <div><strong>Lokasi Gudang:</strong> {{ $stockOpname->gudang->nama_gudang }}</div>
                    <div><strong>Petugas Lapangan:</strong> .....................................................................</div>
                    <div><strong>Total Item:</strong> {{ count($items) }} Produk</div>
                </div>
            </div>

            <table class="report-table">
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

            <!-- Count Sheet Signature Block -->
            <div class="report-signature">
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
                        <div style="font-size: 7pt; color: #64748b;">Tgl: {{ $stockOpname->tanggal->format('d/m/Y') }}</div>
                    </div>
                    <div style="width: 30%;">
                        <div>Kepala Gudang / Owner,</div>
                        <div style="height: 38px;"></div>
                        <div style="font-weight: bold; text-decoration: underline;">................................................</div>
                        <div style="font-size: 7pt; color: #64748b;">Tgl: .......................................</div>
                    </div>
                </div>
            </div>
        @else
            <!-- Official Berita Acara Rekonsiliasi (Completed Mode) -->
            <div style="border-bottom: 2px solid #0f172a; padding-bottom: 6px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3 style="font-weight: 800; margin: 0; font-size: 13pt; letter-spacing: 0.5px; text-transform: uppercase; color: #0f172a;">
                            BERITA ACARA HASIL STOK OPNAME FISIK
                        </h3>
                        <div style="font-size: 8.5pt; font-weight: 600; color: #475569;">
                            NATURAL GYPSUM & PLAFON PVC BY PANDAKONG
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 2px 7px; border: 1.5px solid #0f172a; font-weight: 800; font-size: 8pt; text-transform: uppercase; border-radius: 4px; background-color: #f1f5f9;">
                            STATUS: {{ $stockOpname->status->label() }}
                        </span>
                        <div style="font-size: 8pt; color: #334155; margin-top: 2px;">
                            No: <strong style="font-family: monospace;">{{ $stockOpname->no_stock_opname }}</strong> &bull;
                            Tanggal: <strong>{{ $stockOpname->tanggal->format('d F Y') }}</strong>
                        </div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 8pt; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #cbd5e1;">
                    <div><strong>Gudang:</strong> {{ $stockOpname->gudang->nama_gudang }}</div>
                    <div><strong>Petugas:</strong> {{ $stockOpname->user->name ?? '-' }}</div>
                    <div><strong>Waktu Dokumen:</strong> {{ $stockOpname->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            <!-- KPI Summary Bar (Compact) -->
            @php
                $totItem = $stockOpname->details->count();
                $totCocok = $stockOpname->details->where('selisih', 0)->count();
                $totLebih = $stockOpname->details->filter(fn($d) => (float)$d->selisih > 0)->count();
                $totKurang = $stockOpname->details->filter(fn($d) => (float)$d->selisih < 0)->count();
            @endphp
            <div style="display: flex; gap: 8px; margin-bottom: 6px; font-size: 8pt;">
                <div style="flex: 1; border: 1px solid #cbd5e1; padding: 4px 6px; background-color: #f8fafc; border-radius: 3px;">
                    <div style="font-size: 7pt; color: #64748b;">TOTAL PRODUK</div>
                    <div style="font-weight: 700; font-size: 9pt;">{{ $totItem }} Item</div>
                </div>
                <div style="flex: 1; border: 1px solid #bbf7d0; padding: 4px 6px; background-color: #f0fdf4; border-radius: 3px;">
                    <div style="font-size: 7pt; color: #166534;">SESUAI (COCOK)</div>
                    <div style="font-weight: 700; font-size: 9pt; color: #166534;">{{ $totCocok }} Item</div>
                </div>
                <div style="flex: 1; border: 1px solid #fed7aa; padding: 4px 6px; background-color: #fff7ed; border-radius: 3px;">
                    <div style="font-size: 7pt; color: #9a3412;">SELISIH LEBIH (+)</div>
                    <div style="font-weight: 700; font-size: 9pt; color: #9a3412;">{{ $totLebih }} Item</div>
                </div>
                <div style="flex: 1; border: 1px solid #fecaca; padding: 4px 6px; background-color: #fef2f2; border-radius: 3px;">
                    <div style="font-size: 7pt; color: #991b1b;">SELISIH KURANG (-)</div>
                    <div style="font-weight: 700; font-size: 9pt; color: #991b1b;">{{ $totKurang }} Item</div>
                </div>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 3%; text-align: center;">NO</th>
                        <th style="width: 12%;">KODE</th>
                        <th style="width: 33%;">NAMA PRODUK & SPESIFIKASI</th>
                        <th style="width: 8%; text-align: center;">SATUAN</th>
                        <th style="width: 10%; text-align: center;">STOK SISTEM</th>
                        <th style="width: 10%; text-align: center;">STOK FISIK</th>
                        <th style="width: 10%; text-align: center;">SELISIH</th>
                        <th style="width: 14%;">CATATAN PENYESUAIAN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockOpname->details as $dIdx => $d)
                        @php
                            $satLabel = $d->produk->satuan ? (is_object($d->produk->satuan) ? $d->produk->satuan->label() : (string) $d->produk->satuan) : 'Pcs';
                            $sel = (float)$d->selisih;
                            $sis = (float)$d->stok_sistem;
                            $fis = (float)$d->stok_fisik;
                            $isPvcD = str_contains(strtolower($satLabel), 'meter') && !empty($d->produk->panjang_meter) && !empty($d->produk->isi_per_kemasan);
                            $isiD = (float)($d->produk->isi_per_kemasan ?? 0);
                            $pjgD = (float)($d->produk->panjang_meter ?? 0);
                        @endphp
                        <tr>
                            <td style="text-align: center; font-weight: bold;">{{ $dIdx + 1 }}</td>
                            <td style="font-family: monospace; font-weight: 700;">{{ $d->produk->kode_katalog ?? '-' }}</td>
                            <td>
                                <div style="font-weight: 600;">{{ $d->produk->nama ?? '-' }}</div>
                                <div style="font-size: 7pt; color: #475569;">
                                    {{ $d->produk->kategori->nama_kategori ?? '' }} &bull; {{ $d->produk->merek->nama_merek ?? '' }}
                                    @if($isPvcD)
                                        &bull; <span style="color: #1e3a8a;">{{ $d->produk->kemasan ?? 'Dus' }} ({{ $isiD }} Lbr @ {{ $pjgD }}m = {{ $isiD * $pjgD }}m)</span>
                                    @elseif(!empty($d->produk->kemasan))
                                        &bull; <span style="color: #1e3a8a;">{{ $d->produk->kemasan }}@if($isiD > 0) (isi {{ number_format($isiD, 0, ',', '.') }})@endif</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align: center;">{{ $satLabel }}</td>
                            <td style="text-align: center;">{{ number_format($sis, 0, ',', '.') }}</td>
                            <td style="text-align: center; font-weight: 700;">{{ number_format($fis, 0, ',', '.') }}</td>
                            <td style="text-align: center; font-weight: 700; color: {{ $sel > 0 ? '#166534' : ($sel < 0 ? '#991b1b' : '#334155') }};">
                                @if($sel > 0)
                                    +{{ number_format($sel, 0, ',', '.') }}
                                @elseif($sel < 0)
                                    {{ number_format($sel, 0, ',', '.') }}
                                @else
                                    0
                                @endif
                            </td>
                            <td style="font-size: 7.5pt;">{{ $d->catatan ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Berita Acara Signature Block -->
            <div class="report-signature">
                <div style="display: flex; justify-content: space-between; text-align: center; font-size: 8pt;">
                    <div style="width: 30%;">
                        <div>Petugas Lapangan / Staff Gudang,</div>
                        <div style="height: 38px;"></div>
                        <div style="font-weight: bold; text-decoration: underline;">................................................</div>
                        <div style="font-size: 7pt; color: #64748b;">Tgl: .......................................</div>
                    </div>
                    <div style="width: 30%;">
                        <div>Petugas Input Sistem (Operator),</div>
                        <div style="height: 38px;"></div>
                        <div style="font-weight: bold; text-decoration: underline;">{{ $stockOpname->user->name ?? 'Operator' }}</div>
                        <div style="font-size: 7pt; color: #64748b;">Tgl: {{ $stockOpname->tanggal->format('d/m/Y') }}</div>
                    </div>
                    <div style="width: 30%;">
                        <div>Mengetahui & Menyetujui (Owner),</div>
                        <div style="height: 38px;"></div>
                        <div style="font-weight: bold; text-decoration: underline;">................................................</div>
                        <div style="font-size: 7pt; color: #64748b;">Tgl: .......................................</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Screen-Only Application UI -->
    <div class="no-print">
        <!-- Page Header (Screen Only) -->
        <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 flex-column flex-md-row gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <a href="{{ route('inventory.stock-opname.index') }}" class="btn btn-outline-secondary btn-sm py-0 px-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h4 class="mb-0 font-weight-bold text-dark">Detail Stok Opname: {{ $stockOpname->no_stock_opname }}</h4>
                    @php $statusEnum = $stockOpname->status; @endphp
                    <span class="badge bg-{{ $statusEnum->badgeColor() }} px-2 py-1 shadow-sm">
                        {{ $statusEnum->label() }}
                    </span>
                </div>
                <p class="text-muted mb-0 small">Informasi hasil pemeriksaan stok fisik dan rekonsiliasi mutasi sistem.</p>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap w-100 w-md-auto">
                @if($isDraft)
                    <button type="button" wire:click="openPrintCountSheetModal" class="btn btn-outline-secondary btn-sm shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-printer me-1"></i> Cetak Lembar Hitung
                    </button>
                    <button type="button" wire:click="exportTemplate" class="btn btn-outline-success btn-sm shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
                    </button>
                    <button type="button" wire:click="openImportExcelModal" class="btn btn-success btn-sm shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Import Excel
                    </button>
                @else
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-printer me-1"></i> Cetak Berita Acara
                    </button>
                    <button type="button" wire:click="exportTemplate" class="btn btn-outline-success btn-sm shadow-sm flex-fill flex-md-grow-0">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Hasil Excel
                    </button>
                @endif
            </div>
        </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('warning'))
        <div class="alert alert-warning alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($stockOpname->status === \App\Domains\Inventory\Enums\StatusStockOpname::Dibatalkan)
        <div class="alert alert-danger d-flex align-items-center mb-4 no-print" role="alert">
            <i class="bi bi-x-octagon-fill fs-4 me-3 flex-shrink-0"></i>
            <div>
                <div class="fw-bold">Dokumen Stok Opname Ini Telah Dibatalkan</div>
                <div class="small">
                    Dibatalkan pada {{ $stockOpname->dibatalkan_at ? $stockOpname->dibatalkan_at->format('d/m/Y H:i') : '-' }} 
                    oleh <strong>{{ $stockOpname->dibatalkanBy->name ?? 'User' }}</strong>.
                    <br>Alasan Pembatalan: <em>"{{ $stockOpname->alasan_batal ?? '-' }}"</em>
                </div>
            </div>
        </div>
    @endif

    <!-- Metadata Card (Screen Only) -->
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="text-muted small">Nomor Dokumen</div>
                    <div class="fw-bold text-primary font-monospace">{{ $stockOpname->no_stock_opname }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted small">Tanggal Pelaksanaan</div>
                    <div class="fw-semibold text-dark">{{ $stockOpname->tanggal->format('d F Y') }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted small">Lokasi Gudang</div>
                    <div class="fw-semibold text-dark">
                        <i class="bi bi-building me-1 text-secondary"></i>{{ $stockOpname->gudang->nama_gudang }}
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted small">Petugas Pelaksana</div>
                    <div class="fw-semibold text-dark">{{ $stockOpname->user->name ?? '-' }}</div>
                </div>
                @if($stockOpname->catatan)
                    <div class="col-12 mt-2 pt-2 border-top">
                        <div class="text-muted small fw-medium">Catatan Dokumen:</div>
                        <div class="p-2 bg-light rounded text-secondary small">{{ $stockOpname->catatan }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-1 text-primary"></i> Rincian Item Opname</h6>
            @if($isDraft)
                <div class="d-flex gap-2">
                    <select wire:model="selectedProdukId" class="form-select form-select-sm" style="max-width: 250px;">
                        <option value="">-- Tambah Produk --</option>
                        @foreach($availableProducts as $p)
                            <option value="{{ $p->id }}">[{{ $p->kode_katalog ?? '-' }}] {{ $p->nama }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="addProduct" class="btn btn-sm btn-outline-primary" @if(!$selectedProdukId) disabled @endif>
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover {{ $isDraft ? 'table-mobile-cards' : '' }} align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3" style="width: 35%;">Produk & Kemasan</th>
                            <th class="py-3 text-center" style="width: 15%;">Stok Sistem</th>
                            <th class="py-3 text-center" style="width: 18%;">Stok Fisik</th>
                            <th class="py-3 text-center" style="width: 14%;">Selisih</th>
                            <th class="py-3" style="width: 18%;">Catatan</th>
                            @if($isDraft)
                                <th class="pe-3 py-3 text-end no-print" style="width: 5%;">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @if($isDraft)
                            @forelse($items as $index => $item)
                                @php
                                    $selisih = (float)($item['stok_fisik'] ?? 0) - (float)$item['stok_sistem'];
                                @endphp
                                <tr wire:key="draft-item-{{ $item['produk_id'] }}">
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark">{{ $item['nama_produk'] }}</div>
                                        <div class="small text-muted font-monospace">{{ $item['kode_produk'] }} &bull; Satuan: {{ $item['satuan'] }}</div>
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
                                    </td>
                                    <td class="text-center" data-label="Stok Sistem">
                                        <span class="badge bg-light text-dark border fs-7 px-2 py-1">
                                            {{ number_format((float) $item['stok_sistem'], 0, ',', '.') }} {{ $item['satuan'] }}
                                        </span>
                                    </td>
                                    <td class="text-center" data-label="Stok Fisik">
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
                                        </div>
                                        @error('items.'.$index.'.stok_fisik') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-center" data-label="Selisih">
                                        @if($selisih > 0)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">+{{ number_format($selisih, 0, ',', '.') }}</span>
                                        @elseif($selisih < 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">{{ number_format($selisih, 0, ',', '.') }}</span>
                                        @else
                                            <span class="badge bg-light text-muted border px-2 py-1">0 (Cocok)</span>
                                        @endif
                                    </td>
                                    <td data-label="Catatan Item">
                                        <input type="text" wire:model="items.{{ $index }}.catatan" class="form-control form-control-sm py-0 fs-7" placeholder="Catatan item...">
                                    </td>
                                    <td class="pe-3 text-end no-print">
                                        <button type="button" wire:click="removeProduct({{ $index }})" class="btn btn-outline-danger btn-sm py-1 px-2 w-100 w-md-auto" title="Hapus">
                                            <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus Item</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada item dalam draft ini.</td>
                                </tr>
                            @endforelse
                        @else
                            @foreach($stockOpname->details as $d)
                                @php
                                    $satuanLabel = $d->produk->satuan ? (is_object($d->produk->satuan) ? $d->produk->satuan->label() : (string) $d->produk->satuan) : 'Pcs';
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark">{{ $d->produk->nama ?? '-' }}</div>
                                        <div class="small text-muted font-monospace">{{ $d->produk->kode_katalog ?? '-' }} &bull; Satuan: {{ $satuanLabel }}</div>
                                        @if(!empty($d->produk->kemasan) || !empty($d->produk->isi_per_kemasan))
                                            @php
                                                $isPvcDetailBadge = str_contains(strtolower($satuanLabel), 'meter') && !empty($d->produk->panjang_meter) && !empty($d->produk->isi_per_kemasan);
                                                $firstWordSatD = strtolower(strtok($satuanLabel, " (/"));
                                                $firstWordKemD = strtolower(strtok($d->produk->kemasan ?? '', " (/"));
                                                $isSameUnitKemD = ($firstWordKemD !== '' && (
                                                    $firstWordSatD === $firstWordKemD
                                                    || (str_contains(strtolower($satuanLabel), 'dus') && str_contains(strtolower($d->produk->kemasan ?? ''), 'dus'))
                                                ));
                                                $isiDVal = (float)($d->produk->isi_per_kemasan ?? 0);
                                                $pjgDVal = (float)($d->produk->panjang_meter ?? 0);
                                            @endphp
                                            <div class="badge bg-light text-primary border border-primary-subtle mt-1" style="font-size: 0.72rem;">
                                                <i class="bi bi-box-seam me-1"></i>{{ $d->produk->kemasan ?? 'Kemasan' }}
                                                @if($isPvcDetailBadge)
                                                    ({{ $isiDVal }} Lembar @ {{ $pjgDVal }}m = {{ $isiDVal * $pjgDVal }}m/{{ strtolower($d->produk->kemasan ?? 'dus') }})
                                                @elseif($isSameUnitKemD)
                                                    (isi {{ number_format($isiDVal, 0, ',', '.') }} pcs/{{ strtolower($d->produk->kemasan ?? 'dus') }})
                                                @elseif(!empty($d->produk->isi_per_kemasan))
                                                    (isi {{ number_format($isiDVal, 0, ',', '.') }} {{ $satuanLabel }})
                                                @endif
                                                @if(!$isPvcDetailBadge && !empty($d->produk->panjang_meter))
                                                    @ {{ $pjgDVal }}m
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            {{ number_format((float) $d->stok_sistem, 0, ',', '.') }} {{ $satuanLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">
                                        {{ number_format((float) $d->stok_fisik, 0, ',', '.') }} {{ $satuanLabel }}
                                    </td>
                                    <td class="text-center">
                                        @if($d->selisih > 0)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                +{{ number_format((float) $d->selisih, 0, ',', '.') }} (Lebih)
                                            </span>
                                        @elseif($d->selisih < 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                {{ number_format((float) $d->selisih, 0, ',', '.') }} (Kurang)
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border px-2 py-1">
                                                0 (Cocok)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        {{ $d->catatan ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>



    <!-- Actions Toolbar (Screen Only) -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-2 no-print">
        <a href="{{ route('inventory.stock-opname.index') }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>

        <div class="d-flex gap-2 flex-wrap flex-fill flex-sm-grow-0 justify-content-end">
            @if($isDraft)
                @can('delete', $stockOpname)
                    <button type="button" wire:click="promptDeleteDraftModal" class="btn btn-outline-danger flex-fill flex-sm-grow-0" wire:loading.attr="disabled">
                        <i class="bi bi-trash me-1"></i> Hapus Draft
                    </button>
                @endcan
                <button type="button" wire:click="refreshDraftSnapshots" class="btn btn-outline-warning flex-fill flex-sm-grow-0" wire:loading.attr="disabled" title="Perbarui baseline snapshot dengan stok live sistem saat ini jika ada mutasi di tengah opname">
                    <i class="bi bi-arrow-clockwise me-1"></i> Perbarui Snapshot
                </button>
                <button type="button" wire:click="updateDraft" class="btn btn-outline-primary flex-fill flex-sm-grow-0" wire:loading.attr="disabled">
                    <i class="bi bi-save me-1"></i> Simpan Draft
                </button>
                <button type="button" wire:click="completeDraft" class="btn btn-success flex-fill flex-sm-grow-0" wire:loading.attr="disabled" onclick="return confirm('Selesaikan Stok Opname ini dan sesuaikan stok di sistem?') || event.stopImmediatePropagation()">
                    <i class="bi bi-check2-circle me-1"></i> Selesaikan Stok Opname
                </button>
            @endif

            @if($stockOpname->status === \App\Domains\Inventory\Enums\StatusStockOpname::Selesai)
                @can('delete', $stockOpname)
                    <button type="button" wire:click="promptCancelModal" class="btn btn-outline-danger flex-fill flex-sm-grow-0" wire:loading.attr="disabled">
                        <i class="bi bi-x-circle me-1"></i> Batalkan Stok Opname
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <!-- Poin 1: Modal Kalkulator Kemasan Cerdas (Draft Mode) -->
    @if($isPackagingCalcOpen)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1070;" aria-modal="true" role="dialog">
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
                            <button type="button" wire:click="closePackagingCalculator" class="btn btn-outline-secondary">Batal</button>
                            <button type="button" wire:click="applyPackagingCalculation" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Terapkan ke Stok Fisik
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Poin 3: Modal Cetak Lembar Hitung Lapangan (Draft Mode) -->
    @if($isPrintCountSheetModalOpen)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
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
                            Pilih mode lembar kerja fisik yang akan dicetak untuk dibawa oleh staf gudang:
                        </p>

                        <div class="list-group mb-4">
                            <label class="list-group-item list-group-item-action d-flex gap-3 align-items-center cursor-pointer p-3 rounded-3 mb-2 border">
                                <input class="form-check-input flex-shrink-0" type="radio" wire:model.live="countSheetMode" value="blind">
                                <div>
                                    <div class="fw-bold text-dark"><i class="bi bi-shield-check text-success me-1"></i> Mode Blind Count (Hitung Buta) — Direkomendasikan</div>
                                    <small class="text-muted">Kolom stok sistem disembunyikan. Petugas wajib menghitung fisik asli tanpa melihat acuan angka sistem.</small>
                                </div>
                            </label>
                            <label class="list-group-item list-group-item-action d-flex gap-3 align-items-center cursor-pointer p-3 rounded-3 border">
                                <input class="form-check-input flex-shrink-0" type="radio" wire:model.live="countSheetMode" value="guided">
                                <div>
                                    <div class="fw-bold text-dark"><i class="bi bi-eye text-primary me-1"></i> Mode Guided Count (Dengan Angka Sistem)</div>
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

    <!-- Poin 5: Modal Import Excel (Draft Mode) -->
    @if($isImportExcelModalOpen)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
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
                            Unggah file spreadsheet Excel (.xlsx) yang telah diisi kolom <code>STOK FISIK</code>. Hasilnya akan langsung memperbarui draft opname ini.
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
                                        {{ count($importSummary['unmatched']) }} kode barang tidak cocok dengan draft.
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
                                    <i class="bi bi-check2-circle me-1"></i> Terapkan ke Draft
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Modal (Existing) -->
    @if ($isCancelModalOpen)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-white py-3 border-0">
                        <h6 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Batalkan Stok Opname Ini?</h6>
                        <button type="button" wire:click="closeCancelModal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <div class="alert alert-warning py-2 small border-0 mb-3 rounded-3">
                            <strong>Perhatian:</strong> Membatalkan stok opname akan membalikkan (reversal) seluruh mutasi stok penyesuaian yang pernah dibuat oleh dokumen ini.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Alasan Pembatalan <span class="text-danger">*</span></label>
                            <textarea wire:model="alasan_batal" rows="3" class="form-control form-control-sm @error('alasan_batal') is-invalid @enderror" placeholder="Contoh: Salah input gudang, data dihitung ulang..."></textarea>
                            @error('alasan_batal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" wire:click="closeCancelModal" class="btn btn-outline-secondary">Tutup</button>
                            <button type="button" wire:click="cancelStockOpname" class="btn btn-danger" wire:loading.attr="disabled">
                                <i class="bi bi-x-circle me-1"></i> Ya, Batalkan Dokumen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Draft Modal (Existing) -->
    @if ($isDeleteDraftModalOpen)
        <div class="modal fade show d-block no-print" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-body p-4 text-center">
                        <div class="d-inline-flex p-3 rounded-circle bg-danger bg-opacity-10 text-danger mb-3">
                            <i class="bi bi-trash-fill fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Hapus Draft Opname?</h5>
                        <p class="text-muted small mb-4">
                            Draft dokumen <strong>{{ $stockOpname->no_stock_opname }}</strong> beserta seluruh baris itemnya akan dihapus secara permanen.
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" wire:click="closeDeleteDraftModal" class="btn btn-outline-secondary px-4">Batal</button>
                            <button type="button" wire:click="deleteDraft" class="btn btn-danger px-4" wire:loading.attr="disabled">
                                <i class="bi bi-trash me-1"></i> Ya, Hapus Draft
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>
</div>
