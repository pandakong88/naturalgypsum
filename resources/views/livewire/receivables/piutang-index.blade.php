<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Daftar Piutang Pelanggan</h4>
            <p class="text-muted small mb-0">Pantau saldo piutang, status kredit, dan riwayat faktur tertunggak per pelanggan.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('view_receivable_reports')
                <a href="{{ route('laporan.piutang') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i>Laporan Piutang
                </a>
            @endcan
            @can('record_payments')
                <a href="{{ route('pembayaran.create') }}" class="btn btn-primary">
                    <i class="bi bi-cash-stack me-1"></i>Catat Pembayaran
                </a>
            @endcan
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-danger-subtle text-danger rounded-3 me-3">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Total Piutang Aktif</span>
                            <h5 class="fw-bold mb-0 text-danger">Rp {{ number_format($totalPiutangAktif, 2, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 me-3">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Pelanggan Berpiutang</span>
                            <h5 class="fw-bold mb-0 text-warning-emphasis">{{ number_format($totalPelangganBerpiutang) }} Orang</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-info-subtle text-info rounded-3 me-3">
                            <i class="bi bi-receipt fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Faktur Belum Lunas</span>
                            <h5 class="fw-bold mb-0 text-info">{{ number_format($totalFakturBelumLunas) }} Faktur</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-success-subtle text-success rounded-3 me-3">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Penerimaan Bulan Ini</span>
                            <h5 class="fw-bold mb-0 text-success">Rp {{ number_format($totalBayarBulanIni, 2, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label small text-muted mb-1">Pencarian Nama / Kontak</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control"
                            placeholder="Cari nama atau kontak pelanggan..."
                        >
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small text-muted mb-1">Tipe Pelanggan</label>
                    <select wire:model.live="tipeFilter" class="form-select">
                        <option value="">Semua Tipe</option>
                        @foreach ($tipeOptions as $tipe)
                            <option value="{{ $tipe->value }}">{{ $tipe->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1">Filter Piutang</label>
                    <select wire:model.live="piutangFilter" class="form-select">
                        <option value="berpiutang">Hanya Pelanggan Berpiutang</option>
                        <option value="overlimit">Melebihi Limit Kredit</option>
                        <option value="semua">Semua Pelanggan</option>
                    </select>
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
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th role="button" wire:click="sortByColumn('nama')" class="user-select-none">
                                Nama Pelanggan
                                @if ($sortBy === 'nama')
                                    <i class="bi bi-sort-alpha-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th>Tipe & Kontak</th>
                            <th class="text-center">Total Transaksi</th>
                            <th role="button" wire:click="sortByColumn('total_penjualan_nominal')" class="text-end user-select-none">
                                Total Pembelian
                                @if ($sortBy === 'total_penjualan_nominal')
                                    <i class="bi bi-sort-numeric-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th class="text-end">Telah Dibayar</th>
                            <th role="button" wire:click="sortByColumn('total_sisa_piutang')" class="text-end user-select-none">
                                Sisa Piutang
                                @if ($sortBy === 'total_sisa_piutang')
                                    <i class="bi bi-sort-numeric-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th role="button" wire:click="sortByColumn('limit_piutang')" class="text-end user-select-none" style="min-width: 140px;">
                                Limit Kredit
                                @if ($sortBy === 'limit_piutang')
                                    <i class="bi bi-sort-numeric-{{ $sortDirection === 'asc' ? 'down' : 'up-alt' }} text-primary ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1 small"></i>
                                @endif
                            </th>
                            <th class="text-end pe-4" style="width: 180px;">Aksi</th>
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
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $pelanggans->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $pelanggan->nama }}</div>
                                    <small class="text-muted"><i class="bi bi-person-badge me-1"></i>ID: #{{ $pelanggan->id }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border me-1">{{ $pelanggan->tipe->label() ?? 'Umum' }}</span>
                                    <small class="text-muted d-block">{{ $pelanggan->kontak ?? '-' }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        {{ number_format($pelanggan->total_penjualan_count ?? 0) }} Faktur
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp {{ number_format((float) ($pelanggan->total_penjualan_nominal ?? 0), 2, ',', '.') }}
                                </td>
                                <td class="text-end text-muted">
                                    Rp {{ number_format((float) ($pelanggan->total_dibayar_nominal ?? 0), 2, ',', '.') }}
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
                                            <span class="badge bg-danger text-white d-block mt-1" style="font-size: 0.7rem;">
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
                                            title="Rincian Faktur"
                                        >
                                            <i class="bi bi-receipt me-1"></i>Faktur
                                        </button>
                                        @if ($sisaPiutang > 0)
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
                                <td colspan="9" class="text-center py-5 text-muted">
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

    <!-- Customer Unpaid Invoices Modal -->
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
                                <h5 class="modal-title fw-bold text-dark mb-0">Rincian Faktur Tertunggak</h5>
                                <div class="text-muted small mt-1">
                                    Daftar nota penjualan yang belum lunas untuk pelanggan ini.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($whatsAppUrl)
                                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success text-white px-3 shadow-sm" title="Kirim Pengingat Tagihan via WhatsApp">
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
                                    <span><i class="bi bi-person-badge text-secondary me-1"></i>ID Pelanggan: #{{ $selectedCustomer->id }}</span>
                                    <span><i class="bi bi-telephone text-secondary me-1"></i>{{ $selectedCustomer->kontak ?? 'Tidak ada kontak' }}</span>
                                    <span><i class="bi bi-wallet text-secondary me-1"></i>Plafon Kredit: {{ (float) $selectedCustomer->limit_piutang > 0 ? 'Rp ' . number_format((float) $selectedCustomer->limit_piutang, 0, ',', '.') : 'Tanpa Limit' }}</span>
                                </div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <div class="d-inline-flex flex-column align-items-md-end bg-white border border-danger-subtle rounded-3 p-2 px-3 shadow-sm">
                                    <span class="text-muted small" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Total Sisa Piutang</span>
                                    <span class="h5 fw-bold text-danger mb-0">Rp {{ number_format($selectedTotalSisa, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Body / Table -->
                    <div class="modal-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                <thead class="table-light text-muted small text-uppercase fw-semibold border-bottom">
                                    <tr>
                                        <th class="ps-4 py-3" style="width: 170px;">No. Faktur</th>
                                        <th class="py-3">Tanggal</th>
                                        <th class="text-center py-3">Umur Piutang</th>
                                        <th class="text-end py-3">Total Faktur</th>
                                        <th class="text-end py-3">Telah Dibayar</th>
                                        <th class="text-end py-3">Sisa Piutang</th>
                                        <th class="text-center py-3">Status</th>
                                        <th class="text-end pe-4 py-3" style="width: 140px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($selectedInvoices as $inv)
                                        @php
                                            $daysOverdue = (int) \Carbon\Carbon::parse($inv->tanggal)->diffInDays(now());
                                            if ($daysOverdue > 60) {
                                                $agingBadge = 'bg-danger text-white';
                                                $agingLabel = $daysOverdue . ' hari (> 60)';
                                            } elseif ($daysOverdue > 30) {
                                                $agingBadge = 'bg-warning-subtle text-warning-emphasis';
                                                $agingLabel = $daysOverdue . ' hari (30+)';
                                            } else {
                                                $agingBadge = 'bg-success-subtle text-success';
                                                $agingLabel = $daysOverdue . ' hari';
                                            }
                                        @endphp
                                        <tr>
                                            <td class="ps-4">
                                                <a href="{{ route('penjualan.show', $inv->id) }}" class="fw-bold text-primary text-decoration-none font-monospace">
                                                    {{ $inv->no_penjualan }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="text-dark">{{ $inv->tanggal->format('d/m/Y') }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $agingBadge }} border px-2 py-1">
                                                    <i class="bi bi-clock me-1"></i>{{ $agingLabel }}
                                                </span>
                                            </td>
                                            <td class="text-end fw-semibold">
                                                Rp {{ number_format((float) $inv->total_akhir, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end text-muted">
                                                Rp {{ number_format((float) $inv->total_dibayar, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end fw-bold text-danger">
                                                Rp {{ number_format((float) $inv->sisa_piutang, 2, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $inv->status_pembayaran->badgeClass() }}">
                                                    {{ $inv->status_pembayaran->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('penjualan.show', $inv->id) }}" class="btn btn-outline-secondary" title="Lihat Faktur">
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
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                                                Tidak ada faktur tertunggak untuk pelanggan ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if ($selectedInvoices->isNotEmpty())
                                    <tfoot class="table-light border-top fw-bold text-nowrap">
                                        <tr>
                                            <td colspan="3" class="ps-4 text-end text-muted small text-uppercase">Total Akumulasi:</td>
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
                            <button type="button" class="btn btn-outline-secondary" onclick="window.print()" title="Cetak Rincian Faktur">
                                <i class="bi bi-printer me-1"></i>Cetak Rincian
                            </button>
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
