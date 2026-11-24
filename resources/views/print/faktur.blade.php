<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faktur Penjualan - {{ $penjualan->no_penjualan }}</title>
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
            padding: 20px;
            line-height: 1.45;
        }
        .page-container {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px 36px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            border-radius: 6px;
            transition: max-width 0.2s ease;
        }

        /* Screen Action Bar */
        .action-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background-color: #1e40af;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-success {
            background-color: #059669;
            color: #ffffff;
        }
        .btn-success:hover {
            background-color: #047857;
        }
        .btn-secondary {
            background-color: #475569;
            color: #ffffff;
        }
        .btn-secondary:hover {
            background-color: #334155;
        }
        .btn-outline {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-outline:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
        }
        .paper-switcher {
            font-size: 11px;
            display: inline-flex;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            background: #fff;
        }
        .paper-btn {
            padding: 6px 10px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
        }
        .paper-btn.active {
            background: #1e40af;
            color: #fff;
        }

        /* Invoice Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 16px;
            gap: 20px;
        }
        .company-brand {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            flex: 1;
        }
        .company-logo {
            max-height: 58px;
            max-width: 130px;
            object-fit: contain;
            border-radius: 3px;
        }
        .company-info h1 {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .company-info .subtitle {
            font-size: 11px;
            color: #475569;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .company-info .contact {
            font-size: 10.5px;
            color: #64748b;
            line-height: 1.45;
        }

        /* Title & Meta Block */
        .invoice-title-block {
            text-align: right;
            flex-shrink: 0;
        }
        .invoice-title-block h2 {
            font-size: 19px;
            font-weight: 800;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .meta-table {
            margin-left: auto;
            border-collapse: collapse;
            font-size: 11px;
        }
        .meta-table td {
            padding: 1.5px 0 1.5px 8px;
        }
        .meta-table .label {
            color: #64748b;
            font-weight: 500;
        }
        .meta-table .value {
            font-weight: 700;
            color: #0f172a;
        }

        /* Cap Stempel Status */
        .stamp-box {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 4px;
            transform: rotate(-2deg);
        }
        .stamp-lunas {
            border: 2px solid #059669;
            color: #059669;
            background-color: #ecfdf5;
        }
        .stamp-sebagian {
            border: 2px solid #d97706;
            color: #d97706;
            background-color: #fffbeb;
        }
        .stamp-belum {
            border: 2px solid #dc2626;
            color: #dc2626;
            background-color: #fef2f2;
        }

        /* Cards Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
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
            padding: 5px 12px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-card-body {
            padding: 8px 12px;
            font-size: 11px;
        }
        .info-row {
            display: flex;
            margin-bottom: 3.5px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            width: 105px;
            color: #64748b;
            flex-shrink: 0;
        }
        .info-val {
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }
        .tier-badge {
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
            background: #e2e8f0;
            color: #1e293b;
            border: 1px solid #cbd5e1;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .items-table thead th {
            background-color: #1e40af;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border: 1px solid #1e40af;
        }
        .items-table tbody td {
            padding: 6.5px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .font-mono { font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }

        .item-spec-tag {
            display: inline-block;
            font-size: 9px;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 0 4px;
            border-radius: 3px;
            margin-right: 4px;
        }
        .pkg-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: #0369a1;
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            padding: 1px 6px;
            border-radius: 4px;
            margin-top: 2px;
        }

        /* Summary Wrapper */
        .summary-wrapper {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 16px;
            margin-bottom: 22px;
            align-items: start;
            page-break-inside: avoid;
        }
        .terms-box {
            font-size: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 5px;
            background-color: #f8fafc;
        }
        .terms-box h4 {
            font-size: 10.5px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .terms-box ul {
            padding-left: 14px;
            color: #475569;
            line-height: 1.45;
        }
        .terms-box li {
            margin-bottom: 2px;
        }

        /* Bank Account Card */
        .bank-card {
            margin-top: 8px;
            padding: 8px 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 5px;
        }
        .bank-card-title {
            font-weight: 700;
            color: #1e40af;
            font-size: 10.5px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .bank-item {
            font-family: monospace;
            font-size: 10.5px;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .bank-item strong {
            color: #1e3a8a;
        }

        /* Totals Table */
        .totals-card {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            background: #fff;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        .totals-table td {
            padding: 4.5px 10px;
        }
        .totals-table .total-label {
            color: #475569;
            text-align: right;
            font-weight: 500;
        }
        .totals-table .total-val {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
        .totals-table tr.grand-total {
            background-color: #1e3a8a;
            color: #ffffff;
        }
        .totals-table tr.grand-total .total-label,
        .totals-table tr.grand-total .total-val {
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 800;
            padding: 7px 10px;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .sig-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 95px;
        }
        .sig-title {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }
        .sig-line {
            border-top: 1px dashed #64748b;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Print Settings */
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
                border-radius: 0;
            }
            .action-bar {
                display: none !important;
            }
            @page {
                size: auto;
                margin: 10mm 12mm;
            }
            .items-table thead th {
                background-color: #1e40af !important;
                color: #ffffff !important;
            }
            .totals-table tr.grand-total {
                background-color: #1e3a8a !important;
                color: #ffffff !important;
            }
            .totals-table tr.grand-total .total-label,
            .totals-table tr.grand-total .total-val {
                color: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Toolbar (Hidden on Print) -->
    <div class="action-bar">
        <div class="btn-group">
            <a href="{{ route('penjualan.show', $penjualan->id) }}" class="btn btn-outline">
                &larr; Kembali
            </a>
            <a href="{{ route('penjualan.print.surat-jalan', $penjualan) }}" class="btn btn-success">
                🚚 Buka Surat Jalan
            </a>
            <a href="{{ route('penjualan.print.thermal', $penjualan) }}" class="btn btn-secondary">
                🧾 Struk Kasir
            </a>
        </div>
        <div class="btn-group">
            <div class="paper-switcher" title="Ubah pratinjau lebar kertas">
                <button type="button" class="paper-btn active" onclick="setPaper('a4')">A4</button>
                <button type="button" class="paper-btn" onclick="setPaper('a5')">A5</button>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Faktur (Print / PDF)
            </button>
        </div>
    </div>

    <!-- Printable Invoice Page -->
    <div class="page-container" id="invoiceContainer">

        @php
            $toko = \App\Domains\Administration\Models\ProfilPerusahaan::getSettings();
        @endphp

        <!-- Header -->
        <div class="invoice-header">
            <div class="company-brand">
                @if ($toko->logo_path)
                    <img src="{{ asset('storage/' . $toko->logo_path) }}" alt="{{ $toko->nama_toko }}" class="company-logo">
                @endif
                <div class="company-info">
                    <h1>{!! $toko->nama_toko !!}</h1>
                    @if ($toko->slogan)
                        <div class="subtitle">{{ $toko->slogan }}</div>
                    @endif
                    <div class="contact">
                        {{ $toko->alamat ?: 'Alamat Toko / Pergudangan' }}
                        @if ($toko->telepon) &bull; Telp / WA: <strong>{{ $toko->telepon }}</strong> @endif
                        @if ($toko->email) &bull; Email: {{ $toko->email }} @endif
                    </div>
                </div>
            </div>

            <div class="invoice-title-block">
                <h2>FAKTUR PENJUALAN</h2>
                <table class="meta-table">
                    <tr>
                        <td class="label">No. Faktur:</td>
                        <td class="value font-mono">{{ $penjualan->no_penjualan }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal:</td>
                        <td class="value">
                            {{ $penjualan->tanggal->format('d/m/Y') }}
                            @if ($penjualan->created_at)
                                <span style="font-size: 10px; color: #64748b; font-weight: normal; margin-left: 2px;">
                                    {{ $penjualan->created_at->format('H:i') }} WIB
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Kasir / Admin:</td>
                        <td class="value">{{ $penjualan->user->name ?? ($penjualan->creator->name ?? 'Admin') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Metode Bayar:</td>
                        <td class="value">{{ $penjualan->getMetodeLabel() }}</td>
                    </tr>
                </table>

                <!-- Stempel Status -->
                <div>
                    @if ($penjualan->status_pembayaran->value === 'lunas')
                        <div class="stamp-box stamp-lunas">&#10004; LUNAS</div>
                    @elseif ($penjualan->status_pembayaran->value === 'sebagian')
                        <div class="stamp-box stamp-sebagian">&#9684; SEBAGIAN</div>
                    @else
                        <div class="stamp-box stamp-belum">&#9888; BELUM LUNAS</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Info Grid (Customer & Delivery) -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <span>Kepada Yth. (Pelanggan)</span>
                    <span class="tier-badge">{{ $penjualan->pelanggan->tipe->label() ?? 'Umum' }}</span>
                </div>
                <div class="info-card-body">
                    <div class="info-row">
                        <span class="info-label">Nama Pelanggan:</span>
                        <span class="info-val" style="font-size: 12px; color: #1e3a8a;">{{ $penjualan->pelanggan->nama ?? 'Pelanggan Umum' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Telepon / WA:</span>
                        <span class="info-val font-mono">{{ $penjualan->pelanggan->kontak ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Alamat / Lokasi:</span>
                        <span class="info-val">{{ $penjualan->pelanggan->alamat ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <span>Logistik & Pengeluaran</span>
                    <span style="font-weight: normal; text-transform: none; color: #64748b;">
                        {{ $penjualan->gudang->nama_gudang ?? 'Gudang Utama' }}
                    </span>
                </div>
                <div class="info-card-body">
                    <div class="info-row">
                        <span class="info-label">Sopir / Ekspedisi:</span>
                        <span class="info-val">
                            {{ $penjualan->nama_sopir ?: 'Ambil Sendiri / Armada Toko' }}
                            @if ($penjualan->plat_nomor)
                                ({{ $penjualan->plat_nomor }})
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tujuan Kirim:</span>
                        <span class="info-val" style="color: #0369a1;">
                            {{ $penjualan->alamat_pengiriman ?: 'Ambil Sendiri di Toko / Gudang' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Catatan Nota:</span>
                        <span class="info-val">{{ $penjualan->keterangan ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 32px;">#</th>
                    <th class="text-start">Deskripsi Barang & Spesifikasi</th>
                    <th class="text-center" style="width: 85px;">Gudang</th>
                    <th class="text-center" style="width: 60px;">Qty</th>
                    <th class="text-center" style="width: 55px;">Satuan</th>
                    <th class="text-end" style="width: 100px;">Harga Satuan (Rp)</th>
                    <th class="text-end" style="width: 75px;">Diskon</th>
                    <th class="text-end" style="width: 115px;">Subtotal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalQtyFisik = 0;
                    $subtotalProduk = 0;
                @endphp
                @foreach ($penjualan->getGroupedDetailsForInvoice() as $index => $detail)
                    @php
                        $qty = (float) $detail->jumlah;
                        $totalQtyFisik += $qty;
                        $subtotalProduk += (float) $detail->subtotal;
                    @endphp
                    <tr>
                        <td class="text-center font-mono" style="color: #64748b;">{{ $index + 1 }}</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">
                                {{ $detail->produk->nama ?? '-' }}
                            </div>
                            <div style="margin-top: 1.5px;">
                                @if ($detail->produk->kode_katalog)
                                    <span class="item-spec-tag">SKU: {{ $detail->produk->kode_katalog }}</span>
                                @endif
                                @if ($detail->produk->merek)
                                    <span class="item-spec-tag">{{ $detail->produk->merek->nama_merek }}</span>
                                @endif
                                @if (!empty($detail->produk->ukuran))
                                    <span class="item-spec-tag">{{ $detail->produk->ukuran }}</span>
                                @endif
                            </div>
                            @php
                                $packagingNote = $detail->produk?->getKonversiFisik((float) $detail->jumlah);
                            @endphp
                            @if ($packagingNote && $packagingNote['has_conversion'])
                                <div class="pkg-badge">
                                    📦 {{ $packagingNote['badge_text'] }}
                                </div>
                            @endif
                        </td>
                        <td class="text-center" style="font-size: 10.5px; color: #475569;">
                            {{ $detail->gudang->nama_gudang ?? '-' }}
                        </td>
                        <td class="text-center font-mono" style="font-weight: 700; font-size: 12px; color: #0f172a;">
                            {{ number_format($qty, 0, ',', '.') }}
                        </td>
                        <td class="text-center" style="font-size: 10.5px; color: #475569;">
                            {{ $detail->produk->satuan->label() ?? '' }}
                        </td>
                        <td class="text-end font-mono">
                            {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-mono" style="font-size: 10.5px; color: #64748b;">
                            @if ((float) $detail->nilai_diskon > 0)
                                @if ($detail->jenis_diskon?->value === 'persentase')
                                    {{ number_format((float) $detail->nilai_diskon, 1, ',', '.') }}%
                                @else
                                    Rp {{ number_format((float) $detail->nilai_diskon, 0, ',', '.') }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end font-mono" style="font-weight: 700; color: #0f172a;">
                            {{ number_format((float) $detail->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary & Totals -->
        <div class="summary-wrapper">
            <!-- Left: Terms & Bank Accounts -->
            <div class="terms-box">
                <h4>Ketentuan & Syarat Transaksi:</h4>
                <ul>
                    @if ($toko->catatan_kaki_faktur)
                        <li>{{ $toko->catatan_kaki_faktur }}</li>
                    @else
                        <li>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa perjanjian tertulis.</li>
                        <li>Komplain kondisi fisik barang diterima maksimal 1x24 jam sejak barang diterima di lokasi.</li>
                        <li>Pembayaran transfer sah jika dana telah efektif masuk ke rekening bank toko.</li>
                    @endif
                </ul>

                @if ($penjualan->metode_pembayaran === 'transfer' || $penjualan->status_pembayaran->value !== 'lunas')
                    @if (!empty($toko->rekening_bank) && count($toko->rekening_bank) > 0)
                        <div class="bank-card">
                            <div class="bank-card-title">
                                💳 REKENING PEMBAYARAN TRANSFER (RESMI):
                            </div>
                            @foreach ($toko->rekening_bank as $bank)
                                @if (filled($bank['nama_bank'] ?? null))
                                    <div class="bank-item">
                                        &bull; <strong>{{ strtoupper($bank['nama_bank']) }}:</strong> {{ $bank['nomor_rekening'] ?? '-' }}
                                        <span style="font-size: 9.5px; color: #475569;">(a.n {{ $bank['atas_nama'] ?? '-' }})</span>
                                    </div>
                                @endif
                            @endforeach
                            <div style="font-size: 9.5px; color: #64748b; margin-top: 3px;">
                                * Harap konfirmasikan bukti transfer dengan mencantumkan No. Faktur <strong>{{ $penjualan->no_penjualan }}</strong>.
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Right: Breakdown Totals -->
            <div class="totals-card">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Subtotal Barang ({{ number_format($totalQtyFisik, 0, ',', '.') }} item):</td>
                        <td class="total-val font-mono">Rp {{ number_format($subtotalProduk, 0, ',', '.') }}</td>
                    </tr>
                    @if ((float) $penjualan->biaya_ongkir > 0)
                        <tr>
                            <td class="total-label">Biaya Ongkir / Ekspedisi:</td>
                            <td class="total-val font-mono">Rp {{ number_format((float) $penjualan->biaya_ongkir, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="total-label">GRAND TOTAL:</td>
                        <td class="total-val font-mono">Rp {{ number_format((float) $penjualan->total_akhir, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Total Dibayar:</td>
                        <td class="total-val font-mono" style="color: #059669; font-weight: 700;">
                            Rp {{ number_format((float) $penjualan->total_dibayar, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if ((float) $penjualan->sisa_piutang > 0)
                        <tr style="background-color: #fef2f2;">
                            <td class="total-label" style="color: #dc2626; font-weight: 700;">Sisa Tagihan (Piutang):</td>
                            <td class="total-val font-mono" style="color: #dc2626; font-weight: 800;">
                                Rp {{ number_format((float) $penjualan->sisa_piutang, 0, ',', '.') }}
                            </td>
                        </tr>
                    @elseif ((float) $penjualan->total_dibayar > (float) $penjualan->total_akhir)
                        @php $kembali = (float) $penjualan->total_dibayar - (float) $penjualan->total_akhir; @endphp
                        <tr>
                            <td class="total-label" style="color: #059669;">Kembalian Kasir:</td>
                            <td class="total-val font-mono" style="color: #059669; font-weight: 700;">
                                Rp {{ number_format($kembali, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- 3 Formal Signatures -->
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-title">Penerima / Pelanggan,</div>
                <div class="sig-line">( {{ $penjualan->pelanggan->nama ?? '..........................' }} )</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Petugas Gudang / Pengirim,</div>
                <div class="sig-line">( {{ $penjualan->nama_sopir ?: '..........................' }} )</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Hormat Kami (Kasir / Toko),</div>
                <div class="sig-line">( {{ $penjualan->user->name ?? ($penjualan->creator->name ?? 'Kasir') }} )</div>
            </div>
        </div>

    </div>

    <script>
        function setPaper(size) {
            const container = document.getElementById('invoiceContainer');
            const btns = document.querySelectorAll('.paper-btn');
            btns.forEach(b => b.classList.remove('active'));

            if (size === 'a5') {
                container.style.maxWidth = '620px';
                event.target.classList.add('active');
            } else {
                container.style.maxWidth = '820px';
                event.target.classList.add('active');
            }
        }
    </script>
</body>
</html>
