<?php

namespace App\Livewire\Payments;

use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Daftar Pembayaran')]
class PembayaranIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $metodeFilter = '';

    public string $startDate = '';

    public string $endDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMetodeFilter(): void
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

    public function render(): View
    {
        Gate::authorize('viewAny', Pembayaran::class);

        $query = Pembayaran::query()
            ->with(['pelanggan', 'user', 'alokasi.penjualan']);

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_pembayaran', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->metodeFilter)) {
            $query->where('metode', $this->metodeFilter);
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        $pembayarans = $query->latest('tanggal')->latest('id')->paginate(15);

        return view('livewire.payments.pembayaran-index', [
            'pembayarans' => $pembayarans,
            'metodeOptions' => MetodePembayaran::cases(),
        ]);
    }
}
