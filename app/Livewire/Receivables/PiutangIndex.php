<?php

namespace App\Livewire\Receivables;

use App\Domains\Administration\Models\ProfilPerusahaan;
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

    /**
     * Standar batas waktu tempo normal (hari) untuk pemborong/pelanggan Toko Natural Gypsum & PVC.
     */
    public const TEMPO_STANDAR_HARI = 14;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $tipeFilter = '';

    public string $piutangFilter = 'berpiutang'; // 'berpiutang' | 'overlimit' | 'semua'

    /**
     * Filter umur piutang (aging): '' | 'lancar' (<=14) | 'jatuh_tempo' (15-30) | 'menunggak' (31-60) | 'macet' (>60) | 'lewat_tempo' (>14)
     */
    public string $agingFilter = '';

    public string $sortBy = 'total_sisa_piutang';

    public string $sortDirection = 'desc';

    public ?int $selectedPelangganId = null;

    public bool $isDetailModalOpen = false;

    /**
     * Daftar ID faktur yang sedang dibuka rincian barang & histori cicilannya di dalam modal.
     *
     * @var array<int, int>
     */
    public array $expandedInvoiceIds = [];

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

    public function updatedAgingFilter(): void
    {
        if (filled($this->agingFilter) && $this->piutangFilter === 'semua') {
            $this->piutangFilter = 'berpiutang';
        }

        $this->resetPage();
    }

    public function setAgingBucket(string $bucket): void
    {
        $this->agingFilter = $this->agingFilter === $bucket ? '' : $bucket;

        if (filled($this->agingFilter) && $this->piutangFilter === 'semua') {
            $this->piutangFilter = 'berpiutang';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->tipeFilter = '';
        $this->piutangFilter = 'berpiutang';
        $this->agingFilter = '';
        $this->sortBy = 'total_sisa_piutang';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = in_array($column, ['nama', 'oldest_unpaid_date'], true) ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    public function showCustomerInvoices(int $pelangganId): void
    {
        $this->selectedPelangganId = $pelangganId;
        $this->isDetailModalOpen = true;
        $this->expandedInvoiceIds = [];
    }

    public function closeDetailModal(): void
    {
        $this->isDetailModalOpen = false;
        $this->selectedPelangganId = null;
        $this->expandedInvoiceIds = [];
    }

    public function toggleInvoiceDetail(int $invoiceId): void
    {
        if (in_array($invoiceId, $this->expandedInvoiceIds, true)) {
            $this->expandedInvoiceIds = array_values(array_diff($this->expandedInvoiceIds, [$invoiceId]));
        } else {
            $this->expandedInvoiceIds[] = $invoiceId;
        }
    }

    public function toggleAllInvoiceDetails(): void
    {
        if (! $this->selectedPelangganId) {
            return;
        }

        $allIds = Penjualan::where('pelanggan_id', $this->selectedPelangganId)
            ->whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->where('sisa_piutang', '>', 0)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($this->expandedInvoiceIds) === count($allIds)) {
            $this->expandedInvoiceIds = [];
        } else {
            $this->expandedInvoiceIds = $allIds;
        }
    }

    /**
     * Build WhatsApp reminder URL for unpaid invoices including official store bank accounts.
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

        $toko = ProfilPerusahaan::getSettings();
        $namaToko = $toko->nama_toko ?: 'Natural Gypsum & PVC';

        $formattedTotal = number_format($totalSisa, 0, ',', '.');
        $invoiceList = $invoices->take(6)->map(function ($inv) {
            $tgl = $inv->tanggal ? Carbon::parse($inv->tanggal)->format('d/m/Y') : '-';
            $jatuhTempo = $inv->tanggal ? Carbon::parse($inv->tanggal)->addDays(self::TEMPO_STANDAR_HARI)->format('d/m/Y') : '-';
            $sisa = number_format((float) $inv->sisa_piutang, 0, ',', '.');

            return "- {$inv->no_penjualan} (Tgl: {$tgl} | Jth Tempo: {$jatuhTempo}): Rp {$sisa}";
        })->implode("\n");

        if ($invoices->count() > 6) {
            $moreCount = $invoices->count() - 6;
            $invoiceList .= "\n- dan {$moreCount} faktur lainnya...";
        }

        $bankLines = '';
        if (! empty($toko->rekening_bank) && is_array($toko->rekening_bank)) {
            $validBanks = array_filter($toko->rekening_bank, fn ($b) => filled($b['nama_bank'] ?? null) && filled($b['nomor_rekening'] ?? null));
            if (! empty($validBanks)) {
                $bankFormatted = collect($validBanks)->map(function ($b) {
                    $bankName = strtoupper((string) ($b['nama_bank'] ?? ''));
                    $noRek = (string) ($b['nomor_rekening'] ?? '');
                    $atasNama = (string) ($b['atas_nama'] ?? '');

                    return "• *{$bankName}*: {$noRek}".($atasNama ? " (a.n {$atasNama})" : '');
                })->implode("\n");

                $bankLines = "\n\nPembayaran transfer dapat melalui rekening resmi kami:\n{$bankFormatted}";
            }
        }

        $message = "Halo Yth. Bpk/Ibu *{$customer->nama}*,\n\n"
            ."Kami dari *{$namaToko}* ingin mengonfirmasi saldo piutang berjalan Anda (Tempo standar ".self::TEMPO_STANDAR_HARI." hari):\n"
            ."Total Sisa Tagihan: *Rp {$formattedTotal}*\n"
            ."Jumlah Faktur Belum Lunas: *{$invoices->count()} faktur*\n\n"
            ."Rincian Faktur Tertunggak:\n{$invoiceList}"
            .$bankLines
            ."\n\nMohon informasi atau konfirmasi terkait rencana pembayaran tagihan tersebut.\n"
            .'Terima kasih atas kerjasamanya! 🙏';

        return 'https://api.whatsapp.com/send?phone='.$phone.'&text='.rawurlencode($message);
    }

    public function render(): View
    {
        abort_if(! auth()->user()->can('view_receivables') && ! auth()->user()->can('view_payments'), 403);

        $today = Carbon::today();
        $date14DaysAgo = $today->copy()->subDays(self::TEMPO_STANDAR_HARI)->toDateString();
        $date30DaysAgo = $today->copy()->subDays(30)->toDateString();
        $date60DaysAgo = $today->copy()->subDays(60)->toDateString();

        // Base active unpaid sales query
        $unpaidBaseQuery = Penjualan::whereNull('deleted_at')
            ->whereNull('dibatalkan_at')
            ->where('sisa_piutang', '>', 0);

        // Main KPI Stats
        $totalPiutangAktif = (float) (clone $unpaidBaseQuery)->sum('sisa_piutang');

        $totalPelangganBerpiutang = Pelanggan::whereNull('deleted_at')
            ->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0))
            ->count();

        $totalFakturBelumLunas = (clone $unpaidBaseQuery)->count();

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $totalBayarBulanIni = (float) Pembayaran::whereNull('deleted_at')
            ->whereDate('tanggal', '>=', $startOfMonth)
            ->sum('jumlah_bayar');

        // 14-Day Standard Aging Buckets Breakdown
        $agingBuckets = [
            'lancar' => [
                'nominal' => (float) (clone $unpaidBaseQuery)->whereDate('tanggal', '>=', $date14DaysAgo)->sum('sisa_piutang'),
                'count' => (int) (clone $unpaidBaseQuery)->whereDate('tanggal', '>=', $date14DaysAgo)->count(),
            ],
            'jatuh_tempo' => [
                'nominal' => (float) (clone $unpaidBaseQuery)->whereDate('tanggal', '<', $date14DaysAgo)->whereDate('tanggal', '>=', $date30DaysAgo)->sum('sisa_piutang'),
                'count' => (int) (clone $unpaidBaseQuery)->whereDate('tanggal', '<', $date14DaysAgo)->whereDate('tanggal', '>=', $date30DaysAgo)->count(),
            ],
            'menunggak' => [
                'nominal' => (float) (clone $unpaidBaseQuery)->whereDate('tanggal', '<', $date30DaysAgo)->whereDate('tanggal', '>=', $date60DaysAgo)->sum('sisa_piutang'),
                'count' => (int) (clone $unpaidBaseQuery)->whereDate('tanggal', '<', $date30DaysAgo)->whereDate('tanggal', '>=', $date60DaysAgo)->count(),
            ],
            'macet' => [
                'nominal' => (float) (clone $unpaidBaseQuery)->whereDate('tanggal', '<', $date60DaysAgo)->sum('sisa_piutang'),
                'count' => (int) (clone $unpaidBaseQuery)->whereDate('tanggal', '<', $date60DaysAgo)->count(),
            ],
        ];

        $totalPiutangLewatTempo = $agingBuckets['jatuh_tempo']['nominal']
            + $agingBuckets['menunggak']['nominal']
            + $agingBuckets['macet']['nominal'];

        $totalFakturLewatTempo = $agingBuckets['jatuh_tempo']['count']
            + $agingBuckets['menunggak']['count']
            + $agingBuckets['macet']['count'];

        // Customer Query with focused unpaid metrics + oldest invoice aging + last payment date
        $query = Pelanggan::query()
            ->withCount([
                'penjualans as total_penjualan_count' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at'),
                'penjualans as unpaid_penjualan_count' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0),
            ])
            ->withSum(['penjualans as total_penjualan_nominal' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'total_akhir')
            ->withSum(['penjualans as total_dibayar_nominal' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'total_dibayar')
            ->withSum(['penjualans as unpaid_penjualan_nominal' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)], 'total_akhir')
            ->withSum(['penjualans as unpaid_dibayar_nominal' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)], 'total_dibayar')
            ->withSum(['penjualans as total_sisa_piutang' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')], 'sisa_piutang')
            ->withMin(['penjualans as oldest_unpaid_date' => fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)], 'tanggal')
            ->withMax(['pembayarans as last_payment_date' => fn ($q) => $q->whereNull('deleted_at')], 'tanggal');

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

        // Filter by 14-day aging tier (based on customer's unpaid invoices)
        if ($this->agingFilter === 'lancar') {
            $query->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)->whereDate('tanggal', '>=', $date14DaysAgo))
                ->whereDoesntHave('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)->whereDate('tanggal', '<', $date14DaysAgo));
        } elseif ($this->agingFilter === 'jatuh_tempo') {
            $query->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)->whereDate('tanggal', '<', $date14DaysAgo)->whereDate('tanggal', '>=', $date30DaysAgo));
        } elseif ($this->agingFilter === 'menunggak') {
            $query->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)->whereDate('tanggal', '<', $date30DaysAgo)->whereDate('tanggal', '>=', $date60DaysAgo));
        } elseif ($this->agingFilter === 'macet') {
            $query->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)->whereDate('tanggal', '<', $date60DaysAgo));
        } elseif ($this->agingFilter === 'lewat_tempo') {
            $query->whereHas('penjualans', fn ($q) => $q->whereNull('deleted_at')->whereNull('dibatalkan_at')->where('sisa_piutang', '>', 0)->whereDate('tanggal', '<', $date14DaysAgo));
        }

        if (in_array($this->sortBy, ['nama', 'total_penjualan_nominal', 'unpaid_penjualan_nominal', 'total_sisa_piutang', 'limit_piutang', 'oldest_unpaid_date'], true)) {
            $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
            $query->orderBy($this->sortBy, $direction);
        } else {
            $query->orderByDesc('total_sisa_piutang');
        }

        $pelanggans = $query->paginate(15);

        // Selected customer invoices for modal (with project items, warehouse, and payment history!)
        $selectedCustomer = null;
        $selectedInvoices = collect();
        $selectedTotalSisa = 0.0;
        $whatsAppUrl = null;

        if ($this->selectedPelangganId) {
            $selectedCustomer = Pelanggan::withMax(['pembayarans as last_payment_date' => fn ($q) => $q->whereNull('deleted_at')], 'tanggal')
                ->find($this->selectedPelangganId);

            if ($selectedCustomer) {
                $selectedInvoices = Penjualan::where('pelanggan_id', $selectedCustomer->id)
                    ->whereNull('deleted_at')
                    ->whereNull('dibatalkan_at')
                    ->where('sisa_piutang', '>', 0)
                    ->with([
                        'details.produk.merek',
                        'gudang',
                        'alokasiPembayarans' => fn ($q) => $q->whereHas('pembayaran', fn ($pq) => $pq->whereNull('deleted_at')),
                        'alokasiPembayarans.pembayaran.user',
                    ])
                    ->orderBy('tanggal')
                    ->orderBy('id')
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
            'agingBuckets' => $agingBuckets,
            'totalPiutangLewatTempo' => $totalPiutangLewatTempo,
            'totalFakturLewatTempo' => $totalFakturLewatTempo,
            'tempoStandarHari' => self::TEMPO_STANDAR_HARI,
            'tipeOptions' => TipePelanggan::cases(),
            'selectedCustomer' => $selectedCustomer,
            'selectedInvoices' => $selectedInvoices,
            'selectedTotalSisa' => $selectedTotalSisa,
            'whatsAppUrl' => $whatsAppUrl,
        ]);
    }
}
