<?php

namespace App\Livewire\Inventory;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\JenisMutasiStok;
use App\Domains\Inventory\Models\MutasiStok;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Xlsx\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Mutasi Stok')]
class MutasiStokIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $startDate = '';

    public string $endDate = '';

    #[Url]
    public string $filterProduk = '';

    #[Url]
    public string $filterGudang = '';

    public string $filterJenis = '';

    public string $filterUser = '';

    public string $filterArah = ''; // '' (semua), 'masuk', 'keluar'

    public int $perPage = 15;

    // Detail modal state
    public bool $isDetailModalOpen = false;

    public ?MutasiStok $selectedMutasi = null;

    public function updatedSearch(): void
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

    public function updatedFilterProduk(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGudang(): void
    {
        $this->resetPage();
    }

    public function updatedFilterJenis(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUser(): void
    {
        $this->resetPage();
    }

    public function updatedFilterArah(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function filterByArah(string $arah = ''): void
    {
        if ($this->filterArah === $arah) {
            $this->filterArah = '';
        } else {
            $this->filterArah = $arah;
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'startDate',
            'endDate',
            'filterProduk',
            'filterGudang',
            'filterJenis',
            'filterUser',
            'filterArah',
        ]);
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return filled($this->search)
            || filled($this->startDate)
            || filled($this->endDate)
            || filled($this->filterProduk)
            || filled($this->filterGudang)
            || filled($this->filterJenis)
            || filled($this->filterUser)
            || filled($this->filterArah);
    }

    public function openDetailModal(int $mutasiId): void
    {
        $this->selectedMutasi = MutasiStok::with(['produk.kategori', 'produk.merek', 'gudang', 'user'])
            ->findOrFail($mutasiId);
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal(): void
    {
        $this->isDetailModalOpen = false;
        $this->selectedMutasi = null;
    }

    public function getDocumentUrl(MutasiStok $mutasi): ?string
    {
        if (empty($mutasi->referensi_id)) {
            return null;
        }

        $refType = strtolower((string) $mutasi->referensi_type);

        if (str_contains($refType, 'penjualan')) {
            return route('penjualan.show', $mutasi->referensi_id);
        }
        if (str_contains($refType, 'pembelian')) {
            return route('pembelian.show', $mutasi->referensi_id);
        }
        if (str_contains($refType, 'transfer')) {
            return route('transfer.show', $mutasi->referensi_id);
        }
        if (str_contains($refType, 'opname')) {
            return route('inventory.stock-opname.detail', $mutasi->referensi_id);
        }
        if (str_contains($refType, 'retur_supplier') || str_contains($refType, 'retursupplier')) {
            return route('retur-supplier.show', $mutasi->referensi_id);
        }
        if (str_contains($refType, 'retur_pelanggan') || str_contains($refType, 'returpelanggan')) {
            return route('retur-pelanggan.show', $mutasi->referensi_id);
        }
        if (str_contains($refType, 'produksi')) {
            return route('produksi.show', $mutasi->referensi_id);
        }

        return null;
    }

    /**
     * @return Builder<MutasiStok>
     */
    protected function getBaseFilterQuery(): Builder
    {
        /** @var Builder<MutasiStok> $query */
        $query = MutasiStok::query();

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('referensi', 'like', "%{$search}%")
                    ->orWhereHas('produk', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode_katalog', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        if (filled($this->filterProduk)) {
            $query->where('produk_id', $this->filterProduk);
        }

        if (filled($this->filterGudang)) {
            $query->where('gudang_id', $this->filterGudang);
        }

        if (filled($this->filterJenis)) {
            $query->where('jenis', $this->filterJenis);
        }

        if (filled($this->filterUser)) {
            $query->where('user_id', $this->filterUser);
        }

        return $query;
    }

    /**
     * @return Builder<MutasiStok>
     */
    protected function getFilteredQuery(): Builder
    {
        $query = $this->getBaseFilterQuery()
            ->with(['produk.kategori', 'produk.merek', 'gudang', 'user']);

        if ($this->filterArah === 'masuk') {
            $query->where('jumlah', '>', 0);
        } elseif ($this->filterArah === 'keluar') {
            $query->where('jumlah', '<', 0);
        }

        return $query;
    }

    /**
     * @return array{
     *     totalMutasi: int,
     *     totalMasuk: float,
     *     totalKeluar: float,
     *     netPergerakan: float
     * }
     */
    protected function calculateMetrics(): array
    {
        $baseQuery = $this->getBaseFilterQuery();

        $totalMutasi = (int) (clone $baseQuery)->count();
        $totalMasuk = (float) (clone $baseQuery)->where('jumlah', '>', 0)->sum('jumlah');
        $totalKeluar = abs((float) (clone $baseQuery)->where('jumlah', '<', 0)->sum('jumlah'));
        $netPergerakan = $totalMasuk - $totalKeluar;

        return [
            'totalMutasi' => $totalMutasi,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'netPergerakan' => $netPergerakan,
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('viewAny', MutasiStok::class);

        $query = $this->getFilteredQuery();
        $mutasis = $query->latest('tanggal')->latest('id')->get();

        $filename = 'Laporan_Mutasi_Stok_'.now()->format('Y-m-d_His').'.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), 'export_mutasi_').'.xlsx';

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
            'TANGGAL & WAKTU',
            'JENIS PERGERAKAN',
            'KODE BARANG',
            'NAMA PRODUK',
            'GUDANG',
            'JUMLAH MUTASI',
            'SATUAN',
            'NO. REFERENSI',
            'TIPE SUMBER',
            'OPERATOR',
        ];

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        $dataStyle = (new Style)->withFontSize(10);

        foreach ($mutasis as $index => $mutasi) {
            $rowValues = [
                $index + 1,
                $mutasi->tanggal ? $mutasi->tanggal->format('d/m/Y H:i') : '-',
                $mutasi->jenis->label(),
                $mutasi->produk->kode_katalog ?? '-',
                $mutasi->produk->nama ?? '-',
                $mutasi->gudang->nama_gudang ?? '-',
                (float) $mutasi->jumlah,
                $mutasi->produk->satuan->value ?? '',
                $mutasi->referensi,
                $mutasi->referensi_type ?: 'Manual',
                $mutasi->user->name ?? 'Sistem',
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
        Gate::authorize('viewAny', MutasiStok::class);

        $query = $this->getFilteredQuery();
        $mutasis = $query->latest('tanggal')->latest('id')->paginate($this->perPage);

        return view('livewire.inventory.mutasi-stok-index', [
            'mutasis' => $mutasis,
            'metrics' => $this->calculateMetrics(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'produks' => Produk::orderBy('nama')->get(),
            'users' => User::orderBy('name')->get(),
            'jenisOptions' => JenisMutasiStok::cases(),
        ]);
    }
}
