<?php

namespace App\Livewire\Inventory;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Inventory\Actions\CancelTransferGudangAction;
use App\Domains\Inventory\Models\DetailTransferGudang;
use App\Domains\Inventory\Models\TransferGudang;
use Exception;
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
use OpenSpout\Writer\Xlsx\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Transfer Antar Gudang')]
class TransferIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $gudangAsalFilter = '';

    public string $gudangTujuanFilter = '';

    public string $startDate = '';

    public string $endDate = '';

    public int $perPage = 15;

    // Quick preview modal state
    public bool $isQuickModalOpen = false;

    public ?TransferGudang $selectedTransfer = null;

    // Cancellation modal state
    public bool $isCancelModalOpen = false;

    public ?TransferGudang $transferToCancel = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGudangAsalFilter(): void
    {
        $this->resetPage();
    }

    public function updatedGudangTujuanFilter(): void
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
            'gudangAsalFilter',
            'gudangTujuanFilter',
            'startDate',
            'endDate',
        ]);
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return filled($this->search)
            || filled($this->gudangAsalFilter)
            || filled($this->gudangTujuanFilter)
            || filled($this->startDate)
            || filled($this->endDate);
    }

    public function openQuickModal(int $id): void
    {
        $this->selectedTransfer = TransferGudang::with([
            'gudangAsal',
            'gudangTujuan',
            'user',
            'details.produk.kategori',
            'details.produk.merek',
        ])->findOrFail($id);

        $this->isQuickModalOpen = true;
    }

    public function closeQuickModal(): void
    {
        $this->isQuickModalOpen = false;
        $this->selectedTransfer = null;
    }

    public function cancelTransfer(int $id, CancelTransferGudangAction $action): void
    {
        $transfer = TransferGudang::findOrFail($id);
        Gate::authorize('delete', $transfer);

        try {
            $action->execute($transfer);
            session()->flash('message', 'Transfer '.$transfer->no_transfer.' berhasil dibatalkan dan stok dikembalikan.');

            if ($this->selectedTransfer && $this->selectedTransfer->id === $id) {
                $this->closeQuickModal();
            }
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan transfer: '.$e->getMessage());
        }
    }

    public function promptCancelTransfer(int $id): void
    {
        $this->transferToCancel = TransferGudang::with([
            'gudangAsal',
            'gudangTujuan',
            'details.produk',
        ])->findOrFail($id);
        $this->isCancelModalOpen = true;
    }

    public function closeCancelModal(): void
    {
        $this->isCancelModalOpen = false;
        $this->transferToCancel = null;
    }

    public function executeCancelTransfer(CancelTransferGudangAction $action): void
    {
        if (! $this->transferToCancel) {
            return;
        }

        $transfer = $this->transferToCancel;
        Gate::authorize('delete', $transfer);

        try {
            $action->execute($transfer);
            session()->flash('message', 'Transfer '.$transfer->no_transfer.' berhasil dibatalkan dan stok telah dikembalikan.');

            if ($this->selectedTransfer && $this->selectedTransfer->id === $transfer->id) {
                $this->closeQuickModal();
            }
            $this->closeCancelModal();
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan transfer: '.$e->getMessage());
            $this->closeCancelModal();
        }
    }

    /**
     * @return Builder<TransferGudang>
     */
    protected function getBaseFilterQuery(): Builder
    {
        /** @var Builder<TransferGudang> $query */
        $query = TransferGudang::query();

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_transfer', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('gudangAsal', function ($gq) use ($search) {
                        $gq->where('nama_gudang', 'like', "%{$search}%");
                    })
                    ->orWhereHas('gudangTujuan', function ($gq) use ($search) {
                        $gq->where('nama_gudang', 'like', "%{$search}%");
                    })
                    ->orWhereHas('details.produk', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode_katalog', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->gudangAsalFilter)) {
            $query->where('gudang_asal_id', $this->gudangAsalFilter);
        }

        if (filled($this->gudangTujuanFilter)) {
            $query->where('gudang_tujuan_id', $this->gudangTujuanFilter);
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
     * @return Builder<TransferGudang>
     */
    protected function getFilteredQuery(): Builder
    {
        return $this->getBaseFilterQuery()
            ->with(['gudangAsal', 'gudangTujuan', 'user', 'details.produk']);
    }

    /**
     * @return array{
     *     totalTransfers: int,
     *     totalItems: int,
     *     totalQuantity: float,
     *     mostActiveRoute: string
     * }
     */
    protected function calculateMetrics(): array
    {
        $baseQuery = $this->getBaseFilterQuery();

        $transferIds = (clone $baseQuery)->pluck('id');
        $totalTransfers = $transferIds->count();

        $detailQuery = DetailTransferGudang::whereIn('transfer_gudang_id', $transferIds);
        $totalItems = (int) (clone $detailQuery)->distinct('produk_id')->count('produk_id');
        $totalQuantity = (float) (clone $detailQuery)->sum('jumlah');

        // Find most active route: (gudang_asal_id -> gudang_tujuan_id)
        $route = (clone $baseQuery)
            ->select('gudang_asal_id', 'gudang_tujuan_id', DB::raw('count(*) as route_count'))
            ->groupBy('gudang_asal_id', 'gudang_tujuan_id')
            ->orderByDesc('route_count')
            ->with(['gudangAsal', 'gudangTujuan'])
            ->first();

        $mostActiveRoute = '-';
        if ($route && $route->gudangAsal && $route->gudangTujuan) {
            $mostActiveRoute = $route->gudangAsal->nama_gudang.' → '.$route->gudangTujuan->nama_gudang;
        }

        return [
            'totalTransfers' => $totalTransfers,
            'totalItems' => $totalItems,
            'totalQuantity' => $totalQuantity,
            'mostActiveRoute' => $mostActiveRoute,
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('viewAny', TransferGudang::class);

        $query = $this->getFilteredQuery();
        $transfers = $query->latest('tanggal')->latest('id')->get();

        $filename = 'Laporan_Transfer_Gudang_'.now()->format('Y-m-d_His').'.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), 'export_transfer_').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($tempPath);

        // Header style: Navy background, White bold text
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('1E3A8A')
            ->withFontSize(11);

        $headers = [
            'NO',
            'NO. TRANSFER',
            'TANGGAL',
            'GUDANG ASAL',
            'GUDANG TUJUAN',
            'JUMLAH ITEM',
            'TOTAL KUANTITAS',
            'DICATAT OLEH',
            'KETERANGAN',
        ];

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        $dataStyle = (new Style)->withFontSize(10);

        foreach ($transfers as $index => $t) {
            $rowValues = [
                $index + 1,
                $t->no_transfer,
                $t->tanggal ? $t->tanggal->format('d/m/Y') : '-',
                $t->gudangAsal->nama_gudang ?? '-',
                $t->gudangTujuan->nama_gudang ?? '-',
                $t->details->count(),
                (float) $t->details->sum('jumlah'),
                $t->user->name ?? 'Sistem',
                $t->keterangan ?? '-',
            ];

            $writer->addRow(Row::fromValuesWithStyle($rowValues, $dataStyle));
        }

        $writer->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', TransferGudang::class);

        $query = $this->getFilteredQuery();
        $transfers = $query->latest('tanggal')->latest('id')->paginate($this->perPage);

        return view('livewire.inventory.transfer-index', [
            'transfers' => $transfers,
            'metrics' => $this->calculateMetrics(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
        ]);
    }
}
