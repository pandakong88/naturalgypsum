<?php

namespace App\Livewire\Production;

use App\Domains\Production\Actions\CancelProduksiAction;
use App\Domains\Production\Models\Produksi;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Produksi')]
class ProduksiDetail extends Component
{
    public Produksi $produksi;

    public function mount(int $id): void
    {
        $this->produksi = Produksi::with([
            'gudang',
            'user',
            'creator',
            'updater',
            'details.produk.kategori',
            'details.produk.merek',
        ])->findOrFail($id);

        Gate::authorize('view', $this->produksi);
    }

    public function cancel(CancelProduksiAction $action): mixed
    {
        Gate::authorize('delete', $this->produksi);

        try {
            $action->execute($this->produksi);
            session()->flash('message', 'Produksi '.$this->produksi->no_produksi.' berhasil dibatalkan dan stok dikembalikan.');

            return redirect()->route('produksi.index');
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan produksi: '.$e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.production.produksi-detail');
    }
}
