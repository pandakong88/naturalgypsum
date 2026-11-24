<?php

namespace App\Livewire\Purchasing;

use App\Domains\Purchasing\Actions\CancelPembelianAction;
use App\Domains\Purchasing\Actions\DeletePembelianPOAction;
use App\Domains\Purchasing\Actions\ReceivePembelianAction;
use App\Domains\Purchasing\Actions\RecordPembayaranPembelianAction;
use App\Domains\Purchasing\Actions\UpdateShippingDocsAction;
use App\Domains\Purchasing\Models\Pembelian;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Pembelian')]
class PembelianDetail extends Component
{
    public Pembelian $pembelian;

    // Receive Goods Modal State
    public bool $showReceiveModal = false;

    public string $receive_no_surat_jalan = '';

    public string $receive_no_faktur_supplier = '';

    public string $receive_no_polisi = '';

    public string $receive_tanggal_diterima = '';

    // Record Payment Modal State
    public bool $showPaymentModal = false;

    public float $pay_nominal = 0;

    public string $pay_tanggal_bayar = '';

    public string $pay_metode_pembayaran = 'transfer';

    public string $pay_keterangan = '';

    // Cancel Modal State
    public bool $showCancelModal = false;

    public string $cancel_alasan = '';

    // Delete PO Modal State
    public bool $showDeleteModal = false;

    // Edit Shipping & Logistics Modal State
    public bool $showEditShippingModal = false;

    public string $edit_no_surat_jalan = '';

    public string $edit_no_faktur_supplier = '';

    public string $edit_no_kontainer = '';

    public string $edit_no_polisi = '';

    public string $edit_tanggal_jatuh_tempo = '';

    public string $edit_keterangan = '';

    public function mount(int $id): void
    {
        $this->loadPembelian($id);
        Gate::authorize('view', $this->pembelian);
    }

    protected function loadPembelian(int $id): void
    {
        $this->pembelian = Pembelian::with([
            'supplier',
            'user',
            'creator',
            'updater',
            'penerima',
            'details.produk.kategori',
            'details.produk.merek',
            'details.gudang',
            'pembayaran.user',
        ])->findOrFail($id);
    }

    public function openReceiveModal(): void
    {
        Gate::authorize('update', $this->pembelian);

        $this->receive_no_surat_jalan = (string) ($this->pembelian->no_surat_jalan ?? '');
        $this->receive_no_faktur_supplier = (string) ($this->pembelian->no_faktur_supplier ?? '');
        $this->receive_no_polisi = (string) ($this->pembelian->no_polisi ?? '');
        $this->receive_tanggal_diterima = now()->toDateString();
        $this->showReceiveModal = true;
    }

    public function closeReceiveModal(): void
    {
        $this->showReceiveModal = false;
        $this->resetValidation();
    }

    public function processReceive(ReceivePembelianAction $action): void
    {
        Gate::authorize('update', $this->pembelian);

        $this->validate([
            'receive_tanggal_diterima' => 'required|date',
            'receive_no_surat_jalan' => 'nullable|string|max:100',
            'receive_no_faktur_supplier' => 'nullable|string|max:100',
            'receive_no_polisi' => 'nullable|string|max:50',
        ]);

        $action->execute(
            pembelian: $this->pembelian,
            userId: (int) auth()->id(),
            noSuratJalan: filled($this->receive_no_surat_jalan) ? $this->receive_no_surat_jalan : null,
            noFakturSupplier: filled($this->receive_no_faktur_supplier) ? $this->receive_no_faktur_supplier : null,
            noPolisi: filled($this->receive_no_polisi) ? $this->receive_no_polisi : null,
            tanggalDiterima: $this->receive_tanggal_diterima
        );

        $this->loadPembelian($this->pembelian->id);
        $this->showReceiveModal = false;
        session()->flash('message', 'Barang berhasil diverifikasi & diterima! Stok otomatis masuk ke kartu stok gudang.');
    }

    public function openPaymentModal(): void
    {
        Gate::authorize('update', $this->pembelian);

        $this->pay_nominal = (float) $this->pembelian->sisa_hutang;
        $this->pay_tanggal_bayar = now()->toDateString();
        $this->pay_metode_pembayaran = 'transfer';
        $this->pay_keterangan = '';
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->resetValidation();
    }

