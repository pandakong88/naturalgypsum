<?php

namespace App\Livewire\Payments;

use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    public string $sumberFilter = ''; // '' | 'piutang' | 'kasir'

    public string $statusFilter = 'aktif'; // 'aktif' | 'dibatalkan' | 'semua'

    public string $startDate = '';

    public string $endDate = '';

    public string $datePreset = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMetodeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSumberFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->datePreset = '';
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->datePreset = '';
        $this->resetPage();
    }

    public function setDatePreset(string $preset): void
    {
        $today = Carbon::today();
        $this->datePreset = $preset;

        switch ($preset) {
            case 'today':
                $this->startDate = $today->toDateString();
                $this->endDate = $today->toDateString();
                break;
            case 'yesterday':
                $yesterday = $today->copy()->subDay();
                $this->startDate = $yesterday->toDateString();
                $this->endDate = $yesterday->toDateString();
                break;
            case '7days':
                $this->startDate = $today->copy()->subDays(6)->toDateString();
                $this->endDate = $today->toDateString();
                break;
            case 'month':
                $this->startDate = $today->copy()->startOfMonth()->toDateString();
                $this->endDate = $today->copy()->endOfMonth()->toDateString();
                break;
            default:
                $this->datePreset = '';
                $this->startDate = '';
                $this->endDate = '';
                break;
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'metodeFilter',
            'sumberFilter',
            'statusFilter',
            'startDate',
            'endDate',
            'datePreset',
        ]);
        $this->resetPage();
    }

    protected function applyCommonFilters(Builder $query): Builder
    {
        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function (Builder $q) use ($search) {
                $q->where('no_pembayaran', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function (Builder $pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('alokasi.penjualan', function (Builder $iq) use ($search) {
                        $iq->where('no_penjualan', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->metodeFilter)) {
            $query->where('metode', $this->metodeFilter);
        }

        if ($this->sumberFilter === 'kasir') {
            $query->where('catatan', 'like', 'Pembayaran kasir langsung untuk faktur%');
        } elseif ($this->sumberFilter === 'piutang') {
            $query->where(function (Builder $q) {
                $q->whereNull('catatan')
                    ->orWhere('catatan', 'not like', 'Pembayaran kasir langsung untuk faktur%');
            });
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        return $query;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Pembayaran::class);

        // Main table query (respecting statusFilter)
        $query = Pembayaran::query()
            ->with(['pelanggan', 'user', 'creator', 'alokasi.penjualan'])
            ->withSum('alokasi', 'jumlah_dialokasikan');

        if ($this->statusFilter === 'dibatalkan') {
            $query->onlyTrashed();
        } elseif ($this->statusFilter === 'semua') {
            $query->withTrashed();
        }

        $this->applyCommonFilters($query);

        $pembayarans = $query->latest('tanggal')->latest('id')->paginate(15);

        // KPI Summary calculation (active payments matching current search/date/source/method filters)
        $statsBase = $this->applyCommonFilters(Pembayaran::query());

        $totalNominal = (float) (clone $statsBase)->sum('jumlah_bayar');
        $totalTransaksi = (int) (clone $statsBase)->count();
        $totalTunai = (float) (clone $statsBase)->where('metode', MetodePembayaran::Tunai->value)->sum('jumlah_bayar');
        $totalTransfer = (float) (clone $statsBase)->where('metode', MetodePembayaran::Transfer->value)->sum('jumlah_bayar');
        $totalPelunasanPiutang = (float) (clone $statsBase)->where(function (Builder $q) {
            $q->whereNull('catatan')
                ->orWhere('catatan', 'not like', 'Pembayaran kasir langsung untuk faktur%');
        })->sum('jumlah_bayar');
        $totalKasirLangsung = max(0.00, round($totalNominal - $totalPelunasanPiutang, 2));
        $totalDibatalkan = (int) $this->applyCommonFilters(Pembayaran::onlyTrashed())->count();

        return view('livewire.payments.pembayaran-index', [
            'pembayarans' => $pembayarans,
            'metodeOptions' => MetodePembayaran::cases(),
            'totalNominal' => $totalNominal,
            'totalTransaksi' => $totalTransaksi,
            'totalTunai' => $totalTunai,
            'totalTransfer' => $totalTransfer,
            'totalPelunasanPiutang' => $totalPelunasanPiutang,
            'totalKasirLangsung' => $totalKasirLangsung,
            'totalDibatalkan' => $totalDibatalkan,
        ]);
    }
}
