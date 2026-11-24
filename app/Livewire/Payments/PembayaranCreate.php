<?php

namespace App\Livewire\Payments;

use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Actions\CreatePembayaranAction;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Catat Pembayaran')]
class PembayaranCreate extends Component
{
    public ?int $pelanggan_id = null;

    public string $pelangganSearchQuery = '';

    public bool $onlyWithDebt = true;

    public string $tanggal = '';

    public float|int|string|null $jumlah_bayar = null;

    public string $metode = 'tunai';

    public ?string $bank_tujuan = null;

    public ?string $nomor_referensi = null;

    public ?string $catatan = null;

    /**
     * @var array<int, array{
     *     penjualan_id: int,
     *     no_penjualan: string,
     *     tanggal: string,
     *     days_overdue: int,
     *     total_akhir: float,
     *     total_dibayar: float,
     *     sisa_piutang: float,
     *     jumlah_dialokasikan: float|int|string
     * }>
     */
    public array $allocations = [];

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'pelanggan_id' => ['required', 'integer', 'exists:pelanggan,id'],
            'tanggal' => ['required', 'date'],
            'jumlah_bayar' => ['required', 'numeric', 'gt:0'],
            'metode' => ['required', 'string', 'in:tunai,transfer,lainnya'],
            'bank_tujuan' => ['nullable', 'string', 'max:50'],
            'nomor_referensi' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],
            'allocations.*.jumlah_dialokasikan' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'pelanggan_id.required' => 'Pilih pelanggan terlebih dahulu.',
            'tanggal.required' => 'Tanggal pembayaran wajib diisi.',
            'jumlah_bayar.required' => 'Jumlah pembayaran wajib diisi.',
            'jumlah_bayar.gt' => 'Jumlah pembayaran harus lebih besar dari 0.',
            'metode.required' => 'Pilih metode pembayaran.',
            'allocations.*.jumlah_dialokasikan.min' => 'Alokasi pembayaran tidak boleh negatif.',
        ];
    }

    public function mount(?int $pelanggan_id = null, ?int $penjualan_id = null): void
    {
        Gate::authorize('create', Pembayaran::class);

        $this->tanggal = Carbon::now()->toDateString();

        if ($pelanggan_id) {
            $this->selectPelanggan($pelanggan_id);
        } elseif ($penjualan_id) {
            $penjualan = Penjualan::find($penjualan_id);
            if ($penjualan) {
                $this->selectPelanggan($penjualan->pelanggan_id);
                // Pre-fill payment amount with invoice balance if available
                $this->jumlah_bayar = (float) $penjualan->sisa_piutang;
                foreach ($this->allocations as $index => $alloc) {
                    if ($alloc['penjualan_id'] === $penjualan->id) {
                        $this->allocations[$index]['jumlah_dialokasikan'] = (float) $penjualan->sisa_piutang;
                    }
                }
            }
        }
    }

    public function selectPelanggan(int $id): void
    {
        $this->pelanggan_id = $id;
        $this->pelangganSearchQuery = '';
        $this->loadCustomerInvoices();
    }

    public function clearPelanggan(): void
    {
        $this->pelanggan_id = null;
        $this->allocations = [];
        $this->pelangganSearchQuery = '';
    }

    public function updatedPelangganId(): void
    {
        $this->loadCustomerInvoices();
    }

    public function loadCustomerInvoices(): void
    {
        $this->allocations = [];

        if (! $this->pelanggan_id) {
            return;
        }

        $invoices = Penjualan::where('pelanggan_id', $this->pelanggan_id)
            ->whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->where('sisa_piutang', '>', 0)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $now = Carbon::now();
        foreach ($invoices as $inv) {
            $daysOverdue = (int) Carbon::parse($inv->tanggal)->diffInDays($now);
            $this->allocations[] = [
                'penjualan_id' => $inv->id,
                'no_penjualan' => $inv->no_penjualan,
                'tanggal' => $inv->tanggal->format('d/m/Y'),
                'days_overdue' => $daysOverdue,
                'total_akhir' => (float) $inv->total_akhir,
                'total_dibayar' => (float) $inv->total_dibayar,
                'sisa_piutang' => (float) $inv->sisa_piutang,
                'jumlah_dialokasikan' => 0,
            ];
        }
    }

    public function allocateMax(int $index): void
    {
        if (! isset($this->allocations[$index])) {
            return;
        }

        $sisaPiutang = (float) $this->allocations[$index]['sisa_piutang'];
        $currentAllocatedThisRow = (float) ($this->allocations[$index]['jumlah_dialokasikan'] ?? 0);
        $totalBayar = (float) ($this->jumlah_bayar ?: 0);

        // Smart behavior: If payment amount is empty/0, set full invoice and auto-fill payment amount
        if ($totalBayar <= 0.00) {
            $this->allocations[$index]['jumlah_dialokasikan'] = $sisaPiutang;
            $this->jumlah_bayar = $this->getTotalDialokasikanProperty();

            return;
        }

        $totalAllocatedOthers = $this->getTotalDialokasikanProperty() - $currentAllocatedThisRow;
        $remainingPaymentCapacity = max(0.00, $totalBayar - $totalAllocatedOthers);

        // Fill min of invoice balance and available payment capacity
        $fillAmount = min($sisaPiutang, $remainingPaymentCapacity);
        $this->allocations[$index]['jumlah_dialokasikan'] = round($fillAmount, 2);
    }

    /**
     * Auto allocate payment amount using FIFO (First In, First Out / Oldest Invoice First).
     */
    public function autoAllocateFifo(): void
    {
        $totalBayar = (float) ($this->jumlah_bayar ?: 0);
        if ($totalBayar <= 0.00) {
            return;
        }

        $remainingCapacity = $totalBayar;
        foreach ($this->allocations as $index => $alloc) {
            $sisa = (float) $alloc['sisa_piutang'];
            $allocAmount = min($sisa, $remainingCapacity);
            $this->allocations[$index]['jumlah_dialokasikan'] = round($allocAmount, 2);
            $remainingCapacity = max(0.00, round($remainingCapacity - $allocAmount, 2));
        }
    }

    /**
     * Auto allocate 100% to all outstanding invoices and set total payment amount accordingly.
     */
    public function payAllInvoices(): void
    {
        $totalDebt = 0.00;
        foreach ($this->allocations as $index => $alloc) {
            $sisa = (float) $alloc['sisa_piutang'];
            $this->allocations[$index]['jumlah_dialokasikan'] = $sisa;
            $totalDebt += $sisa;
        }

        $this->jumlah_bayar = round($totalDebt, 2);
    }

    /**
     * Reset all invoice allocation inputs to zero.
     */
    public function resetAllocations(): void
    {
        foreach ($this->allocations as $index => $alloc) {
            $this->allocations[$index]['jumlah_dialokasikan'] = 0;
        }
    }

    public function __get($property): mixed
    {
        if ($property === 'jumlah_bayar') {
            return $this->jumlah_bayar = null;
        }

        return parent::__get($property);
    }

    #[Computed]
    public function totalDialokasikan(): float
    {
        $total = 0.00;
        foreach ($this->allocations as $alloc) {
            $total += (float) ($alloc['jumlah_dialokasikan'] ?? 0);
        }

        return round($total, 2);
    }

    #[Computed]
    public function sisaBelumDialokasikan(): float
    {
        $totalBayar = (float) ($this->jumlah_bayar ?: 0);

        return max(0.00, round($totalBayar - $this->totalDialokasikan(), 2));
    }

    #[Computed]
    public function hasUnallocatedWithRemainingDebt(): bool
    {
        $totalDebt = 0.00;
        foreach ($this->allocations as $alloc) {
            $totalDebt += (float) ($alloc['sisa_piutang'] ?? 0);
        }

        $remainingUnpaidDebt = max(0.00, round($totalDebt - $this->totalDialokasikan(), 2));

        return $this->sisaBelumDialokasikan() > 0.00 && $remainingUnpaidDebt > 0.00;
    }

    public function getTotalDialokasikanProperty(): float
    {
        return $this->totalDialokasikan();
    }

    public function getSisaBelumDialokasikanProperty(): float
    {
        return $this->sisaBelumDialokasikan();
    }

    public function save(CreatePembayaranAction $action): mixed
    {
        Gate::authorize('create', Pembayaran::class);

        $this->validate();

        $jumlahBayar = round((float) $this->jumlah_bayar, 2);
        $totalDialokasikan = $this->getTotalDialokasikanProperty();

        if ($totalDialokasikan > $jumlahBayar) {
            $this->addError('allocations', 'Total alokasi (Rp '.number_format($totalDialokasikan, 2, ',', '.').') tidak boleh melebihi jumlah bayar (Rp '.number_format($jumlahBayar, 2, ',', '.').').');

            return null;
        }

        // Opsi 2 (Cerdas): Seluruh sisa pembayaran harus dialokasikan jika pelanggan masih memiliki faktur tertunggak
        $totalDebt = 0.00;
        foreach ($this->allocations as $alloc) {
            $totalDebt += (float) ($alloc['sisa_piutang'] ?? 0);
        }
        $remainingUnpaidDebt = max(0.00, round($totalDebt - $totalDialokasikan, 2));
        $unallocatedPayment = $this->sisaBelumDialokasikan();

        if ($unallocatedPayment > 0.00 && $remainingUnpaidDebt > 0.00) {
            $this->addError('allocations', 'Masih terdapat dana sebesar Rp '.number_format($unallocatedPayment, 2, ',', '.').' yang belum dialokasikan ke faktur tertunggak (tersisa piutang Rp '.number_format($remainingUnpaidDebt, 2, ',', '.').'). Silakan alokasikan seluruhnya atau gunakan tombol "Alokasi Otomatis (FIFO)".');

            return null;
        }

        $allocData = [];
        foreach ($this->allocations as $alloc) {
            $amount = round((float) ($alloc['jumlah_dialokasikan'] ?? 0), 2);
            if ($amount > 0) {
                if ($amount > (float) $alloc['sisa_piutang']) {
                    $this->addError('allocations', "Alokasi untuk faktur {$alloc['no_penjualan']} melebihi sisa piutang faktur tersebut.");

                    return null;
                }

                $allocData[] = [
                    'penjualan_id' => (int) $alloc['penjualan_id'],
                    'jumlah_dialokasikan' => $amount,
                ];
            }
        }

        // Format catatan if bank transfer details exist
        $fullCatatan = trim($this->catatan);
        if ($this->metode === 'transfer') {
            $bankNotes = [];
            if (filled($this->bank_tujuan)) {
                $bankNotes[] = 'Bank: '.trim($this->bank_tujuan);
            }
            if (filled($this->nomor_referensi)) {
                $bankNotes[] = 'Ref: '.trim($this->nomor_referensi);
            }
            if (! empty($bankNotes)) {
                $prefix = '['.implode(' | ', $bankNotes).']';
                $fullCatatan = $fullCatatan ? $prefix.' '.$fullCatatan : $prefix;
            }
        }

        $pembayaran = $action->execute([
            'pelanggan_id' => (int) $this->pelanggan_id,
            'tanggal' => $this->tanggal,
            'jumlah_bayar' => $jumlahBayar,
            'metode' => $this->metode,
            'catatan' => $fullCatatan,
            'alokasi' => $allocData,
        ]);

        session()->flash('message', "Pembayaran {$pembayaran->no_pembayaran} berhasil dicatat.");

        return redirect()->route('pembayaran.show', $pembayaran->id);
    }

    public function render(): View
    {
        Gate::authorize('create', Pembayaran::class);

        // Searchable customers
        $pelangganQuery = Pelanggan::query()->whereNull('deleted_at');

        if ($this->onlyWithDebt) {
            $pelangganQuery->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0));
        }

        if (filled($this->pelangganSearchQuery)) {
            $pelangganQuery->search($this->pelangganSearchQuery);
        }

        $searchResults = filled($this->pelangganSearchQuery)
            ? $pelangganQuery->withSum(['penjualans as total_sisa_piutang' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'sisa_piutang')
                ->withCount(['penjualans as total_faktur_piutang' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)])
                ->orderBy('nama')
                ->limit(10)
                ->get()
            : collect();

        $selectedPelanggan = null;
        if ($this->pelanggan_id) {
            $selectedPelanggan = Pelanggan::whereNull('deleted_at')
                ->withSum(['penjualans as total_sisa_piutang' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'sisa_piutang')
                ->withCount(['penjualans as total_faktur_piutang' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)])
                ->find($this->pelanggan_id);
        }

        return view('livewire.payments.pembayaran-create', [
            'searchResults' => $searchResults,
            'selectedPelanggan' => $selectedPelanggan,
            'metodeOptions' => MetodePembayaran::cases(),
            'bankOptions' => ['BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'CIMB Niaga', 'Lainnya'],
        ]);
    }
}
