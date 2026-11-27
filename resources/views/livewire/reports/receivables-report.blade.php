<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.hub') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Piutang</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-clock-history text-warning me-2"></i>Laporan Piutang &amp; Analisis Umur Tagihan (Aging {{ $tempoStandarHari }} Hari)
            </h1>
            <p class="text-muted small mb-0">Pemantauan saldo piutang berjalan, analisis kolektibilitas tempo {{ $tempoStandarHari }} hari, dan akses cetak Surat Tagihan (SOA).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('piutang.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-wallet2 me-1"></i> Kelola Piutang
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Cetak
            </button>
            <a href="{{ route('laporan.hub') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Hub
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Saldo Piutang</div>
                    <div class="h4 fw-bold text-danger mb-1">Rp {{ number_format($summary['total_piutang'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">{{ number_format($summary['total_faktur_belum_lunas'] ?? 0, 0, ',', '.') }} Faktur Belum Lunas</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Lancar (0–{{ $tempoStandarHari }} Hari)</div>
                    <div class="h4 fw-bold text-success mb-1">Rp {{ number_format($summary['piutang_lancar'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Dalam masa tempo standar</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Lewat Tempo (&gt; {{ $tempoStandarHari }} Hr)</div>
                    <div class="h4 fw-bold text-warning-emphasis mb-1">Rp {{ number_format($summary['piutang_lewat_tempo'] ?? 0, 0, ',', '.') }}</div>
                    <div class="small text-muted">Macet (&gt;60 hr): <strong class="text-danger">Rp {{ number_format($summary['piutang_macet'] ?? 0, 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Pelanggan Berhutang</div>
                    <div class="h4 fw-bold text-primary mb-1">{{ number_format($summary['total_pelanggan_berhutang'] ?? 0, 0, ',', '.') }} Pelanggan</div>
                    <div class="small text-muted">Debitur aktif saat ini</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Debtors Card -->
    @if(count($topDebtors) > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-gray-800">
                    <i class="bi bi-trophy text-warning me-2"></i>Top 5 Debitur dengan Saldo Piutang Terbesar
                </h6>
                <span class="badge bg-light text-secondary border small">Prioritas Penagihan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th>Nama Pelanggan</th>
                                <th class="text-center">Jumlah Faktur Belum Lunas</th>
                                <th class="text-center">Umur Nota Tertua</th>
                                <th class="text-end">Total Tagihan Piutang</th>
                                <th class="text-end pe-3" style="width: 150px;">Surat Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topDebtors as $idx => $debtor)
                                @php
                                    $oldestDays = !empty($debtor['oldest_date']) ? (int) \Carbon\Carbon::parse($debtor['oldest_date'])->diffInDays(now()) : 0;
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td class="fw-semibold">{{ $debtor['nama'] }}</td>
                                    <td class="text-center">{{ $debtor['invoice_count'] }} faktur</td>
                                    <td class="text-center">
                                        <span class="badge {{ $oldestDays > $tempoStandarHari ? 'bg-warning-subtle text-warning-emphasis border' : 'bg-success-subtle text-success border' }}">
                                            {{ $oldestDays }} Hari
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($debtor['total_debt'], 0, ',', '.') }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('piutang.print.tagihan', $debtor['pelanggan_id']) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                                            <i class="bi bi-printer me-1"></i>Cetak SOA
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari No Faktur / Pelanggan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Ketik no faktur / nama...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pelanggan</label>
                    <select wire:model.live="pelanggan_id" class="form-select form-select-sm">
                        <option value="">Semua Pelanggan</option>
                        @foreach($pelangganList as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Kategori Aging ({{ $tempoStandarHari }} Hr)</label>
                    <select wire:model.live="aging" class="form-select form-select-sm">
                        <option value="">Semua Umur</option>
                        <option value="lewat_tempo">Semua Lewat Tempo (&gt;14 Hr)</option>
                        <option value="lancar">Lancar (0–14 Hr)</option>
                        <option value="jatuh_tempo">Jatuh Tempo (15–30 Hr)</option>
                        <option value="menunggak">Menunggak (31–60 Hr)</option>
                        <option value="macet">Macet (&gt;60 Hr)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Periode Nota</label>
                    <select wire:model.live="period" class="form-select form-select-sm">
                        <option value="all">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="last_7_days">7 Hari Terakhir</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="custom">Kustom Tanggal</option>
                    </select>
                </div>
                @if($period === 'custom')
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Rentang Tanggal</label>
                        <div class="input-group input-group-sm">
                            <input type="date" wire:model.live="startDate" class="form-control">
                            <span class="input-group-text">s/d</span>
                            <input type="date" wire:model.live="endDate" class="form-control">
                        </div>
                    </div>
                @endif
                <div class="col-md-auto d-flex align-items-end">
                    <button wire:click="resetFilter" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-gray-800">
                <i class="bi bi-list-task me-2"></i>Daftar Faktur Piutang Belum Lunas
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Per halaman:</span>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Tanggal &amp; Tempo</th>
                        <th>No Faktur</th>
                        <th>Pelanggan</th>
                        <th class="text-center">Umur Piutang</th>
                        <th class="text-end">Total Faktur</th>
                        <th class="text-end">Telah Dibayar</th>
                        <th class="text-end">Sisa Piutang</th>
                        <th class="text-center">Status Bayar</th>
                        <th class="pe-3 text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $row)
                        @php
                            $tglNota = \Carbon\Carbon::parse($row->tanggal);
                            $jthTempo = $tglNota->copy()->addDays($tempoStandarHari);
                            $daysAge = (int) $tglNota->diffInDays(now());
                            $ageBadge = $daysAge > 60 ? 'bg-danger text-white' : ($daysAge > 30 ? 'bg-danger-subtle text-danger border' : ($daysAge > $tempoStandarHari ? 'bg-warning-subtle text-warning-emphasis border' : 'bg-success-subtle text-success border'));
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $invoices->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-semibold">{{ $tglNota->format('d/m/Y') }}</div>
                                <small class="{{ $daysAge > $tempoStandarHari ? 'text-danger fw-semibold' : 'text-muted' }}" style="font-size: 0.72rem;">
                                    Tempo: {{ $jthTempo->format('d/m/Y') }}
                                </small>
                            </td>
                            <td class="fw-bold text-primary font-monospace">{{ $row->no_penjualan }}</td>
                            <td>{{ $row->pelanggan->nama ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge {{ $ageBadge }}">
                                    {{ $daysAge }} Hari
                                </span>
                            </td>
                            <td class="text-end">Rp {{ number_format($row->total_akhir, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($row->total_dibayar, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-danger">Rp {{ number_format($row->sisa_piutang, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($row->status_pembayaran->value === 'sebagian')
                                    <span class="badge bg-warning text-dark">Sebagian</span>
                                @else
                                    <span class="badge bg-danger">Belum Bayar</span>
                                @endif
                            </td>
                            <td class="pe-3 text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('penjualan.show', $row->id) }}" class="btn btn-outline-primary" title="Lihat Detail Faktur">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($row->pelanggan_id)
                                        <a href="{{ route('piutang.print.tagihan', $row->pelanggan_id) }}" target="_blank" class="btn btn-outline-dark" title="Cetak Surat Tagihan Pelanggan">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                Tidak ada piutang outstanding yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
