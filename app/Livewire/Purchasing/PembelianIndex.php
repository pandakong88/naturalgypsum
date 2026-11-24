<?php

namespace App\Livewire\Purchasing;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Models\Pembelian;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Daftar Pembelian')]
class PembelianIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    #[Url]
    public string $supplierFilter = '';

    #[Url]
    public string $gudangFilter = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $statusPembayaranFilter = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $sortField = 'tanggal';

    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter(): void
    {
        $this->resetPage();
    }

    public function updatedGudangFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusPembayaranFilter(): void
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

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'supplierFilter', 'gudangFilter', 'statusFilter', 'statusPembayaranFilter', 'startDate', 'endDate']);
        $this->sortField = 'tanggal';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function setDateFilter(string $period): void
    {
        $now = Carbon::now();
        match ($period) {
            'today' => [
                $this->startDate = $now->toDateString(),
                $this->endDate = $now->toDateString(),
            ],
            'week' => [
                $this->startDate = $now->copy()->subDays(6)->toDateString(),
                $this->endDate = $now->toDateString(),
            ],
            'month' => [
                $this->startDate = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate = $now->copy()->endOfMonth()->toDateString(),
            ],
            default => null,
        };
        $this->resetPage();
    }

    public function clearDateFilter(): void
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->resetPage();
    }

    public function setQuickFilter(string $filter): void
    {
        match ($filter) {
            'ordered' => [
                $this->statusFilter = 'ordered',
                $this->statusPembayaranFilter = '',
            ],
            'received' => [
                $this->statusFilter = 'received',
                $this->statusPembayaranFilter = '',
            ],
            'tempo' => [
                $this->statusFilter = '',
                $this->statusPembayaranFilter = 'tempo',
            ],
            'cancelled' => [
                $this->statusFilter = 'cancelled',
                $this->statusPembayaranFilter = '',
            ],
            default => [
                $this->statusFilter = '',
                $this->statusPembayaranFilter = '',
            ],
        };
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return filled($this->search)
            || filled($this->supplierFilter)
            || filled($this->gudangFilter)
            || filled($this->statusFilter)
            || filled($this->statusPembayaranFilter)
            || filled($this->startDate)
            || filled($this->endDate);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Pembelian::class);

        $query = Pembelian::query()
            ->with(['supplier', 'user', 'penerima', 'details.produk', 'details.gudang']);

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_pembelian', 'like', "%{$search}%")
                    ->orWhere('no_faktur_supplier', 'like', "%{$search}%")
                    ->orWhere('no_surat_jalan', 'like', "%{$search}%")
                    ->orWhere('no_kontainer', 'like', "%{$search}%")
                    ->orWhere('no_polisi', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('nama_supplier', 'like', "%{$search}%");
                    })
                    ->orWhereHas('details.produk', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode_katalog', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->supplierFilter)) {
            $query->where('supplier_id', $this->supplierFilter);
        }

        if (filled($this->gudangFilter)) {
            $query->whereHas('details', function ($dq) {
                $dq->where('gudang_id', $this->gudangFilter);
            });
        }

        if (filled($this->statusFilter)) {
            $statusVal = match ($this->statusFilter) {
                'dipesan', 'ordered' => 'ordered',
                'diterima', 'received' => 'received',
                'dibatalkan', 'cancelled' => 'cancelled',
                default => $this->statusFilter,
            };
            $query->where('status', $statusVal);
        }

        if (filled($this->statusPembayaranFilter)) {
            $query->where('status_pembayaran', $this->statusPembayaranFilter);
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        // Calculate KPIs from filtered query (before pagination)
        $kpiQuery = (clone $query);
        $totalNilai = (float) (clone $kpiQuery)->sum('total');
        $totalTransaksi = (clone $kpiQuery)->count();
        $totalHutang = (float) (clone $kpiQuery)->where('status_pembayaran', '!=', 'lunas')->sum('sisa_hutang');
        $totalOrdered = (clone $kpiQuery)->where('status', 'ordered')->count();

        // Apply Sorting
        if ($this->sortField === 'supplier') {
            $query->join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
                ->orderBy('supplier.nama_supplier', $this->sortDirection)
                ->select('pembelian.*');
        } elseif ($this->sortField === 'total') {
            $query->orderBy('total', $this->sortDirection);
        } elseif ($this->sortField === 'sisa_hutang') {
            $query->orderBy('sisa_hutang', $this->sortDirection);
        } else {
            $query->orderBy('tanggal', $this->sortDirection)->orderBy('id', $this->sortDirection);
        }

        $pembelians = $query->paginate($this->perPage);

        return view('livewire.purchasing.pembelian-index', [
            'pembelians' => $pembelians,
            'suppliers' => Supplier::orderBy('nama_supplier')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'kpi' => [
                'totalNilai' => $totalNilai,
                'totalTransaksi' => $totalTransaksi,
                'totalHutang' => $totalHutang,
                'totalOrdered' => $totalOrdered,
            ],
        ]);
    }
}
