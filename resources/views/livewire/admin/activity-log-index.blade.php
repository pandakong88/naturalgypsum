<div>
    <!-- Page Header & Action Controls -->
    <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Audit Trail & Activity Log</h4>
            <p class="text-muted mb-0 small">Catatan rekam jejak aktivitas operasional, transaksi material, dan administrasi sistem.</p>
        </div>
        <div class="d-flex align-items-center gap-2 w-100 w-sm-auto">
            <button wire:click="exportCsv" class="btn btn-outline-success btn-sm shadow-sm flex-fill flex-sm-grow-0" wire:loading.attr="disabled" title="Unduh data audit trail terfilter ke file CSV (Excel)">
                <span wire:loading.remove wire:target="exportCsv">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Ekspor CSV
                </span>
                <span wire:loading wire:target="exportCsv">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Mengekspor...
                </span>
            </button>
            <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-2">
                <i class="bi bi-shield-lock me-1"></i> Immutable Audit
            </span>
        </div>
    </div>

    <!-- Summary KPI Cards Hari Ini (2x2 Grid on Mobile) -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Aktivitas Hari Ini</span>
                            <h3 class="fw-bold mb-0 text-primary fs-4 fs-sm-3">{{ number_format($kpis['totalHariIni'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary p-3 d-none d-sm-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-activity fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Modifikasi (Update)</span>
                            <h3 class="fw-bold mb-0 text-info fs-4 fs-sm-3">{{ number_format($kpis['totalUpdate'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="rounded-circle bg-info-subtle text-info p-3 d-none d-sm-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Hapus & Batal</span>
                            <h3 class="fw-bold mb-0 text-danger fs-4 fs-sm-3">{{ number_format($kpis['totalDeleteCancel'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="rounded-circle bg-danger-subtle text-danger p-3 d-none d-sm-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2.5 p-sm-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="min-w-0">
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem;">Aktor Teraktif</span>
                            <h6 class="fw-bold mb-0 text-dark text-truncate" title="{{ $kpis['topActor'] }}">
                                {{ $kpis['topActor'] }}
                            </h6>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success p-3 d-none d-sm-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-person-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm mb-3 mb-md-4">
        <div class="card-body p-2.5 p-md-3">
            <div class="row g-2">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-start-0" placeholder="Cari deskripsi, target, aktor...">
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-2">
                    <select wire:model.live="filterModule" class="form-select form-select-sm">
                        <option value="">-- Semua Modul --</option>
                        @foreach($modules as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <select wire:model.live="filterUser" class="form-select form-select-sm">
                        <option value="">-- Semua Pengguna --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <select wire:model.live="filterAction" class="form-select form-select-sm">
                        <option value="">-- Semua Aksi --</option>
                        @foreach($actions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-9 col-sm-6 col-md-4 col-lg-2">
                    <div class="input-group input-group-sm">
                        <input type="date" wire:model.live="startDate" class="form-control" title="Dari Tanggal">
                        <span class="input-group-text bg-light px-1">-</span>
                        <input type="date" wire:model.live="endDate" class="form-control" title="Sampai Tanggal">
                    </div>
                </div>
                <div class="col-3 col-md-12 col-lg-1 text-end">
                    <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm w-100" title="Reset Seluruh Filter">
                        <i class="bi bi-arrow-counterclockwise"></i><span class="d-none d-sm-inline ms-1">Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Card List & Desktop Table Logs -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <!-- Mobile Card List View (< 768px) -->
            <div class="d-md-none">
                @forelse($logs as $log)
                    @php
                        $badgeColor = match($log->action) {
                            'create' => 'success',
                            'update' => 'primary',
                            'delete' => 'danger',
                            'restore' => 'info',
                            'cancel' => 'warning text-dark',
                            'complete' => 'success',
                            'login' => 'info',
                            'logout' => 'secondary',
                            default => 'secondary'
                        };
                    @endphp
                    <div class="p-3 border-bottom" wire:key="log-mob-{{ $log->id }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1.5">
                            <div>
                                <button
                                    type="button"
                                    wire:click="filterByUserClick({{ $log->user_id ?? 0 }})"
                                    class="btn btn-link p-0 fw-bold text-dark text-decoration-none text-start"
                                >
                                    <i class="bi bi-person-circle me-1 text-primary"></i>{{ $log->user_name ?? ($log->user?->name ?? 'System') }}
                                </button>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }} ({{ $log->created_at->diffForHumans() }})
                                </div>
                            </div>
                            <button
                                type="button"
                                wire:click="filterByActionClick('{{ $log->action }}')"
                                class="badge bg-{{ $badgeColor }} border-0 text-uppercase px-2 py-1 flex-shrink-0"
                                style="font-size: 0.68rem;"
                            >
                                {{ $log->action }}
                            </button>
                        </div>

                        @if($log->subject_label)
                            <div class="d-flex align-items-center flex-wrap gap-1 mb-1.5">
                                <span class="badge bg-light text-dark border fw-semibold">
                                    {{ $log->subject_label }}
                                </span>
                                @if($log->subject_type)
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">
                                        <i class="bi bi-tag me-0.5"></i>{{ class_basename($log->subject_type) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="small text-dark mb-2" style="line-height: 1.35;">
                            {{ $log->description }}
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-1.5 border-top border-light-subtle">
                            <small class="text-muted font-monospace" style="font-size: 0.7rem;">
                                @if($log->ip_address)
                                    IP: {{ $log->ip_address }}
                                @endif
                            </small>
                            <button wire:click="showDetail({{ $log->id }})" class="btn btn-outline-primary btn-sm py-1 px-2.5">
                                <i class="bi bi-eye me-1"></i>Rincian
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 px-3 text-muted">
                        <i class="bi bi-journal-x display-6 d-block mb-2 text-secondary"></i>
                        Tidak ada catatan log aktivitas yang ditemukan sesuai filter.
                    </div>
                @endforelse
            </div>

            <!-- Desktop Table View (>= 768px) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3" style="width: 16%;">Waktu</th>
                            <th class="py-3" style="width: 17%;">Pengguna (Actor)</th>
                            <th class="py-3 text-center" style="width: 10%;">Aksi</th>
                            <th class="py-3" style="width: 18%;">Target / Dokumen</th>
                            <th class="py-3" style="width: 29%;">Deskripsi Aktivitas</th>
                            <th class="pe-3 py-3 text-end" style="width: 10%;">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-dark">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                    <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <div>
                                        <button
                                            type="button"
                                            wire:click="filterByUserClick({{ $log->user_id ?? 0 }})"
                                            class="btn btn-link p-0 fw-bold text-dark text-decoration-none text-start d-block"
                                            title="Filter log untuk pengguna {{ $log->user_name ?? ($log->user?->name ?? 'System') }}"
                                        >
                                            <i class="bi bi-person me-1 text-primary"></i>{{ $log->user_name ?? ($log->user?->name ?? 'System') }}
                                        </button>
                                        @if($log->ip_address)
                                            <small class="text-muted"><code>IP: {{ $log->ip_address }}</code></small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @php
                                        $badgeColor = match($log->action) {
                                            'create' => 'success',
                                            'update' => 'primary',
                                            'delete' => 'danger',
                                            'restore' => 'info',
                                            'cancel' => 'warning text-dark',
                                            'complete' => 'success',
                                            'login' => 'info',
                                            'logout' => 'secondary',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="filterByActionClick('{{ $log->action }}')"
                                        class="badge bg-{{ $badgeColor }} border-0 text-uppercase px-2 py-1 fs-7 cursor-pointer"
                                        title="Filter aksi {{ $log->action }}"
                                    >
                                        {{ $log->action }}
                                    </button>
                                </td>
                                <td>
                                    @if($log->subject_label)
                                        <span class="badge bg-light text-dark border fw-semibold d-inline-block text-truncate" style="max-width: 180px;">
                                            {{ $log->subject_label }}
                                        </span>
                                        @if($log->subject_type)
                                            <div class="small text-muted mt-1 fs-7">
                                                <i class="bi bi-tag me-1"></i>{{ class_basename($log->subject_type) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $log->description }}</div>
                                </td>
                                <td class="pe-3 text-end">
                                    <button wire:click="showDetail({{ $log->id }})" class="btn btn-outline-primary btn-sm py-1 px-2 shadow-sm">
                                        <i class="bi bi-eye me-1"></i>Rincian
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x display-6 d-block mb-2 text-secondary"></i>
                                    Tidak ada catatan log aktivitas yang ditemukan sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Detail Modal with Visual Diff Comparison -->
    @if($selectedLog)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-2.5 px-3">
                        <div>
                            <h6 class="modal-title fw-bold text-dark mb-0">
                                <i class="bi bi-journal-text me-1.5 text-primary"></i>
                                Rincian Log Aktivitas #{{ $selectedLog->id }}
                            </h6>
                            <small class="text-muted">{{ $selectedLog->created_at->format('d F Y, H:i:s') }}</small>
                        </div>
                        <button type="button" wire:click="closeDetail" class="btn-close"></button>
                    </div>
                    <div class="modal-body p-3 p-md-4">
                        <!-- Metadata summary grid -->
                        <div class="row g-2 g-md-3 mb-3 mb-md-4 bg-light p-2.5 p-md-3 rounded border mx-0">
                            <div class="col-6 col-md-3">
                                <div class="text-muted small" style="font-size: 0.74rem;">Waktu Eksekusi</div>
                                <div class="fw-bold small">{{ $selectedLog->created_at->format('d/m/Y H:i:s') }}</div>
                                <small class="text-secondary" style="font-size: 0.72rem;">{{ $selectedLog->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small" style="font-size: 0.74rem;">Pengguna (Actor)</div>
                                <div class="fw-bold text-primary small">{{ $selectedLog->user_name ?? ($selectedLog->user?->name ?? 'System') }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">ID: {{ $selectedLog->user_id ?? 'System' }}</small>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small" style="font-size: 0.74rem;">Jenis Aksi</div>
                                <div class="fw-bold text-uppercase mt-0.5">
                                    <span class="badge bg-{{ match($selectedLog->action) {
                                        'create' => 'success',
                                        'update' => 'primary',
                                        'delete' => 'danger',
                                        'restore' => 'info',
                                        'cancel' => 'warning text-dark',
                                        'complete' => 'success',
                                        default => 'secondary'
                                    } }} px-2 py-1">
                                        {{ $selectedLog->action }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small" style="font-size: 0.74rem;">Subjek / Modul</div>
                                <div class="fw-semibold text-dark small text-break">{{ $selectedLog->subject_label ?? '-' }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ class_basename($selectedLog->subject_type ?? 'N/A') }}</small>
                            </div>
                            <div class="col-12 border-top pt-2 mt-2">
                                <div class="text-muted small" style="font-size: 0.74rem;">Deskripsi Lengkap:</div>
                                <div class="fw-semibold text-dark small">{{ $selectedLog->description }}</div>
                            </div>
                            @if($selectedLog->ip_address || $selectedLog->user_agent)
                                <div class="col-12 border-top pt-2 mt-2">
                                    <div class="small text-muted text-break" style="font-size: 0.74rem;">
                                        <strong>Koneksi:</strong> IP <code>{{ $selectedLog->ip_address ?? '-' }}</code> &bull; User Agent: <span class="text-secondary">{{ $selectedLog->user_agent ?? '-' }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Before & After Comparison -->
                        @if($selectedLog->old_values || $selectedLog->new_values)
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-arrow-left-right me-1 text-primary"></i>Perubahan Data (Before & After)
                                </h6>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button
                                        type="button"
                                        wire:click="setDetailTab('table')"
                                        class="btn {{ $activeDetailTab === 'table' ? 'btn-primary' : 'btn-outline-primary' }}"
                                    >
                                        <i class="bi bi-table me-1"></i>Visual Diff
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setDetailTab('json')"
                                        class="btn {{ $activeDetailTab === 'json' ? 'btn-primary' : 'btn-outline-primary' }}"
                                    >
                                        <i class="bi bi-code-slash me-1"></i>Raw JSON
                                    </button>
                                </div>
                            </div>

                            @if($activeDetailTab === 'table')
                                @php
                                    $old = $selectedLog->old_values ?? [];
                                    $new = $selectedLog->new_values ?? [];
                                    $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
                                @endphp

                                <!-- Mobile Visual Diff Cards (< 768px) -->
                                <div class="d-md-none d-flex flex-column gap-2 mb-3">
                                    @foreach($allKeys as $key)
                                        @php
                                            $valOld = $old[$key] ?? null;
                                            $valNew = $new[$key] ?? null;
                                            $isChanged = ($valOld !== $valNew);
                                            $isAdded = (!array_key_exists($key, $old) && array_key_exists($key, $new));
                                            $isRemoved = (array_key_exists($key, $old) && !array_key_exists($key, $new));
                                            $renderVal = fn($val) => is_array($val) ? json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (is_bool($val) ? ($val ? 'true' : 'false') : (is_null($val) ? 'NULL' : (string)$val));
                                        @endphp
                                        <div class="border rounded-3 p-2.5 {{ $isChanged ? 'border-warning bg-warning-subtle bg-opacity-10' : 'bg-white' }}">
                                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1.5 pb-1 border-bottom">
                                                <div class="fw-bold text-dark font-monospace small">
                                                    {{ ucwords(str_replace('_', ' ', $key)) }}
                                                    <span class="text-muted fw-normal" style="font-size: 0.7rem;">({{ $key }})</span>
                                                </div>
                                                @if($isAdded)
                                                    <span class="badge bg-success" style="font-size: 0.68rem;">+ Baru</span>
                                                @elseif($isRemoved)
                                                    <span class="badge bg-danger" style="font-size: 0.68rem;">- Hapus</span>
                                                @elseif($isChanged)
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">&Delta; Ubah</span>
                                                @else
                                                    <span class="badge bg-secondary" style="font-size: 0.68rem;">= Sama</span>
                                                @endif
                                            </div>
                                            <div class="row g-1.5 small font-monospace">
                                                @if(array_key_exists($key, $old))
                                                    <div class="col-12">
                                                        <div class="p-1.5 rounded {{ $isChanged ? 'bg-danger-subtle text-danger text-decoration-line-through' : 'bg-light text-muted' }} text-break" style="font-size: 0.76rem;">
                                                            <strong class="text-uppercase d-inline-block me-1" style="font-size: 0.65rem;">Sebelum:</strong>
                                                            {{ $renderVal($valOld) }}
                                                        </div>
                                                    </div>
                                                @endif
                                                @if(array_key_exists($key, $new))
                                                    <div class="col-12">
                                                        <div class="p-1.5 rounded {{ $isChanged ? 'bg-success-subtle text-success fw-bold' : 'bg-light text-dark' }} text-break" style="font-size: 0.76rem;">
                                                            <strong class="text-uppercase d-inline-block me-1" style="font-size: 0.65rem;">Sesudah:</strong>
                                                            {{ $renderVal($valNew) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Desktop Visual Diff Table (>= 768px) -->
                                <div class="table-responsive border rounded mb-3 d-none d-md-block">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light small text-uppercase">
                                            <tr>
                                                <th style="width: 25%;">Atribut / Kolom</th>
                                                <th style="width: 35%;" class="bg-danger-subtle text-danger">Nilai Sebelum (Old)</th>
                                                <th style="width: 35%;" class="bg-success-subtle text-success">Nilai Sesudah (New)</th>
                                                <th style="width: 5%;" class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allKeys as $key)
                                                @php
                                                    $valOld = $old[$key] ?? null;
                                                    $valNew = $new[$key] ?? null;
                                                    $isChanged = ($valOld !== $valNew);
                                                    $isAdded = (!array_key_exists($key, $old) && array_key_exists($key, $new));
                                                    $isRemoved = (array_key_exists($key, $old) && !array_key_exists($key, $new));
                                                    $renderVal = fn($val) => is_array($val) ? json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (is_bool($val) ? ($val ? 'true' : 'false') : (is_null($val) ? 'NULL' : (string)$val));
                                                @endphp
                                                <tr class="{{ $isChanged ? 'table-warning' : '' }}">
                                                    <td class="fw-semibold text-dark font-monospace small">
                                                        {{ ucwords(str_replace('_', ' ', $key)) }}
                                                        <small class="d-block text-muted">({{ $key }})</small>
                                                    </td>
                                                    <td class="{{ $isChanged ? 'bg-danger-subtle text-danger text-decoration-line-through' : 'text-muted' }} small font-monospace">
                                                        {{ array_key_exists($key, $old) ? $renderVal($valOld) : '-' }}
                                                    </td>
                                                    <td class="{{ $isChanged ? 'bg-success-subtle text-success fw-bold' : 'text-dark' }} small font-monospace">
                                                        {{ array_key_exists($key, $new) ? $renderVal($valNew) : '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if($isAdded)
                                                            <span class="badge bg-success small">+ Baru</span>
                                                        @elseif($isRemoved)
                                                            <span class="badge bg-danger small">- Hapus</span>
                                                        @elseif($isChanged)
                                                            <span class="badge bg-warning text-dark small">&Delta; Ubah</span>
                                                        @else
                                                            <span class="badge bg-secondary small">= Sama</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <!-- Raw JSON View -->
                                <div class="row g-3 mb-3">
                                    @if($selectedLog->old_values)
                                        <div class="col-md-6">
                                            <div class="card border border-danger-subtle h-100">
                                                <div class="card-header bg-danger-subtle text-danger py-2 fw-bold small">
                                                    <i class="bi bi-dash-circle me-1"></i> Data Sebelum (Old Values JSON)
                                                </div>
                                                <div class="card-body p-2 bg-light">
                                                    <pre class="mb-0 small text-danger font-monospace" style="max-height: 280px; overflow-y: auto;">{{ json_encode($selectedLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if($selectedLog->new_values)
                                        <div class="col-md-{{ $selectedLog->old_values ? '6' : '12' }}">
                                            <div class="card border border-success-subtle h-100">
                                                <div class="card-header bg-success-subtle text-success py-2 fw-bold small">
                                                    <i class="bi bi-plus-circle me-1"></i> Data Sesudah (New Values JSON)
                                                </div>
                                                <div class="card-body p-2 bg-light">
                                                    <pre class="mb-0 small text-success font-monospace" style="max-height: 280px; overflow-y: auto;">{{ json_encode($selectedLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif

                        @if($selectedLog->properties)
                            <div class="mt-3">
                                <h6 class="fw-bold text-dark mb-1 small">
                                    <i class="bi bi-info-square me-1 text-secondary"></i>Metadata Tambahan (Properties):
                                </h6>
                                <pre class="bg-light p-2 rounded small text-secondary font-monospace">{{ json_encode($selectedLog->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" wire:click="closeDetail" class="btn btn-secondary btn-sm">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
