<?php

namespace App\Livewire\Inventory;

use App\Domains\Inventory\Actions\CancelTransferGudangAction;
use App\Domains\Inventory\Models\TransferGudang;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Transfer Gudang')]
class TransferDetail extends Component
{
    public TransferGudang $transfer;

    public bool $isCancelModalOpen = false;

    public function mount(int $id): void
    {
        $this->transfer = TransferGudang::with([
            'gudangAsal',
            'gudangTujuan',
            'user',
            'creator',
            'updater',
            'details.produk.kategori',
            'details.produk.merek',
        ])->findOrFail($id);

        Gate::authorize('view', $this->transfer);
    }

    public function openCancelModal(): void
    {
        $this->isCancelModalOpen = true;
    }

    public function closeCancelModal(): void
    {
        $this->isCancelModalOpen = false;
    }

    public function cancel(CancelTransferGudangAction $action): mixed
    {
        Gate::authorize('delete', $this->transfer);

        try {
            $action->execute($this->transfer);
            $this->isCancelModalOpen = false;
            session()->flash('message', 'Transfer '.$this->transfer->no_transfer.' berhasil dibatalkan dan stok dikembalikan.');

            return redirect()->route('transfer.index');
        } catch (Exception $e) {
            $this->isCancelModalOpen = false;
            session()->flash('error', 'Gagal membatalkan transfer: '.$e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.inventory.transfer-detail');
    }
}
