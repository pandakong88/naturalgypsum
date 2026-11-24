<?php

namespace App\Livewire\Reports;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Reports\Services\ProductionReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Produksi')]
class ProductionReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $gudang_id = '';

    public string $produk_id = '';

    public string $period = 'this_month'; // today, last_7_days, this_month, last_month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public int $perPage = 25;

    public function mount(): void
    {
        abort_if(! auth()->user()->can('view_production_reports'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGudangId(): void
    {
        $this->resetPage();
    }

    public function updatingProdukId(): void
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
        $this->reset(['search', 'gudang_id', 'produk_id', 'startDate', 'endDate']);
        $this->period = 'this_month';
        $this->resetPage();
    }

    public function render(ProductionReportService $service): View
    {
        $filters = [
            'search' => $this->search,
            'gudang_id' => $this->gudang_id,
            'produk_id' => $this->produk_id,
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        return view('livewire.reports.production-report', [
            'productions' => $service->getFilteredQuery($filters)->paginate($this->perPage),
            'summary' => $service->getSummary($filters),
            'gudangList' => Gudang::orderBy('nama_gudang')->get(),
            'produkList' => Produk::orderBy('nama')->get(),
        ]);
    }
}
