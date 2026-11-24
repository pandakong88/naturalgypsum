<?php

namespace App\Livewire\Reports;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Reports\Services\PaymentReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Pembayaran')]
class PaymentReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $metode = '';

    public string $pelanggan_id = '';

    public string $period = 'this_month'; // today, last_7_days, this_month, last_month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public int $perPage = 25;

    public function mount(): void
    {
        abort_if(! auth()->user()->can('view_payment_reports'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMetode(): void
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
        $this->reset(['search', 'metode', 'pelanggan_id', 'startDate', 'endDate']);
        $this->period = 'this_month';
        $this->resetPage();
    }

    public function render(PaymentReportService $service): View
    {
        $filters = [
            'search' => $this->search,
            'metode' => $this->metode,
            'pelanggan_id' => $this->pelanggan_id,
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        return view('livewire.reports.payment-report', [
            'payments' => $service->getFilteredQuery($filters)->paginate($this->perPage),
            'summary' => $service->getSummary($filters),
            'pelangganList' => Pelanggan::orderBy('nama')->get(),
        ]);
    }
}
