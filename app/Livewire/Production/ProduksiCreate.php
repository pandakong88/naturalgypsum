<?php

namespace App\Livewire\Production;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Production\Actions\CreateProduksiAction;
use App\Domains\Production\Models\Produksi;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tambah Catatan Produksi')]
class ProduksiCreate extends Component
{
    public ?int $gudang_id = null;

    public string $tanggal = '';

    public string $shift = '';

    public string $penanggung_jawab = '';

    public string $catatan = '';

    public string $quickSearchOutput = '';

    public string $quickSearchMaterial = '';

    /**
     * @var array<int, array{
     *     produk_id: int|string|null,
     *     jumlah: float|int|string|null
     * }>
     */
    public array $items = [];

    public bool $useMaterials = false;

    /**
     * @var array<int, array{
     *     produk_id: int|string|null,
     *     jumlah: float|int|string|null
     * }>
     */
    public array $materials = [];

    // Product Catalog Modal state
    public bool $isCatalogModalOpen = false;

    public string $catalogTarget = 'output'; // 'output' | 'material'

    public ?int $replaceRowIndex = null;

    public string $catalogSearchQuery = '';

    public ?int $catalogKategoriId = null;

    public ?int $catalogMerekId = null;

    public string $catalogStatusStok = ''; // '' | 'kritis' | 'menipis' | 'habis' | 'aman'

    public string $catalogViewTab = 'all'; // 'all' | 'selected'

    /** @var array<int, int> */
    public array $catalogSelectedProductIds = [];

    public int $catalogLimit = 50;

    // Image Preview Lightbox Modal state
    public bool $isImageModalOpen = false;

    public ?int $imageModalProdukId = null;

    // Roll / Kemasan converter modal helper
    public bool $isRollModalOpen = false;

    public ?int $activeItemIndex = null;

    public float|int|string $rollCount = 1;

    public float|int|string $rollLength = 0;

    public float|int|string $eceranCount = 0;

    public string $converterUnitLabel = 'Roll / Kemasan';

