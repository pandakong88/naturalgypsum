<?php

namespace App\Livewire\Returns;

use App\Domains\Returns\Actions\CancelReturPelangganAction;
use App\Domains\Returns\Models\ReturPelanggan;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Retur Pelanggan')]
class ReturPelangganDetail extends Component
{
    public ReturPelanggan $retur;

    public function mount(int $id): void
    {
        $this->retur = ReturPelanggan::with([
            'pelanggan',
            'produk.kategori',
            'produk.merek',
            'gudang',
            'penjualan',
            'user',
            'creator',
            'updater',
        ])->findOrFail($id);

        Gate::authorize('view', $this->retur);
    }

    public function cancel(CancelReturPelangganAction $action): mixed
    {
        Gate::authorize('delete', $this->retur);

        try {
            $action->execute($this->retur);
            session()->flash('message', 'Retur '.$this->retur->no_retur.' berhasil dibatalkan dan stok dikurangi kembali.');

            return redirect()->route('retur-pelanggan.index');
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan retur pelanggan: '.$e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.returns.retur-pelanggan-detail');
    }
}
