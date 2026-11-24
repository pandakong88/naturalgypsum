<?php

namespace App\Livewire\Returns;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Returns\Actions\CancelReturPelangganAction;
use App\Domains\Returns\Models\ReturPelanggan;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Retur dari Pelanggan')]
class ReturPelangganIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $pelangganFilter = '';

    public string $gudangFilter = '';

    public string $startDate = '';

    public string $endDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPelangganFilter(): void
    {
        $this->resetPage();
    }

    public function updatedGudangFilter(): void
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

    public function cancelRetur(int $id, CancelReturPelangganAction $action): void
    {
        $retur = ReturPelanggan::findOrFail($id);
        Gate::authorize('delete', $retur);

        try {
            $action->execute($retur);
            session()->flash('message', 'Retur '.$retur->no_retur.' berhasil dibatalkan dan stok dikurangi kembali.');
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan retur pelanggan: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ReturPelanggan::class);

        $query = ReturPelanggan::query()
            ->with(['pelanggan', 'produk', 'gudang', 'penjualan', 'user']);

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_retur', 'like', "%{$search}%")
                    ->orWhere('alasan', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sq) use ($search) {
                        $sq->where('nama_pelanggan', 'like', "%{$search}%");
                    })
                    ->orWhereHas('produk', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('penjualan', function ($sq) use ($search) {
                        $sq->where('no_penjualan', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->pelangganFilter)) {
            $query->where('pelanggan_id', $this->pelangganFilter);
        }

        if (filled($this->gudangFilter)) {
            $query->where('gudang_id', $this->gudangFilter);
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        $returs = $query->latest('tanggal')->latest('id')->paginate(15);

        return view('livewire.returns.retur-pelanggan-index', [
            'returs' => $returs,
            'pelanggans' => Pelanggan::orderBy('nama_pelanggan')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
        ]);
    }
}
