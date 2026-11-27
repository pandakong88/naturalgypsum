<?php

namespace App\Livewire\Production;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Production\Actions\CancelProduksiAction;
use App\Domains\Production\Models\DetailProduksi;
use App\Domains\Production\Models\Produksi;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Catatan Produksi')]
class ProduksiIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $gudangFilter = '';

    public string $statusFilter = 'aktif'; // 'aktif' | 'dibatalkan' | 'semua'

    public string $startDate = '';

    public string $endDate = '';

    public string $datePreset = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGudangFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->datePreset = '';
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->datePreset = '';
        $this->resetPage();
    }

    public function setDatePreset(string $preset): void
    {
        $today = Carbon::today();
        $this->datePreset = $preset;

        switch ($preset) {
            case 'today':
                $this->startDate = $today->toDateString();
                $this->endDate = $today->toDateString();
                break;
            case 'yesterday':
                $yesterday = $today->copy()->subDay();
                $this->startDate = $yesterday->toDateString();
                $this->endDate = $yesterday->toDateString();
                break;
            case '7days':
                $this->startDate = $today->copy()->subDays(6)->toDateString();
                $this->endDate = $today->toDateString();
                break;
            case 'month':
                $this->startDate = $today->copy()->startOfMonth()->toDateString();
                $this->endDate = $today->copy()->endOfMonth()->toDateString();
                break;
            default:
                $this->datePreset = '';
                $this->startDate = '';
                $this->endDate = '';
                break;
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'gudangFilter',
            'statusFilter',
            'startDate',
            'endDate',
            'datePreset',
        ]);
        $this->resetPage();
    }

    public function cancelProduksi(int $id, CancelProduksiAction $action): void
    {
        $produksi = Produksi::findOrFail($id);
        Gate::authorize('delete', $produksi);

        try {
            $action->execute($produksi);
            session()->flash('message', 'Produksi '.$produksi->no_produksi.' berhasil dibatalkan dan stok dikurangi.');
        } catch (Exception $e) {
            session()->flash('error', 'Gagal membatalkan produksi: '.$e->getMessage());
        }
    }

    protected function applyCommonFilters(Builder $query): Builder
    {
        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function (Builder $q) use ($search) {
                $q->where('no_produksi', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('gudang', function (Builder $gq) use ($search) {
                        $gq->where('nama_gudang', 'like', "%{$search}%");
                    })
                    ->orWhereHas('details.produk', function (Builder $pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode_katalog', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->gudangFilter)) {
            $query->where('gudang_id', $this->gudangFilter);
        }

        if (filled($this->startDate)) {
            $query->whereDate('tanggal', '>=', $this->startDate);
        }

        if (filled($this->endDate)) {
            $query->whereDate('tanggal', '<=', $this->endDate);
        }

        return $query;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Produksi::class);

        $query = Produksi::query()
            ->with(['gudang', 'user', 'creator', 'details.produk', 'bahanMutations.produk']);

        if ($this->statusFilter === 'dibatalkan') {
            $query->onlyTrashed();
        } elseif ($this->statusFilter === 'semua') {
            $query->withTrashed();
        }

        $this->applyCommonFilters($query);

        $produksis = $query->latest('tanggal')->latest('id')->paginate(15);

        // KPI Summary for active production batches matching current filters
        $statsBase = $this->applyCommonFilters(Produksi::query());
        $activeIdsSubquery = (clone $statsBase)->select('id');

        $totalBatch = (int) (clone $statsBase)->count();
        $activeDetails = DetailProduksi::with('produk')
            ->whereIn('produksi_id', $activeIdsSubquery)
            ->get();

        $totalOutputQty = (float) $activeDetails->sum('jumlah');
        $totalJenisProduk = (int) $activeDetails->pluck('produk_id')->unique()->count();
        $estimasiNilaiOutput = 0.0;
        foreach ($activeDetails as $d) {
            $hpp = (float) ($d->produk?->harga_beli ?: $d->produk?->harga_jual ?: 0);
            $estimasiNilaiOutput += ((float) $d->jumlah) * $hpp;
        }

        $totalDibatalkan = (int) $this->applyCommonFilters(Produksi::onlyTrashed())->count();

        return view('livewire.production.produksi-index', [
            'produksis' => $produksis,
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'totalBatch' => $totalBatch,
            'totalOutputQty' => $totalOutputQty,
            'totalJenisProduk' => $totalJenisProduk,
            'estimasiNilaiOutput' => round($estimasiNilaiOutput, 2),
            'totalDibatalkan' => $totalDibatalkan,
        ]);
    }
}
