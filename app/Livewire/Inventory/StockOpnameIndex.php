<?php

namespace App\Livewire\Inventory;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\DetailStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Stok Opname')]
class StockOpnameIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $filterGudang = '';

    public string $filterStatus = '';

    public string $startDate = '';

    public string $endDate = '';

    public int $perPage = 15;

    public bool $isQuickModalOpen = false;

    public ?StockOpname $selectedStockOpname = null;

    public bool $isDeleteDraftModalOpen = false;

    public ?StockOpname $draftToDelete = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGudang(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterGudang',
            'filterStatus',
            'startDate',
            'endDate',
        ]);
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return filled($this->search)
            || filled($this->filterGudang)
            || filled($this->filterStatus)
            || filled($this->startDate)
            || filled($this->endDate);
    }

    public function openQuickModal(int $id): void
    {
        $this->selectedStockOpname = StockOpname::with([
            'gudang',
            'user',
            'dibatalkanBy',
            'details.produk.kategori',
            'details.produk.merek',
        ])->findOrFail($id);

        $this->isQuickModalOpen = true;
    }

    public function closeQuickModal(): void
    {
        $this->isQuickModalOpen = false;
        $this->selectedStockOpname = null;
    }

    public function promptDeleteDraft(int $id): void
    {
        $this->draftToDelete = StockOpname::with(['gudang', 'details'])->findOrFail($id);
        $this->isDeleteDraftModalOpen = true;
    }

    public function closeDeleteDraftModal(): void
    {
        $this->isDeleteDraftModalOpen = false;
        $this->draftToDelete = null;
    }

    public function executeDeleteDraft(): void
    {
        if (! $this->draftToDelete) {
            return;
        }

        Gate::authorize('delete', $this->draftToDelete);

        if ($this->draftToDelete->status !== StatusStockOpname::Draft) {
            session()->flash('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
            $this->closeDeleteDraftModal();

            return;
        }

        $noOpname = $this->draftToDelete->no_stock_opname;

        DB::transaction(function () {
            $this->draftToDelete->details()->delete();
            $this->draftToDelete->delete();
        });

        if ($this->selectedStockOpname && $this->selectedStockOpname->id === $this->draftToDelete->id) {
            $this->closeQuickModal();
        }

        $this->closeDeleteDraftModal();
        session()->flash('success', 'Draft Stok Opname '.$noOpname.' berhasil dihapus secara permanen.');
    }

    /**
     * @return Builder<StockOpname>
     */
    protected function getBaseFilterQuery(): Builder
    {
        $query = StockOpname::query();

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_stock_opname', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('gudang', fn ($gq) => $gq->where('nama_gudang', 'like', "%{$search}%"))
                    ->orWhereHas('details.produk', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode_katalog', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->filterGudang)) {
            $query->where('gudang_id', $this->filterGudang);
        }

        if (filled($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        return $query;
    }

    /**
     * @return Builder<StockOpname>
     */
    protected function getFilteredQuery(): Builder
    {
        return $this->getBaseFilterQuery()
            ->with(['gudang', 'user'])
            ->withCount('details');
    }

    /**
     * @return array{
     *     totalDokumen: int,
     *     totalSelesai: int,
     *     totalDraft: int,
     *     totalItemSelisih: int,
     *     netSelisih: float
     * }
     */
    protected function calculateMetrics(): array
    {
        $baseQuery = $this->getBaseFilterQuery();

        $opnameIds = (clone $baseQuery)->pluck('id');
        $totalDokumen = $opnameIds->count();
        $totalSelesai = (clone $baseQuery)->where('status', StatusStockOpname::Selesai)->count();
        $totalDraft = (clone $baseQuery)->where('status', StatusStockOpname::Draft)->count();

        $detailQuery = DetailStockOpname::whereIn('stock_opname_id', $opnameIds);
        $totalItemSelisih = (int) (clone $detailQuery)->where('selisih', '!=', 0)->count();
        $netSelisih = (float) (clone $detailQuery)->sum('selisih');

        return [
            'totalDokumen' => $totalDokumen,
            'totalSelesai' => $totalSelesai,
            'totalDraft' => $totalDraft,
            'totalItemSelisih' => $totalItemSelisih,
            'netSelisih' => $netSelisih,
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('viewAny', StockOpname::class);

        $opnames = $this->getBaseFilterQuery()
            ->with(['gudang', 'user', 'details'])
            ->latest('tanggal')
            ->latest('id')
            ->get();

        $filename = 'Laporan_Stok_Opname_'.now()->format('Y-m-d_His').'.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), 'export_so_').'.xlsx';

        $options = new Options;
        $writer = new XlsxWriter($options);
        $writer->openToFile($tempPath);

        // Sheet 1: Daftar Dokumen Opname
        $sheet1 = $writer->getCurrentSheet();
        $sheet1->setName('Daftar Stok Opname');
        $sheet1->setColumnWidth(6, 1);   // NO
        $sheet1->setColumnWidth(20, 2);  // NO. DOKUMEN
        $sheet1->setColumnWidth(14, 3);  // TANGGAL
        $sheet1->setColumnWidth(24, 4);  // GUDANG
        $sheet1->setColumnWidth(16, 5);  // STATUS
        $sheet1->setColumnWidth(22, 6);  // PETUGAS
        $sheet1->setColumnWidth(14, 7);  // TOTAL ITEM
        $sheet1->setColumnWidth(14, 8);  // ITEM COCOK
        $sheet1->setColumnWidth(16, 9);  // ITEM BERSELISIH
        $sheet1->setColumnWidth(14, 10); // NET SELISIH
        $sheet1->setColumnWidth(35, 11); // CATATAN

        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('1E3A8A')
            ->withFontSize(11);

        $headers = [
            'NO',
            'NO. DOKUMEN',
            'TANGGAL',
            'GUDANG',
            'STATUS',
            'PETUGAS',
            'TOTAL ITEM',
            'ITEM COCOK',
            'ITEM BERSELISIH',
            'NET SELISIH',
            'CATATAN',
        ];

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        $dataStyle = (new Style)->withFontSize(10);

        foreach ($opnames as $index => $so) {
            $totalDetails = $so->details->count();
            $cocokCount = $so->details->where('selisih', 0)->count();
            $diffCount = $so->details->where('selisih', '!=', 0)->count();
            $netDiff = (float) $so->details->sum('selisih');

            $rowValues = [
                $index + 1,
                $so->no_stock_opname,
                $so->tanggal ? $so->tanggal->format('d/m/Y') : '-',
                $so->gudang->nama_gudang ?? '-',
                $so->status->label(),
                $so->user->name ?? 'Sistem',
                $totalDetails,
                $cocokCount,
                $diffCount,
                $netDiff,
                $so->catatan ?? '-',
            ];

            $writer->addRow(Row::fromValuesWithStyle($rowValues, $dataStyle));
        }

        // Sheet 2: Ringkasan & KPI
        $sheet2 = $writer->addNewSheetAndMakeItCurrent();
        $sheet2->setName('Ringkasan & KPI');
        $sheet2->setColumnWidth(28, 1);
        $sheet2->setColumnWidth(48, 2);

        $sectionStyle = (new Style)->withFontBold(true)->withFontSize(11)->withBackgroundColor('E0E7FF');
        $headerTableStyle = (new Style)->withFontBold(true)->withFontSize(10)->withFontColor(Color::WHITE)->withBackgroundColor('1E3A8A');
        $bodyStyle = (new Style)->withFontSize(10);

        $writer->addRow(Row::fromValuesWithStyle(['RINGKASAN AKTIVITAS STOK OPNAME', ''], $sectionStyle));
        $writer->addRow(Row::fromValuesWithStyle(['METRIK / PARAMETER', 'NILAI / KETERANGAN'], $headerTableStyle));

        $totalDokumen = $opnames->count();
        $totalSelesai = $opnames->where('status', StatusStockOpname::Selesai)->count();
        $totalDraft = $opnames->where('status', StatusStockOpname::Draft)->count();
        $totalBatal = $opnames->where('status', StatusStockOpname::Dibatalkan)->count();

        $namaGudangFilter = 'Semua Gudang';
        if ($this->filterGudang) {
            $gudangObj = Gudang::find($this->filterGudang);
            $namaGudangFilter = $gudangObj ? $gudangObj->nama_gudang : 'ID #'.$this->filterGudang;
        }

        $kpiRows = [
            ['Total Dokumen Tereksport', $totalDokumen.' Dokumen'],
            ['Dokumen Selesai', $totalSelesai.' Dokumen'],
            ['Dokumen Masih Draft', $totalDraft.' Dokumen'],
            ['Dokumen Dibatalkan', $totalBatal.' Dokumen'],
            ['Filter Gudang', $namaGudangFilter],
            ['Filter Status', $this->filterStatus ? (StatusStockOpname::tryFrom($this->filterStatus)?->label() ?? $this->filterStatus) : 'Semua Status'],
            ['Filter Tanggal', ($this->startDate ?: 'Awal').' s/d '.($this->endDate ?: 'Sekarang')],
            ['Waktu Ekspor', now()->format('d/m/Y H:i:s').' WIB'],
            ['Operator Ekspor', auth()->user()->name ?? 'Staff'],
        ];

        foreach ($kpiRows as $kRow) {
            $writer->addRow(Row::fromValuesWithStyle($kRow, $bodyStyle));
        }

        $writer->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', StockOpname::class);

        $query = $this->getFilteredQuery();
        $stockOpnames = $query->latest('tanggal')->latest('id')->paginate($this->perPage);

        return view('livewire.inventory.stock-opname-index', [
            'stockOpnames' => $stockOpnames,
            'metrics' => $this->calculateMetrics(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'statuses' => StatusStockOpname::cases(),
        ]);
    }
}
