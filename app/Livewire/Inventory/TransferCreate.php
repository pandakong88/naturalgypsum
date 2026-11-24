<?php

namespace App\Livewire\Inventory;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Actions\CreateTransferGudangAction;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Inventory\Models\TransferGudang;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tambah Transfer Gudang')]
class TransferCreate extends Component
{
    public ?int $gudang_asal_id = null;

    public ?int $gudang_tujuan_id = null;

    public string $tanggal = '';

    public string $keterangan = '';

    public bool $onlyAvailableStock = false;

    public bool $isConfirmModalOpen = false;

    public bool $isImageModalOpen = false;

    public ?int $imageModalProdukId = null;

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

    #[Computed]
    public function imageModalProduk(): ?Produk
    {
        if (! $this->imageModalProdukId) {
            return null;
        }

        return Produk::with(['kategori', 'merek'])->find($this->imageModalProdukId);
    }

    // Quick Search Autocomplete Bar
    public string $quickSearchQuery = '';

    // Bulk Catalog Modal
    public bool $isCatalogModalOpen = false;

    public string $catalogSearchQuery = '';

    public ?int $catalogKategoriId = null;

    public ?int $catalogMerekId = null;

    /** @var array<int, int> */
    public array $catalogSelectedProductIds = [];

    // Smart Packaging Helper Modal
    public bool $isPackagingCalcOpen = false;

    public ?int $calcItemIndex = null;

    public ?string $calcItemName = null;

    public ?string $calcKemasanLabel = null;

    public ?float $calcIsiPerKemasan = null;

    public ?float $calcPanjangMeter = null;

    public ?string $calcSatuan = null;

    public ?float $calcStokAsal = null;

    public ?float $calcJmlKemasan = null;

    public float|int|string|null $calcJmlEceran = null;

    public function updatedGudangAsalId(): void
    {
        if ($this->gudang_tujuan_id && (int) $this->gudang_tujuan_id === (int) $this->gudang_asal_id) {
            $this->gudang_tujuan_id = null;
        }
    }

    public function getAvailableStock(int|string|null $produkId): float
    {
        $pId = (int) $produkId;
        if (! $this->gudang_asal_id || $pId <= 0) {
            return 0.0;
        }

        $stok = Stok::where('produk_id', $pId)
            ->where('gudang_id', $this->gudang_asal_id)
            ->value('jumlah');

        return (float) ($stok ?? 0.0);
    }

    public function getDestinationStock(int|string|null $produkId): float
    {
        $pId = (int) $produkId;
        if (! $this->gudang_tujuan_id || $pId <= 0) {
            return 0.0;
        }

        $stok = Stok::where('produk_id', $pId)
            ->where('gudang_id', $this->gudang_tujuan_id)
            ->value('jumlah');

        return (float) ($stok ?? 0.0);
    }

    public function fillMaxQuantity(int $index): void
    {
        if (! isset($this->items[$index]['produk_id'])) {
            return;
        }

        $maxStock = $this->getAvailableStock($this->items[$index]['produk_id']);
        $this->items[$index]['jumlah'] = $maxStock > 0 ? (string) $maxStock : '0';
    }

