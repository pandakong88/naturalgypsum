<?php

namespace App\Livewire\Inventory;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Enums\StatusStok;
use App\Domains\Inventory\Models\Stok;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Stok Saat Ini')]
class StokIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    #[Url]
    public string $filterGudang = '';

    #[Url]
    public string $filterKategori = '';

    #[Url]
    public string $filterMerek = '';

    #[Url]
    public string $filterStatus = '';

    public string $sortBy = 'terbaru';

    public int $perPage = 15;

    // Multi-warehouse detail modal state
    public bool $isDetailModalOpen = false;

    public ?int $selectedProdukId = null;

    public ?Produk $selectedProduk = null;

    /** @var array<int, array{gudang_id: int, gudang: string, lokasi: string, jumlah: float, status: StatusStok}> */
    public array $warehouseBreakdown = [];

    public float $totalCrossWarehouseStock = 0.0;

    // Image Preview / Lightbox modal state
    public bool $isImageModalOpen = false;

    public ?Produk $imageModalProduk = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGudang(): void
    {
        $this->resetPage();
    }

    public function updatedFilterKategori(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMerek(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function filterByStatus(?string $status = null): void
    {
        if ($this->filterStatus === $status) {
            $this->filterStatus = '';
        } else {
            $this->filterStatus = $status ?? '';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterGudang',
            'filterKategori',
            'filterMerek',
            'filterStatus',
            'sortBy',
        ]);
        $this->sortBy = 'terbaru';
        $this->perPage = 15;
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return filled($this->search)
            || filled($this->filterGudang)
            || filled($this->filterKategori)
            || filled($this->filterMerek)
            || filled($this->filterStatus)
            || $this->sortBy !== 'terbaru';
    }

    public function openDetailModal(int $produkId): void
    {
        $this->selectedProduk = Produk::with(['kategori', 'merek'])->findOrFail($produkId);
        $this->selectedProdukId = $this->selectedProduk->id;

        $gudangs = Gudang::orderBy('nama_gudang')->get();
        $stoks = Stok::where('produk_id', $this->selectedProdukId)->get()->keyBy('gudang_id');

        $this->warehouseBreakdown = [];
        $this->totalCrossWarehouseStock = 0.0;

        foreach ($gudangs as $gudang) {
            $qty = isset($stoks[$gudang->id]) ? (float) $stoks[$gudang->id]->jumlah : 0.000;
            $this->totalCrossWarehouseStock += $qty;
            $this->warehouseBreakdown[] = [
                'gudang_id' => $gudang->id,
                'gudang' => $gudang->nama_gudang,
                'lokasi' => $gudang->lokasi ?? '-',
                'jumlah' => $qty,
                'status' => StatusStok::determine($qty, (float) $this->selectedProduk->stok_minimum),
            ];
        }

        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal(): void
    {
        $this->isDetailModalOpen = false;
        $this->selectedProduk = null;
        $this->selectedProdukId = null;
        $this->warehouseBreakdown = [];
        $this->totalCrossWarehouseStock = 0.0;
    }

    public function openImageModal(int $id): void
    {
        $this->imageModalProduk = Produk::with(['kategori', 'merek'])->find($id);
        if ($this->imageModalProduk && $this->imageModalProduk->foto) {
            $this->isImageModalOpen = true;
        }
    }

    public function closeImageModal(): void
    {
        $this->isImageModalOpen = false;
        $this->imageModalProduk = null;
    }

    /**
     * @return Builder<Produk>|Builder<Stok>
     */
    protected function getFilteredStokQuery(): Builder
    {
        if (blank($this->filterGudang)) {
            return $this->getConsolidatedProdukQuery();
        }

        return $this->getGranularStokQuery();
    }

    /**
     * @return Builder<Produk>
     */
    protected function getConsolidatedProdukQuery(): Builder
    {
        /** @var Builder<Produk> $query */
        $query = Produk::query()
            ->whereNull('deleted_at')
            ->with(['kategori', 'merek', 'stok.gudang'])
            ->withSum('stok as total_stok', 'jumlah');

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_katalog', 'like', "%{$search}%")
                    ->orWhere('ukuran', 'like', "%{$search}%");
            });
        }

        if (filled($this->filterKategori)) {
            $selectedKat = Kategori::with('children')->find($this->filterKategori);
            $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                : [$this->filterKategori];

            $query->whereIn('kategori_id', $catIds);
        }

        if (filled($this->filterMerek)) {
            $query->where('merek_id', $this->filterMerek);
        }

        if (filled($this->filterStatus)) {
            $totalStokSql = '(SELECT COALESCE(SUM(stok.jumlah), 0) FROM stok WHERE stok.produk_id = produk.id)';
            if ($this->filterStatus === StatusStok::Habis->value) {
                $query->whereRaw("{$totalStokSql} <= 0");
            } elseif ($this->filterStatus === StatusStok::Menipis->value) {
                $query->whereRaw("{$totalStokSql} > 0 AND {$totalStokSql} <= produk.stok_minimum");
            } elseif ($this->filterStatus === StatusStok::Aman->value) {
                $query->whereRaw("{$totalStokSql} > produk.stok_minimum");
            }
        }

        return $query;
    }

    /**
     * @return Builder<Stok>
     */
    protected function getGranularStokQuery(): Builder
    {
        /** @var Builder<Stok> $query */
        $query = Stok::query()
            ->with(['produk.kategori', 'produk.merek', 'gudang'])
            ->where('gudang_id', $this->filterGudang);

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->whereHas('produk', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_katalog', 'like', "%{$search}%")
                    ->orWhere('ukuran', 'like', "%{$search}%");
            });
        }

        if (filled($this->filterKategori)) {
            $selectedKat = Kategori::with('children')->find($this->filterKategori);
            $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                : [$this->filterKategori];

            $query->whereHas('produk', function ($q) use ($catIds) {
                $q->whereIn('kategori_id', $catIds);
            });
        }

        if (filled($this->filterMerek)) {
            $query->whereHas('produk', function ($q) {
                $q->where('merek_id', $this->filterMerek);
            });
        }

        if (filled($this->filterStatus)) {
            if ($this->filterStatus === StatusStok::Habis->value) {
                $query->where('jumlah', '<=', 0);
            } elseif ($this->filterStatus === StatusStok::Menipis->value) {
                $query->where('jumlah', '>', 0)
                    ->whereHas('produk', function ($q) {
                        $q->whereRaw('stok.jumlah <= produk.stok_minimum');
                    });
            } elseif ($this->filterStatus === StatusStok::Aman->value) {
                $query->whereHas('produk', function ($q) {
                    $q->whereRaw('stok.jumlah > produk.stok_minimum');
                });
            }
        }

        return $query;
    }

    /**
     * @param  Builder<Produk>|Builder<Stok>  $query
     * @return Builder<Produk>|Builder<Stok>
     */
    protected function applySorting(Builder $query): Builder
    {
        if (blank($this->filterGudang)) {
            return match ($this->sortBy) {
                'stok_asc' => $query->orderBy('total_stok', 'asc'),
                'stok_desc' => $query->orderBy('total_stok', 'desc'),
                'nama_asc' => $query->orderBy('nama', 'asc'),
                'nama_desc' => $query->orderBy('nama', 'desc'),
                'kode_asc' => $query->orderBy('kode_katalog', 'asc'),
                default => $query->latest('updated_at'),
            };
        }

        return match ($this->sortBy) {
            'stok_asc' => $query->orderBy('jumlah', 'asc'),
            'stok_desc' => $query->orderBy('jumlah', 'desc'),
            'nama_asc' => $query->join('produk as p_sort', 'stok.produk_id', '=', 'p_sort.id')
                ->orderBy('p_sort.nama', 'asc')
                ->select('stok.*'),
            'nama_desc' => $query->join('produk as p_sort', 'stok.produk_id', '=', 'p_sort.id')
                ->orderBy('p_sort.nama', 'desc')
                ->select('stok.*'),
            'kode_asc' => $query->join('produk as p_sort', 'stok.produk_id', '=', 'p_sort.id')
                ->orderBy('p_sort.kode_katalog', 'asc')
                ->select('stok.*'),
            default => $query->latest('stok.updated_at'),
        };
    }

    /**
     * @return array{
     *     totalSku: int,
     *     totalFisik: float,
     *     totalHabis: int,
     *     totalMenipis: int,
     *     totalAman: int,
     *     totalNilaiAset: float
     * }
     */
    protected function calculateMetrics(): array
    {
        $canViewPrices = auth()->check() && auth()->user()->can('view_purchase_prices');

        if (blank($this->filterGudang)) {
            // Mode Konsolidasi Total Lintas Gudang
            $metricsQuery = DB::table('produk')
                ->leftJoin('stok', 'produk.id', '=', 'stok.produk_id')
                ->whereNull('produk.deleted_at')
                ->groupBy('produk.id', 'produk.stok_minimum', 'produk.harga_beli')
                ->selectRaw('
                    produk.id,
                    COALESCE(SUM(stok.jumlah), 0) as total_stok,
                    produk.stok_minimum,
                    produk.harga_beli
                ');

            $metrics = DB::query()->fromSub($metricsQuery, 'p_summary')->selectRaw('
                COUNT(*) as total_sku,
                COALESCE(SUM(total_stok), 0) as total_fisik,
                COALESCE(SUM(CASE WHEN total_stok <= 0 THEN 1 ELSE 0 END), 0) as total_habis,
                COALESCE(SUM(CASE WHEN total_stok > 0 AND total_stok <= stok_minimum THEN 1 ELSE 0 END), 0) as total_menipis,
                COALESCE(SUM(CASE WHEN total_stok > stok_minimum THEN 1 ELSE 0 END), 0) as total_aman'
                .($canViewPrices ? ', COALESCE(SUM(CASE WHEN total_stok > 0 THEN total_stok * harga_beli ELSE 0 END), 0) as total_nilai_aset' : '')
            )->first();

            return [
                'totalSku' => (int) ($metrics->total_sku ?? 0),
                'totalFisik' => (float) ($metrics->total_fisik ?? 0.0),
                'totalHabis' => (int) ($metrics->total_habis ?? 0),
                'totalMenipis' => (int) ($metrics->total_menipis ?? 0),
                'totalAman' => (int) ($metrics->total_aman ?? 0),
                'totalNilaiAset' => $canViewPrices ? (float) ($metrics->total_nilai_aset ?? 0.0) : 0.0,
            ];
        }

        // Mode Granular Spesifik Gudang
        $query = Stok::query()
            ->join('produk', 'stok.produk_id', '=', 'produk.id')
            ->whereNull('produk.deleted_at')
            ->where('stok.gudang_id', $this->filterGudang);

        $metrics = $query->selectRaw('
            COUNT(DISTINCT stok.produk_id) as total_sku,
            COALESCE(SUM(stok.jumlah), 0) as total_fisik,
            COALESCE(SUM(CASE WHEN stok.jumlah <= 0 THEN 1 ELSE 0 END), 0) as total_habis,
            COALESCE(SUM(CASE WHEN stok.jumlah > 0 AND stok.jumlah <= produk.stok_minimum THEN 1 ELSE 0 END), 0) as total_menipis,
            COALESCE(SUM(CASE WHEN stok.jumlah > produk.stok_minimum THEN 1 ELSE 0 END), 0) as total_aman'
            .($canViewPrices ? ', COALESCE(SUM(CASE WHEN stok.jumlah > 0 THEN stok.jumlah * produk.harga_beli ELSE 0 END), 0) as total_nilai_aset' : '')
        )->first();

        return [
            'totalSku' => (int) ($metrics->total_sku ?? 0),
            'totalFisik' => (float) ($metrics->total_fisik ?? 0.0),
            'totalHabis' => (int) ($metrics->total_habis ?? 0),
            'totalMenipis' => (int) ($metrics->total_menipis ?? 0),
            'totalAman' => (int) ($metrics->total_aman ?? 0),
            'totalNilaiAset' => $canViewPrices ? (float) ($metrics->total_nilai_aset ?? 0.0) : 0.0,
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('viewAny', Stok::class);

        $query = $this->getFilteredStokQuery();
        $this->applySorting($query);

        $isConsolidated = blank($this->filterGudang);
        $canViewPrice = auth()->check() && auth()->user()->can('view_purchase_prices');
        $filename = 'Laporan_Stok_Saat_Ini_'.now()->format('Y-m-d_His').'.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), 'export_stok_').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($tempPath);

        // Header style: Deep Navy background, White bold text
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor('1E3A8A')
            ->withFontSize(11);

        $headers = [
            'NO',
            'KODE BARANG',
            'NAMA PRODUK',
            'UKURAN / SPESIFIKASI',
            'KATEGORI',
            'MEREK',
            'GUDANG',
            'STOK FISIK',
            'SATUAN',
            'STOK MINIMUM',
            'STATUS',
        ];

        if ($canViewPrice) {
            $headers[] = 'HARGA POKOK (HPP)';
            $headers[] = 'ESTIMASI NILAI ASET';
        }

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        $dataStyle = (new Style)->withFontSize(10);

        $index = 0;
        foreach ($query->cursor() as $item) {
            $index++;

            if ($isConsolidated) {
                /** @var Produk $produk */
                $produk = $item;
                $qty = (float) ($produk->total_stok ?? 0);
                $status = StatusStok::determine($qty, (float) $produk->stok_minimum);

                $activeStoks = $produk->stok->filter(fn ($s) => (float) $s->jumlah != 0);
                if ($activeStoks->isEmpty()) {
                    $gudangText = 'Semua Gudang (0 Lokasi)';
                } else {
                    $breakdown = $activeStoks->map(fn ($s) => "{$s->gudang->nama_gudang}: ".number_format((float) $s->jumlah, 0, ',', '.'))->implode(', ');
                    $gudangText = "Semua Gudang ({$breakdown})";
                }
            } else {
                /** @var Stok $stok */
                $stok = $item;
                $produk = $stok->produk;
                $qty = (float) $stok->jumlah;
                $status = $stok->status;
                $gudangText = $stok->gudang->nama_gudang ?? '-';
            }

            $rowValues = [
                $index,
                $produk->kode_katalog,
                $produk->nama,
                $produk->ukuran ?? '-',
                $produk->kategori->nama_kategori ?? '-',
                $produk->merek->nama_merek ?? '-',
                $gudangText,
                $qty,
                $produk->satuan->value ?? '',
                (float) $produk->stok_minimum,
                $status->label(),
            ];

            if ($canViewPrice) {
                $hargaBeli = (float) $produk->harga_beli;
                $nilaiAset = $qty * $hargaBeli;
                $rowValues[] = $hargaBeli;
                $rowValues[] = $nilaiAset;
            }

            $writer->addRow(Row::fromValuesWithStyle($rowValues, $dataStyle));
        }

        $writer->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Stok::class);

        $query = $this->getFilteredStokQuery();
        $this->applySorting($query);

        $stoks = $query->paginate($this->perPage);

        return view('livewire.inventory.stok-index', [
            'stoks' => $stoks,
            'isConsolidated' => blank($this->filterGudang),
            'metrics' => $this->calculateMetrics(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'rootKategoris' => Kategori::root()->with('children')->orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
            'statusOptions' => StatusStok::cases(),
            'hasActiveFilters' => $this->hasActiveFilters,
            'canViewPurchasePrices' => auth()->check() && auth()->user()->can('view_purchase_prices'),
        ]);
    }
}
