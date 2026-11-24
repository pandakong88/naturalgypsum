<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk Kasir - {{ $penjualan->no_penjualan }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: "Courier New", Consolas, "Liberation Mono", Courier, monospace;
            font-size: 11px;
            color: #000000;
            background-color: #f1f5f9;
            padding: 20px;
            line-height: 1.35;
        }

        /* Screen Action Bar */
        .action-bar {
            max-width: 360px;
            margin: 0 auto 16px auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .btn-row {
            display: flex;
            gap: 6px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid transparent;
            flex: 1;
            transition: all 0.15s ease;
        }
        .btn-dark {
            background-color: #0f172a;
            color: #ffffff;
        }
        .btn-dark:hover {
            background-color: #1e293b;
        }
        .btn-outline {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-outline:hover {
            background-color: #f8fafc;
        }
        .width-switcher {
            display: flex;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            font-size: 11px;
        }
        .width-btn {
            flex: 1;
            padding: 6px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            text-align: center;
        }
        .width-btn.active {
            background: #0f172a;
            color: #ffffff;
        }

        /* Thermal Ticket Container */
        .receipt-ticket {
            max-width: 340px;
            margin: 0 auto;
            background: #ffffff;
            padding: 16px 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.1);
            border-radius: 4px;
            transition: max-width 0.2s ease;
        }

        /* 58mm preview mode */
        .receipt-ticket.mode-58mm {
            max-width: 250px;
            padding: 12px 10px;
            font-size: 10px;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }

        /* Store Header */
        .receipt-header {
            text-align: center;
            margin-bottom: 8px;
        }
        .store-logo {
            max-height: 40px;
            max-width: 100px;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .store-title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.25;
        }
        .mode-58mm .store-title {
            font-size: 12px;
        }
        .store-sub {
            font-size: 10px;
            margin-top: 2px;
            color: #222;
        }
        .mode-58mm .store-sub {
            font-size: 9px;
        }

        /* Divider lines */
        .divider {
            border-top: 1px dashed #000000;
            margin: 6px 0;
        }
        .divider-double {
            border-top: 2px dashed #000000;
            margin: 8px 0;
        }

        /* Meta block */
        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            line-height: 1.35;
        }
        .mode-58mm .meta-row {
            font-size: 9.5px;
        }

        /* Line Items */
        .item-block {
            margin-bottom: 6px;
        }
        .item-name {
            font-size: 11px;
            font-weight: bold;
            word-wrap: break-word;
            line-height: 1.25;
        }
        .mode-58mm .item-name {
            font-size: 10px;
        }
        .item-calc {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            padding-left: 4px;
            margin-top: 1px;
        }
        .mode-58mm .item-calc {
            font-size: 9.5px;
        }
        .item-badge-note {
            font-size: 9.5px;
            padding-left: 4px;
            color: #111;
        }
        .mode-58mm .item-badge-note {
            font-size: 8.5px;
        }

        /* Totals */
        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            line-height: 1.4;
        }
        .mode-58mm .totals-row {
            font-size: 9.5px;
        }
        .grand-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13.5px;
            font-weight: bold;
            padding: 3px 0;
        }
        .mode-58mm .grand-total-row {
            font-size: 12px;
        }

        /* Footer */
        .footer-note {
            text-align: center;
            font-size: 9.5px;
            margin-top: 8px;
            line-height: 1.35;
        }
        .mode-58mm .footer-note {
            font-size: 8.5px;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
                margin: 0;
            }
            .action-bar {
                display: none !important;
            }
            .receipt-ticket {
                box-shadow: none;
                padding: 2px;
                max-width: 100% !important;
                width: 100%;
            }
            @page {
                size: auto;
                margin: 0mm 2mm;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Action Bar -->
    <div class="action-bar">
        <button onclick="window.print()" class="btn btn-dark">
            🖨️ Cetak Struk Kasir (POS)
        </button>
        <div class="width-switcher">
            <button type="button" class="width-btn active" onclick="setWidth('80mm')">Lebar 80mm (Standar)</button>
            <button type="button" class="width-btn" onclick="setWidth('58mm')">Lebar 58mm (Mini)</button>
        </div>
        <div class="btn-row">
            <a href="{{ route('penjualan.show', $penjualan->id) }}" class="btn btn-outline">
                &larr; Detail
            </a>
            <a href="{{ route('penjualan.print.faktur', $penjualan) }}" class="btn btn-outline">
                📄 Faktur A4
            </a>
            <a href="{{ route('penjualan.print.surat-jalan', $penjualan) }}" class="btn btn-outline">
                🚚 Surat Jalan
            </a>
        </div>
    </div>

    <!-- Thermal Struk Ticket -->
    <div class="receipt-ticket" id="thermalTicket">

        @php
            $toko = \App\Domains\Administration\Models\ProfilPerusahaan::getSettings();
        @endphp

        <!-- Store Header -->
        <div class="receipt-header">
            @if ($toko->logo_path)
                <div>
                    <img src="{{ asset('storage/' . $toko->logo_path) }}" alt="{{ $toko->nama_toko }}" class="store-logo">
                </div>
            @endif
            <div class="store-title">{!! $toko->nama_toko !!}</div>
            @if ($toko->slogan)
                <div class="store-sub">{{ $toko->slogan }}</div>
            @endif
            @if ($toko->alamat)
                <div class="store-sub">{{ $toko->alamat }}</div>
            @endif
            @if ($toko->telepon)
                <div class="store-sub">Telp/WA: {{ $toko->telepon }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Meta Information -->
        <div class="meta-row">
            <span>No: {{ $penjualan->no_penjualan }}</span>
            <span>{{ $penjualan->tanggal->format('d/m/y') }} {{ $penjualan->created_at ? $penjualan->created_at->format('H:i') : '' }}</span>
        </div>
        <div class="meta-row">
            <span>Kasir: {{ Str::limit($penjualan->user->name ?? ($penjualan->creator->name ?? 'Kasir'), 14) }}</span>
            <span>{{ $penjualan->gudang->nama_gudang ?? 'Gudang' }}</span>
        </div>
        <div class="meta-row">
            <span>Plg  : {{ Str::limit($penjualan->pelanggan->nama ?? 'Umum', 16) }}</span>
            <span>[{{ $penjualan->pelanggan->tipe->label() ?? 'Umum' }}]</span>
        </div>
        <div class="meta-row">
            <span>Bayar: {{ $penjualan->getMetodeLabel() }}</span>
            <span>
                @if ($penjualan->status_pembayaran->value === 'lunas')
                    [LUNAS]
                @elseif ($penjualan->status_pembayaran->value === 'sebagian')
                    [SEBAGIAN]
                @else
                    [TEMPO]
                @endif
            </span>
        </div>
        @if ($penjualan->nama_sopir || $penjualan->plat_nomor)
            <div class="meta-row">
                <span>Kirim: {{ Str::limit($penjualan->nama_sopir ?: 'Armada', 12) }}</span>
                <span>{{ $penjualan->plat_nomor ?: '' }}</span>
            </div>
        @endif

        <div class="divider"></div>

        <!-- Line Items -->
        @php
            $subtotalProduk = 0;
            $totalItemFisik = 0;
        @endphp
        @foreach ($penjualan->getGroupedDetailsForInvoice() as $detail)
            @php
                $qty = (float) $detail->jumlah;
                $totalItemFisik += $qty;
                $subtotalProduk += (float) $detail->subtotal;
                $pkgNote = $detail->produk?->getKonversiFisik($qty);
            @endphp
            <div class="item-block">
                <div class="item-name">{{ $detail->produk->nama ?? '-' }}</div>
                <div class="item-calc">
                    <span>{{ number_format($qty, 0, ',', '.') }} {{ $detail->produk->satuan->label() ?? '' }} x {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</span>
                </div>
                @if ($pkgNote && $pkgNote['has_conversion'])
                    <div class="item-badge-note">
                        &gt; {{ $pkgNote['badge_text'] }}
                    </div>
                @endif
                @if ((float) $detail->nilai_diskon > 0)
                    <div class="item-calc" style="color: #444;">
                        <span>&nbsp;&bull; Disc:</span>
                        <span>
                            -{{ $detail->jenis_diskon?->value === 'persentase' ? number_format((float) $detail->nilai_diskon, 1, ',', '.') . '%' : 'Rp ' . number_format((float) $detail->nilai_diskon, 0, ',', '.') }}
                        </span>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="divider"></div>

        <!-- Totals -->
        <div class="totals-row">
            <span>Item ({{ number_format($totalItemFisik, 0, ',', '.') }} unit):</span>
            <span>Rp {{ number_format($subtotalProduk, 0, ',', '.') }}</span>
        </div>
        @if ((float) $penjualan->biaya_ongkir > 0)
            <div class="totals-row">
                <span>Ongkir:</span>
                <span>Rp {{ number_format((float) $penjualan->biaya_ongkir, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="divider"></div>

        <div class="grand-total-row">
            <span>TOTAL:</span>
            <span>Rp {{ number_format((float) $penjualan->total_akhir, 0, ',', '.') }}</span>
        </div>

        <div class="totals-row">
            <span>Bayar:</span>
            <span>Rp {{ number_format((float) $penjualan->total_dibayar, 0, ',', '.') }}</span>
        </div>

        @if ((float) $penjualan->total_dibayar > (float) $penjualan->total_akhir)
            @php $kembali = (float) $penjualan->total_dibayar - (float) $penjualan->total_akhir; @endphp
            <div class="totals-row fw-bold">
                <span>Kembali:</span>
                <span>Rp {{ number_format($kembali, 0, ',', '.') }}</span>
            </div>
        @endif

        @if ((float) $penjualan->sisa_piutang > 0)
            <div class="totals-row fw-bold">
                <span>Sisa Tagihan:</span>
                <span>Rp {{ number_format((float) $penjualan->sisa_piutang, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="divider-double"></div>

        <!-- Footer -->
        <div class="footer-note">
            @if ($penjualan->metode_pembayaran === 'transfer' || (float) $penjualan->sisa_piutang > 0)
                @if (!empty($toko->rekening_bank) && count($toko->rekening_bank) > 0)
                    <div class="fw-bold" style="margin-bottom: 2px;">REK. TRANSFER RESMI:</div>
                    @foreach ($toko->rekening_bank as $bank)
                        @if (filled($bank['nama_bank'] ?? null))
                            <div>{{ strtoupper($bank['nama_bank']) }}: {{ $bank['nomor_rekening'] }}</div>
                            <div style="font-size: 8.5px; margin-bottom: 2px;">(a.n {{ $bank['atas_nama'] }})</div>
                        @endif
                    @endforeach
                    <div class="divider"></div>
                @endif
            @endif
            <div class="fw-bold">*** TERIMA KASIH ***</div>
            <div style="margin-top: 2px;">
                @if ($toko->catatan_kaki_faktur)
                    {{ $toko->catatan_kaki_faktur }}
                @else
                    Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa nota resmi.
                @endif
            </div>
        </div>

    </div>

    <script>
        function setWidth(mode) {
            const ticket = document.getElementById('thermalTicket');
            const btns = document.querySelectorAll('.width-btn');
            btns.forEach(b => b.classList.remove('active'));

            if (mode === '58mm') {
                ticket.classList.add('mode-58mm');
                event.target.classList.add('active');
            } else {
                ticket.classList.remove('mode-58mm');
                event.target.classList.add('active');
            }
        }
    </script>
</body>
</html>
