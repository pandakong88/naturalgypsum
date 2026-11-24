<?php

namespace App\Livewire\Payments;

use App\Domains\Receivables\Actions\CancelPembayaranAction;
use App\Domains\Receivables\Models\Pembayaran;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Pembayaran')]
class PembayaranDetail extends Component
{
    public int $pembayaranId;

    public bool $isCancelModalOpen = false;

    public function mount(int $id): void
    {
        $this->pembayaranId = $id;
        $pembayaran = Pembayaran::withTrashed()->findOrFail($id);
        Gate::authorize('view', $pembayaran);
    }

    public function openCancelModal(): void
    {
        $pembayaran = Pembayaran::findOrFail($this->pembayaranId);
        Gate::authorize('cancel', $pembayaran);
        $this->isCancelModalOpen = true;
    }

    public function closeCancelModal(): void
    {
        $this->isCancelModalOpen = false;
    }

    public function cancelPayment(CancelPembayaranAction $action): mixed
    {
        $pembayaran = Pembayaran::findOrFail($this->pembayaranId);
        Gate::authorize('cancel', $pembayaran);

        $action->execute($pembayaran);

        session()->flash('message', "Pembayaran {$pembayaran->no_pembayaran} berhasil dibatalkan dan sisa piutang faktur telah dikembalikan.");

        return redirect()->route('pembayaran.index');
    }

    public function render(): View
    {
        $pembayaran = Pembayaran::withTrashed()
            ->with(['pelanggan', 'user', 'creator', 'updater', 'alokasi.penjualan'])
            ->findOrFail($this->pembayaranId);

        Gate::authorize('view', $pembayaran);

        return view('livewire.payments.pembayaran-detail', [
            'pembayaran' => $pembayaran,
        ]);
    }
}
