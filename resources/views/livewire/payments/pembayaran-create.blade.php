<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Catat Pembayaran Pelanggan</h4>
            <p class="text-muted small mb-0">Input penerimaan pembayaran piutang dan alokasikan ke faktur pelanggan (otomatis FIFO atau pilih per faktur).</p>
        </div>
        <div class="d-flex gap-2">
            @can('view_receivables')
                <a href="{{ route('piutang.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-journal-bookmark me-1"></i>Daftar Piutang
                </a>
            @endcan
            <a href="{{ route('pembayaran.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Error Summary if any -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Terdapat kesalahan pengisian:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form wire:submit="save">
        <!-- Payment Header Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold mb-0 text-primary">
                    <i class="bi bi-wallet2 me-1"></i> Informasi Pembayaran & Pelanggan
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Customer Selection: Autocomplete or Selected Card -->
                    <div class="col-lg-6">
                        @if ($selectedPelanggan)
                            <!-- Kartu Pelanggan Terpilih -->
                            <div class="card border border-primary-subtle bg-light shadow-sm p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                            <h6 class="fw-bold mb-0 text-dark fs-5">{{ $selectedPelanggan->nama }}</h6>
                                            <span class="badge bg-white text-dark border">
                                                {{ $selectedPelanggan->tipe->label() ?? 'Umum' }}
                                            </span>
                                        </div>
                                        <div class="text-muted small d-flex flex-wrap gap-3">
                                            <span><i class="bi bi-person-badge text-secondary me-1"></i>ID: #{{ $selectedPelanggan->id }}</span>
                                            <span><i class="bi bi-telephone text-secondary me-1"></i>{{ $selectedPelanggan->kontak ?? 'Tanpa nomor' }}</span>
                                            <span><i class="bi bi-shield-lock text-secondary me-1"></i>Plafon: {{ (float) $selectedPelanggan->limit_piutang > 0 ? 'Rp ' . number_format((float) $selectedPelanggan->limit_piutang, 0, ',', '.') : 'Tanpa Limit' }}</span>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="clearPelanggan" class="btn btn-sm btn-outline-secondary" title="Ganti Pelanggan">
                                        <i class="bi bi-arrow-repeat me-1"></i>Ganti
                                    </button>
                                </div>
                                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="small text-muted d-block">Total Piutang Berjalan ({{ $selectedPelanggan->total_faktur_piutang ?? count($allocations) }} faktur):</span>
                                        <span class="fw-bold text-danger fs-6">Rp {{ number_format((float) ($selectedPelanggan->total_sisa_piutang ?? 0), 2, ',', '.') }}</span>
                                    </div>
                                    @if (count($allocations) > 0)
                                        <button type="button" wire:click="payAllInvoices" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-all me-1"></i>Lunasi Seluruh Piutang
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" wire:model="pelanggan_id" value="{{ $selectedPelanggan->id }}">
                        @else
                            <!-- Input Pencarian Autocomplete -->
                            <div class="position-relative">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold mb-0">Pilih Pelanggan <span class="text-danger">*</span></label>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="onlyDebtCheck" wire:model.live="onlyWithDebt">
                                        <label class="form-check-label small text-muted" for="onlyDebtCheck">Hanya yang berpiutang</label>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input
                                        type="text"
                                        wire:model.live.debounce.250ms="pelangganSearchQuery"
                                        class="form-control @error('pelanggan_id') is-invalid @enderror"
                                        placeholder="Ketik nama pelanggan atau no. telp..."
                                    >
                                    @if (filled($pelangganSearchQuery))
                                        <button type="button" wire:click="$set('pelangganSearchQuery', '')" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                </div>
                                @error('pelanggan_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <!-- Floating Autocomplete Dropdown List -->
                                @if (filled($pelangganSearchQuery) && count($searchResults) > 0)
                                    <div class="list-group position-absolute w-100 shadow-lg mt-1 z-3 border border-primary overflow-hidden" style="max-height: 260px; overflow-y: auto;">
                                        @foreach ($searchResults as $p)
                                            <button
                                                type="button"
                                                wire:click="selectPelanggan({{ $p->id }})"
                                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small"
                                            >
                                                <div>
                                                    <strong class="text-dark fs-6">{{ $p->nama }}</strong>
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        <i class="bi bi-telephone me-1"></i>{{ $p->kontak ?? 'Tanpa nomor' }}
                                                        <span class="badge bg-light text-dark border ms-1">{{ $p->tipe->label() ?? 'Umum' }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    @if ((float) ($p->total_sisa_piutang ?? 0) > 0)
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-block">
                                                            Piutang: Rp {{ number_format((float) $p->total_sisa_piutang, 0, ',', '.') }}
                                                        </span>
                                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $p->total_faktur_piutang ?? 0 }} faktur</small>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                            Lunas
                                                        </span>
                                                    @endif
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif (filled($pelangganSearchQuery))
                                    <div class="list-group position-absolute w-100 shadow mt-1 z-3">
                                        <div class="list-group-item py-3 text-center text-muted small">
                                            <i class="bi bi-emoji-neutral me-1"></i>Pelanggan '<strong>{{ $pelangganSearchQuery }}</strong>' tidak ditemukan.
                                        </div>
                                    </div>
                                @endif

                                <!-- Quick Select Customers with Active Debt -->
                                @if (!filled($pelangganSearchQuery) && count($quickDebtCustomers) > 0)
                                    <div class="mt-2 border rounded bg-light p-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                                            <span class="small fw-semibold text-secondary" style="font-size: 0.75rem;">
                                                <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Pilih Cepat Pelanggan Berpiutang:
                                            </span>
                                            <span class="small text-muted" style="font-size: 0.72rem;">Klik untuk memuat faktur</span>
                                        </div>
                                        <div class="list-group list-group-flush rounded border overflow-hidden" style="max-height: 175px; overflow-y: auto;">
                                            @foreach ($quickDebtCustomers as $qp)
                                                <button
                                                    type="button"
                                                    wire:click="selectPelanggan({{ $qp->id }})"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-2 small"
                                                >
                                                    <div class="text-truncate me-2">
                                                        <span class="fw-semibold text-dark">{{ $qp->nama }}</span>
                                                        <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.68rem;">
                                                            {{ $qp->tipe->label() ?? 'Umum' }}
                                                        </span>
                                                    </div>
                                                    <div class="text-end text-nowrap">
                                                        <span class="fw-bold text-danger">
                                                            Rp {{ number_format((float) $qp->total_sisa_piutang, 0, ',', '.') }}
                                                        </span>
                                                        <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.68rem;">
                                                            {{ $qp->total_faktur_piutang }} nota
                                                        </span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <small class="text-muted mt-1 d-block" style="font-size: 0.78rem;">
                                        Ketik nama pelanggan untuk mencari, lalu klik salah satu untuk memuat faktur.
                                    </small>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Payment Meta: Tanggal & Metode -->
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" wire:model="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
                                @error('tanggal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select wire:model.live="metode" class="form-select @error('metode') is-invalid @enderror">
                                    @foreach ($metodeOptions as $m)
                                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                    @endforeach
                                </select>
                                @error('metode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($metode === 'transfer')
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small text-muted">Bank Tujuan</label>
                                    <select wire:model="bank_tujuan" class="form-select form-select-sm">
                                        <option value="">-- Pilih Bank --</option>
                                        @foreach ($bankOptions as $bank)
                                            <option value="{{ $bank }}">{{ $bank }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small text-muted">No. Referensi / Bukti Transfer</label>
                                    <input type="text" wire:model="nomor_referensi" class="form-control form-control-sm" placeholder="Contoh: TRF-BCA-123456">
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Jumlah Bayar & Catatan -->
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Jumlah Bayar Diterima (Rp) <span class="text-danger">*</span></label>
                            @if ($this->totalDialokasikan > 0 && abs((float) ($jumlah_bayar ?: 0) - $this->totalDialokasikan) > 0.01)
                                <button
                                    type="button"
                                    wire:click="syncJumlahBayarWithAllocations"
                                    class="btn btn-link btn-sm p-0 text-primary text-decoration-none fw-semibold"
                                    style="font-size: 0.76rem;"
                                >
                                    <i class="bi bi-arrows-collapse me-1"></i>Samakan dgn Alokasi (Rp {{ number_format($this->totalDialokasikan, 0, ',', '.') }})
                                </button>
                            @endif
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-success-subtle text-success fw-bold">Rp</span>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                wire:model.live.debounce.300ms="jumlah_bayar"
                                class="form-control form-control-lg fw-bold text-success @error('jumlah_bayar') is-invalid @enderror"
                                placeholder="0.00"
                            >
                            @if (!empty($allocations) && (float) ($jumlah_bayar ?: 0) > 0)
                                <button
                                    type="button"
                                    wire:click="autoAllocateFifo"
                                    class="btn btn-outline-primary"
                                    title="Bagikan nominal ini secara otomatis mulai dari faktur terlama (FIFO)"
                                >
                                    <i class="bi bi-magic me-1"></i>Bagi FIFO
                                </button>
                            @endif
                        </div>
                        @error('jumlah_bayar')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @if ((float) ($jumlah_bayar ?: 0) > 0)
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle me-1"></i>Nominal: <strong>Rp {{ number_format((float) $jumlah_bayar, 2, ',', '.') }}</strong>
                            </small>
                        @else
                            <small class="text-muted d-block mt-1" style="font-size: 0.76rem;">
                                Tips: Klik tombol <strong>Penuh</strong> pada faktur di bawah untuk menghitung otomatis, atau ketik nominal cicilan lalu klik <strong>Bagi FIFO</strong>.
                            </small>
                        @endif
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Catatan / Keterangan</label>
                        <input type="text" wire:model="catatan" class="form-control @error('catatan') is-invalid @enderror" placeholder="Catatan tambahan (opsional)...">
                        @error('catatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Allocation Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h6 class="card-title fw-bold mb-0 text-primary">
                        <i class="bi bi-receipt-cutoff me-1"></i> Alokasi Pembayaran ke Faktur Penjualan
                    </h6>
                </div>
                @if (!empty($allocations))
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" wire:click="autoAllocateFifo" class="btn btn-outline-primary btn-sm" title="Alokasikan jumlah bayar ke faktur terlama lebih dulu">
                            <i class="bi bi-magic me-1"></i> Alokasi Otomatis (FIFO)
                        </button>
                        <button type="button" wire:click="payAllInvoices" class="btn btn-outline-success btn-sm" title="Isi pembayaran dan lunasi semua faktur sekaligus">
                            <i class="bi bi-check-all me-1"></i> Lunasi Semua ({{ count($allocations) }} Faktur)
                        </button>
                        <button type="button" wire:click="resetAllocations" class="btn btn-outline-secondary btn-sm" title="Kosongkan semua alokasi">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                    </div>
                @endif
            </div>
            <div class="card-body p-0">
                @if (empty($pelanggan_id))
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-person-exclamation fs-1 d-block mb-2 text-secondary"></i>
                        Silakan pilih pelanggan terlebih dahulu untuk memuat daftar faktur yang belum lunas.
                    </div>
                @elseif (empty($allocations))
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                        Pelanggan ini tidak memiliki faktur dengan sisa piutang. Seluruh faktur sudah lunas.
                        <p class="small mt-2 mb-0">Pembayaran tetap dapat dicatat tanpa alokasi sebagai saldo titipan/deposit.</p>
                    </div>
                @else
                    @if ($this->hasUnallocatedWithRemainingDebt)
                        <div class="alert alert-warning border-0 border-start border-4 border-warning rounded-0 m-0 p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-2"></i>
                                <div>
                                    <span class="fw-bold d-block text-dark">Sisa Pembayaran Belum Dialokasikan</span>
                                    <small class="text-muted">Masih ada dana <strong>Rp {{ number_format($this->sisaBelumDialokasikan, 2, ',', '.') }}</strong> yang belum dialokasikan ke faktur tertunggak di bawah ini.</small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                @if ($this->totalDialokasikan > 0)
                                    <button type="button" wire:click="syncJumlahBayarWithAllocations" class="btn btn-outline-dark btn-sm fw-semibold">
                                        Sesuaikan Jumlah Bayar (Rp {{ number_format($this->totalDialokasikan, 0, ',', '.') }})
                                    </button>
                                @endif
                                <button type="button" wire:click="autoAllocateFifo" class="btn btn-warning btn-sm fw-bold">
                                    <i class="bi bi-magic me-1"></i> Habiskan ke Faktur (FIFO)
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 table-mobile-cards">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>No. Faktur & Rincian Barang</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Umur Piutang</th>
                                    <th class="text-end">Total Faktur</th>
                                    <th class="text-end">Sisa Piutang</th>
                                    <th class="text-center">Status & Sisa Akhir</th>
                                    <th style="min-width: 245px;" class="text-end">Alokasi Bayar (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allocations as $index => $alloc)
                                    @php
                                        $daysOverdue = (int) ($alloc['days_overdue'] ?? 0);
                                        if ($daysOverdue > 30) {
                                            $agingBadge = 'bg-danger text-white';
                                            $agingLabel = $daysOverdue . ' hr (>30)';
                                        } elseif ($daysOverdue > 14) {
                                            $agingBadge = 'bg-warning-subtle text-warning-emphasis';
                                            $agingLabel = $daysOverdue . ' hr (>14)';
                                        } else {
                                            $agingBadge = 'bg-success-subtle text-success';
                                            $agingLabel = $daysOverdue . ' hr';
                                        }

                                        $sisaAwal = (float) $alloc['sisa_piutang'];
                                        $dialokasikan = (float) ($alloc['jumlah_dialokasikan'] ?? 0);
                                        $sisaAkhir = max(0.00, round($sisaAwal - $dialokasikan, 2));
                                        $isRowFull = $dialokasikan >= $sisaAwal && $sisaAwal > 0;
                                        $isRowPartial = $dialokasikan > 0 && $dialokasikan < $sisaAwal;
                                    @endphp
                                    <tr class="{{ $isRowFull ? 'table-success bg-opacity-10' : ($isRowPartial ? 'table-warning bg-opacity-10' : '') }}" wire:key="alloc-row-{{ $index }}-{{ $alloc['penjualan_id'] }}">
                                        <td class="text-center text-muted fw-semibold d-none d-md-table-cell">{{ $index + 1 }}</td>
                                        <td data-label="Faktur #{{ $index + 1 }}">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <a
                                                    href="{{ route('penjualan.show', $alloc['penjualan_id']) }}"
                                                    target="_blank"
                                                    class="fw-bold font-monospace text-primary text-decoration-none"
                                                    title="Buka detail faktur di tab baru"
                                                >
                                                    {{ $alloc['no_penjualan'] }}
                                                    <i class="bi bi-box-arrow-up-right small ms-1" style="font-size: 0.7rem;"></i>
                                                </a>
                                                @if ((float) $alloc['total_dibayar'] > 0)
                                                    <span class="badge bg-info-subtle text-info-emphasis border" style="font-size: 0.68rem;">
                                                        Sudah terbayar Rp {{ number_format($alloc['total_dibayar'], 0, ',', '.') }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!empty($alloc['items_summary']))
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                    <i class="bi bi-box-seam me-1"></i>{{ $alloc['items_summary'] }}
                                                </small>
                                            @endif
                                            @if (!empty($alloc['info_proyek']))
                                                <small class="text-secondary d-block" style="font-size: 0.73rem;">
                                                    <i class="bi bi-geo-alt me-1"></i>{{ $alloc['info_proyek'] }}
                                                </small>
                                            @endif
                                        </td>
                                        <td data-label="Tanggal Nota">{{ $alloc['tanggal'] }}</td>
                                        <td class="text-center" data-label="Umur Piutang">
                                            <span class="badge {{ $agingBadge }} border px-2 py-1">
                                                <i class="bi bi-clock me-1"></i>{{ $agingLabel }}
                                            </span>
                                        </td>
                                        <td class="text-end text-muted font-monospace" data-label="Total Faktur">
                                            Rp {{ number_format($alloc['total_akhir'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-end fw-bold text-danger font-monospace" data-label="Sisa Piutang">
                                            Rp {{ number_format($alloc['sisa_piutang'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-center" data-label="Status Akhir">
                                            @if ($isRowFull)
                                                <span class="badge bg-success text-white">
                                                    <i class="bi bi-check-circle-fill me-1"></i>AKAN LUNAS
                                                </span>
                                                <small class="text-success d-block fw-semibold" style="font-size: 0.72rem;">Sisa Akhir: Rp 0</small>
                                            @elseif ($isRowPartial)
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-pie-chart-fill me-1"></i>Cicilan Sebagian
                                                </span>
                                                <small class="text-danger d-block fw-semibold" style="font-size: 0.72rem;">
                                                    Sisa Akhir: Rp {{ number_format($sisaAkhir, 0, ',', '.') }}
                                                </small>
                                            @else
                                                <span class="badge bg-light text-muted border">Belum Dialokasi</span>
                                            @endif
                                        </td>
                                        <td data-label="Alokasi Bayar (Rp)">
                                            <div class="input-group input-group-sm">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $alloc['sisa_piutang'] }}"
                                                    wire:model.live.debounce.300ms="allocations.{{ $index }}.jumlah_dialokasikan"
                                                    class="form-control text-end fw-bold @error("allocations.{$index}.jumlah_dialokasikan") is-invalid @enderror"
                                                    placeholder="0.00"
                                                >
                                                <button
                                                    type="button"
                                                    wire:click="allocateMax({{ $index }})"
                                                    class="btn {{ $isRowFull ? 'btn-success' : 'btn-outline-primary' }} btn-sm"
                                                    title="Lunasi penuh faktur ini"
                                                >
                                                    Penuh
                                                </button>
                                                @if ($dialokasikan > 0)
                                                    <button
                                                        type="button"
                                                        wire:click="clearAllocation({{ $index }})"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        title="Kosongkan alokasi baris ini"
                                                    >
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                @endif
                                            </div>
                                            @error("allocations.{$index}.jumlah_dialokasikan")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Responsive Allocation Summary Footer -->
                    <div class="p-3 bg-light border-top">
                        <div class="row g-2 justify-content-end">
                            <div class="col-12 col-md-6 col-lg-5">
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <span class="small fw-semibold text-secondary">Total Pembayaran Diterima:</span>
                                    <span class="fw-bold text-success font-monospace">Rp {{ number_format((float) ($jumlah_bayar ?: 0), 2, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <span class="small fw-semibold text-secondary">Total Dialokasikan ke Faktur:</span>
                                    <span class="fw-bold text-primary font-monospace">Rp {{ number_format($this->totalDialokasikan, 2, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-1.5 flex-wrap gap-1">
                                    <div>
                                        <span class="small fw-bold text-dark">Sisa Belum Dialokasikan:</span>
                                        @if ($this->hasUnallocatedWithRemainingDebt)
                                            <span class="badge bg-danger ms-1" style="font-size: 0.68rem;">Wajib Dialokasikan</span>
                                        @elseif ($this->sisaBelumDialokasikan > 0)
                                            <span class="badge bg-info text-dark ms-1" style="font-size: 0.68rem;">Titipan / Deposit</span>
                                        @endif
                                    </div>
                                    <span class="fw-bold font-monospace {{ $this->hasUnallocatedWithRemainingDebt ? 'text-danger' : ($this->sisaBelumDialokasikan > 0 ? 'text-info-emphasis' : 'text-muted') }}">
                                        Rp {{ number_format($this->sisaBelumDialokasikan, 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-white py-3 d-flex justify-content-end gap-2">
                <a href="{{ route('pembayaran.index') }}" class="btn btn-light border">Batal</a>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check2-circle me-1"></i>Simpan Pembayaran
                    </span>
                    <span wire:loading wire:target="save">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
