<?php

namespace App\Livewire\Production;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Production\Actions\CancelProduksiAction;
use App\Domains\Production\Models\Produksi;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Catatan Produksi')]
class ProduksiIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $gudangFilter = '';

    public string $startDate = '';

    public string $endDate = '';

    public function updatedSearch(): void
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

    public function cancelProduksi(int $id, CancelProduksiAction $action): void
    {
        $produksi = Produksi::findOrFail($id);
        Gate::authorize('delete', $produksi);

        try {
            $action->execute($produksi);
            session()->flash('message', 'Produksi '.$produksi->no_produksi.' berhasil dibatalkan dan stok dikurangi.');
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan produksi: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Produksi::class);

        $query = Produksi::query()
            ->with(['gudang', 'user', 'details.produk']);

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_produksi', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('gudang', function ($gq) use ($search) {
                        $gq->where('nama_gudang', 'like', "%{$search}%");
                    })
                    ->orWhereHas('details.produk', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%");
                    });
            });
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

        $produksis = $query->latest('tanggal')->latest('id')->paginate(15);

        return view('livewire.production.produksi-index', [
            'produksis' => $produksis,
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
        ]);
    }
}