    protected function rules(): array
    {
        $rules = [
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'tanggal' => ['required', 'date'],
            'shift' => ['nullable', 'string', 'max:50'],
            'penanggung_jawab' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'integer', 'exists:produk,id'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
        ];

        if ($this->useMaterials) {
            $rules['materials'] = ['nullable', 'array'];
            $rules['materials.*.produk_id'] = ['nullable', 'integer', 'exists:produk,id'];
            $rules['materials.*.jumlah'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'gudang_id.required' => 'Pilih gudang tujuan hasil produksi.',
            'tanggal.required' => 'Tanggal produksi wajib diisi.',
            'items.required' => 'Minimal harus ada satu baris hasil produksi. Silakan pilih produk dari katalog.',
            'items.min' => 'Minimal harus ada satu baris hasil produksi.',
            'items.*.produk_id.required' => 'Pilih produk hasil produksi dari katalog.',
            'items.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'items.*.jumlah.gt' => 'Jumlah harus lebih besar dari 0.',
        ];
    }

    public function mount(): void
    {
        Gate::authorize('create', Produksi::class);
        $this->tanggal = Carbon::now()->toDateString();
        $this->gudang_id = Gudang::first()?->id;
    }

    public function addItem(): void
    {
        $this->openCatalogModal('output');
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function clearAllItems(): void
    {
        $this->items = [];
    }

    public function quickAddOutputProduct(int $produkId): void
    {
        $this->quickSearchOutput = '';

        foreach ($this->items as $idx => $item) {
            if (empty($item['produk_id'])) {
                $this->items[$idx]['produk_id'] = $produkId;
                if (empty($this->items[$idx]['jumlah'])) {
                    $this->items[$idx]['jumlah'] = 1;
                }

                return;
            }
            if ((int) $item['produk_id'] === $produkId) {
                $this->items[$idx]['jumlah'] = ((float) ($item['jumlah'] ?: 0)) + 1;

                return;
            }
        }

        $this->items[] = [
            'produk_id' => $produkId,
            'jumlah' => 1,
        ];
    }

    public function toggleUseMaterials(): void
    {
        $this->useMaterials = ! $this->useMaterials;
    }

    public function addMaterial(): void
    {
        $this->useMaterials = true;
        $this->openCatalogModal('material');
    }

    public function removeMaterial(int $index): void
    {
        unset($this->materials[$index]);
        $this->materials = array_values($this->materials);
    }

    public function clearAllMaterials(): void
    {
        $this->materials = [];
    }

    public function quickAddMaterialProduct(int $produkId): void
    {
        $this->quickSearchMaterial = '';
        $this->useMaterials = true;

        foreach ($this->materials as $idx => $mat) {
            if (empty($mat['produk_id'])) {
                $this->materials[$idx]['produk_id'] = $produkId;
                if (empty($this->materials[$idx]['jumlah'])) {
                    $this->materials[$idx]['jumlah'] = 1;
                }

                return;
            }
            if ((int) $mat['produk_id'] === $produkId) {
                $this->materials[$idx]['jumlah'] = ((float) ($mat['jumlah'] ?: 0)) + 1;

                return;
            }
        }

        $this->materials[] = [
            'produk_id' => $produkId,
            'jumlah' => 1,
        ];
    }

    // =========================================================================
    // Product Catalog Modal Methods
    // =========================================================================

    public function openCatalogModal(string $target = 'output', ?int $replaceIndex = null): void
    {
        $this->catalogTarget = $target === 'material' ? 'material' : 'output';
        if ($this->catalogTarget === 'material') {
            $this->useMaterials = true;
        }
        $this->replaceRowIndex = $replaceIndex;
        $this->catalogSearchQuery = '';
        $this->catalogKategoriId = null;
        $this->catalogMerekId = null;
        $this->catalogStatusStok = '';
        $this->catalogViewTab = 'all';
        $this->catalogSelectedProductIds = [];
        $this->catalogLimit = 50;
        $this->isCatalogModalOpen = true;
    }

    public function closeCatalogModal(): void
    {
        $this->isCatalogModalOpen = false;
        $this->replaceRowIndex = null;
        $this->catalogSelectedProductIds = [];
    }

    public function setCatalogMerek(?int $merekId): void
    {
        $this->catalogMerekId = $merekId;
        $this->catalogLimit = 50;
    }

    public function setCatalogViewTab(string $tab): void
    {
        $this->catalogViewTab = in_array($tab, ['all', 'selected'], true) ? $tab : 'all';
    }

    public function loadMoreCatalog(): void
    {
        $this->catalogLimit += 50;
    }

    public function toggleCatalogProduct(int $produkId): void
    {
        // If replacing a specific row, select immediately and close modal
        if ($this->replaceRowIndex !== null) {
            if ($this->catalogTarget === 'material' && isset($this->materials[$this->replaceRowIndex])) {
                $this->materials[$this->replaceRowIndex]['produk_id'] = $produkId;
            } elseif ($this->catalogTarget === 'output' && isset($this->items[$this->replaceRowIndex])) {
                $this->items[$this->replaceRowIndex]['produk_id'] = $produkId;
            }
            $this->closeCatalogModal();

            return;
        }

        if (in_array($produkId, $this->catalogSelectedProductIds, true)) {
            $this->catalogSelectedProductIds = array_values(array_diff($this->catalogSelectedProductIds, [$produkId]));
        } else {
            $this->catalogSelectedProductIds[] = $produkId;
        }
    }

    public function selectAllFoundCatalogProducts(): void
    {
        $foundIds = $this->buildCatalogQuery()
            ->limit($this->catalogLimit)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->catalogSelectedProductIds = array_values(array_unique(array_merge($this->catalogSelectedProductIds, $foundIds)));
    }

    public function clearSelectedCatalogProducts(): void
    {
        $this->catalogSelectedProductIds = [];
        if ($this->catalogViewTab === 'selected') {
            $this->catalogViewTab = 'all';
        }
    }

    public function addSelectedCatalogProducts(): void
    {
        if (empty($this->catalogSelectedProductIds)) {
            return;
        }

        foreach ($this->catalogSelectedProductIds as $produkId) {
            if ($this->catalogTarget === 'material') {
                $this->quickAddMaterialProduct((int) $produkId);
            } else {
                $this->quickAddOutputProduct((int) $produkId);
            }
        }

        $this->closeCatalogModal();
    }

    protected function buildCatalogQuery(): Builder
    {
        $gudangId = (int) $this->gudang_id;

        $query = Produk::query()
            ->with(['kategori', 'merek'])
            ->withSum(['stok as stok_gudang_aktif' => function ($sq) use ($gudangId) {
                if ($gudangId > 0) {
                    $sq->where('gudang_id', $gudangId);
                }
            }], 'jumlah');

        if ($this->catalogViewTab === 'selected') {
            $ids = ! empty($this->catalogSelectedProductIds) ? $this->catalogSelectedProductIds : [0];
            $query->whereIn('id', $ids);

            return $query->orderBy('nama');
        }

        if (filled(trim($this->catalogSearchQuery))) {
            $term = trim($this->catalogSearchQuery);
            $query->where(function (Builder $q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                    ->orWhere('kode_katalog', 'like', "%{$term}%");
            });
        }

        if ($this->catalogKategoriId) {
            $selectedKat = Kategori::with('children')->find($this->catalogKategoriId);
            $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                ? $selectedKat->children->pluck('id')->push($selectedKat->id)->all()
                : [$this->catalogKategoriId];
            $query->whereIn('kategori_id', $catIds);
        }

        if ($this->catalogMerekId) {
            $query->where('merek_id', $this->catalogMerekId);
        }

        if (filled($this->catalogStatusStok)) {
            $stokSub = $gudangId > 0
                ? "(SELECT COALESCE(SUM(jumlah), 0) FROM stok WHERE stok.produk_id = produk.id AND stok.gudang_id = {$gudangId})"
                : '(SELECT COALESCE(SUM(jumlah), 0) FROM stok WHERE stok.produk_id = produk.id)';

            switch ($this->catalogStatusStok) {
                case 'habis':
                    $query->whereRaw("{$stokSub} <= 0");
                    break;
                case 'kritis':
                    $query->whereRaw("{$stokSub} < produk.stok_minimum");
                    break;
                case 'menipis':
                    $query->whereRaw("{$stokSub} > 0 AND {$stokSub} <= produk.stok_minimum");
                    break;
                case 'aman':
                    $query->whereRaw("{$stokSub} > produk.stok_minimum");
                    break;
            }
        }

        return $query->orderBy('nama');
    }

    // =========================================================================
    // Image Preview Lightbox Modal
    // =========================================================================

    public function openImageModal(int $produkId): void
    {
        $this->imageModalProdukId = $produkId;
        $this->isImageModalOpen = true;
    }

    public function closeImageModal(): void
    {
        $this->isImageModalOpen = false;
        $this->imageModalProdukId = null;
    }

    // =========================================================================
    // Roll / Kemasan Converter Modal
    // =========================================================================

    public function openRollConverter(int $index): void
    {
        $this->activeItemIndex = $index;
        $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);
        $produk = Produk::find($produkId);

        if ($produk && (float) $produk->panjang_per_roll > 0) {
            $this->rollLength = (float) $produk->panjang_per_roll;
            $this->converterUnitLabel = 'Roll';
        } elseif ($produk && (float) $produk->isi_per_kemasan > 0) {
            $pMeter = (float) ($produk->panjang_meter ?: 1.0);
            $this->rollLength = $produk->satuan->value === 'meter' && $pMeter > 1
                ? round((float) $produk->isi_per_kemasan * $pMeter, 2)
                : (float) $produk->isi_per_kemasan;
            $this->converterUnitLabel = $produk->kemasan ?: 'Kemasan/Dus';
        } else {
            $this->rollLength = 25;
            $this->converterUnitLabel = 'Roll / Kemasan';
        }

        $this->rollCount = 1;
        $this->eceranCount = 0;
        $this->isRollModalOpen = true;
    }

    public function applyRollConversion(): void
    {
        if ($this->activeItemIndex !== null && isset($this->items[$this->activeItemIndex])) {
            $calculated = ((float) $this->rollCount * (float) $this->rollLength) + (float) ($this->eceranCount ?: 0);
            $this->items[$this->activeItemIndex]['jumlah'] = $calculated;
        }

        $this->closeRollConverter();
    }

    public function closeRollConverter(): void
    {
        $this->isRollModalOpen = false;
        $this->activeItemIndex = null;
    }

    public function save(CreateProduksiAction $action): mixed
    {
        Gate::authorize('create', Produksi::class);

        // Clean out completely empty rows if other valid rows exist
        $nonEmptyItems = array_values(array_filter($this->items, fn ($row) => ! empty($row['produk_id'])));
        if (! empty($nonEmptyItems)) {
            $this->items = $nonEmptyItems;
        }

        $this->validate();

        $metaParts = [];
        if (filled($this->shift)) {
            $metaParts[] = 'Shift: '.trim($this->shift);
        }
        if (filled($this->penanggung_jawab)) {
            $metaParts[] = 'Tim/Tukang: '.trim($this->penanggung_jawab);
        }

        $fullCatatan = trim($this->catatan);
        if (! empty($metaParts)) {
            $prefix = '['.implode(' | ', $metaParts).']';
            $fullCatatan = $fullCatatan ? $prefix.' '.$fullCatatan : $prefix;
        }

        $validMaterials = [];
        if ($this->useMaterials) {
            foreach ($this->materials as $mat) {
                $pId = (int) ($mat['produk_id'] ?? 0);
                $qty = (float) ($mat['jumlah'] ?? 0);
                if ($pId > 0 && $qty > 0) {
                    $validMaterials[] = [
                        'produk_id' => $pId,
                        'jumlah' => $qty,
                    ];
                }
            }
        }

        try {
            $produksi = $action->execute([
                'gudang_id' => (int) $this->gudang_id,
                'tanggal' => $this->tanggal,
                'catatan' => $fullCatatan,
                'items' => $this->items,
                'materials' => $validMaterials,
            ]);

            $msg = 'Catatan Produksi '.$produksi->no_produksi.' berhasil dicatat dan stok barang jadi bertambah.';
            if (! empty($validMaterials)) {
                $msg .= ' Stok bahan baku yang dipakai juga telah dikurangi otomatis.';
            }

            session()->flash('message', $msg);

            return redirect()->route('produksi.show', $produksi->id);
        } catch (Exception $e) {
            $this->addError('general', $e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        Gate::authorize('create', Produksi::class);

        $gudangAktif = $this->gudang_id ? Gudang::find($this->gudang_id) : null;
        $stockService = app(StockService::class);

        // Build lookup map of selected products for row metadata (stock, unit, HPP, photo, conversion badge)
        $selectedProductIds = collect($this->items)
            ->pluck('produk_id')
            ->merge(collect($this->materials)->pluck('produk_id'))
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $selectedProductsMap = [];
        if (! empty($selectedProductIds)) {
            $loadedProducts = Produk::with(['kategori', 'merek'])
                ->whereIn('id', $selectedProductIds)
                ->get();

            foreach ($loadedProducts as $lp) {
                $selectedProductsMap[$lp->id] = [
                    'model' => $lp,
                    'stok' => $gudangAktif ? $stockService->getStock($lp, $gudangAktif) : 0.0,
                    'satuan_label' => $lp->satuan->label(),
                    'hpp' => (float) ($lp->harga_beli ?: $lp->harga_jual ?: 0),
                ];
            }
        }

        // Quick search results for Output Barang Jadi
        $outputSearchResults = collect();
        if (filled(trim($this->quickSearchOutput))) {
            $term = trim($this->quickSearchOutput);
            $outputSearchResults = Produk::with(['kategori', 'merek'])
                ->where(function ($q) use ($term) {
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode_katalog', 'like', "%{$term}%");
                })
                ->orderBy('nama')
                ->limit(10)
                ->get();
        }

        // Quick search results for Raw Materials
        $materialSearchResults = collect();
        if (filled(trim($this->quickSearchMaterial))) {
            $term = trim($this->quickSearchMaterial);
            $materialSearchResults = Produk::with(['kategori', 'merek'])
                ->where(function ($q) use ($term) {
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode_katalog', 'like', "%{$term}%");
                })
                ->orderBy('nama')
                ->limit(10)
                ->get();
        }

        // Catalog Modal Products
        $catalogProducts = collect();
        $catalogTotalCount = 0;
        if ($this->isCatalogModalOpen) {
            $catQuery = $this->buildCatalogQuery();
            $catalogTotalCount = (clone $catQuery)->count();
            $catalogProducts = $catQuery->limit($this->catalogLimit)->get();
        }

        $imageModalProduk = $this->imageModalProdukId ? Produk::with(['kategori', 'merek'])->find($this->imageModalProdukId) : null;

        return view('livewire.production.produksi-create', [
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'rootKategoris' => Kategori::root()->with('children')->orderBy('nama_kategori')->get(),
            'mereks' => Merek::whereHas('produks')->orderBy('nama_merek')->get(),
            'selectedProductsMap' => $selectedProductsMap,
            'outputSearchResults' => $outputSearchResults,
            'materialSearchResults' => $materialSearchResults,
            'catalogProducts' => $catalogProducts,
            'catalogTotalCount' => $catalogTotalCount,
            'imageModalProduk' => $imageModalProduk,
        ]);
    }
}
