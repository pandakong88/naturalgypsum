<?php

namespace App\Livewire\Reports;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Reports\Services\PurchaseReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Pembelian')]
class PurchaseReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $supplier_id = '';

    public string $gudang_id = '';

    public string $status = '';

    public string $status_pembayaran = '';

    public string $period = 'this_month'; // all, today, last_7_days, this_month, last_month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public int $perPage = 25;

    public function mount(): void
    {
        abort_if(! auth()->user()->can('view_purchase_reports'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSupplierId(): void
    {
        $this->resetPage();
    }

    public function updatingGudangId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingStatusPembayaran(): void
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
        $this->reset(['search', 'supplier_id', 'gudang_id', 'status', 'status_pembayaran', 'startDate', 'endDate']);
        $this->period = 'this_month';
        $this->resetPage();
    }

    public function render(PurchaseReportService $service): View
    {
        $filters = [
            'search' => $this->search,
            'supplier_id' => $this->supplier_id,
            'gudang_id' => $this->gudang_id,
            'status' => $this->status,
            'status_pembayaran' => $this->status_pembayaran,
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        return view('livewire.reports.purchase-report', [
            'purchases' => $service->getFilteredQuery($filters)->paginate($this->perPage),
            'summary' => $service->getSummary($filters),
            'topSuppliers' => $service->getTopSuppliers($filters, 5),
            'supplierList' => Supplier::orderBy('nama_supplier')->get(),
            'gudangList' => Gudang::orderBy('nama_gudang')->get(),
            'canViewPurchasePrices' => auth()->user()->can('view_purchase_prices'),
        ]);
    }
}
