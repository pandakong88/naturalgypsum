<?php

namespace App\Livewire\Reports;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hub Laporan & Analisis')]
class ReportHub extends Component
{
    public function render(): View
    {
        return view('livewire.reports.hub');
    }
}
