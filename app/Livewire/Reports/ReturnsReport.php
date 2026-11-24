<?php

namespace App\Livewire\Reports;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Reports\Services\ReturnsReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Retur')]
class ReturnsReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $type = 'supplier'; // supplier, pelanggan

    public string $search = '';

    public string $gudang_id = '';

    public string $supplier_id = '';

    public string $pelanggan_id = '';

    public string $period = 'this_month'; // today, last_7_days, this_month, last_month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public int $perPage = 25;

    public function mount(): void
    {
        abort_if(! auth()->user()->can('view_return_reports'), 403);
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGudangId(): void
    {
        $this->resetPage();
    }

    public function updatingSupplierId(): void
    {
        $this->resetPage();
    }

    public function updatingPelangganId(): void
    {
        $this->resetPage();
    }

    public function updatingPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->startDate = null;
            $this->endDate = null;
        }
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset(['search', 'gudang_id', 'supplier_id', 'pelanggan_id', 'startDate', 'endDate']);
        $this->period = 'this_month';
        $this->resetPage();
    }

    public function render(ReturnsReportService $service): View
    {
        $filters = [
            'search' => $this->search,
            'gudang_id' => $this->gudang_id,
            'supplier_id' => $this->supplier_id,
            'pelanggan_id' => $this->pelanggan_id,
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        $returns = $this->type === 'supplier'
            ? $service->getFilteredSupplierQuery($filters)->paginate($this->perPage)
            : $service->getFilteredCustomerQuery($filters)->paginate($this->perPage);

        return view('livewire.reports.returns-report', [
            'returns' => $returns,
            'summary' => $service->getSummary($filters),
            'gudangList' => Gudang::orderBy('nama_gudang')->get(),
            'supplierList' => Supplier::orderBy('nama_supplier')->get(),
            'pelangganList' => Pelanggan::orderBy('nama')->get(),
        ]);
    }
}
