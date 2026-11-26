<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($isPO ?? false) ? 'Surat Pesanan (PO)' : 'Bukti Pembelian & Barang Masuk' }} - {{ $pembelian->no_pembelian }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            color: #222;
            background-color: #f4f6f9;
            padding: 20px;
        }
        .page-container {
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            padding: 30px 35px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 4px;
        }

        /* Screen Action Bar */
        .action-bar {
            max-width: 850px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary {
            background-color: #0d6efd;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .btn-outline {
            background-color: #fff;
            color: #333;
            border-color: #ccc;
        }
        .btn-outline:hover {
            background-color: #f8f9fa;
        }

        /* Header Layout */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #222;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .company-info h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #111;
            margin-bottom: 3px;
        }
        .company-info .subtitle {
            font-size: 11px;
            color: #555;
            font-weight: 600;
            margin-bottom: 3px;
        }
        .company-info .contact {
            font-size: 10.5px;
            color: #666;
            line-height: 1.4;
        }
        .title-block {
            text-align: right;
        }
        .title-block h2 {
            font-size: 17px;
            font-weight: 800;
            color: #0d6efd;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 5px;
        }
        .meta-table {
            margin-left: auto;
            border-collapse: collapse;
            font-size: 11px;
        }
        .meta-table td {
            padding: 2px 0 2px 8px;
        }
        .meta-table .label {
            color: #666;
            font-weight: 500;
        }
        .meta-table .value {
            font-weight: 700;
            color: #111;
        }

        /* Logistics & Supplier Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 18px;
            padding: 12px 16px;
            background-color: #fafbfc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .info-col h3 {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }
        .info-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 11.5px;
        }
        .info-label {
            width: 120px;
            color: #64748b;
            flex-shrink: 0;
        }
        .info-val {
            font-weight: 600;
            color: #0f172a;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 8px 6px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .items-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .font-mono { font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

        /* Summary & Totals */
        .summary-wrapper {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            align-items: start;
        }
        .notes-box {
            font-size: 10.5px;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 4px;
            background-color: #fafbfc;
        }
        .notes-box h4 {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #334155;
        }
        .terbilang-box {
            background-color: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 6px 10px;
            font-style: italic;
            font-weight: 600;
            color: #1e40af;
            border-radius: 2px;
            margin-top: 6px;
            font-size: 11px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        .totals-table td {
            padding: 5px 6px;
        }
        .totals-table .total-label {
            color: #64748b;
            text-align: right;
        }
        .totals-table .total-val {
            text-align: right;
            font-weight: 600;
        }
        .totals-table tr.grand-total td {
            border-top: 2px solid #222;
            border-bottom: 2px solid #222;
            font-size: 13px;
            font-weight: 800;
            color: #0d6efd;
            padding: 6px 6px;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .sig-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 90px;
        }
        .sig-title {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 55px;
        }
        .sig-line {
            border-top: 1px dotted #333;
            width: 150px;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 11px;
            font-weight: 700;
        }

        /* Mobile Screen Responsiveness (Does not affect Print / PDF) */
        @media screen and (max-width: 640px) {
            body {
                padding: 10px;
            }
            .page-container {
                padding: 16px 14px;
                border-radius: 8px;
            }
            .action-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            .action-bar > div {
                width: 100%;
                display: flex;
            }
            .action-bar .btn {
                width: 100%;
                justify-content: center;
                text-align: center;
                padding: 8px 12px;
                font-size: 12px;
            }
            .doc-header {
                flex-direction: column;
                gap: 12px;
            }
            .title-block {
                text-align: left;
                width: 100%;
                border-top: 1px dashed #cbd5e1;
                padding-top: 10px;
            }
            .meta-table {
                margin-left: 0;
                width: 100%;
            }
            .meta-table td {
                padding: 2px 4px 2px 0;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 10px 12px;
            }
            .items-table,
            .items-table tbody,
            .items-table tr,
            .items-table td {
                display: block;
                width: 100% !important;
            }
            .items-table thead {
                display: none;
            }
            .items-table tbody tr {
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                margin-bottom: 10px;
                padding: 8px 10px;
                background: #ffffff;
            }
            .items-table tbody td {
                border: none;
                border-bottom: 1px dashed #f1f5f9;
                padding: 4px 0;
                text-align: right;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }
            .items-table tbody td:first-child {
                display: none;
            }
            .items-table tbody td.item-desc-cell {
                display: block;
                text-align: left;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 6px;
                margin-bottom: 4px;
            }
            .items-table tbody td:last-child {
                border-bottom: none;
                padding-top: 6px;
            }
            .items-table tbody td[data-label]::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
                font-size: 10.5px;
                text-align: left;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .summary-wrapper {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .totals-table {
                order: -1;
                border: 1px solid #e2e8f0;
                border-radius: 5px;
            }
            .signatures {
                grid-template-columns: 1fr;
                gap: 14px;
                margin-top: 16px;
            }
            .sig-box {
                min-height: 65px;
                border: 1px dashed #e2e8f0;
                border-radius: 5px;
                padding: 8px;
            }
            .sig-title {
                margin-bottom: 24px;
            }
        }

        /* Print Settings */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            .page-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .action-bar {
                display: none !important;
            }
            @page {
                size: A4;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Toolbar (Hidden on Print) -->
    <div class="action-bar">
        <div>
            <a href="{{ route('pembelian.show', $pembelian->id) }}" class="btn btn-outline">
                &larr; Kembali ke Detail Pembelian
            </a>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ {{ ($isPO ?? false) ? 'Cetak Surat Pesanan PO (Print / PDF)' : 'Cetak Bukti Penerimaan (Print / PDF)' }}
            </button>
        </div>
    </div>

    <!-- Printable Document Page -->
    <div class="page-container">

        <!-- Header -->
        @php
            $toko = \App\Domains\Administration\Models\ProfilPerusahaan::getSettings();
        @endphp
        <div class="doc-header">
            <div style="display: flex; align-items: flex-start; gap: 14px; flex: 1;">
                <img src="{{ $toko->getLogoUrl() }}" alt="{{ $toko->nama_toko }}" style="height: 54px; max-width: 130px; width: auto; object-fit: contain; flex-shrink: 0;">
                <div class="company-info">
                    @if (strtoupper(trim($toko->nama_toko)) === 'NATURAL GYPSUM & PVC')
                        <div style="display: inline-block; line-height: 1; margin-bottom: 5px;">
                            <span style="display: none;">{{ $toko->nama_toko }}</span>
                            <div style="font-size: 23px; font-weight: 900; color: #18181B; letter-spacing: 3.5px;">NATURAL</div>
                            <div style="height: 2px; background-color: #18181B; margin: 3px 0 4px 0;"></div>
                            <div style="font-size: 10.5px; font-weight: 800; color: #B87D4B; letter-spacing: 4.8px;">GYPSUM &amp; PVC</div>
                        </div>
                    @else
                        <h1>{{ $toko->nama_toko }}</h1>
                    @endif
                    <div class="subtitle">{{ $toko->slogan ?: 'Aplikator dan Penyedia Bahan Gypsum dan PVC' }}</div>
                    <div class="contact">
                        {{ $toko->alamat ?: 'Jln. Piyungan Prambanan KM 2 Pucung' }}
                        @if ($toko->telepon) &bull; Telp / WA: <strong>{{ $toko->telepon }}</strong> @endif
                    </div>
                </div>
            </div>
            <div class="title-block">
                <h2>{{ ($isPO ?? false) ? 'SURAT PESANAN (PURCHASE ORDER)' : 'BUKTI PENERIMAAN BARANG' }}</h2>
                <table class="meta-table">
                    <tr>
                        <td class="label">{{ ($isPO ?? false) ? 'No. PO:' : 'No. Pembelian:' }}</td>
                        <td class="value font-mono">{{ $pembelian->no_pembelian }}</td>
                    </tr>
                    <tr>
                        <td class="label">{{ ($isPO ?? false) ? 'Tanggal PO:' : 'Tanggal Masuk:' }}</td>
                        <td class="value">{{ $pembelian->tanggal->format('d/m/Y') }}</td>
                    </tr>
                    @if ($pembelian->no_faktur_supplier)
                        <tr>
                            <td class="label">No. Faktur Pabrik:</td>
                            <td class="value font-mono">{{ $pembelian->no_faktur_supplier }}</td>
                        </tr>
                    @endif
                    @if ($pembelian->no_surat_jalan)
                        <tr>
                            <td class="label">No. Surat Jalan:</td>
                            <td class="value font-mono">{{ $pembelian->no_surat_jalan }}</td>
                        </tr>
                    @endif
                    @if ($pembelian->no_kontainer)
                        <tr>
                            <td class="label">No. Kontainer:</td>
                            <td class="value font-mono">{{ $pembelian->no_kontainer }}</td>
                        </tr>
                    @endif
                    @if ($pembelian->no_polisi)
                        <tr>
                            <td class="label">No. Polisi / Truk:</td>
                            <td class="value font-mono">{{ $pembelian->no_polisi }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Dibuat Oleh:</td>
                        <td class="value">{{ $pembelian->user->name ?? ($pembelian->creator->name ?? 'Admin Purchasing') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-col">
                <h3>Informasi Supplier / Pabrik</h3>
                <div class="info-row">
                    <span class="info-label">Nama Supplier</span>
                    <span class="info-val">{{ $pembelian->supplier->nama_supplier ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kontak / Sales</span>
                    <span class="info-val">{{ $pembelian->supplier->kontak ?? 'Tidak ada kontak' }}</span>
                </div>
            </div>
            <div class="info-col">
                <h3>Logistik & Ketentuan</h3>
                <div class="info-row">
                    <span class="info-label">Syarat Bayar</span>
                    <span class="info-val">{{ $pembelian->status_pembayaran?->label() ?? 'Sesuai Kesepakatan' }}
                        @if ($pembelian->tanggal_jatuh_tempo)
                            (Jatuh Tempo: {{ $pembelian->tanggal_jatuh_tempo->format('d/m/Y') }})
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Keterangan / Memo</span>
                    <span class="info-val" style="font-style: italic;">{{ $pembelian->keterangan ?: 'Tidak ada catatan khusus' }}</span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">No.</th>
                    <th class="text-start">Produk & SKU</th>
                    <th class="text-start" style="width: 140px;">Kategori & Merek</th>
                    <th class="text-start" style="width: 130px;">Gudang Tujuan</th>
                    <th class="text-end" style="width: 120px;">{{ ($isPO ?? false) ? 'Qty Dipesan' : 'Qty Masuk' }}</th>
                    @can('view_purchase_prices')
                        <th class="text-end" style="width: 125px;">Harga Beli Satuan</th>
                        <th class="text-end" style="width: 135px;">Subtotal (Rp)</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @foreach ($pembelian->details as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-start item-desc-cell">
                            <strong>{{ $detail->produk->nama ?? '-' }}</strong>
                            @if ($detail->produk?->kode_katalog)
                                <div class="font-mono" style="color: #64748b; font-size: 10px;">
                                    SKU: {{ $detail->produk->kode_katalog }}
                                </div>
                            @endif
                        </td>
                        <td class="text-start" data-label="Kategori & Merek">
                            <span>{{ $detail->produk?->kategori?->nama_kategori ?? '-' }}</span>
                            @if ($detail->produk?->merek?->nama_merek)
                                <div style="color: #64748b; font-size: 10px;">
                                    {{ $detail->produk->merek->nama_merek }}
                                </div>
                            @endif
                        </td>
                        <td class="text-start" data-label="Gudang Tujuan">
                            <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 3px; font-weight: 600;">
                                {{ $detail->gudang->nama_gudang ?? '-' }}
                            </span>
                        </td>
                        <td class="text-end font-mono fw-bold" data-label="{{ ($isPO ?? false) ? 'Qty Dipesan' : 'Qty Masuk' }}">
                            <div>
                                {{ number_format((float) $detail->jumlah, 2, ',', '.') }}
                                <span style="font-weight: normal; color: #64748b; font-size: 10px;">{{ $detail->produk?->satuan?->label() ?? '' }}</span>
                                @php
                                    $konv = $detail->produk?->getKonversiFisik((float) $detail->jumlah);
                                @endphp
                                @if ($detail->qty_lembar && $detail->panjang_meter)
                                    <div style="color: #0369a1; font-size: 9.5px; font-weight: normal;">
                                        ({{ (int) $detail->qty_lembar }} lbr &times; {{ (float) $detail->panjang_meter }}m)
                                    </div>
                                    @if ($konv && !empty($konv['jumlah_kemasan_utuh']))
                                        <div style="color: #64748b; font-size: 9px; font-style: italic; font-weight: normal;">
                                            {{ $konv['badge_text'] }}
                                        </div>
                                    @endif
                                @elseif ($konv && $konv['has_conversion'])
                                    <div style="color: #64748b; font-size: 9.5px; font-weight: normal;">
                                        {{ $konv['badge_text'] }}
                                    </div>
                                @endif
                            </div>
                        </td>
                        @can('view_purchase_prices')
                            <td class="text-end font-mono" data-label="Harga Beli Satuan">
                                <div>
                                    Rp {{ number_format((float) $detail->harga_beli_satuan, 2, ',', '.') }}
                                    @if ($detail->diskon_satuan > 0)
                                        <div style="font-size: 9px; color: #dc2626;">
                                            Diskon: -Rp {{ number_format((float) $detail->diskon_satuan, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end font-mono fw-bold" data-label="Subtotal (Rp)">
                                Rp {{ number_format((float) $detail->subtotal, 2, ',', '.') }}
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary & Totals -->
        <div class="summary-wrapper">
            <div class="notes-box">
                @if ($isPO ?? false)
                    <h4>Instruksi Pemesanan & Pengiriman:</h4>
                    <p>&bull; Harap siapkan dan kirimkan barang pesanan sesuai spesifikasi mutu dan kuantitas di atas.</p>
                    <p>&bull; Harap melampirkan Surat Jalan dan Faktur asli saat armada tiba di toko / gudang kami.</p>
                @else
                    <h4>Catatan & Verifikasi Gudang:</h4>
                    <p>&bull; Bukti ini menyatakan bahwa barang fisik telah diterima dan diverifikasi sesuai jumlah di atas.</p>
                    <p>&bull; Kuantitas barang telah otomatis tercatat ke kartu stok masing-masing gudang penerima.</p>
                @endif
                @can('view_purchase_prices')
                    <div class="terbilang-box">
                        Terbilang: # {{ $terbilang }} #
                    </div>
                @endcan
            </div>

            <table class="totals-table">
                <tr>
                    <td class="total-label">Total Barang / Item:</td>
                    <td class="total-val">
                        {{ $pembelian->details->count() }} Line Item
                    </td>
                </tr>
                <tr>
                    <td class="total-label">Total Kuantitas Fisik:</td>
                    <td class="total-val font-mono">
                        {{ number_format((float) $pembelian->details->sum('jumlah'), 2, ',', '.') }}
                    </td>
                </tr>
                @can('view_purchase_prices')
                    <tr class="grand-total">
                        <td class="total-label">TOTAL PEMBELIAN:</td>
                        <td class="total-val font-mono">
                            Rp {{ number_format((float) $pembelian->total, 2, ',', '.') }}
                        </td>
                    </tr>
                @endcan
            </table>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            @if ($isPO ?? false)
                <div class="sig-box">
                    <div class="sig-title">Dibuat Oleh (Purchasing),</div>
                    <div class="sig-line">
                        {{ $pembelian->user->name ?? ($pembelian->creator->name ?? 'Admin Purchasing') }}
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-title">Disetujui Oleh (Pimpinan),</div>
                    <div class="sig-line">
                        ( Manajemen Toko )
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-title">Konfirmasi Supplier (Pabrik),</div>
                    <div class="sig-line">
                        ( {{ $pembelian->supplier->nama_supplier ?? 'Sales Pabrik' }} )
                    </div>
                </div>
            @else
                <div class="sig-box">
                    <div class="sig-title">Pengirim / Ekspedisi,</div>
                    <div class="sig-line">
                        ( Sopir / Ekspedisi )
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-title">Diterima Oleh (Gudang),</div>
                    <div class="sig-line">
                        {{ $pembelian->penerima->name ?? ($pembelian->user->name ?? ($pembelian->creator->name ?? 'Petugas Gudang')) }}
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-title">Mengetahui (Pimpinan),</div>
                    <div class="sig-line">
                        ( Manajemen Toko )
                    </div>
                </div>
            @endif
        </div>

    </div>

</body>
</html>
