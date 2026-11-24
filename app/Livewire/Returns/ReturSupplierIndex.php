<?php

namespace App\Livewire\Returns;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Returns\Actions\CancelReturSupplierAction;
use App\Domains\Returns\Models\ReturSupplier;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Retur ke Supplier')]
class ReturSupplierIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $supplierFilter = '';

    public string $gudangFilter = '';

    public string $startDate = '';

    public string $endDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter(): void
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

    public function cancelRetur(int $id, CancelReturSupplierAction $action): void
    {
        $retur = ReturSupplier::findOrFail($id);
        Gate::authorize('delete', $retur);

        try {
            $action->execute($retur);
            session()->flash('message', 'Retur '.$retur->no_retur.' berhasil dibatalkan dan stok dikembalikan.');
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan retur supplier: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ReturSupplier::class);

        $query = ReturSupplier::query()
            ->with(['supplier', 'produk', 'gudang', 'pembelian', 'user']);

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_retur', 'like', "%{$search}%")
                    ->orWhere('alasan', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('nama_supplier', 'like', "%{$search}%");
                    })
                    ->orWhereHas('produk', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('pembelian', function ($bq) use ($search) {
                        $bq->where('no_pembelian', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->supplierFilter)) {
            $query->where('supplier_id', $this->supplierFilter);
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

        return view('livewire.returns.retur-supplier-index', [
            'returs' => $returs,
            'suppliers' => Supplier::orderBy('nama_supplier')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
        ]);
    }
}
