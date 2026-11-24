<?php

namespace App\Livewire\Admin;

use App\Domains\Audit\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Audit Trail & Activity Log')]
class ActivityLogIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $filterUser = '';

    public string $filterAction = '';

    public string $filterModule = '';

    public string $startDate = '';

    public string $endDate = '';

    public ?int $selectedLogId = null;

    public ?ActivityLog $selectedLog = null;

    public string $activeDetailTab = 'table'; // 'table' or 'json'

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUser(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAction(): void
    {
        $this->resetPage();
    }

    public function updatedFilterModule(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function filterByUserClick(int $userId): void
    {
        $this->filterUser = (string) $userId;
        $this->resetPage();
    }

    public function filterByActionClick(string $action): void
    {
        $this->filterAction = $action;
        $this->resetPage();
    }

    public function filterByModuleClick(string $module): void
    {
        $this->filterModule = $module;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterUser', 'filterAction', 'filterModule', 'startDate', 'endDate']);
        $this->resetPage();
    }

    public function showDetail(int $logId): void
    {
        $this->selectedLog = ActivityLog::with('user')->findOrFail($logId);
        $this->selectedLogId = $logId;
        $this->activeDetailTab = 'table';
    }

    public function closeDetail(): void
    {
        $this->reset(['selectedLogId', 'selectedLog', 'activeDetailTab']);
    }

    public function setDetailTab(string $tab): void
    {
        $this->activeDetailTab = $tab;
    }

    /**
     * Build base query with current filters.
     */
    protected function buildQuery(): Builder
    {
        return ActivityLog::query()
            ->with('user')
            ->search($this->search)
            ->filterByUser($this->filterUser ? (int) $this->filterUser : null)
            ->filterByAction($this->filterAction ?: null)
            ->filterByModule($this->filterModule ?: null)
            ->filterByDateRange($this->startDate ?: null, $this->endDate ?: null)
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * Export currently filtered activity logs to CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $query = $this->buildQuery();
        $filename = 'audit-trail-'.Carbon::now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Waktu',
                'Pengguna (Actor)',
                'Aksi',
                'Modul / Tipe Dokumen',
                'Target / Label',
                'Deskripsi Aktivitas',
                'IP Address',
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user_name ?? ($log->user?->name ?? 'System'),
                        strtoupper((string) $log->action),
                        class_basename($log->subject_type ?? '-'),
                        $log->subject_label ?? '-',
                        $log->description,
                        $log->ip_address ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $today = Carbon::today();

        // Summary KPI statistics for today
        $totalHariIni = ActivityLog::whereDate('created_at', $today)->count();
        $totalUpdate = ActivityLog::whereDate('created_at', $today)->where('action', 'update')->count();
        $totalDeleteCancel = ActivityLog::whereDate('created_at', $today)->whereIn('action', ['delete', 'cancel'])->count();
        $topActorRecord = ActivityLog::whereDate('created_at', $today)
            ->select('user_name', DB::raw('count(*) as count'))
            ->groupBy('user_name')
            ->orderByDesc('count')
            ->first();

        $kpis = [
            'totalHariIni' => $totalHariIni,
            'totalUpdate' => $totalUpdate,
            'totalDeleteCancel' => $totalDeleteCancel,
            'topActor' => $topActorRecord ? "{$topActorRecord->user_name} ({$topActorRecord->count}x)" : '-',
        ];

        return view('livewire.admin.activity-log-index', [
            'logs' => $this->buildQuery()->paginate(20),
            'users' => User::withTrashed()->orderBy('name')->get(),
            'kpis' => $kpis,
            'modules' => [
                'master' => 'Master Data (Produk, Kategori, Merek, Gudang, Supplier, Pelanggan)',
                'sales' => 'Penjualan & Retur Penjualan',
                'purchasing' => 'Pembelian & Retur Supplier',
                'inventory' => 'Inventaris & Stok Opname',
                'security' => 'Keamanan & Akun User',
            ],
            'actions' => [
                'create' => 'Create',
                'update' => 'Update',
                'delete' => 'Delete',
                'restore' => 'Restore',
                'cancel' => 'Cancel',
                'complete' => 'Complete',
                'login' => 'Login',
                'logout' => 'Logout',
            ],
        ]);
    }
}
