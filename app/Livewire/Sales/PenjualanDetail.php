<?php

namespace App\Livewire\Sales;

use App\Domains\Sales\Actions\CancelPenjualanAction;
use App\Domains\Sales\Models\Penjualan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Penjualan')]
class PenjualanDetail extends Component
{
    public Penjualan $penjualan;

    public bool $isCancelModalOpen = false;

    public string $alasanBatal = '';

    public function mount(int $id): void
    {
        $this->penjualan = Penjualan::withTrashed()->with([
            'pelanggan',
            'gudang',
            'user',
            'creator',
            'updater',
            'dibatalkanBy',
            'details.gudang',
            'details.produk.kategori',
            'details.produk.merek',
        ])->findOrFail($id);

        Gate::authorize('view', $this->penjualan);
    }

    public function openCancelModal(): void
    {
        Gate::authorize('cancel', $this->penjualan);
        $this->alasanBatal = '';
        $this->resetErrorBag('alasanBatal');
        $this->isCancelModalOpen = true;
    }

    public function closeCancelModal(): void
    {
        $this->isCancelModalOpen = false;
        $this->alasanBatal = '';
    }

    public function confirmCancelPenjualan(CancelPenjualanAction $action): void
    {
        Gate::authorize('cancel', $this->penjualan);

        $this->validate([
            'alasanBatal' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'alasanBatal.required' => 'Alasan pembatalan nota wajib diisi.',
            'alasanBatal.min' => 'Alasan pembatalan minimal 3 karakter.',
            'alasanBatal.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ]);

        try {
            $this->penjualan = $action->execute($this->penjualan, $this->alasanBatal);
            session()->flash('message', "Nota penjualan {$this->penjualan->no_penjualan} berhasil dibatalkan. Stok barang telah dikembalikan ke gudang.");
            $this->closeCancelModal();
        } catch (\Throwable $e) {
            $this->addError('alasanBatal', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.sales.penjualan-detail');
    }
}