    public function incrementQuantity(int $index, float $delta = 1.0): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $current = (float) ($this->items[$index]['jumlah'] ?? 0);
        $this->items[$index]['jumlah'] = (string) ($current + $delta);
    }

    public function decrementQuantity(int $index, float $delta = 1.0): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $current = (float) ($this->items[$index]['jumlah'] ?? 0);
        $newQty = max(1.0, $current - $delta);
        $this->items[$index]['jumlah'] = (string) $newQty;
    }

    public function openPackagingCalculator(int $index): void
    {
        if (! isset($this->items[$index]['produk_id']) || empty($this->items[$index]['produk_id'])) {
            return;
        }

        $pId = (int) $this->items[$index]['produk_id'];
        $produk = Produk::find($pId);
        if (! $produk) {
            return;
        }

        $isRoll = $produk->isRollProduct();
        $this->calcItemIndex = $index;
        $this->calcItemName = $produk->nama;
        $this->calcKemasanLabel = $produk->kemasan ?: ($isRoll ? 'Roll' : 'Dus / Kemasan Pabrik');
        $this->calcIsiPerKemasan = $produk->isi_per_kemasan !== null ? (float) $produk->isi_per_kemasan : ($isRoll && $produk->panjang_per_roll ? (float) $produk->panjang_per_roll : null);
        $this->calcPanjangMeter = $isRoll ? null : ($produk->panjang_meter !== null ? (float) $produk->panjang_meter : null);
        $this->calcSatuan = $produk->satuan?->label() ?? 'Unit';
        $this->calcStokAsal = $this->getAvailableStock($pId);

        $this->calcJmlKemasan = null;
        $this->calcJmlEceran = null;
        $this->isPackagingCalcOpen = true;
    }

    public function closePackagingCalculator(): void
    {
        $this->isPackagingCalcOpen = false;
        $this->calcItemIndex = null;
        $this->calcItemName = null;
        $this->calcKemasanLabel = null;
        $this->calcIsiPerKemasan = null;
        $this->calcPanjangMeter = null;
        $this->calcSatuan = null;
        $this->calcStokAsal = null;
        $this->calcJmlKemasan = null;
        $this->calcJmlEceran = null;
    }

    public function updatedCalcJmlEceran(mixed $value): void
    {
        if (is_string($value)) {
            $this->calcJmlEceran = str_replace(',', '.', trim($value));
        }
    }

    #[Computed]
    public function computedPhysicalTransferQty(): float
    {
        if ($this->calcItemIndex === null || ! isset($this->items[$this->calcItemIndex]['produk_id'])) {
            return 0.0;
        }

        $pId = (int) $this->items[$this->calcItemIndex]['produk_id'];
        $produk = Produk::find($pId);
        if (! $produk) {
            return 0.0;
        }

        $jmlKemasan = (float) ($this->calcJmlKemasan ?: 0);
        $rawEceran = is_string($this->calcJmlEceran) ? str_replace(',', '.', $this->calcJmlEceran) : $this->calcJmlEceran;
        $jmlEceran = is_numeric($rawEceran) ? (float) $rawEceran : 0.0;
        $isiPerKemasan = (float) ($produk->isi_per_kemasan ?: 0);
        $panjangMeter = (float) ($produk->panjang_meter ?: 0);
        $satuan = strtolower((string) ($produk->satuan?->value ?? ''));
        $kemasan = strtolower((string) ($produk->kemasan ?? ''));

        // Case 0: Roll items with length in meters (e.g. Kasa Gypsum 90m, Bubble Foil 25m)
        if ($produk->isRollProduct() || strcasecmp($kemasan, 'roll') === 0 || str_contains($satuan, 'roll')) {
            $rollLen = (float) ($produk->panjang_per_roll ?: ($isiPerKemasan ?: 25.0));

            return (float) (($jmlKemasan * $rollLen) + $jmlEceran);
        }

        // Case 1: Plafon PVC sold in meters (dus of sheets with length)
        if (str_contains($satuan, 'meter') && $panjangMeter > 0 && $isiPerKemasan > 0) {
            $fromKemasan = $jmlKemasan * $isiPerKemasan * $panjangMeter;
            $fromEceran = $jmlEceran * $panjangMeter; // loose sheets * length

            return (float) ($fromKemasan + $fromEceran);
        }

        // Case 2: Roll items with length in meters (fallback)
        if (str_contains($satuan, 'meter') && $panjangMeter > 0 && $isiPerKemasan <= 0) {
            return (float) (($jmlKemasan * $panjangMeter) + $jmlEceran);
        }

        // Case 3: Items whose system unit IS the packaging itself (e.g. Satuan = 'Dus', Kemasan = 'Dus', Isi = 100 pcs)
        $firstWordSatuan = strtolower(strtok($satuan, ' (/'));
        $firstWordKemasan = strtolower(strtok($kemasan, ' (/'));
        $isUnitSameAsKemasan = ($firstWordKemasan !== '' && (
            $firstWordSatuan === $firstWordKemasan
            || (str_contains($satuan, 'dus') && str_contains($kemasan, 'dus'))
            || (str_contains($satuan, 'box') && str_contains($kemasan, 'box'))
            || (str_contains($satuan, 'pack') && str_contains($kemasan, 'pack'))
        ));

        if ($isUnitSameAsKemasan && $isiPerKemasan > 0) {
            $fromKemasan = $jmlKemasan;
            $fromEceran = $jmlEceran / $isiPerKemasan;

            return round((float) ($fromKemasan + $fromEceran), 4);
        }

        // Case 4: Items with packaging capacity converting to smaller unit (Hollow: 5 ikat * 20 = 100 btg; Gypsum: 2 pallet * 80 = 160 lbr)
        if ($isiPerKemasan > 0) {
            return (float) (($jmlKemasan * $isiPerKemasan) + $jmlEceran);
        }

        // Case 5: Default arithmetic sum
        return (float) ($jmlKemasan + $jmlEceran);
    }

    #[Computed]
    public function isCalcQtyOverStock(): bool
    {
        if ($this->calcStokAsal === null) {
            return false;
        }

        return $this->computedPhysicalTransferQty > $this->calcStokAsal;
    }

    public function fillAllFullBoxesFromSource(): void
    {
        if ($this->calcItemIndex === null || ! isset($this->items[$this->calcItemIndex]['produk_id'])) {
            return;
        }

        $pId = (int) $this->items[$this->calcItemIndex]['produk_id'];
        $produk = Produk::find($pId);
        if (! $produk) {
            return;
        }

        $availStock = $this->getAvailableStock($pId);
        $konv = $produk->getKonversiFisik($availStock);

        if ($konv['has_conversion'] && isset($konv['jumlah_kemasan_utuh'])) {
            $this->calcJmlKemasan = (float) $konv['jumlah_kemasan_utuh'];
            $this->calcJmlEceran = 0;
        }
    }

    public function applyPackagingCalculation(): void
    {
        if ($this->isCalcQtyOverStock) {
            return;
        }

        if ($this->calcItemIndex !== null && isset($this->items[$this->calcItemIndex])) {
            $qty = $this->computedPhysicalTransferQty;
            $this->items[$this->calcItemIndex]['jumlah'] = $qty > 0 ? (string) $qty : '0';
        }

        $this->closePackagingCalculator();
    }

    public function addProductToTransfer(int|Produk $produk, float $qty = 1.0): void
    {
        $produkId = $produk instanceof Produk ? $produk->id : $produk;

        // Jika produk sudah ada di baris, tambah kuantitasnya
        foreach ($this->items as $idx => $item) {
            if ((int) ($item['produk_id'] ?? 0) === $produkId) {
                $currentQty = (float) ($item['jumlah'] ?? 0);
                $this->items[$idx]['jumlah'] = (string) ($currentQty + $qty);

                return;
            }
        }

        // Cari baris kosong pertama jika ada
        foreach ($this->items as $idx => $item) {
            if (empty($item['produk_id'])) {
                $this->items[$idx] = [
                    'produk_id' => $produkId,
                    'jumlah' => (string) $qty,
                ];

                return;
            }
        }

        // Jika tidak ada baris kosong, tambahkan baris baru
        $this->items[] = [
            'produk_id' => $produkId,
            'jumlah' => (string) $qty,
        ];
    }

    public function addQuickProduct(int $produkId): void
    {
        $this->addProductToTransfer($produkId, 1.0);
        $this->quickSearchQuery = '';
    }

    public function clearAllItems(): void
    {
        $this->items = [
            [
                'produk_id' => null,
                'jumlah' => '',
            ],
        ];
    }

    public function openCatalogModal(): void
    {
        $this->catalogSearchQuery = '';
        $this->catalogKategoriId = null;
        $this->catalogMerekId = null;
        $this->catalogSelectedProductIds = [];
        $this->isCatalogModalOpen = true;
    }

    public function closeCatalogModal(): void
    {
        $this->isCatalogModalOpen = false;
        $this->catalogSelectedProductIds = [];
        $this->catalogMerekId = null;
    }

    public function toggleCatalogProduct(int $produkId): void
    {
        if (in_array($produkId, $this->catalogSelectedProductIds, true)) {
            $this->catalogSelectedProductIds = array_values(array_filter($this->catalogSelectedProductIds, fn ($id) => $id !== $produkId));
        } else {
            $this->catalogSelectedProductIds[] = $produkId;
        }
    }

    public function selectAllFoundCatalogProducts(): void
    {
        $query = Produk::query();
        if (filled(trim($this->catalogSearchQuery))) {
            $term = trim($this->catalogSearchQuery);
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                    ->orWhere('kode_katalog', 'like', "%{$term}%");
            });
        }
        if ($this->catalogKategoriId) {
            $selectedKat = Kategori::with('children')->find($this->catalogKategoriId);
            $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                : [$this->catalogKategoriId];
            $query->whereIn('kategori_id', $catIds);
        }
        if ($this->catalogMerekId) {
            $query->where('merek_id', $this->catalogMerekId);
        }
        if ($this->onlyAvailableStock && $this->gudang_asal_id) {
            $gId = $this->gudang_asal_id;
            $query->whereHas('stok', function ($q) use ($gId) {
                $q->where('gudang_id', $gId)->where('jumlah', '>', 0);
            });
        }

        $foundIds = $query->pluck('id')->all();
        $this->catalogSelectedProductIds = array_values(array_unique(array_merge($this->catalogSelectedProductIds, $foundIds)));
    }

    public function clearSelectedCatalogProducts(): void
    {
        $this->catalogSelectedProductIds = [];
    }

    public function addSelectedCatalogProducts(): void
    {
        if (empty($this->catalogSelectedProductIds)) {
            $this->closeCatalogModal();

            return;
        }

        foreach ($this->catalogSelectedProductIds as $pId) {
            $this->addProductToTransfer((int) $pId, 1.0);
        }

        $this->closeCatalogModal();
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     nama: string,
     *     kode_katalog: string,
     *     kategori: string,
     *     merek: string,
     *     satuan: string,
     *     stok: float,
     *     foto_url: ?string
     * }>
     */
    public function getQuickSearchResultsProperty(): array
    {
        if (! filled(trim($this->quickSearchQuery))) {
            return [];
        }

        $term = trim($this->quickSearchQuery);
        $gudangId = $this->gudang_asal_id;

        $products = Produk::with(['kategori', 'merek', 'stok' => function ($q) use ($gudangId) {
            if ($gudangId) {
                $q->where('gudang_id', $gudangId);
            }
        }])
            ->where(function ($q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                    ->orWhere('kode_katalog', 'like', "%{$term}%")
                    ->orWhereHas('merek', fn ($mq) => $mq->where('nama_merek', 'like', "%{$term}%"));
            })
            ->limit(10)
            ->get();

        return $products->map(function ($p) use ($gudangId) {
            $stok = $gudangId ? (float) ($p->stok->first()?->jumlah ?? 0) : 0.0;

            return [
                'id' => $p->id,
                'nama' => $p->nama,
                'kode_katalog' => $p->kode_katalog ?? '-',
                'kategori' => $p->kategori?->nama_kategori ?? '-',
                'merek' => $p->merek?->nama_merek ?? '-',
                'satuan' => $p->satuan?->label() ?? 'Unit',
                'stok' => $stok,
                'foto_url' => $p->foto_url,
            ];
        })->toArray();
    }

    /**
     * @var array<int, array{
     *     produk_id: int|string|null,
     *     jumlah: float|int|string|null
     * }>
     */
    public array $items = [];

    protected function rules(): array
    {
        return [
            'gudang_asal_id' => ['required', 'integer', 'exists:gudang,id'],
            'gudang_tujuan_id' => ['required', 'integer', 'exists:gudang,id', 'different:gudang_asal_id'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'integer', 'exists:produk,id'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'gudang_asal_id.required' => 'Pilih gudang asal.',
            'gudang_tujuan_id.required' => 'Pilih gudang tujuan.',
            'gudang_tujuan_id.different' => 'Gudang tujuan tidak boleh sama dengan gudang asal.',
            'tanggal.required' => 'Tanggal transfer wajib diisi.',
            'items.required' => 'Minimal harus ada satu baris barang.',
            'items.min' => 'Minimal harus ada satu baris barang.',
            'items.*.produk_id.required' => 'Pilih produk.',
            'items.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'items.*.jumlah.gt' => 'Jumlah harus lebih besar dari 0.',
        ];
    }

    public function getTotalTransferredQuantityProperty(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += (float) ($item['jumlah'] ?? 0);
        }

        return $total;
    }

    /**
     * @return array<int>
     */
    public function getDuplicateProductIdsProperty(): array
    {
        $counts = [];
        foreach ($this->items as $item) {
            $pId = (int) ($item['produk_id'] ?? 0);
            if ($pId > 0) {
                $counts[$pId] = ($counts[$pId] ?? 0) + 1;
            }
        }

        $duplicates = [];
        foreach ($counts as $pId => $cnt) {
            if ($cnt > 1) {
                $duplicates[] = $pId;
            }
        }

        return $duplicates;
    }

    public function getHasOverflowItemsProperty(): bool
    {
        if (! $this->gudang_asal_id) {
            return false;
        }

        foreach ($this->items as $item) {
            $pId = (int) ($item['produk_id'] ?? 0);
            $qty = (float) ($item['jumlah'] ?? 0);
            if ($pId > 0 && $qty > 0) {
                $avail = $this->getAvailableStock($pId);
                if ($qty > $avail) {
                    return true;
                }
            }
        }

        return false;
    }

    public function mount(?int $from_gudang_id = null, ?int $to_gudang_id = null, ?int $produk_id = null): void
    {
        Gate::authorize('create', TransferGudang::class);
        $this->tanggal = Carbon::now()->toDateString();

        if ($from_gudang_id && Gudang::where('id', $from_gudang_id)->exists()) {
            $this->gudang_asal_id = $from_gudang_id;
        }

        if ($to_gudang_id && Gudang::where('id', $to_gudang_id)->exists() && $to_gudang_id !== $from_gudang_id) {
            $this->gudang_tujuan_id = $to_gudang_id;
        }

        if ($produk_id && Produk::where('id', $produk_id)->exists()) {
            $this->items = [
                [
                    'produk_id' => $produk_id,
                    'jumlah' => '1',
                ],
            ];
        } else {
            $this->addItem();
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'produk_id' => null,
            'jumlah' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function promptConfirmation(): void
    {
        $this->validate();
        $this->isConfirmModalOpen = true;
    }

    public function closeConfirmationModal(): void
    {
        $this->isConfirmModalOpen = false;
    }

    public function save(CreateTransferGudangAction $action): mixed
    {
        Gate::authorize('create', TransferGudang::class);
        $this->validate();

        try {
            $transfer = $action->execute([
                'gudang_asal_id' => (int) $this->gudang_asal_id,
                'gudang_tujuan_id' => (int) $this->gudang_tujuan_id,
                'tanggal' => $this->tanggal,
                'keterangan' => $this->keterangan,
                'items' => $this->items,
            ]);

            $this->isConfirmModalOpen = false;
            session()->flash('message', 'Transfer '.$transfer->no_transfer.' berhasil dicatat.');

            return redirect()->route('transfer.show', $transfer->id);
        } catch (Exception $e) {
            $this->isConfirmModalOpen = false;
            $this->addError('general', $e->getMessage());

            return null;
        }
    }

    public function selectProductForRow(int $index, int $produkId): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['produk_id'] = $produkId;
            if (empty($this->items[$index]['jumlah'])) {
                $this->items[$index]['jumlah'] = '1';
            }
        }
    }

    public function render(): View
    {
        Gate::authorize('create', TransferGudang::class);

        $gudangAsalId = $this->gudang_asal_id;
        $produkQuery = Produk::with(['kategori', 'merek', 'stok' => function ($q) use ($gudangAsalId) {
            if ($gudangAsalId) {
                $q->where('gudang_id', $gudangAsalId);
            }
        }])->orderBy('nama');

        if ($this->onlyAvailableStock && $gudangAsalId) {
            $produkQuery->whereHas('stok', function ($q) use ($gudangAsalId) {
                $q->where('gudang_id', $gudangAsalId)->where('jumlah', '>', 0);
            });
        }

        $allProduks = $produkQuery->get();
        $select2Products = $allProduks->map(function ($p) use ($gudangAsalId) {
            return [
                'id' => $p->id,
                'nama' => $p->nama,
                'kode' => $p->kode_katalog ?? '-',
                'kategori' => $p->kategori?->nama_kategori ?? '-',
                'merek' => $p->merek?->nama_merek ?? '-',
                'satuan' => $p->satuan?->label() ?? 'Unit',
                'stok' => $gudangAsalId ? (float) ($p->stok->first()?->jumlah ?? 0) : 0.0,
                'foto_url' => $p->foto_url,
            ];
        })->toArray();

        $catalogProducts = [];
        if ($this->isCatalogModalOpen) {
            $catQuery = Produk::with(['kategori', 'merek', 'stok' => function ($q) use ($gudangAsalId) {
                if ($gudangAsalId) {
                    $q->where('gudang_id', $gudangAsalId);
                }
            }]);

            if (filled(trim($this->catalogSearchQuery))) {
                $term = trim($this->catalogSearchQuery);
                $catQuery->where(function ($q) use ($term) {
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode_katalog', 'like', "%{$term}%");
                });
            }

            if ($this->catalogKategoriId) {
                $selectedKat = Kategori::with('children')->find($this->catalogKategoriId);
                $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                    ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                    : [$this->catalogKategoriId];
                $catQuery->whereIn('kategori_id', $catIds);
            }

            if ($this->catalogMerekId) {
                $catQuery->where('merek_id', $this->catalogMerekId);
            }

            if ($this->onlyAvailableStock && $gudangAsalId) {
                $catQuery->whereHas('stok', function ($q) use ($gudangAsalId) {
                    $q->where('gudang_id', $gudangAsalId)->where('jumlah', '>', 0);
                });
            }

            $catalogProducts = $catQuery->orderBy('nama')->get()->map(function ($p) use ($gudangAsalId) {
                return [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'kode_katalog' => $p->kode_katalog ?? '-',
                    'kategori' => $p->kategori?->nama_kategori ?? '-',
                    'merek' => $p->merek?->nama_merek ?? '-',
                    'satuan' => $p->satuan?->label() ?? 'Unit',
                    'stok' => $gudangAsalId ? (float) ($p->stok->first()?->jumlah ?? 0) : 0.0,
                    'foto_url' => $p->foto_url,
                ];
            })->toArray();
        }

        return view('livewire.inventory.transfer-create', [
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'produks' => $allProduks,
            'select2Products' => $select2Products,
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'rootKategoris' => Kategori::root()->with('children')->orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
            'catalogProducts' => $catalogProducts,
            'quickSearchResults' => $this->quickSearchResults,
        ]);
    }
}
