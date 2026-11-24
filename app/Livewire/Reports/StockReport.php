<?php

namespace App\Livewire\Reports;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Reports\Services\StockReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Stok & Estimasi Nilai')]
class StockReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $gudang_id = '';

    public string $kategori_id = '';

    public string $merek_id = '';

    public bool $stok_rendah = false;

    public int $perPage = 25;

    public function mount(): void
    {
        abort_if(! auth()->user()->can('view_stock_reports'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGudangId(): void
    {
        $this->resetPage();
    }

    public function updatingKategoriId(): void
    {
        $this->resetPage();
    }

    public function updatingMerekId(): void
    {
        $this->resetPage();
    }

    public function updatingStokRendah(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset(['search', 'gudang_id', 'kategori_id', 'merek_id', 'stok_rendah']);
        $this->resetPage();
    }

    public function render(StockReportService $service): View
    {
        $filters = [
            'search' => $this->search,
            'gudang_id' => $this->gudang_id,
            'kategori_id' => $this->kategori_id,
            'merek_id' => $this->merek_id,
            'stok_rendah' => $this->stok_rendah,
        ];

        return view('livewire.reports.stock-report', [
            'stocks' => $service->getFilteredQuery($filters)->paginate($this->perPage),
            'summary' => $service->getSummary($filters),
            'gudangList' => Gudang::orderBy('nama_gudang')->get(),
            'kategoriList' => Kategori::orderBy('nama_kategori')->get(),
            'rootKategoris' => Kategori::root()->with('children')->orderBy('nama_kategori')->get(),
            'merekList' => Merek::orderBy('nama_merek')->get(),
            'canViewPurchasePrices' => auth()->user()->can('view_purchase_prices'),
        ]);
    }
}
