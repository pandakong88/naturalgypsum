<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Catat Pembayaran Pelanggan</h4>
            <p class="text-muted small mb-0">Input penerimaan pembayaran dan alokasikan manual ke faktur piutang pelanggan.</p>
        </div>
        <div>
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
                                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                    <span class="small text-muted">Total Piutang Berjalan:</span>
                                    <span class="fw-bold text-danger">Rp {{ number_format((float) ($selectedPelanggan->total_sisa_piutang ?? 0), 2, ',', '.') }}</span>
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
                                <small class="text-muted mt-1 d-block" style="font-size: 0.78rem;">
                                    Ketik nama pelanggan untuk mencari, lalu klik salah satu untuk memuat faktur.
                                </small>
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
                        <label class="form-label fw-semibold">Jumlah Bayar Diterima (Rp) <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            wire:model.live.debounce.300ms="jumlah_bayar"
                            class="form-control form-control-lg fw-bold text-success @error('jumlah_bayar') is-invalid @enderror"
                            placeholder="0.00"
                        >
                        @error('jumlah_bayar')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if ((float) ($jumlah_bayar ?: 0) > 0)
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle me-1"></i>Format: <strong>Rp {{ number_format((float) $jumlah_bayar, 2, ',', '.') }}</strong>
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
                <h6 class="card-title fw-bold mb-0 text-primary">
                    <i class="bi bi-receipt-cutoff me-1"></i> Alokasi Pembayaran ke Faktur Penjualan
                </h6>
                @if (!empty($allocations))
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" wire:click="autoAllocateFifo" class="btn btn-outline-primary btn-sm" title="Alokasikan jumlah bayar ke faktur terlama lebih dulu">
                            <i class="bi bi-magic me-1"></i> Alokasi Otomatis (FIFO)
                        </button>
                        <button type="button" wire:click="payAllInvoices" class="btn btn-outline-success btn-sm" title="Isi pembayaran dan lunasi semua faktur sekaligus">
                            <i class="bi bi-check-all me-1"></i> Lunasi Semua
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
                            <button type="button" wire:click="autoAllocateFifo" class="btn btn-warning btn-sm fw-bold">
                                <i class="bi bi-magic me-1"></i> Habiskan ke Faktur (FIFO)
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>No. Faktur</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Umur Piutang</th>
                                    <th class="text-end">Total Faktur</th>
                                    <th class="text-end">Telah Dibayar</th>
                                    <th class="text-end">Sisa Piutang</th>
                                    <th style="min-width: 220px;" class="text-end">Alokasi Bayar (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allocations as $index => $alloc)
                                    @php
                                        $daysOverdue = (int) ($alloc['days_overdue'] ?? 0);
                                        if ($daysOverdue > 60) {
                                            $agingBadge = 'bg-danger text-white';
                                            $agingLabel = $daysOverdue . ' hr (>60)';
                                        } elseif ($daysOverdue > 30) {
                                            $agingBadge = 'bg-warning-subtle text-warning-emphasis';
                                            $agingLabel = $daysOverdue . ' hr (30+)';
                                        } else {
                                            $agingBadge = 'bg-success-subtle text-success';
                                            $agingLabel = $daysOverdue . ' hr';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="fw-bold font-monospace text-primary">{{ $alloc['no_penjualan'] }}</span>
                                        </td>
                                        <td>{{ $alloc['tanggal'] }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $agingBadge }} border px-2 py-1">
                                                <i class="bi bi-clock me-1"></i>{{ $agingLabel }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            Rp {{ number_format($alloc['total_akhir'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-end text-muted">
                                            Rp {{ number_format($alloc['total_dibayar'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            Rp {{ number_format($alloc['sisa_piutang'], 2, ',', '.') }}
                                        </td>
                                        <td>
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
                                                    class="btn btn-outline-primary btn-sm"
                                                    title="Alokasikan maksimal"
                                                >
                                                    Penuh
                                                </button>
                                            </div>
                                            @error("allocations.{$index}.jumlah_dialokasikan")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="7" class="text-end fw-bold">Total Pembayaran Diterima:</td>
                                    <td class="text-end fw-bold text-success fs-6">
                                        Rp {{ number_format((float) ($jumlah_bayar ?: 0), 2, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end fw-bold">Total Dialokasikan ke Faktur:</td>
                                    <td class="text-end fw-bold text-primary fs-6">
                                        Rp {{ number_format($this->totalDialokasikan, 2, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="{{ $this->sisaBelumDialokasikan > 0 ? ($this->hasUnallocatedWithRemainingDebt ? 'table-warning' : 'table-light') : 'table-light' }}">
                                    <td colspan="7" class="text-end fw-bold">
                                        Sisa Belum Dialokasikan:
                                        @if ($this->hasUnallocatedWithRemainingDebt)
                                            <span class="badge bg-danger ms-1">Wajib Dialokasikan ke Faktur</span>
                                        @elseif ($this->sisaBelumDialokasikan > 0)
                                            <span class="badge bg-info text-dark ms-1">Kelebihan Bayar / Titipan</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold {{ $this->hasUnallocatedWithRemainingDebt ? 'text-danger' : ($this->sisaBelumDialokasikan > 0 ? 'text-info-emphasis' : 'text-muted') }} fs-6">
                                        Rp {{ number_format($this->sisaBelumDialokasikan, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
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
