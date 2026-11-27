<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">Daftar Piutang Pelanggan</h4>
                <span class="badge bg-dark-subtle text-dark border small">
                    <i class="bi bi-calendar-check me-1 text-warning"></i>Standar Tempo: {{ $tempoStandarHari }} Hari
                </span>
            </div>
            <p class="text-muted small mb-0">Pantau saldo piutang aktif, umur jatuh tempo pemborong/proyek, histori cicilan, dan cetak surat tagihan resmi.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('view_receivable_reports')
                <a href="{{ route('laporan.piutang') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i>Laporan Piutang
                </a>
            @endcan
            @can('record_payments')
                <a href="{{ route('pembayaran.create') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-cash-stack me-1"></i>Catat Pembayaran
                </a>
            @endcan
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-danger-subtle text-danger rounded-3 me-3">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Total Piutang Aktif</span>
                            <h5 class="fw-bold mb-0 text-danger">Rp {{ number_format($totalPiutangAktif, 2, ',', '.') }}</h5>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ number_format($totalFakturBelumLunas) }} Faktur Belum Lunas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 me-3">
                            <i class="bi bi-exclamation-octagon fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Lewat Tempo (&gt; {{ $tempoStandarHari }} Hari)</span>
                            <h5 class="fw-bold mb-0 text-warning-emphasis">Rp {{ number_format($totalPiutangLewatTempo, 2, ',', '.') }}</h5>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ number_format($totalFakturLewatTempo) }} Faktur Perlu Ditagih</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-info-subtle text-info rounded-3 me-3">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Pelanggan Berpiutang</span>
                            <h5 class="fw-bold mb-0 text-info">{{ number_format($totalPelangganBerpiutang) }} Orang</h5>
                            <small class="text-muted" style="font-size: 0.75rem;">Pemborong / Toko / Proyek Aktif</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-success-subtle text-success rounded-3 me-3">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Penerimaan Bulan Ini</span>
                            <h5 class="fw-bold mb-0 text-success">Rp {{ number_format($totalBayarBulanIni, 2, ',', '.') }}</h5>
                            <small class="text-muted" style="font-size: 0.75rem;">Total Cicilan &amp; Pelunasan Masuk</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive 14-Day Aging Bucket Strip -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-lg-3">
            <button
                type="button"
                wire:click="setAgingBucket('lancar')"
                class="btn w-100 text-start p-3 rounded-3 border shadow-xs transition-all {{ $agingFilter === 'lancar' ? 'bg-success-subtle border-success' : 'bg-white border-light-subtle' }}"
            >
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-check-circle me-1"></i>Lancar (0–14 Hari)
                    </span>
                    <span class="small fw-bold text-muted">{{ $agingBuckets['lancar']['count'] }} Nota</span>
                </div>
                <div class="fw-bold text-dark fs-6">Rp {{ number_format($agingBuckets['lancar']['nominal'], 0, ',', '.') }}</div>
                <small class="text-muted" style="font-size: 0.72rem;">Dalam masa tempo standar 14 hari</small>
            </button>
        </div>
        <div class="col-6 col-lg-3">
            <button
                type="button"
                wire:click="setAgingBucket('jatuh_tempo')"
                class="btn w-100 text-start p-3 rounded-3 border shadow-xs transition-all {{ $agingFilter === 'jatuh_tempo' ? 'bg-warning-subtle border-warning' : 'bg-white border-light-subtle' }}"
            >
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        <i class="bi bi-clock-history me-1"></i>Jatuh Tempo (15–30 Hr)
                    </span>
                    <span class="small fw-bold text-muted">{{ $agingBuckets['jatuh_tempo']['count'] }} Nota</span>
                </div>
                <div class="fw-bold text-dark fs-6">Rp {{ number_format($agingBuckets['jatuh_tempo']['nominal'], 0, ',', '.') }}</div>
                <small class="text-muted" style="font-size: 0.72rem;">Lewat tempo 14 hari (waktunya follow-up)</small>
            </button>
        </div>
        <div class="col-6 col-lg-3">
            <button
                type="button"
                wire:click="setAgingBucket('menunggak')"
                class="btn w-100 text-start p-3 rounded-3 border shadow-xs transition-all {{ $agingFilter === 'menunggak' ? 'bg-danger-subtle border-danger' : 'bg-white border-light-subtle' }}"
            >
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="bi bi-exclamation-triangle me-1"></i>Menunggak (31–60 Hr)
                    </span>
                    <span class="small fw-bold text-muted">{{ $agingBuckets['menunggak']['count'] }} Nota</span>
                </div>
                <div class="fw-bold text-danger fs-6">Rp {{ number_format($agingBuckets['menunggak']['nominal'], 0, ',', '.') }}</div>
                <small class="text-muted" style="font-size: 0.72rem;">Tunggakan lebih dari 1 bulan</small>
            </button>
        </div>
        <div class="col-6 col-lg-3">
            <button
                type="button"
                wire:click="setAgingBucket('macet')"
                class="btn w-100 text-start p-3 rounded-3 border shadow-xs transition-all {{ $agingFilter === 'macet' ? 'bg-dark text-white border-dark' : 'bg-white border-light-subtle' }}"
            >
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-danger text-white">
                        <i class="bi bi-fire me-1"></i>Macet / Kritis (&gt; 60 Hr)
                    </span>
                    <span class="small fw-bold {{ $agingFilter === 'macet' ? 'text-light' : 'text-muted' }}">{{ $agingBuckets['macet']['count'] }} Nota</span>
                </div>
                <div class="fw-bold {{ $agingFilter === 'macet' ? 'text-white' : 'text-danger' }} fs-6">Rp {{ number_format($agingBuckets['macet']['nominal'], 0, ',', '.') }}</div>
                <small class="{{ $agingFilter === 'macet' ? 'text-light opacity-75' : 'text-muted' }}" style="font-size: 0.72rem;">Prioritas penagihan khusus</small>
            </button>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-4">
                    <label class="form-label small text-muted mb-1">Pencarian Nama / Kontak</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari nama pemborong, toko, atau nomor WA..."
                        >
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Tipe Pelanggan</label>
                    <select wire:model.live="tipeFilter" class="form-select">
                        <option value="">Semua Tipe</option>
                        @foreach ($tipeOptions as $tipe)
                            <option value="{{ $tipe->value }}">{{ $tipe->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Status Kredit</label>
                    <select wire:model.live="piutangFilter" class="form-select">
                        <option value="berpiutang">Hanya Pelanggan Berpiutang</option>
                        <option value="overlimit">Melebihi Limit Kredit</option>
                        <option value="semua">Semua Pelanggan</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small text-muted mb-1">Umur Piutang (Tempo {{ $tempoStandarHari }} Hr)</label>
                    <select wire:model.live="agingFilter" class="form-select">
                        <option value="">Semua Umur Piutang</option>
                        <option value="lewat_tempo">Semua Lewat Tempo (&gt; 14 Hari)</option>
                        <option value="lancar">Lancar (0–14 Hari)</option>
                        <option value="jatuh_tempo">Jatuh Tempo (15–30 Hari)</option>
                        <option value="menunggak">Menunggak (31–60 Hari)</option>
                        <option value="macet">Macet / Kritis (&gt; 60 Hari)</option>
                    </select>
                </div>
                <div class="col-md-2 col-lg-1 d-grid">
                    <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 45px;">No</th>
                            <th role="button" wire:click="sortByColumn('nama')" class="user-select-none">
                                Pelanggan &amp; Kontak
                                @if ($sortBy === 'nama')
                                    <i class="bi bi-sort-alpha-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th class="text-center">Faktur Tertunggak</th>
                            <th role="button" wire:click="sortByColumn('oldest_unpaid_date')" class="text-center user-select-none">
                                Umur &amp; Jatuh Tempo
                                @if ($sortBy === 'oldest_unpaid_date')
                                    <i class="bi bi-sort-numeric-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th role="button" wire:click="sortByColumn('total_penjualan_nominal')" class="text-end user-select-none">
                                Nilai Nota &amp; Cicilan
                                @if ($sortBy === 'total_penjualan_nominal')
                                    <i class="bi bi-sort-numeric-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th role="button" wire:click="sortByColumn('total_sisa_piutang')" class="text-end user-select-none">
                                Sisa Piutang
                                @if ($sortBy === 'total_sisa_piutang')
                                    <i class="bi bi-sort-numeric-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th role="button" wire:click="sortByColumn('limit_piutang')" class="text-end user-select-none" style="min-width: 145px;">
                                Limit Kredit
                                @if ($sortBy === 'limit_piutang')
                                    <i class="bi bi-sort-numeric-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th class="text-end pe-4" style="width: 215px;">Aksi Penagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pelanggans as $index => $pelanggan)
                            @php
                                $sisaPiutang = (float) ($pelanggan->total_sisa_piutang ?? 0);
                                $limit = (float) ($pelanggan->limit_piutang ?? 0);
                                $isOverLimit = $limit > 0 && $sisaPiutang > $limit;
                                $percentUsed = $limit > 0 ? min(100, round(($sisaPiutang / $limit) * 100)) : 0;
                                $barColor = $isOverLimit ? 'bg-danger' : ($percentUsed >= 80 ? 'bg-warning' : 'bg-success');

                                $unpaidCount = (int) ($pelanggan->unpaid_penjualan_count ?? 0);
                                $totalCount = (int) ($pelanggan->total_penjualan_count ?? 0);
                                $unpaidNominal = (float) ($pelanggan->unpaid_penjualan_nominal ?? 0);
                                $unpaidDibayar = (float) ($pelanggan->unpaid_dibayar_nominal ?? 0);

                                // Calculate oldest unpaid invoice age & 14-day due status
                                $oldestDate = $pelanggan->oldest_unpaid_date ? \Carbon\Carbon::parse($pelanggan->oldest_unpaid_date) : null;
                                $oldestDays = $oldestDate ? (int) $oldestDate->diffInDays(now()) : 0;
                                $dueDate = $oldestDate ? $oldestDate->copy()->addDays($tempoStandarHari) : null;

                                if (! $oldestDate || $sisaPiutang <= 0) {
                                    $ageBadgeClass = 'bg-success-subtle text-success border-success-subtle';
                                    $ageStatusText = 'Lunas';
                                } elseif ($oldestDays > 60) {
                                    $ageBadgeClass = 'bg-danger text-white';
                                    $ageStatusText = $oldestDays . ' Hari (Macet >60)';
                                } elseif ($oldestDays > 30) {
                                    $ageBadgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                                    $ageStatusText = $oldestDays . ' Hari (Menunggak)';
                                } elseif ($oldestDays > $tempoStandarHari) {
                                    $ageBadgeClass = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                                    $ageStatusText = $oldestDays . ' Hari (Lewat Tempo)';
                                } else {
                                    $ageBadgeClass = 'bg-success-subtle text-success border-success-subtle';
                                    $ageStatusText = $oldestDays . ' Hari (Lancar)';
                                }

                                $lastPayDate = $pelanggan->last_payment_date ? \Carbon\Carbon::parse($pelanggan->last_payment_date) : null;
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $pelanggans->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-bold text-dark">{{ $pelanggan->nama }}</span>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                            {{ $pelanggan->tipe->label() ?? 'Umum' }}
                                        </span>
                                    </div>
                                    <div class="small text-muted mt-1 d-flex align-items-center gap-2 flex-wrap">
                                        <span><i class="bi bi-telephone me-1"></i>{{ $pelanggan->kontak ?: 'Tanpa kontak' }}</span>
                                        @if ($lastPayDate)
                                            <span class="text-success" title="Tanggal pembayaran/cicilan terakhir">
                                                &bull; <i class="bi bi-check2-circle"></i> Bayar terakhir: {{ $lastPayDate->format('d/m/Y') }}
                                            </span>
                                        @elseif ($sisaPiutang > 0)
                                            <span class="text-secondary">
                                                &bull; Belum ada cicilan
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($unpaidCount > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            {{ number_format($unpaidCount) }} Faktur Belum Lunas
                                        </span>
                                        <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">
                                            Total histori: {{ number_format($totalCount) }} Faktur
                                        </small>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            0 Tertunggak ({{ number_format($totalCount) }} Faktur)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($oldestDate && $sisaPiutang > 0)
                                        <span class="badge {{ $ageBadgeClass }} border px-2 py-1">
                                            <i class="bi bi-clock me-1"></i>{{ $ageStatusText }}
                                        </span>
                                        <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">
                                            Nota tertua: {{ $oldestDate->format('d/m/Y') }}
                                            @if ($dueDate)
                                                (Tempo: {{ $dueDate->format('d/m/Y') }})
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold text-dark" title="Total nilai faktur yang sedang tertunggak">
                                        Rp {{ number_format($unpaidNominal > 0 ? $unpaidNominal : (float) ($pelanggan->total_penjualan_nominal ?? 0), 2, ',', '.') }}
                                    </div>
                                    @if ($unpaidDibayar > 0)
                                        <small class="text-success d-block" style="font-size: 0.73rem;">
                                            Dicicil: Rp {{ number_format($unpaidDibayar, 0, ',', '.') }}
                                        </small>
                                    @else
                                        <small class="text-muted d-block" style="font-size: 0.73rem;">
                                            Total bayar: Rp {{ number_format((float) ($pelanggan->total_dibayar_nominal ?? 0), 0, ',', '.') }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $sisaPiutang > 0 ? 'text-danger' : 'text-success' }}">
                                    Rp {{ number_format($sisaPiutang, 2, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    @if ($limit > 0)
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted" style="font-size: 0.75rem;">Limit:</small>
                                            <span class="small fw-semibold {{ $isOverLimit ? 'text-danger' : 'text-dark' }}">
                                                Rp {{ number_format($limit, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 5px;" title="Terpakai {{ $percentUsed }}%">
                                            <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $percentUsed }}%"></div>
                                        </div>
                                        @if ($isOverLimit)
                                            <span class="badge bg-danger text-white d-block mt-1" style="font-size: 0.7rem;" title="Melebihi Limit Kredit">
                                                Over +Rp {{ number_format($sisaPiutang - $limit, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">{{ $percentUsed }}% terpakai</small>
                                        @endif
                                    @else
                                        <span class="text-muted small">Tanpa Limit</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            type="button"
                                            wire:click="showCustomerInvoices({{ $pelanggan->id }})"
                                            class="btn btn-outline-secondary"
                                            title="Rincian Faktur, Barang Proyek &amp; Histori Cicilan"
                                        >
                                            <i class="bi bi-receipt me-1"></i>Faktur
                                        </button>
                                        @if ($sisaPiutang > 0)
                                            <a
                                                href="{{ route('piutang.print.tagihan', $pelanggan->id) }}"
                                                target="_blank"
                                                class="btn btn-outline-dark"
                                                title="Cetak Surat Tagihan Resmi (Statement of Account)"
                                            >
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            @can('record_payments')
                                                <a
                                                    href="{{ route('pembayaran.create', ['pelanggan_id' => $pelanggan->id]) }}"
                                                    class="btn btn-primary"
                                                    title="Bayar Piutang"
                                                >
                                                    <i class="bi bi-cash-coin me-1"></i>Bayar
                                                </a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Tidak ada data piutang pelanggan yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($pelanggans->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $pelanggans->links() }}
            </div>
        @endif
    </div>

    <!-- Customer Unpaid Invoices, Project Items & Installment History Modal -->
    @if ($isDetailModalOpen && $selectedCustomer)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.55);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                    <!-- Modal Header -->
                    <div class="modal-header bg-white border-bottom py-3 px-4">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-primary-subtle text-primary rounded-3 me-3">
                                <i class="bi bi-receipt-cutoff fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">Rincian Faktur Tertunggak &amp; Riwayat Cicilan</h5>
                                <div class="text-muted small mt-1">
                                    Daftar nota belum lunas, rincian pengambilan material proyek, dan histori pembayaran (Standar Tempo {{ $tempoStandarHari }} Hari).
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if ($selectedInvoices->isNotEmpty())
                                <button
                                    type="button"
                                    wire:click="toggleAllInvoiceDetails"
                                    class="btn btn-sm btn-outline-primary px-2.5"
                                    title="Buka atau tutup seluruh rincian barang dan histori cicilan"
                                >
                                    <i class="bi bi-layers me-1"></i>
                                    {{ count($expandedInvoiceIds) === $selectedInvoices->count() ? 'Tutup Rincian Barang' : 'Lihat Semua Barang & Cicilan' }}
                                </button>
                                <a
                                    href="{{ route('piutang.print.tagihan', $selectedCustomer->id) }}"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-dark px-3 shadow-xs"
                                    title="Cetak Surat Tagihan Resmi A4"
                                >
                                    <i class="bi bi-printer me-1"></i>Cetak Surat Tagihan
                                </a>
                            @endif
                            @if ($whatsAppUrl)
                                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success text-white px-3 shadow-sm" title="Kirim Pengingat Tagihan + Nomor Rekening via WhatsApp">
                                    <i class="bi bi-whatsapp me-1"></i>Kirim WA Tagihan
                                </a>
                            @endif
                            <button type="button" class="btn-close ms-2" wire:click="closeDetailModal" aria-label="Close"></button>
                        </div>
                    </div>

                    <!-- Customer Info Strip -->
                    <div class="bg-light px-4 py-3 border-bottom">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-7">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                    <h6 class="fw-bold text-dark mb-0 fs-5">{{ $selectedCustomer->nama }}</h6>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        {{ $selectedCustomer->tipe->label() ?? 'Umum' }}
                                    </span>
                                </div>
                                <div class="text-muted small d-flex align-items-center flex-wrap gap-3">
                                    <span><i class="bi bi-person-badge text-secondary me-1"></i>ID: #{{ $selectedCustomer->id }}</span>
                                    <span><i class="bi bi-telephone text-secondary me-1"></i>{{ $selectedCustomer->kontak ?? 'Tidak ada kontak' }}</span>
                                    <span><i class="bi bi-wallet text-secondary me-1"></i>Plafon Kredit: {{ (float) $selectedCustomer->limit_piutang > 0 ? 'Rp ' . number_format((float) $selectedCustomer->limit_piutang, 0, ',', '.') : 'Tanpa Limit' }}</span>
                                    @if ($selectedCustomer->last_payment_date)
                                        <span class="text-success fw-semibold">
                                            <i class="bi bi-check2-circle me-1"></i>Terakhir Bayar: {{ \Carbon\Carbon::parse($selectedCustomer->last_payment_date)->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <div class="d-inline-flex flex-column align-items-md-end bg-white border border-danger-subtle rounded-3 p-2 px-3 shadow-sm">
                                    <span class="text-muted small" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Total Sisa Piutang ({{ $selectedInvoices->count() }} Faktur)</span>
                                    <span class="h5 fw-bold text-danger mb-0">Rp {{ number_format($selectedTotalSisa, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Body / Table -->
                    <div class="modal-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase fw-semibold border-bottom">
                                    <tr>
                                        <th class="ps-4 py-3" style="width: 180px;">No. Faktur &amp; Proyek</th>
                                        <th class="py-3">Tgl Nota &amp; Tempo</th>
                                        <th class="text-center py-3">Umur Piutang</th>
                                        <th class="py-3">Ringkasan Material</th>
                                        <th class="text-end py-3">Total Faktur</th>
                                        <th class="text-end py-3">Telah Dibayar</th>
                                        <th class="text-end py-3">Sisa Piutang</th>
                                        <th class="text-center py-3">Status</th>
                                        <th class="text-end pe-4 py-3" style="width: 170px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($selectedInvoices as $inv)
                                        @php
                                            $tglNota = \Carbon\Carbon::parse($inv->tanggal);
                                            $tglJatuhTempo = $tglNota->copy()->addDays($tempoStandarHari);
                                            $daysOverdue = (int) $tglNota->diffInDays(now());
                                            $isExpanded = in_array($inv->id, $expandedInvoiceIds, true);

                                            if ($daysOverdue > 60) {
                                                $agingBadge = 'bg-danger text-white';
                                                $agingLabel = $daysOverdue . ' hari (Macet >60)';
                                            } elseif ($daysOverdue > 30) {
                                                $agingBadge = 'bg-danger-subtle text-danger border-danger-subtle';
                                                $agingLabel = $daysOverdue . ' hari (31–60)';
                                            } elseif ($daysOverdue > $tempoStandarHari) {
                                                $agingBadge = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                                                $agingLabel = $daysOverdue . ' hari (Lewat Tempo)';
                                            } else {
                                                $agingBadge = 'bg-success-subtle text-success border-success-subtle';
                                                $agingLabel = $daysOverdue . ' hari (Lancar)';
                                            }

                                            $itemPreview = $inv->details->take(2)->map(function ($d) {
                                                $nama = $d->produk->nama ?? 'Item';
                                                $qty = number_format((float) $d->jumlah, 0, ',', '.');
                                                return "{$nama} ({$qty})";
                                            })->implode(', ');
                                            $moreItemsCount = max(0, $inv->details->count() - 2);
                                        @endphp
                                        <tr class="{{ $isExpanded ? 'table-active' : '' }}">
                                            <td class="ps-4">
                                                <a href="{{ route('penjualan.show', $inv->id) }}" class="fw-bold text-primary text-decoration-none font-monospace">
                                                    {{ $inv->no_penjualan }}
                                                </a>
                                                @if ($inv->alamat_pengiriman)
                                                    <div class="small text-muted text-truncate" style="max-width: 185px;" title="Lokasi Kirim: {{ $inv->alamat_pengiriman }}">
                                                        <i class="bi bi-geo-alt text-secondary me-1"></i>{{ $inv->alamat_pengiriman }}
                                                    </div>
                                                @else
                                                    <div class="small text-muted">
                                                        <i class="bi bi-building text-secondary me-1"></i>{{ $inv->gudang->nama_gudang ?? 'Ambil di Toko' }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="fw-semibold text-dark">{{ $tglNota->format('d/m/Y') }}</div>
                                                <small class="{{ $daysOverdue > $tempoStandarHari ? 'text-danger fw-semibold' : 'text-muted' }}" style="font-size: 0.72rem;">
                                                    Tempo: {{ $tglJatuhTempo->format('d/m/Y') }}
                                                </small>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <span class="badge {{ $agingBadge }} border px-2 py-1">
                                                    <i class="bi bi-clock me-1"></i>{{ $agingLabel }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="small text-dark" style="max-width: 230px;">
                                                    {{ $itemPreview ?: '-' }}
                                                    @if ($moreItemsCount > 0)
                                                        <span class="text-primary fw-semibold">+{{ $moreItemsCount }} item lainnya</span>
                                                    @endif
                                                </div>
                                                <button
                                                    type="button"
                                                    wire:click="toggleInvoiceDetail({{ $inv->id }})"
                                                    class="btn btn-link btn-sm p-0 text-decoration-none small"
                                                    style="font-size: 0.74rem;"
                                                >
                                                    <i class="bi bi-{{ $isExpanded ? 'chevron-up' : 'chevron-down' }} me-1"></i>
                                                    {{ $isExpanded ? 'Sembunyikan Detail' : 'Lihat Rincian Barang & Cicilan (' . $inv->alokasiPembayarans->count() . 'x bayar)' }}
                                                </button>
                                            </td>
                                            <td class="text-end fw-semibold text-nowrap">
                                                Rp {{ number_format((float) $inv->total_akhir, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end text-muted text-nowrap">
                                                Rp {{ number_format((float) $inv->total_dibayar, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end fw-bold text-danger text-nowrap">
                                                Rp {{ number_format((float) $inv->sisa_piutang, 2, ',', '.') }}
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <span class="badge {{ $inv->status_pembayaran->badgeClass() }}">
                                                    {{ $inv->status_pembayaran->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 text-nowrap">
                                                <div class="btn-group btn-group-sm">
                                                    <button
                                                        type="button"
                                                        wire:click="toggleInvoiceDetail({{ $inv->id }})"
                                                        class="btn {{ $isExpanded ? 'btn-secondary' : 'btn-outline-secondary' }}"
                                                        title="Buka Rincian Barang &amp; Cicilan"
                                                    >
                                                        <i class="bi bi-list-ul"></i>
                                                    </button>
                                                    <a href="{{ route('penjualan.show', $inv->id) }}" class="btn btn-outline-secondary" title="Lihat Detail Nota">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @can('record_payments')
                                                        <a href="{{ route('pembayaran.create', ['penjualan_id' => $inv->id]) }}" class="btn btn-primary" title="Bayar Faktur Ini">
                                                            <i class="bi bi-cash-stack me-1"></i>Bayar
                                                        </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Expandable Drawer: Project Items & Payment Installments History -->
                                        @if ($isExpanded)
                                            <tr class="bg-light">
                                                <td colspan="9" class="px-4 py-3 border-bottom">
                                                    <div class="row g-3">
                                                        <!-- Left: Material / Items Taken in This Invoice -->
                                                        <div class="col-lg-7">
                                                            <div class="bg-white border rounded-3 p-3 h-100 shadow-xs">
                                                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                                                    <span class="fw-bold small text-dark text-uppercase">
                                                                        <i class="bi bi-box-seam text-primary me-1"></i>Rincian Barang yang Diambil ({{ $inv->no_penjualan }})
                                                                    </span>
                                                                    <span class="small text-muted">
                                                                        @if ($inv->nama_sopir)
                                                                            Sopir: <strong>{{ $inv->nama_sopir }}</strong> {{ $inv->plat_nomor ? "({$inv->plat_nomor})" : '' }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                                <table class="table table-sm table-borderless mb-0 small">
                                                                    <thead class="text-muted border-bottom">
                                                                        <tr>
                                                                            <th>Material / Produk</th>
                                                                            <th class="text-center">Qty</th>
                                                                            <th class="text-end">Harga Satuan</th>
                                                                            <th class="text-end">Subtotal</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($inv->details as $det)
                                                                            <tr class="border-bottom border-light">
                                                                                <td class="fw-semibold text-dark">
                                                                                    {{ $det->produk->nama ?? '-' }}
                                                                                    @if ($det->produk?->merek)
                                                                                        <span class="badge bg-light text-secondary border ms-1">{{ $det->produk->merek->nama_merek }}</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td class="text-center font-monospace">
                                                                                    {{ number_format((float) $det->jumlah, 0, ',', '.') }} {{ $det->produk?->satuan?->label() ?? '' }}
                                                                                </td>
                                                                                <td class="text-end font-monospace">
                                                                                    Rp {{ number_format((float) $det->harga_satuan, 0, ',', '.') }}
                                                                                </td>
                                                                                <td class="text-end fw-semibold font-monospace">
                                                                                    Rp {{ number_format((float) $det->subtotal, 0, ',', '.') }}
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                        @if ((float) $inv->biaya_ongkir > 0)
                                                                            <tr>
                                                                                <td colspan="3" class="text-end text-muted">Biaya Ongkir / Ekspedisi:</td>
                                                                                <td class="text-end fw-semibold font-monospace">Rp {{ number_format((float) $inv->biaya_ongkir, 0, ',', '.') }}</td>
                                                                            </tr>
                                                                        @endif
                                                                    </tbody>
                                                                </table>
                                                                @if ($inv->keterangan || $inv->alamat_pengiriman)
                                                                    <div class="mt-2 pt-2 border-top small text-muted">
                                                                        @if ($inv->alamat_pengiriman)
                                                                            <div><i class="bi bi-geo-alt me-1"></i><strong>Lokasi Kirim / Proyek:</strong> {{ $inv->alamat_pengiriman }}</div>
                                                                        @endif
                                                                        @if ($inv->keterangan)
                                                                            <div><i class="bi bi-chat-left-text me-1"></i><strong>Catatan Nota:</strong> {{ $inv->keterangan }}</div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Right: Installment / Payment History for This Invoice -->
                                                        <div class="col-lg-5">
                                                            <div class="bg-white border rounded-3 p-3 h-100 shadow-xs">
                                                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                                                    <span class="fw-bold small text-success text-uppercase">
                                                                        <i class="bi bi-clock-history me-1"></i>Riwayat Cicilan / Pembayaran Masuk
                                                                    </span>
                                                                    <span class="badge bg-success-subtle text-success border">
                                                                        {{ $inv->alokasiPembayarans->count() }}x Transaksi
                                                                    </span>
                                                                </div>
                                                                @if ($inv->alokasiPembayarans->isNotEmpty())
                                                                    <table class="table table-sm table-borderless mb-0 small">
                                                                        <thead class="text-muted border-bottom">
                                                                            <tr>
                                                                                <th>No. Bukti</th>
                                                                                <th>Tanggal</th>
                                                                                <th>Metode</th>
                                                                                <th class="text-end">Nominal</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($inv->alokasiPembayarans as $alok)
                                                                                <tr class="border-bottom border-light">
                                                                                    <td>
                                                                                        @if ($alok->pembayaran)
                                                                                            <a href="{{ route('pembayaran.show', $alok->pembayaran->id) }}" class="fw-bold text-decoration-none font-monospace">
                                                                                                {{ $alok->pembayaran->no_pembayaran }}
                                                                                            </a>
                                                                                        @else
                                                                                            -
                                                                                        @endif
                                                                                    </td>
                                                                                    <td>{{ $alok->pembayaran?->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                                                                    <td>
                                                                                        <span class="badge bg-light text-dark border">
                                                                                            {{ $alok->pembayaran?->metode?->label() ?? '-' }}
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-end fw-bold text-success font-monospace">
                                                                                        Rp {{ number_format((float) $alok->jumlah_dialokasikan, 0, ',', '.') }}
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                @else
                                                                    <div class="text-center py-4 text-muted small">
                                                                        <i class="bi bi-cash d-block fs-4 mb-1 opacity-50"></i>
                                                                        Belum ada pembayaran/cicilan yang masuk untuk faktur ini.
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">
                                                <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                                                Tidak ada faktur tertunggak untuk pelanggan ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if ($selectedInvoices->isNotEmpty())
                                    <tfoot class="table-light border-top fw-bold text-nowrap">
                                        <tr>
                                            <td colspan="4" class="ps-4 text-end text-muted small text-uppercase">Total Akumulasi Tagihan Aktif:</td>
                                            <td class="text-end">Rp {{ number_format((float) $selectedInvoices->sum('total_akhir'), 2, ',', '.') }}</td>
                                            <td class="text-end text-muted">Rp {{ number_format((float) $selectedInvoices->sum('total_dibayar'), 2, ',', '.') }}</td>
                                            <td class="text-end text-danger fw-bold fs-6">Rp {{ number_format($selectedTotalSisa, 2, ',', '.') }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer bg-white border-top d-flex justify-content-between align-items-center py-3 px-4 flex-wrap gap-2">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light border" wire:click="closeDetailModal">
                                Tutup
                            </button>
                            @if ($selectedInvoices->isNotEmpty())
                                <a href="{{ route('piutang.print.tagihan', $selectedCustomer->id) }}" target="_blank" class="btn btn-outline-dark" title="Cetak Surat Tagihan Resmi (A4)">
                                    <i class="bi bi-printer me-1"></i>Cetak Surat Tagihan Resmi
                                </a>
                            @endif
                        </div>
                        @can('record_payments')
                            <a href="{{ route('pembayaran.create', ['pelanggan_id' => $selectedCustomer->id]) }}" class="btn btn-primary px-3 shadow-sm">
                                <i class="bi bi-cash-coin me-1"></i>Catat Pembayaran Pelanggan Ini
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
