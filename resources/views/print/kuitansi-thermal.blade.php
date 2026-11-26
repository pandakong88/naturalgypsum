<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk Pembayaran - {{ $pembayaran->no_pembayaran }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            color: #000;
            background-color: #f4f6f9;
            padding: 20px;
        }

        /* Screen Action Bar */
        .action-bar {
            max-width: 320px;
            margin: 0 auto 15px auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
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
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid transparent;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            flex: 1;
        }
        .btn-dark {
            background-color: #212529;
            color: #fff;
        }
        .btn-dark:hover {
            background-color: #1a1e21;
        }
        .btn-outline {
            background-color: #fff;
            color: #333;
            border-color: #ccc;
        }
        .btn-outline:hover {
            background-color: #f8f9fa;
        }

        /* Thermal Ticket Container */
        .receipt-ticket {
            max-width: 300px;
            margin: 0 auto;
            background: #fff;
            padding: 15px 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 2px;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }

        .receipt-header {
            text-align: center;
            margin-bottom: 8px;
        }
        .store-title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .store-sub {
            font-size: 10px;
            margin-top: 2px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .divider-double {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            line-height: 1.35;
        }

        .section-label {
            font-size: 11px;
            font-weight: bold;
            margin: 6px 0 4px 0;
            text-transform: uppercase;
        }

        .item-block {
            margin-bottom: 6px;
        }
        .item-line1 {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 11px;
        }
        .item-line2 {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #333;
            padding-left: 4px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            line-height: 1.4;
        }
        .grand-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
            font-weight: bold;
            padding: 4px 0;
        }

        .footer-note {
            text-align: center;
            font-size: 9.5px;
            margin-top: 10px;
            line-height: 1.35;
        }

        @media print {
            body {
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            .action-bar {
                display: none !important;
            }
            .receipt-ticket {
                box-shadow: none;
                padding: 4px;
                max-width: 100%;
                width: 72mm;
            }
            @page {
                size: auto;
                margin: 0mm;
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
        <div class="btn-row">
            <a href="{{ route('pembayaran.show', $pembayaran->id) }}" class="btn btn-outline">
                &larr; Detail
            </a>
            <a href="{{ route('pembayaran.print.kuitansi', $pembayaran) }}" class="btn btn-outline">
                📄 Kuitansi A4
            </a>
        </div>
    </div>

    <!-- Thermal Struk Ticket -->
    <div class="receipt-ticket">

        @php
            $toko = \App\Domains\Administration\Models\ProfilPerusahaan::getSettings();
        @endphp

        <!-- Store Header -->
        <div class="receipt-header">
            <div>
                <img src="{{ $toko->getLogoUrl() }}" alt="{{ $toko->nama_toko }}" style="height: 36px; max-width: 100px; width: auto; object-fit: contain; margin-bottom: 4px;">
            </div>
            <div class="store-title">{{ $toko->nama_toko }}</div>
            <div class="store-sub">{{ $toko->slogan ?: 'Aplikator dan Penyedia Bahan Gypsum dan PVC' }}</div>
            <div class="store-sub fw-bold" style="margin-top: 4px;">BUKTI PEMBAYARAN PIUTANG</div>
        </div>

        <div class="divider"></div>

        <!-- Meta -->
        <div class="meta-row">
            <span>No. Bukti</span>
            <span class="fw-bold">{{ $pembayaran->no_pembayaran }}</span>
        </div>
        <div class="meta-row">
            <span>Tanggal</span>
            <span>{{ $pembayaran->tanggal->format('d/m/Y') }} {{ $pembayaran->created_at->format('H:i') }}</span>
        </div>
        <div class="meta-row">
            <span>Kasir</span>
            <span>{{ $pembayaran->user->name ?? ($pembayaran->creator->name ?? 'Kasir') }}</span>
        </div>
        <div class="meta-row">
            <span>Pelanggan</span>
            <span class="fw-bold">{{ Str::limit($pembayaran->pelanggan->nama ?? 'Umum', 18) }}</span>
        </div>
        <div class="meta-row">
            <span>Metode</span>
            <span>{{ $pembayaran->metode->label() }}</span>
        </div>

        <div class="divider"></div>

        <!-- Alokasi Detail -->
        <div class="section-label">ALOKASI FAKTUR:</div>

        @forelse ($pembayaran->alokasi as $item)
            <div class="item-block">
                <div class="item-line1">
                    <span>{{ $item->penjualan->no_penjualan ?? 'Faktur' }}</span>
                    <span>Rp {{ number_format($item->jumlah_dialokasikan, 0, ',', '.') }}</span>
                </div>
                <div class="item-line2">
                    <span>Tgl: {{ $item->penjualan?->tanggal?->format('d/m/y') ?? '-' }}</span>
                    <span>Sisa: Rp {{ number_format($item->penjualan?->sisa_piutang ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        @empty
            <div class="text-center" style="font-size: 10px; padding: 4px 0; color: #555;">
                (Tidak ada alokasi langsung)
            </div>
        @endforelse

        <div class="divider-double"></div>

        <!-- Totals -->
        <div class="totals-row">
            <span>Total Bayar:</span>
            <span>Rp {{ number_format($pembayaran->jumlah_bayar, 0, ',', '.') }}</span>
        </div>
        <div class="totals-row">
            <span>Total Dialokasi:</span>
            <span>Rp {{ number_format($pembayaran->total_dialokasikan, 0, ',', '.') }}</span>
        </div>

        @if ($pembayaran->sisa_belum_dialokasikan > 0)
            <div class="totals-row">
                <span>Sisa Titipan:</span>
                <span>Rp {{ number_format($pembayaran->sisa_belum_dialokasikan, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="divider"></div>

        <div class="grand-total-row">
            <span>PELUNASAN</span>
            <span>Rp {{ number_format($pembayaran->total_dialokasikan, 0, ',', '.') }}</span>
        </div>

        @if ($pembayaran->catatan)
            <div class="divider"></div>
            <div style="font-size: 10px; font-style: italic;">
                Ket: {{ $pembayaran->catatan }}
            </div>
        @endif

        <div class="divider"></div>

        <!-- Footer -->
        <div class="footer-note">
            <div>Terima Kasih Atas Pembayaran Anda</div>
            <div>Simpan struk ini sebagai bukti pembayaran sah</div>
            <div style="margin-top: 4px;">*** Natural Gypsum & PVC ***</div>
        </div>

    </div>

</body>
</html>