    public function processPayment(RecordPembayaranPembelianAction $action): void
    {
        Gate::authorize('update', $this->pembelian);

        $maxNominal = (float) $this->pembelian->sisa_hutang;

        $this->validate([
            'pay_nominal' => ['required', 'numeric', 'min:1', "max:{$maxNominal}"],
            'pay_tanggal_bayar' => 'required|date',
            'pay_metode_pembayaran' => 'required|string|in:transfer,cash,giro,lainnya',
            'pay_keterangan' => 'nullable|string|max:255',
        ], [
            'pay_nominal.max' => 'Nominal pembayaran tidak boleh melebihi sisa hutang (Rp '.number_format($maxNominal, 0, ',', '.').').',
        ]);

        $action->execute(
            pembelian: $this->pembelian,
            userId: (int) auth()->id(),
            nominal: (float) $this->pay_nominal,
            tanggalBayar: $this->pay_tanggal_bayar,
            metodePembayaran: $this->pay_metode_pembayaran,
            keterangan: filled($this->pay_keterangan) ? $this->pay_keterangan : null
        );

        $this->loadPembelian($this->pembelian->id);
        $this->showPaymentModal = false;
        session()->flash('message', 'Pembayaran hutang supplier berhasil dicatat!');
    }

    public function openEditShippingModal(): void
    {
        Gate::authorize('update', $this->pembelian);

        $this->edit_no_surat_jalan = (string) ($this->pembelian->no_surat_jalan ?? '');
        $this->edit_no_faktur_supplier = (string) ($this->pembelian->no_faktur_supplier ?? '');
        $this->edit_no_kontainer = (string) ($this->pembelian->no_kontainer ?? '');
        $this->edit_no_polisi = (string) ($this->pembelian->no_polisi ?? '');
        $this->edit_tanggal_jatuh_tempo = $this->pembelian->tanggal_jatuh_tempo?->format('Y-m-d') ?? '';
        $this->edit_keterangan = (string) ($this->pembelian->keterangan ?? '');
        $this->showEditShippingModal = true;
    }

    public function closeEditShippingModal(): void
    {
        $this->showEditShippingModal = false;
        $this->resetValidation();
    }

    public function processEditShipping(UpdateShippingDocsAction $action): void
    {
        Gate::authorize('update', $this->pembelian);

        $this->validate([
            'edit_no_surat_jalan' => 'nullable|string|max:100',
            'edit_no_faktur_supplier' => 'nullable|string|max:100',
            'edit_no_kontainer' => 'nullable|string|max:100',
            'edit_no_polisi' => 'nullable|string|max:50',
            'edit_tanggal_jatuh_tempo' => 'nullable|date',
            'edit_keterangan' => 'nullable|string|max:500',
        ]);

        $action->execute($this->pembelian, [
            'no_surat_jalan' => $this->edit_no_surat_jalan,
            'no_faktur_supplier' => $this->edit_no_faktur_supplier,
            'no_kontainer' => $this->edit_no_kontainer,
            'no_polisi' => $this->edit_no_polisi,
            'tanggal_jatuh_tempo' => $this->edit_tanggal_jatuh_tempo,
            'keterangan' => $this->edit_keterangan,
        ], (int) auth()->id());

        $this->loadPembelian($this->pembelian->id);
        $this->showEditShippingModal = false;
        session()->flash('message', 'Dokumen logistik & jatuh tempo berhasil diperbarui!');
    }

    public function openCancelModal(): void
    {
        Gate::authorize('cancel', $this->pembelian);

        $this->cancel_alasan = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->resetValidation();
    }

    public function processCancel(CancelPembelianAction $action): void
    {
        Gate::authorize('cancel', $this->pembelian);

        $this->validate([
            'cancel_alasan' => 'required|string|min:3|max:255',
        ], [
            'cancel_alasan.required' => 'Alasan pembatalan wajib diisi.',
            'cancel_alasan.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        try {
            $isPreviouslyReceived = $this->pembelian->isReceived();
            $action->execute($this->pembelian, $this->cancel_alasan, (int) auth()->id());

            $this->loadPembelian($this->pembelian->id);
            $this->showCancelModal = false;

            $successMsg = $isPreviouslyReceived
                ? "Transaksi {$this->pembelian->no_pembelian} berhasil dibatalkan dan seluruh stok fisik telah ditarik kembali dari gudang."
                : "Pesanan PO {$this->pembelian->no_pembelian} berhasil dibatalkan.";

            session()->flash('message', $successMsg);
        } catch (InvalidArgumentException $e) {
            $this->addError('cancel_alasan', $e->getMessage());
        }
    }

    public function openDeleteModal(): void
    {
        Gate::authorize('delete', $this->pembelian);

        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
    }

    public function processDelete(DeletePembelianPOAction $action): mixed
    {
        Gate::authorize('delete', $this->pembelian);

        try {
            $noPembelian = $this->pembelian->no_pembelian;
            $action->execute($this->pembelian, (int) auth()->id());

            session()->flash('message', "Pesanan pembelian (PO) #{$noPembelian} berhasil dihapus permanen.");

            return redirect()->route('pembelian.index');
        } catch (InvalidArgumentException $e) {
            $this->showDeleteModal = false;
            session()->flash('error', $e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.purchasing.pembelian-detail');
    }
}
