<?php

namespace App\Livewire;

use App\Domains\Reports\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $period = 'this_month'; // today, last_7_days, this_month, last_month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->startDate = null;
            $this->endDate = null;
        }
    }

    public function render(DashboardService $service): View
    {
        $filters = [
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        return view('livewire.dashboard', [
            'stockSummary' => $service->getStockSummary(),
            'salesSummary' => $service->getSalesSummary($filters),
            'purchaseSummary' => $service->getPurchaseSummary($filters),
            'receivablesSummary' => $service->getReceivablesSummary(),
            'paymentSummary' => $service->getPaymentSummary($filters),
            'productionSummary' => $service->getProductionSummary($filters),
            'returnsSummary' => $service->getReturnsSummary($filters),
            'recentTransactions' => $service->getRecentTransactions(8),
        ]);
    }
}
