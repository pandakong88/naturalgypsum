<?php

namespace App\Livewire\Sales;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Sales\Actions\CancelPenjualanAction;
use App\Domains\Sales\Enums\StatusPembayaran;
use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Daftar Penjualan')]
class PenjualanIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'gudang')]
    public string $gudangFilter = '';

    #[Url(as: 'status_bayar')]
    public string $statusPembayaranFilter = '';

    #[Url(as: 'metode')]
    public string $metodePembayaranFilter = '';

    #[Url(as: 'status_nota')]
    public string $statusTransaksiFilter = 'aktif'; // 'aktif', 'dibatalkan', 'semua'

    #[Url(as: 'start')]
    public string $startDate = '';

    #[Url(as: 'end')]
    public string $endDate = '';

    // Modal Cancel State
    public bool $isCancelModalOpen = false;

    public ?int $cancellingPenjualanId = null;

    public string $cancellingNoPenjualan = '';

    public string $alasanBatal = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGudangFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusPembayaranFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMetodePembayaranFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusTransaksiFilter(): void
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

    public function setDateFilter(string $period): void
    {
        $now = Carbon::now();
        match ($period) {
            'today' => [
                $this->startDate = $now->toDateString(),
                $this->endDate = $now->toDateString(),
            ],
            'week' => [
                $this->startDate = $now->copy()->subDays(6)->toDateString(),
                $this->endDate = $now->toDateString(),
            ],
            'month' => [
                $this->startDate = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate = $now->copy()->endOfMonth()->toDateString(),
            ],
            default => null,
        };
        $this->resetPage();
    }

    public function clearDateFilter(): void
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->resetPage();
    }

    public function setQuickFilter(string $filter): void
    {
        match ($filter) {
            'tunai' => [
                $this->statusTransaksiFilter = 'aktif',
                $this->metodePembayaranFilter = 'tunai',
            ],
            'transfer' => [
                $this->statusTransaksiFilter = 'aktif',
                $this->metodePembayaranFilter = 'transfer',
            ],
            'tempo' => [
                $this->statusTransaksiFilter = 'aktif',
                $this->metodePembayaranFilter = 'tempo',
            ],
            'batal' => [
                $this->statusTransaksiFilter = 'dibatalkan',
                $this->metodePembayaranFilter = '',
            ],
            default => [
                $this->statusTransaksiFilter = 'aktif',
                $this->metodePembayaranFilter = '',
            ],
        };
        $this->resetPage();
    }

    public function resetAllFilters(): void
    {
        $this->search = '';
        $this->gudangFilter = '';
        $this->statusPembayaranFilter = '';
        $this->metodePembayaranFilter = '';
        $this->statusTransaksiFilter = 'aktif';
        $this->startDate = '';
        $this->endDate = '';
        $this->resetPage();
    }

    public function openCancelModal(int $id): void
    {
        $penjualan = Penjualan::findOrFail($id);
        Gate::authorize('cancel', $penjualan);

        $this->cancellingPenjualanId = $penjualan->id;
        $this->cancellingNoPenjualan = $penjualan->no_penjualan;
        $this->alasanBatal = '';
        $this->resetErrorBag('alasanBatal');
        $this->isCancelModalOpen = true;
    }

    public function closeCancelModal(): void
    {
        $this->isCancelModalOpen = false;
        $this->cancellingPenjualanId = null;
        $this->cancellingNoPenjualan = '';
        $this->alasanBatal = '';
    }

    public function confirmCancelPenjualan(CancelPenjualanAction $action): void
    {
        if (! $this->cancellingPenjualanId) {
            return;
        }

        $penjualan = Penjualan::findOrFail($this->cancellingPenjualanId);
        Gate::authorize('cancel', $penjualan);

        $this->validate([
            'alasanBatal' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'alasanBatal.required' => 'Alasan pembatalan nota wajib diisi.',
            'alasanBatal.min' => 'Alasan pembatalan minimal 3 karakter.',
            'alasanBatal.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ]);

        try {
            $action->execute($penjualan, $this->alasanBatal);
            session()->flash('message', "Nota penjualan {$this->cancellingNoPenjualan} berhasil dibatalkan. Stok barang telah dikembalikan ke gudang.");
            $this->closeCancelModal();
        } catch (\Throwable $e) {
            $this->addError('alasanBatal', $e->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Penjualan::class);

        $query = match ($this->statusTransaksiFilter) {
            'dibatalkan' => Penjualan::onlyTrashed(),
            'semua' => Penjualan::withTrashed(),
            default => Penjualan::query(),
        };

        $query->with(['pelanggan', 'gudang', 'user', 'creator', 'dibatalkanBy'])
            ->withCount('details');

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_penjualan', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhere('nama_sopir', 'like', "%{$search}%")
                    ->orWhere('plat_nomor', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn ($pq) => $pq->search($search));
            });
        }

        if (filled($this->gudangFilter)) {
            $query->where('gudang_id', $this->gudangFilter);
        }

        if (filled($this->statusPembayaranFilter)) {
            $query->where('status_pembayaran', $this->statusPembayaranFilter);
        }

        if (filled($this->metodePembayaranFilter)) {
            if ($this->metodePembayaranFilter === 'tempo') {
                $query->whereIn('metode_pembayaran', ['tempo', 'belum_bayar']);
            } else {
                $query->where('metode_pembayaran', $this->metodePembayaranFilter);
            }
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        $penjualans = $query->latest('tanggal')->latest('id')->paginate(15);

        return view('livewire.sales.penjualan-index', [
            'penjualans' => $penjualans,
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'statusOptions' => StatusPembayaran::cases(),
        ]);
    }
}
