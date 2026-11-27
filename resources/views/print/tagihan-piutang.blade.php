<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Tagihan Piutang - {{ $pelanggan->nama }}</title>
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
            font-size: 11.5px;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 16px;
            line-height: 1.4;
        }
        .page-container {
            max-width: 840px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px 28px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            border-radius: 6px;
        }

        /* Screen Action Bar */
        .action-bar {
            max-width: 840px;
            margin: 0 auto 12px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 13px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .btn-primary {
            background-color: #18181B;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #27272A;
        }
        .btn-success {
            background-color: #059669;
            color: #ffffff;
        }
        .btn-outline {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        /* Document Header */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #18181B;
            padding-bottom: 12px;
            margin-bottom: 12px;
            gap: 14px;
            flex-wrap: wrap;
        }
        .company-brand {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
            min-width: 230px;
        }
        .company-logo {
            height: 48px;
            width: auto;
            object-fit: contain;
            flex-shrink: 0;
        }
        .company-info h1 {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .company-info .subtitle {
            font-size: 10.5px;
            color: #475569;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .company-info .contact {
            font-size: 10px;
            color: #64748b;
            line-height: 1.35;
        }
        .title-block {
            text-align: right;
            flex-shrink: 0;
        }
        .title-block h2 {
            font-size: 15px;
            font-weight: 800;
            color: #B87D4B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }
        .meta-table {
            margin-left: auto;
            border-collapse: collapse;
            font-size: 10px;
        }
        .meta-table td {
            padding: 1px 0 1px 8px;
            white-space: nowrap;
        }
        .meta-table .label {
            color: #64748b;
        }
        .meta-table .value {
            font-weight: 700;
            color: #0f172a;
        }

        /* Customer & Summary Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }
        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            background-color: #f8fafc;
            overflow: hidden;
        }
        .info-card-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            padding: 5px 10px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-card-body {
            padding: 7px 10px;
            font-size: 10.5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 3px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #64748b;
            white-space: nowrap;
        }
        .info-val {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
        }

        /* Invoices Table — Balanced 5-Column High-Density Layout */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10.5px;
        }
        .items-table thead th {
            background-color: #18181B;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.4px;
            padding: 6px 8px;
            border: 1px solid #18181B;
            white-space: nowrap;
        }
        .items-table tbody td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .col-tight {
            width: 1%;
            white-space: nowrap;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .nowrap { white-space: nowrap; }
        .font-mono { font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

        /* Material Tags inside Wide Column */
        .material-list {
            display: flex;
            flex-wrap: wrap;
            gap: 3px 4px;
            align-items: center;
        }
        .material-pill {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 9.5px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.3;
        }
        .material-pill strong {
            color: #0f172a;
        }
        .age-tag {
            display: inline-block;
            padding: 1px 5px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 3px;
            white-space: nowrap;
        }
        .age-lancar { background: #dcfce7; color: #166534; }
        .age-tempo { background: #fef3c7; color: #92400e; }
        .age-macet { background: #fee2e2; color: #991b1b; }

        /* Summary & Bank */
        .summary-wrapper {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
            align-items: start;
            page-break-inside: avoid;
        }
        .bank-card {
            padding: 8px 11px;
            background: #FAF8F5;
            border: 1px solid #D6CFC7;
            border-left: 4px solid #B87D4B;
            border-radius: 5px;
        }
        .bank-card-title {
            font-weight: 800;
            color: #18181B;
            font-size: 9.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .bank-item {
            font-family: monospace;
            font-size: 10px;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .totals-card {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .totals-table td {
            padding: 4.5px 9px;
            white-space: nowrap;
        }
        .totals-table .total-label {
            color: #475569;
            text-align: right;
            font-weight: 500;
        }
        .totals-table .total-val {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
        }
        .totals-table tr.grand-total {
            background-color: #18181B;
            color: #ffffff;
        }
        .totals-table tr.grand-total td {
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 800;
            padding: 6px 9px;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            text-align: center;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .sig-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 68px;
        }
        .sig-title {
            font-size: 10px;
            font-weight: 600;
            color: #475569;
        }
        .sig-line {
            border-top: 1px dashed #64748b;
            width: 85%;
            margin: 0 auto;
            padding-top: 3px;
            font-size: 10.5px;
            font-weight: 700;
            color: #0f172a;
        }

        @media screen and (max-width: 640px) {
            body { padding: 8px; }
            .page-container { padding: 14px; }
            .info-grid, .summary-wrapper { grid-template-columns: 1fr; }
            .title-block { text-align: left; width: 100%; }
            .meta-table { margin-left: 0; }
            .meta-table td { padding: 1px 8px 1px 0; }
            .action-bar .btn-group { width: 100%; }
            .action-bar .btn { flex: 1; justify-content: center; }

            /* Mobile Card Layout for Invoices Table */
            .items-table, .items-table tbody { display: block; width: 100%; }
            .items-table thead { display: none; }
            .items-table tbody tr {
                display: block;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 10px 12px;
                margin-bottom: 10px;
                background: #ffffff !important;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            }
            .items-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 8px;
                border: none;
                border-bottom: 1px dashed #e2e8f0;
                padding: 6px 0;
                width: 100% !important;
                white-space: normal !important;
                text-align: right;
            }
            .items-table tbody td:first-child {
                display: none;
            }
            .items-table tbody td:nth-child(3) {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }
            .items-table tbody td:last-child {
                border-bottom: none;
                padding-bottom: 2px;
            }
            .items-table tbody td::before {
                content: attr(data-label);
                font-size: 9.5px;
                font-weight: 700;
                text-transform: uppercase;
                color: #64748b;
                text-align: left;
                flex-shrink: 0;
            }
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page-container {
                box-shadow: none;
                padding: 0;
                max-width: 100% !important;
            }
            .action-bar {
                display: none !important;
            }
            .info-grid {
                grid-template-columns: 1fr 1fr !important;
            }
            .summary-wrapper {
                grid-template-columns: 1.2fr 1fr !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <div class="btn-group">
            <a href="{{ route('piutang.index') }}" class="btn btn-outline">
                &larr; Kembali ke Daftar Piutang
            </a>
            @can('record_payments')
                <a href="{{ route('pembayaran.create', ['pelanggan_id' => $pelanggan->id]) }}" class="btn btn-success">
                    💰 Catat Pembayaran Pelanggan Ini
                </a>
            @endcan
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Surat Tagihan (Print / PDF)
            </button>
        </div>
    </div>

    <div class="page-container">
        @php
            $toko = \App\Domains\Administration\Models\ProfilPerusahaan::getSettings();
        @endphp

        <!-- Header -->
        <div class="doc-header">
            <div class="company-brand">
                <img src="{{ $toko->getLogoUrl() }}" alt="{{ $toko->nama_toko }}" class="company-logo">
                <div class="company-info">
                    @if (strtoupper(trim($toko->nama_toko)) === 'NATURAL GYPSUM & PVC')
                        <div style="display: inline-block; line-height: 1; margin-bottom: 3px;">
                            <span style="display: none;">{!! $toko->nama_toko !!}</span>
                            <div style="font-size: 20px; font-weight: 900; color: #18181B; letter-spacing: 3px;">NATURAL</div>
                            <div style="height: 2px; background-color: #18181B; margin: 2px 0 3px 0;"></div>
                            <div style="font-size: 9.5px; font-weight: 800; color: #B87D4B; letter-spacing: 4.2px;">GYPSUM &amp; PVC</div>
                        </div>
                    @else
                        <h1>{!! $toko->nama_toko !!}</h1>
                    @endif
                    @if ($toko->slogan)
                        <div class="subtitle">{{ $toko->slogan }}</div>
                    @endif
                    <div class="contact">
                        {{ $toko->alamat ?: 'Jln. Piyungan Prambanan KM 2 Pucung' }}
                        @if ($toko->telepon) &bull; Telp / WA: <strong>{{ $toko->telepon }}</strong> @endif
                    </div>
                </div>
            </div>

            <div class="title-block">
                <h2>REKAPITULASI TAGIHAN PIUTANG</h2>
                <div style="font-size: 9px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; margin-bottom: 3px;">
                    STATEMENT OF ACCOUNT (SOA)
                </div>
                <table class="meta-table">
                    <tr>
                        <td class="label">Tanggal Cetak:</td>
                        <td class="value">{{ now()->format('d/m/Y H:i') }} WIB</td>
                    </tr>
                    <tr>
                        <td class="label">Standar Tempo:</td>
                        <td class="value">{{ $tempoStandarHari }} Hari Kalender</td>
                    </tr>
                    <tr>
                        <td class="label">Jumlah Faktur:</td>
                        <td class="value">{{ $invoices->count() }} Nota Belum Lunas</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Customer Info & Debt Summary -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <span>Kepada Yth. (Debitur / Pelanggan)</span>
                    <span>{{ $pelanggan->tipe->label() ?? 'Umum' }}</span>
                </div>
                <div class="info-card-body">
                    <div class="info-row">
                        <span class="info-label">Nama Pelanggan:</span>
                        <span class="info-val">{{ $pelanggan->nama }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kontak / WA:</span>
                        <span class="info-val font-mono nowrap">{{ $pelanggan->kontak ?: '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Plafon Kredit:</span>
                        <span class="info-val nowrap">
                            {{ (float) $pelanggan->limit_piutang > 0 ? 'Rp ' . number_format((float) $pelanggan->limit_piutang, 0, ',', '.') : 'Tanpa Limit Khusus' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <span>Ringkasan Status Tagihan</span>
                    <span>Per {{ now()->format('d/m/Y') }}</span>
                </div>
                <div class="info-card-body">
                    @php
                        $lewatTempoCount = $invoices->filter(fn ($i) => \Carbon\Carbon::parse($i->tanggal)->diffInDays(now()) > $tempoStandarHari)->count();
                    @endphp
                    <div class="info-row">
                        <span class="info-label">Total Nilai Faktur:</span>
                        <span class="info-val font-mono nowrap">Rp {{ number_format((float) $invoices->sum('total_akhir'), 0, ',', '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Cicilan Masuk:</span>
                        <span class="info-val font-mono nowrap" style="color: #059669;">Rp {{ number_format((float) $invoices->sum('total_dibayar'), 0, ',', '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status Jatuh Tempo:</span>
                        <span class="info-val nowrap" style="color: {{ $lewatTempoCount > 0 ? '#dc2626' : '#059669' }};">
                            {{ $lewatTempoCount > 0 ? $lewatTempoCount . ' Faktur Lewat Tempo' : 'Dalam Masa Tempo (' . $tempoStandarHari . ' Hr)' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Breakdown Table (Compact 5-Column High-Density Layout) -->
        <div style="width: 100%; overflow-x: auto;">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center col-tight">#</th>
                    <th class="text-start col-tight">No. Faktur &amp; Jatuh Tempo</th>
                    <th class="text-start">Rincian Material / Proyek &amp; Riwayat Cicilan</th>
                    <th class="text-end col-tight">Nilai &amp; Cicilan</th>
                    <th class="text-end col-tight">Sisa Tagihan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $idx => $inv)
                    @php
                        $tgl = \Carbon\Carbon::parse($inv->tanggal);
                        $jthTempo = $tgl->copy()->addDays($tempoStandarHari);
                        $umur = (int) $tgl->diffInDays(now());
                        $ageClass = $umur > 30 ? 'age-macet' : ($umur > $tempoStandarHari ? 'age-tempo' : 'age-lancar');
                        $groupedDetails = $inv->getGroupedDetailsForInvoice();
                    @endphp
                    <tr>
                        <td class="text-center font-mono col-tight" style="color: #64748b;" data-label="No">{{ $idx + 1 }}</td>
                        <td class="col-tight" data-label="No. Faktur & Tempo">
                            <div class="font-mono" style="font-weight: 800; color: #0f172a; font-size: 11px;">{{ $inv->no_penjualan }}</div>
                            <div style="font-size: 9.5px; color: #64748b;">Nota: {{ $tgl->format('d/m/Y') }}</div>
                            <div style="font-size: 9.5px; font-weight: 600; color: {{ $umur > $tempoStandarHari ? '#dc2626' : '#334155' }}; margin-top: 1px;">
                                Tempo: {{ $jthTempo->format('d/m/y') }}
                                <span class="age-tag {{ $ageClass }}" style="margin-left: 2px;">{{ $umur }} Hr</span>
                            </div>
                        </td>
                        <td data-label="Rincian Material & Cicilan">
                            <div class="material-list">
                                @foreach ($groupedDetails as $d)
                                    <span class="material-pill">
                                        {{ $d->produk->nama ?? 'Item' }} <strong>&times; {{ number_format((float) $d->jumlah, 0, ',', '.') }}</strong>
                                    </span>
                                @endforeach
                            </div>
                            @if ($inv->alamat_pengiriman)
                                <div style="font-size: 9.5px; color: #475569; margin-top: 3px;">
                                    📍 Kirim/Proyek: {{ $inv->alamat_pengiriman }}
                                </div>
                            @endif
                            @if ($inv->alokasiPembayarans->isNotEmpty())
                                <div style="font-size: 9.5px; color: #059669; margin-top: 3px; font-weight: 600;">
                                    &#10004; Riwayat Cicilan:
                                    {{ $inv->alokasiPembayarans->map(fn ($a) => ($a->pembayaran?->tanggal?->format('d/m/y') ?? '-') . ' (Rp ' . number_format((float) $a->jumlah_dialokasikan, 0, ',', '.') . ')')->implode(', ') }}
                                </div>
                            @endif
                        </td>
                        <td class="text-end font-mono col-tight" data-label="Nilai & Cicilan">
                            <div style="font-weight: 600; color: #0f172a;">Rp {{ number_format((float) $inv->total_akhir, 0, ',', '.') }}</div>
                            @if ((float) $inv->total_dibayar > 0)
                                <div style="font-size: 9.5px; color: #059669; font-weight: 600;">
                                    Cicil: -Rp {{ number_format((float) $inv->total_dibayar, 0, ',', '.') }}
                                </div>
                            @else
                                <div style="font-size: 9px; color: #94a3b8;">Belum dicicil</div>
                            @endif
                        </td>
                        <td class="text-end font-mono col-tight" style="font-weight: 800; font-size: 11.5px; color: #dc2626;" data-label="Sisa Tagihan">
                            Rp {{ number_format((float) $inv->sisa_piutang, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 18px; color: #64748b;">
                            Pelanggan ini tidak memiliki tagihan piutang tertunggak.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <!-- Bank Accounts & Grand Total -->
        <div class="summary-wrapper">
            <div class="bank-card">
                <div class="bank-card-title">💳 Rekening Pembayaran Transfer Resmi:</div>
                @if (!empty($toko->rekening_bank) && count($toko->rekening_bank) > 0)
                    @foreach ($toko->rekening_bank as $bank)
                        @if (filled($bank['nama_bank'] ?? null))
                            <div class="bank-item">
                                &bull; <strong>{{ strtoupper($bank['nama_bank']) }}:</strong> {{ $bank['nomor_rekening'] ?? '-' }}
                                <span style="font-size: 9.5px; color: #475569;">(a.n {{ $bank['atas_nama'] ?? '-' }})</span>
                            </div>
                        @endif
                    @endforeach
                @endif
                <div style="font-size: 9.5px; color: #475569; margin-top: 5px; border-top: 1px dashed #cbd5e1; padding-top: 4px;">
                    Terbilang: <strong><em># {{ $terbilang }} #</em></strong>
                </div>
            </div>

            <div class="totals-card">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Total Nilai Faktur:</td>
                        <td class="total-val font-mono">Rp {{ number_format((float) $invoices->sum('total_akhir'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Total Telah Dibayar:</td>
                        <td class="total-val font-mono" style="color: #059669;">Rp {{ number_format((float) $invoices->sum('total_dibayar'), 0, ',', '.') }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="total-label">TOTAL SISA TAGIHAN:</td>
                        <td class="total-val font-mono">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-title">Konfirmasi Pelanggan / Debitur,</div>
                <div class="sig-line">( {{ $pelanggan->nama }} )</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Hormat Kami, {{ $toko->nama_toko }}</div>
                <div class="sig-line">( {{ auth()->user()->name ?? 'Bagian Keuangan / Admin' }} )</div>
            </div>
        </div>
    </div>

</body>
</html>
