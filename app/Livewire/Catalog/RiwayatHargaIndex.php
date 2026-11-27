<?php

namespace App\Livewire\Catalog;

use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\RiwayatHargaProduk;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Riwayat Perubahan Harga Produk')]
class RiwayatHargaIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filterJenisHarga = '';

    #[Url(except: '')]
    public string $filterSumber = '';

    #[Url(except: '')]
    public string $filterArah = '';

    #[Url(except: '')]
    public string $filterKategori = '';

    #[Url(except: '')]
    public string $filterMerek = '';

    #[Url(except: '')]
    public string $startDate = '';

    #[Url(except: '')]
    public string $endDate = '';

    public function mount(): void
    {
        abort_unless(
            Auth::user()?->can('view_price_history') || Auth::user()?->can('view_products'),
            403
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterJenisHarga(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSumber(): void
    {
        $this->resetPage();
    }

    public function updatingFilterArah(): void
    {
        $this->resetPage();
    }

    public function updatingFilterKategori(): void
    {
        $this->resetPage();
    }

    public function updatingFilterMerek(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->resetPage();
    }

    public function setJenisHargaFilter(string $jenis): void
    {
        $canViewPurchasePrices = Auth::user()?->can('view_purchase_prices') ?? false;
        if ($jenis === 'harga_beli' && ! $canViewPurchasePrices) {
            return;
        }

        $this->filterJenisHarga = $jenis;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterJenisHarga',
            'filterSumber',
            'filterArah',
            'filterKategori',
            'filterMerek',
            'startDate',
            'endDate',
        ]);
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        abort_unless(
            Auth::user()?->can('view_price_history') || Auth::user()?->can('view_products'),
            403
        );

        $records = $this->buildFilteredQuery()
            ->with(['produk.kategori', 'produk.merek', 'supplier', 'user'])
            ->latest('created_at')
            ->latest('id')
            ->get();

        $filename = 'riwayat_harga_produk_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Waktu Perubahan',
                'Kode SKU',
                'Nama Produk',
                'Kategori',
                'Merek',
                'Jenis Harga',
                'Harga Lama (Rp)',
                'Harga Baru (Rp)',
                'Selisih (Rp)',
                'Selisih (%)',
                'Sumber Perubahan',
                'Referensi',
                'Supplier',
                'Diubah Oleh',
                'Keterangan',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->created_at?->format('Y-m-d H:i:s'),
                    $row->produk?->kode_katalog ?? '-',
                    $row->produk?->nama ?? '-',
                    $row->produk?->kategori?->nama_kategori ?? '-',
                    $row->produk?->merek?->nama_merek ?? '-',
                    $row->jenisHargaLabel(),
                    (float) ($row->harga_lama ?? 0),
                    (float) ($row->harga_baru ?? 0),
                    (float) $row->selisih_nominal,
                    $row->selisih_persen !== null ? (float) $row->selisih_persen : '',
                    $row->sumberLabel(),
                    $row->referensi ?? '-',
                    $row->supplier?->nama_supplier ?? '-',
                    $row->user?->name ?? 'Sistem',
                    $row->keterangan ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function buildBaseQuery(): Builder
    {
        $canViewPurchasePrices = Auth::user()?->can('view_purchase_prices') ?? false;

        return RiwayatHargaProduk::query()
            ->when(! $canViewPurchasePrices, function (Builder $q) {
                $q->where('jenis_harga', '!=', 'harga_beli');
            });
    }

    protected function buildFilteredQuery(): Builder
    {
        $canViewPurchasePrices = Auth::user()?->can('view_purchase_prices') ?? false;

        return $this->buildBaseQuery()
            ->when(filled($this->search), function (Builder $q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('referensi', 'like', $term)
                        ->orWhere('keterangan', 'like', $term)
                        ->orWhereHas('produk', function (Builder $pq) use ($term) {
                            $pq->where('nama', 'like', $term)
                                ->orWhere('kode_katalog', 'like', $term);
                        })
                        ->orWhereHas('supplier', function (Builder $sq) use ($term) {
                            $sq->where('nama_supplier', 'like', $term);
                        });
                });
            })
            ->when(filled($this->filterJenisHarga), function (Builder $q) use ($canViewPurchasePrices) {
                if ($this->filterJenisHarga === 'harga_beli' && ! $canViewPurchasePrices) {
                    return;
                }
                $q->where('jenis_harga', $this->filterJenisHarga);
            })
            ->when(filled($this->filterSumber), function (Builder $q) {
                $q->where('sumber', $this->filterSumber);
            })
            ->when($this->filterArah === 'naik', function (Builder $q) {
                $q->where('selisih_nominal', '>', 0);
            })
            ->when($this->filterArah === 'turun', function (Builder $q) {
                $q->where('selisih_nominal', '<', 0);
            })
            ->when(filled($this->filterKategori), function (Builder $q) {
                $katId = (int) $this->filterKategori;
                $childIds = Kategori::where('parent_id', $katId)->pluck('id')->push($katId)->all();
                $q->whereHas('produk', fn (Builder $pq) => $pq->whereIn('kategori_id', $childIds));
            })
            ->when(filled($this->filterMerek), function (Builder $q) {
                $q->whereHas('produk', fn (Builder $pq) => $pq->where('merek_id', (int) $this->filterMerek));
            })
            ->when(filled($this->startDate), function (Builder $q) {
                $q->whereDate('created_at', '>=', $this->startDate);
            })
            ->when(filled($this->endDate), function (Builder $q) {
                $q->whereDate('created_at', '<=', $this->endDate);
            });
    }

    public function render(): View
    {
        $baseQuery = $this->buildBaseQuery();

        $kpis = [
            'totalPerubahan' => (clone $baseQuery)->count(),
            'totalNaik' => (clone $baseQuery)->where('selisih_nominal', '>', 0)->count(),
            'totalTurun' => (clone $baseQuery)->where('selisih_nominal', '<', 0)->count(),
            'bulanIni' => (clone $baseQuery)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        $riwayats = $this->buildFilteredQuery()
            ->with(['produk.kategori', 'produk.merek', 'supplier', 'user'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return view('livewire.catalog.riwayat-harga-index', [
            'riwayats' => $riwayats,
            'kpis' => $kpis,
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
        ]);
    }
}
