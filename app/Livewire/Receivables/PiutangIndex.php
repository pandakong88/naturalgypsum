<?php

namespace App\Livewire\Receivables;

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Receivables\Models\Pembayaran;
use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Daftar Piutang Pelanggan')]
class PiutangIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $tipeFilter = '';

    public string $piutangFilter = 'berpiutang'; // 'berpiutang' | 'overlimit' | 'semua'

    public string $sortBy = 'total_sisa_piutang';

    public string $sortDirection = 'desc';

    public ?int $selectedPelangganId = null;

    public bool $isDetailModalOpen = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTipeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPiutangFilter(): void
    {
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $column === 'nama' ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    public function showCustomerInvoices(int $pelangganId): void
    {
        $this->selectedPelangganId = $pelangganId;
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal(): void
    {
        $this->isDetailModalOpen = false;
        $this->selectedPelangganId = null;
    }

    /**
     * Build WhatsApp reminder URL for unpaid invoices.
     *
     * @param  Collection<int, Penjualan>  $invoices
     */
    public function getWhatsAppUrl(?Pelanggan $customer, $invoices, float $totalSisa): ?string
    {
        if (! $customer || empty($customer->kontak)) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $customer->kontak);
        if (empty($phone)) {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        $formattedTotal = number_format($totalSisa, 0, ',', '.');
        $invoiceList = $invoices->take(5)->map(function ($inv) {
            $tgl = $inv->tanggal ? $inv->tanggal->format('d/m/Y') : '-';
            $sisa = number_format((float) $inv->sisa_piutang, 0, ',', '.');

            return "- {$inv->no_penjualan} ({$tgl}): Rp {$sisa}";
        })->implode("\n");

        if ($invoices->count() > 5) {
            $moreCount = $invoices->count() - 5;
            $invoiceList .= "\n- dan {$moreCount} faktur lainnya...";
        }

        $message = "Halo Yth. Bpk/Ibu *{$customer->nama}*,\n\n"
            ."Kami dari *Natural Gypsum & PVC* ingin mengonfirmasi saldo piutang berjalan Anda:\n"
            ."Total Tagihan: *Rp {$formattedTotal}*\n"
            ."Jumlah Faktur: *{$invoices->count()} faktur*\n\n"
            ."Rincian Faktur Belum Lunas:\n{$invoiceList}\n\n"
            ."Mohon informasi atau konfirmasi terkait rencana pembayaran tagihan tersebut.\n"
            .'Terima kasih atas kerjasamanya! 🙏';

        return 'https://api.whatsapp.com/send?phone='.$phone.'&text='.rawurlencode($message);
    }

    public function render(): View
    {
        abort_if(! auth()->user()->can('view_receivables') && ! auth()->user()->can('view_payments'), 403);

        // Stats
        $totalPiutangAktif = (float) Penjualan::whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->where('sisa_piutang', '>', 0)
            ->sum('sisa_piutang');

        $totalPelangganBerpiutang = Pelanggan::whereNull('deleted_at')
            ->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0))
            ->count();

        $totalFakturBelumLunas = Penjualan::whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->where('sisa_piutang', '>', 0)
            ->count();

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $totalBayarBulanIni = (float) Pembayaran::whereNull('deleted_at')
            ->whereDate('tanggal', '>=', $startOfMonth)
            ->sum('jumlah_bayar');

        // Customer Query
        $query = Pelanggan::query()
            ->withCount(['penjualans as total_penjualan_count' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')])
            ->withSum(['penjualans as total_penjualan_nominal' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'total_akhir')
            ->withSum(['penjualans as total_dibayar_nominal' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'total_dibayar')
            ->withSum(['penjualans as total_sisa_piutang' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'sisa_piutang');

        if (filled($this->search)) {
            $query->search($this->search);
        }

        if (filled($this->tipeFilter)) {
            $query->where('tipe', $this->tipeFilter);
        }

        if ($this->piutangFilter === 'berpiutang') {
            $query->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0));
        } elseif ($this->piutangFilter === 'overlimit') {
            $query->where('limit_piutang', '>', 0)
                ->whereRaw('(SELECT COALESCE(SUM(sisa_piutang), 0) FROM penjualan WHERE penjualan.pelanggan_id = pelanggan.id AND penjualan.deleted_at IS NULL AND penjualan.dibatalkan_at IS NULL) > pelanggan.limit_piutang');
        }

        if (in_array($this->sortBy, ['nama', 'total_penjualan_nominal', 'total_sisa_piutang', 'limit_piutang'], true)) {
            $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
            $query->orderBy($this->sortBy, $direction);
        } else {
            $query->orderByDesc('total_sisa_piutang');
        }

        $pelanggans = $query->paginate(15);

        // Selected customer invoices for modal
        $selectedCustomer = null;
        $selectedInvoices = collect();
        $selectedTotalSisa = 0.0;
        $whatsAppUrl = null;

        if ($this->selectedPelangganId) {
            $selectedCustomer = Pelanggan::find($this->selectedPelangganId);
            if ($selectedCustomer) {
                $selectedInvoices = Penjualan::where('pelanggan_id', $selectedCustomer->id)
                    ->whereNull('deleted_at')
                    ->whereNull('dibatalkan_at')
                    ->where('sisa_piutang', '>', 0)
                    ->orderBy('tanggal')
                    ->get();

                $selectedTotalSisa = (float) $selectedInvoices->sum('sisa_piutang');
                $whatsAppUrl = $this->getWhatsAppUrl($selectedCustomer, $selectedInvoices, $selectedTotalSisa);
            }
        }

        return view('livewire.receivables.piutang-index', [
            'pelanggans' => $pelanggans,
            'totalPiutangAktif' => $totalPiutangAktif,
            'totalPelangganBerpiutang' => $totalPelangganBerpiutang,
            'totalFakturBelumLunas' => $totalFakturBelumLunas,
            'totalBayarBulanIni' => $totalBayarBulanIni,
            'tipeOptions' => TipePelanggan::cases(),
            'selectedCustomer' => $selectedCustomer,
            'selectedInvoices' => $selectedInvoices,
            'selectedTotalSisa' => $selectedTotalSisa,
            'whatsAppUrl' => $whatsAppUrl,
        ]);
    }
}
