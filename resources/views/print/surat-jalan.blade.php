<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Jalan - SJ-{{ $penjualan->no_penjualan }}</title>
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
        .btn-success {
            background-color: #047857;
            color: #ffffff;
        }
        .btn-success:hover {
            background-color: #065f46;
        }
        .btn-primary {
            background-color: #1e40af;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
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
            background: #047857;
            color: #fff;
        }

        /* Header */
        .header {
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
            color: #0f172a;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        .title-block {
            text-align: right;
            flex-shrink: 0;
        }
        .title-block h2 {
            font-size: 18px;
            font-weight: 800;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.8px;
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

        /* Logistics Grid */
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
            width: 110px;
            color: #64748b;
            flex-shrink: 0;
        }
        .info-val {
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }

        /* Items / Cargo Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .items-table thead th {
            background-color: #047857;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border: 1px solid #047857;
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
            font-size: 10.5px;
            font-weight: 700;
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 1.5px 7px;
            border-radius: 4px;
            margin-top: 2px;
        }

        /* Physical Checklist Box */
        .check-box {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 1.5px solid #64748b;
            border-radius: 3px;
            background-color: #ffffff;
        }

        /* Notes & Instructions */
        .notes-section {
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 10px 14px;
            border-radius: 5px;
            margin-bottom: 22px;
            font-size: 10.5px;
            page-break-inside: avoid;
        }
        .notes-section strong {
            color: #0f172a;
        }

        /* 3 Signatures */
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
                background-color: #047857 !important;
                color: #ffffff !important;
            }
            .check-box {
                border-color: #000000 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Action Bar -->
    <div class="action-bar">
        <div class="btn-group">
            <a href="{{ route('penjualan.show', $penjualan->id) }}" class="btn btn-outline">
                &larr; Kembali
            </a>
            <a href="{{ route('penjualan.print.faktur', $penjualan) }}" class="btn btn-primary">
                📄 Buka Faktur Harga
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
            <button onclick="window.print()" class="btn btn-success">
                🖨️ Cetak Surat Jalan (Gudang & Sopir)
            </button>
        </div>
    </div>

    <!-- Printable Surat Jalan Page -->
    <div class="page-container" id="suratJalanContainer">

        @php
            $toko = \App\Domains\Administration\Models\ProfilPerusahaan::getSettings();
        @endphp

        <!-- Header -->
        <div class="header">
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
                        @if ($toko->telepon) &bull; Telp / WA Gudang: <strong>{{ $toko->telepon }}</strong> @endif
                    </div>
                </div>
            </div>

            <div class="title-block">
                <h2>SURAT JALAN & MUAT</h2>
                <table class="meta-table">
                    <tr>
                        <td class="label">No. Surat Jalan:</td>
                        <td class="value font-mono">SJ-{{ $penjualan->no_penjualan }}</td>
                    </tr>
                    <tr>
                        <td class="label">Ref. Faktur:</td>
                        <td class="value font-mono">{{ $penjualan->no_penjualan }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Muat:</td>
                        <td class="value">
                            {{ $penjualan->tanggal->format('d/m/Y') }}
                            @if ($penjualan->created_at)
                                <span style="font-size: 10px; color: #64748b; font-weight: normal; margin-left: 2px;">
                                    {{ $penjualan->created_at->format('H:i') }} WIB
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Info Grid (Logistics, Driver, Customer Destination) -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <span>Tujuan Pengiriman & Penerima</span>
                    <span style="font-weight: normal; text-transform: none; color: #64748b;">Lokasi Bongkar</span>
                </div>
                <div class="info-card-body">
                    <div class="info-row">
                        <span class="info-label">Nama Penerima:</span>
                        <span class="info-val" style="font-size: 12px; color: #047857;">{{ $penjualan->pelanggan->nama ?? 'Pelanggan Umum' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kontak / Telp HP:</span>
                        <span class="info-val font-mono" style="font-size: 12px; color: #047857;">{{ $penjualan->pelanggan->kontak ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Alamat / Lokasi Kirim:</span>
                        <span class="info-val" style="color: #0369a1;">
                            {{ $penjualan->alamat_pengiriman ?: 'Ambil Sendiri di Toko / Gudang' }}
                        </span>
                    </div>
                </div>
            </div>

            @php
                $groupedByGudang = $penjualan->details->groupBy('gudang_id');
                $isMultiGudang = $groupedByGudang->count() > 1;
            @endphp
            <div class="info-card">
                <div class="info-card-header">
                    <span>Armada, Sopir & Gudang Pengeluaran</span>
                    <span style="font-weight: normal; text-transform: none; color: #64748b;">
                        @if ($isMultiGudang)
                            Multi-Lokasi ({{ $groupedByGudang->count() }} Gudang)
                        @else
                            {{ $penjualan->gudang->nama_gudang ?? 'Gudang Utama' }}
                        @endif
                    </span>
                </div>
                <div class="info-card-body">
                    <div class="info-row">
                        <span class="info-label">Gudang Muat:</span>
                        <span class="info-val">
                            @if ($isMultiGudang)
                                <strong style="color: #b45309;">Multi-Gudang ({{ $groupedByGudang->map(fn($g) => $g->first()->gudang->nama_gudang ?? '-')->unique()->join(', ') }})</strong>
                            @else
                                {{ $penjualan->gudang->nama_gudang ?? '-' }}
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nama Sopir:</span>
                        <span class="info-val">{{ $penjualan->nama_sopir ?: 'Armada Toko / Ekspedisi' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Polisi / Plat:</span>
                        <span class="info-val font-mono">{{ $penjualan->plat_nomor ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cargo / Items Table (NO PRICES) -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 32px;">#</th>
                    <th class="text-start">Nama Material / Barang Bangunan & Spesifikasi</th>
                    <th class="text-center" style="width: 85px;">Gudang Asal</th>
                    <th class="text-center" style="width: 75px;">Qty Muat</th>
                    <th class="text-center" style="width: 65px;">Satuan</th>
                    <th class="text-center" style="width: 65px;" title="Checklist Muat oleh Petugas Gudang">Cek Muat</th>
                    <th class="text-center" style="width: 65px;" title="Checklist Bongkar oleh Penerima Barang">Cek Terima</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalFisik = 0;
                    $itemGlobalIndex = 1;
                @endphp
                @foreach ($groupedByGudang as $gudangId => $detailsGroup)
                    @if ($isMultiGudang)
                        @php
                            $namaGudangGroup = $detailsGroup->first()->gudang->nama_gudang ?? 'Gudang Lain';
                            $countBarang = $detailsGroup->count();
                        @endphp
                        <tr style="background-color: #f8fafc; border-top: 2px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">
                            <td colspan="7" style="padding: 6px 10px; font-weight: 700; color: #047857; font-size: 11px;">
                                📍 LOKASI PENGAMBILAN / MUAT: <strong>{{ strtoupper($namaGudangGroup) }}</strong> ({{ $countBarang }} jenis barang)
                            </td>
                        </tr>
                    @endif
                    @foreach ($detailsGroup as $detail)
                        @php
                            $qty = (float) $detail->jumlah;
                            $totalFisik += $qty;
                        @endphp
                        <tr>
                            <td class="text-center font-mono" style="color: #64748b;">{{ $itemGlobalIndex++ }}</td>
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
                                @if ($konversi = $detail->getDeskripsiKemasan())
                                    <div class="pkg-badge">
                                        📦 {{ $konversi }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center" style="font-size: 10.5px; color: #475569;">
                                {{ $detail->gudang->nama_gudang ?? '-' }}
                            </td>
                            <td class="text-center font-mono" style="font-size: 13.5px; font-weight: 800; color: #0f172a;">
                                {{ number_format($qty, 0, ',', '.') }}
                            </td>
                            <td class="text-center" style="font-weight: 600; color: #475569;">
                                {{ $detail->produk->satuan->label() ?? '' }}
                            </td>
                            <td class="text-center">
                                <span class="check-box"></span>
                            </td>
                            <td class="text-center">
                                <span class="check-box"></span>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: 700; border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a;">
                    <td colspan="3" class="text-end" style="padding: 7px 10px; color: #334155;">TOTAL KUANTITAS FISIK MUATAN:</td>
                    <td class="text-center font-mono" style="font-size: 14px; font-weight: 800; color: #047857; padding: 7px 8px;">
                        {{ number_format($totalFisik, 0, ',', '.') }}
                    </td>
                    <td colspan="3" style="font-size: 10.5px; color: #64748b; padding: 7px 8px;">Unit / Fisik Barang</td>
                </tr>
            </tfoot>
        </table>

        <!-- Notes / Instructions -->
        <div class="notes-section">
            <div style="margin-bottom: 4px;">
                <strong>Instruksi Pengiriman / Sopir:</strong> {{ $penjualan->keterangan ?: 'Pastikan penataan barang di kendaraan aman dari benturan & air hujan.' }}
            </div>
            <div style="color: #64748b;">
                <strong>Perhatian:</strong> Harap periksa kembali jenis, spesifikasi, dan jumlah fisik material saat serah terima di lokasi. Tanda tangan di bawah ini menyatakan barang telah diterima dalam kondisi baik, lengkap, dan tanpa cacat.
            </div>
        </div>

        <!-- 3 Formal Signatures (Gudang, Sopir, Penerima) -->
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-title">Petugas Gudang<br><span style="font-size: 9.5px; font-weight: normal; color: #64748b;">(Yang Menyerahkan)</span></div>
                <div class="sig-line">( {{ $penjualan->user->name ?? ($penjualan->creator->name ?? 'Gudang Toko') }} )</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Sopir / Ekspedisi<br><span style="font-size: 9.5px; font-weight: normal; color: #64748b;">(Yang Membawa Armada)</span></div>
                <div class="sig-line">( {{ $penjualan->nama_sopir ?: 'Pengemudi' }} )</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Penerima / Mandor Proyek<br><span style="font-size: 9.5px; font-weight: normal; color: #64748b;">(Yang Menerima di Lokasi)</span></div>
                <div class="sig-line">( {{ $penjualan->pelanggan->nama ?? 'Penerima Barang' }} )</div>
            </div>
        </div>

    </div>

    <script>
        function setPaper(size) {
            const container = document.getElementById('suratJalanContainer');
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
