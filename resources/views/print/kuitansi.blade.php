<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kuitansi Pembayaran - {{ $pembayaran->no_pembayaran }}</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px 35px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 4px;
        }

        /* Screen Action Bar */
        .action-bar {
            max-width: 800px;
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
        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #5c636a;
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
        .kuitansi-header {
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
        .kuitansi-title-block {
            text-align: right;
        }
        .kuitansi-title-block h2 {
            font-size: 18px;
            font-weight: 800;
            color: #198754;
            text-transform: uppercase;
            letter-spacing: 1px;
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

        /* Formal Receipt Box */
        .receipt-main-box {
            background-color: #fafbfc;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .receipt-row {
            display: flex;
            margin-bottom: 10px;
            align-items: baseline;
            font-size: 12px;
        }
        .receipt-row:last-child {
            margin-bottom: 0;
        }
        .receipt-label {
            width: 150px;
            flex-shrink: 0;
            color: #555;
            font-weight: 600;
        }
        .receipt-colon {
            width: 15px;
            color: #555;
        }
        .receipt-content {
            flex-grow: 1;
            color: #111;
        }
        .terbilang-box {
            background-color: #eef2f7;
            border-left: 4px solid #198754;
            padding: 8px 12px;
            font-style: italic;
            font-weight: 600;
            color: #1e3a8a;
            border-radius: 2px;
            display: inline-block;
            width: 100%;
        }
        .nominal-badge {
            font-size: 16px;
            font-weight: 800;
            color: #198754;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        /* Allocation Table */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #eee;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .items-table th {
            background-color: #f0f3f6;
            color: #333;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 8px 6px;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
        }
        .items-table td {
            padding: 7px 6px;
            border-bottom: 1px solid #eee;
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
            border: 1px solid #e0e0e0;
            padding: 10px;
            border-radius: 4px;
            background-color: #fafbfc;
        }
        .notes-box h4 {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        .totals-table td {
            padding: 4px 6px;
        }
        .totals-table .total-label {
            color: #555;
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
            color: #198754;
            padding: 6px 6px;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            text-align: center;
            margin-top: 25px;
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
            color: #444;
            margin-bottom: 55px;
        }
        .sig-line {
            border-top: 1px dotted #333;
            width: 180px;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 11px;
            font-weight: 700;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-lunas {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #a3cfbb;
        }
        .status-sebagian {
            background-color: #fff3cd;
            color: #664d03;
            border: 1px solid #ffe69c;
        }
        .status-belum {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
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
                flex-wrap: wrap;
                gap: 6px;
            }
            .action-bar .btn {
                flex: 1 1 auto;
                justify-content: center;
                text-align: center;
                padding: 8px 10px;
                font-size: 12px;
            }
            .kuitansi-header {
                flex-direction: column;
                gap: 12px;
            }
            .kuitansi-title-block {
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
            .receipt-main-box {
                padding: 12px 14px;
            }
            .receipt-row {
                flex-direction: column;
                margin-bottom: 10px;
            }
            .receipt-label {
                width: 100%;
                font-size: 11px;
                margin-bottom: 2px;
            }
            .receipt-colon {
                display: none;
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
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('pembayaran.show', $pembayaran->id) }}" class="btn btn-outline">
                &larr; Kembali ke Detail
            </a>
            <a href="{{ route('pembayaran.print.thermal', $pembayaran) }}" class="btn btn-secondary">
                🧾 Struk Kasir (POS)
            </a>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Kuitansi (Print / PDF)
            </button>
        </div>
    </div>

    <!-- Printable Kuitansi Page -->
    <div class="page-container">

        <!-- Header -->
        @php
            $toko = \App\Domains\Administration\Models\ProfilPerusahaan::getSettings();
        @endphp
        <div class="kuitansi-header">
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
                    @if ($toko->slogan)
                        <div class="subtitle">{{ $toko->slogan }}</div>
                    @endif
                    <div class="contact">
                        {{ $toko->alamat ?: 'Alamat Toko' }}
                        @if ($toko->telepon) &bull; Telp / WA: {{ $toko->telepon }} @endif
                        @if ($toko->email) &bull; Email: {{ $toko->email }} @endif
                    </div>
                </div>
            </div>
            <div class="kuitansi-title-block">
                <h2>KUITANSI PEMBAYARAN</h2>
                <table class="meta-table">
                    <tr>
                        <td class="label">No. Kuitansi:</td>
                        <td class="value font-mono">{{ $pembayaran->no_pembayaran }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal:</td>
                        <td class="value">
                            {{ $pembayaran->tanggal->format('d/m/Y') }}
                            @if ($pembayaran->created_at)
                                <span style="font-size: 10.5px; color: #555; font-weight: normal; margin-left: 2px;">
                                    {{ $pembayaran->created_at->format('H:i') }} WIB
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Metode Bayar:</td>
                        <td class="value">{{ $pembayaran->metode->label() }}</td>
                    </tr>
                    <tr>
                        <td class="label">Penerima / Kasir:</td>
                        <td class="value">{{ $pembayaran->user->name ?? ($pembayaran->creator->name ?? 'Kasir') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Receipt Box -->
        <div class="receipt-main-box">
            <div class="receipt-row">
                <span class="receipt-label">Telah Diterima Dari</span>
                <span class="receipt-colon">:</span>
                <span class="receipt-content">
                    <strong>{{ $pembayaran->pelanggan->nama ?? 'Pelanggan Umum' }}</strong>
                    @if ($pembayaran->pelanggan?->kontak)
                        <span style="color: #666; font-size: 11px;">({{ $pembayaran->pelanggan->kontak }})</span>
                    @endif
                    @if ($pembayaran->pelanggan?->tipe)
                        <span style="font-size: 10px; background: #eee; padding: 1px 6px; border-radius: 3px; margin-left: 5px;">
                            {{ $pembayaran->pelanggan->tipe->label() }}
                        </span>
                    @endif
                </span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">Uang Sejumlah</span>
                <span class="receipt-colon">:</span>
                <div class="receipt-content">
                    <div class="terbilang-box">
                        # {{ $terbilang }} #
                    </div>
                </div>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">Jumlah Nominal</span>
                <span class="receipt-colon">:</span>
                <span class="receipt-content nominal-badge">
                    Rp {{ number_format($pembayaran->jumlah_bayar, 0, ',', '.') }}
                </span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">Untuk Pembayaran</span>
                <span class="receipt-colon">:</span>
                <span class="receipt-content">
                    Pelunasan Piutang Transaksi Penjualan Natural Gypsum & PVC
                </span>
            </div>

            @if ($pembayaran->catatan)
                <div class="receipt-row">
                    <span class="receipt-label">Catatan Tambahan</span>
                    <span class="receipt-colon">:</span>
                    <span class="receipt-content" style="font-style: italic; color: #555;">
                        {{ $pembayaran->catatan }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Rincian Alokasi Faktur -->
        <div class="section-title">
            Rincian Alokasi Faktur Penjualan
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">No.</th>
                    <th class="text-start">No. Faktur</th>
                    <th class="text-center" style="width: 85px;">Tanggal</th>
                    <th class="text-end" style="width: 120px;">Total Faktur</th>
                    <th class="text-end" style="width: 120px;">Sisa Piutang</th>
                    <th class="text-center" style="width: 90px;">Status Faktur</th>
                    <th class="text-end" style="width: 130px;">Jumlah Alokasi (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pembayaran->alokasi as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-start font-mono fw-bold" data-label="No. Faktur">
                            {{ $item->penjualan->no_penjualan ?? '-' }}
                        </td>
                        <td class="text-center" data-label="Tanggal">
                            {{ $item->penjualan?->tanggal?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td class="text-end font-mono" data-label="Total Faktur">
                            Rp {{ number_format($item->penjualan?->total ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-mono" data-label="Sisa Piutang">
                            Rp {{ number_format($item->penjualan?->sisa_piutang ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-center" data-label="Status Faktur">
                            @php
                                $status = $item->penjualan?->status_pembayaran?->value ?? 'belum';
                                $statusLabel = $item->penjualan?->status_pembayaran?->label() ?? 'Belum Lunas';
                            @endphp
                            <span class="status-badge status-{{ $status }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-end font-mono fw-bold" data-label="Jumlah Alokasi (Rp)">
                            Rp {{ number_format($item->jumlah_dialokasikan, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 15px; color: #888;">
                            Tidak ada faktur yang dialokasikan langsung pada pembayaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary & Totals -->
        <div class="summary-wrapper">
            <div class="notes-box">
                <h4>Informasi & Ketentuan:</h4>
                <p>&bull; Kuitansi ini adalah bukti pembayaran yang sah dari Natural Gypsum & PVC.</p>
                <p>&bull; Faktur yang telah dialokasikan otomatis mengurangi status piutang pelanggan.</p>
                @if ($pembayaran->sisa_belum_dialokasikan > 0)
                    <p style="color: #0d6efd; font-weight: 600; margin-top: 4px;">
                        &bull; Sisa dana Rp {{ number_format($pembayaran->sisa_belum_dialokasikan, 0, ',', '.') }} tersimpan sebagai titipan/deposit pelanggan.
                    </p>
                @endif
            </div>

            <table class="totals-table">
                <tr>
                    <td class="total-label">Total Pembayaran Diterima:</td>
                    <td class="total-val font-mono">
                        Rp {{ number_format($pembayaran->jumlah_bayar, 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="total-label">Total Dialokasikan:</td>
                    <td class="total-val font-mono">
                        Rp {{ number_format($pembayaran->total_dialokasikan, 0, ',', '.') }}
                    </td>
                </tr>
                @if ($pembayaran->sisa_belum_dialokasikan > 0)
                    <tr>
                        <td class="total-label" style="color: #0d6efd;">Sisa Belum Dialokasikan:</td>
                        <td class="total-val font-mono" style="color: #0d6efd;">
                            Rp {{ number_format($pembayaran->sisa_belum_dialokasikan, 0, ',', '.') }}
                        </td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td class="total-label">TOTAL PELUNASAN:</td>
                    <td class="total-val font-mono">
                        Rp {{ number_format($pembayaran->total_dialokasikan, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-title">Yang Menyerahkan,</div>
                <div class="sig-line">
                    {{ $pembayaran->pelanggan->nama ?? 'Pelanggan / Pembayar' }}
                </div>
            </div>
            <div class="sig-box">
                <div class="sig-title">
                    Natural Gypsum & PVC<br>
                    <span style="font-size: 10px; font-weight: normal; color: #666;">Diterima Oleh Kasir</span>
                </div>
                <div class="sig-line">
                    {{ $pembayaran->user->name ?? ($pembayaran->creator->name ?? 'Kasir / Petugas') }}
                </div>
            </div>
        </div>

    </div>

</body>
</html>
