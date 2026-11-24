<?php

namespace App\Livewire\Returns;

use App\Domains\Returns\Actions\CancelReturSupplierAction;
use App\Domains\Returns\Models\ReturSupplier;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Retur Supplier')]
class ReturSupplierDetail extends Component
{
    public ReturSupplier $retur;

    public function mount(int $id): void
    {
        $this->retur = ReturSupplier::with([
            'supplier',
            'produk.kategori',
            'produk.merek',
            'gudang',
            'pembelian',
            'user',
            'creator',
            'updater',
        ])->findOrFail($id);

        Gate::authorize('view', $this->retur);
    }

    public function cancel(CancelReturSupplierAction $action): mixed
    {
        Gate::authorize('delete', $this->retur);

        try {
            $action->execute($this->retur);
            session()->flash('message', 'Retur '.$this->retur->no_retur.' berhasil dibatalkan dan stok dikembalikan.');

            return redirect()->route('retur-supplier.index');
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan retur supplier: '.$e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.returns.retur-supplier-detail');
    }
}
