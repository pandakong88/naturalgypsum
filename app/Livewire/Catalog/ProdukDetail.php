<?php

namespace App\Livewire\Catalog;

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\MutasiStok;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Sales\Services\DiscountCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Produk')]
class ProdukDetail extends Component
{
    public Produk $produk;

    public bool $isBarcodeModalOpen = false;

    public int $barcodeQty = 1;

    public function mount(int $id): void
    {
        $this->produk = Produk::withTrashed()->with(['kategori.parent', 'merek', 'creator', 'updater'])->findOrFail($id);

        Gate::authorize('view', $this->produk);
    }

    public function openBarcodeModal(): void
    {
        $this->barcodeQty = 1;
        $this->isBarcodeModalOpen = true;
    }

    public function closeBarcodeModal(): void
    {
        $this->isBarcodeModalOpen = false;
        $this->barcodeQty = 1;
    }

    public function render(DiscountCalculationService $discountService): View
    {
        $stokGudangs = Stok::with('gudang')
            ->where('produk_id', $this->produk->id)
            ->get();

        $mutasiTerakhir = MutasiStok::with(['gudang', 'user'])
            ->where('produk_id', $this->produk->id)
            ->latest('tanggal')
            ->limit(10)
            ->get();

        $dummyPemborong = new Pelanggan(['tipe' => TipePelanggan::Pemborong]);
        $pemborongDiscount = $discountService->calculate($this->produk, $dummyPemborong, 1);

        $dummyLangganan = new Pelanggan(['tipe' => TipePelanggan::Langganan]);
        $langgananDiscount = $discountService->calculate($this->produk, $dummyLangganan, 1);

        return view('livewire.catalog.produk-detail', [
            'stokGudangs' => $stokGudangs,
            'mutasiTerakhir' => $mutasiTerakhir,
            'pemborongDiscount' => $pemborongDiscount,
            'langgananDiscount' => $langgananDiscount,
        ]);
    }
}
