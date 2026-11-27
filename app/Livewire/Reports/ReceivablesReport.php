<?php

namespace App\Livewire\Reports;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Reports\Services\ReceivablesReportService;
use App\Livewire\Receivables\PiutangIndex;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Piutang & Tagihan')]
class ReceivablesReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $pelanggan_id = '';

    public string $aging = ''; // '', 'lancar', 'jatuh_tempo', 'menunggak', 'macet', 'lewat_tempo'

    public string $period = 'all'; // all, today, last_7_days, this_month, last_month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public int $perPage = 25;

    public function mount(): void
    {
        abort_if(! auth()->user()->can('view_receivable_reports'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPelangganId(): void
    {
        $this->resetPage();
    }

    public function updatingAging(): void
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
        $this->reset(['search', 'pelanggan_id', 'aging', 'startDate', 'endDate']);
        $this->period = 'all';
        $this->resetPage();
    }

    public function render(ReceivablesReportService $service): View
    {
        $filters = [
            'search' => $this->search,
            'pelanggan_id' => $this->pelanggan_id,
            'aging' => $this->aging,
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        return view('livewire.reports.receivables-report', [
            'invoices' => $service->getFilteredQuery($filters)->paginate($this->perPage),
            'summary' => $service->getSummary($filters),
            'topDebtors' => $service->getTopDebtors(5),
            'pelangganList' => Pelanggan::orderBy('nama')->get(),
            'tempoStandarHari' => PiutangIndex::TEMPO_STANDAR_HARI,
        ]);
    }
}
