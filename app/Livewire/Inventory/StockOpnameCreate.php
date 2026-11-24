<?php

namespace App\Livewire\Inventory;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Actions\CreateStockOpnameAction;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Inventory\Services\StockOpnameExcelService;
use App\Domains\Inventory\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Mulai Stok Opname')]
class StockOpnameCreate extends Component
{
    use WithFileUploads;

    public string $tanggal = '';

    public ?int $gudang_id = null;

    public ?string $catatan = '';

    public ?int $selectedProdukId = null;

    /**
     * @var array<int, array{
     *     produk_id: int,
     *     nama_produk: string,
     *     kode_produk: string,
     *     satuan: string,
     *     stok_sistem: float,
     *     stok_fisik: float|int|string,
     *     catatan: string,
     *     kemasan?: string|null,
     *     isi_per_kemasan?: float|int|null,
     *     panjang_meter?: float|int|null,
     *     kategori?: string|null,
     *     merek?: string|null,
     *     foto_url?: string|null
     * }>
     */
    public array $items = [];

    // Quick Search
    public string $quickSearchQuery = '';

    // Bulk Catalog Modal
    public bool $isCatalogModalOpen = false;

    public string $catalogSearch = '';

    public ?int $catalogKategoriId = null;

    public ?int $catalogMerekId = null;

    /** @var array<int> */
    public array $catalogSelectedIds = [];

    // Filter Tabs: 'all', 'diff', 'match'
    public string $tableFilter = 'all';

    // Lightbox Image Preview Modal
    public bool $isImageModalOpen = false;

    public ?int $imageModalProdukId = null;

    // Modern Confirm Modal
    public bool $isConfirmModalOpen = false;

    // --- Poin 1: Smart Packaging Calculator ---
    public bool $isPackagingCalcOpen = false;

    public ?int $calcItemIndex = null;

    public string $calcItemName = '';

    public string $calcKemasanLabel = '';

    public ?float $calcIsiPerKemasan = null;

    public ?float $calcPanjangMeter = null;

    public string $calcSatuan = '';

    public float|int|string|null $calcJmlKemasan = null;

    public float|int|string|null $calcJmlEceran = null;

    // --- Poin 2: Smart Bulk Load ---
    public bool $isBulkLoadModalOpen = false;

    public ?int $bulkLoadKategoriId = null;

    public bool $bulkLoadOnlyWithStock = false;

    public string $bulkLoadMode = 'replace'; // 'replace' | 'append'

    // --- Poin 3: Physical Count Sheet Print ---
    public bool $isPrintCountSheetModalOpen = false;

    public string $countSheetMode = 'blind'; // 'blind' | 'guided'

    // --- Poin 5: Excel Import & Export ---
    public bool $isImportExcelModalOpen = false;

    public $excelCountFile = null;

    public ?array $importSummary = null;

    protected function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'gudang_id' => 'required|exists:gudang,id',
            'catatan' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.stok_fisik' => 'required|numeric|min:0',
            'items.*.catatan' => 'nullable|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required' => 'Minimal satu produk harus dimasukkan ke dalam stok opname.',
            'items.min' => 'Minimal satu produk harus dimasukkan ke dalam stok opname.',
            'items.*.stok_fisik.required' => 'Stok fisik wajib diisi.',
            'items.*.stok_fisik.numeric' => 'Stok fisik harus berupa angka.',
            'items.*.stok_fisik.min' => 'Stok fisik tidak boleh negatif.',
        ];
    }

    public function mount(): void
    {
        Gate::authorize('create', StockOpname::class);
        $this->tanggal = Carbon::now()->format('Y-m-d');
        $this->gudang_id = Gudang::first()?->id;
    }

    public function updatedGudangId(): void
    {
        $this->refreshStockSnapshots();
    }

    public function addProduct(): void
    {
        if (! $this->selectedProdukId || ! $this->gudang_id) {
            return;
        }

        $produk = Produk::with(['kategori', 'merek'])->find($this->selectedProdukId);
        $gudang = Gudang::find($this->gudang_id);

        if (! $produk || ! $gudang) {
            return;
        }

        foreach ($this->items as $item) {
            if ($item['produk_id'] === $produk->id) {
                $this->selectedProdukId = null;

                return;
            }
        }

        $stokSistem = (float) app(StockService::class)->getStock($produk, $gudang);

        $this->items[] = [
            'produk_id' => $produk->id,
            'nama_produk' => $produk->nama,
            'kode_produk' => $produk->kode_katalog ?? '-',
            'satuan' => $produk->satuan ? (is_object($produk->satuan) ? $produk->satuan->label() : (string) $produk->satuan) : 'Pcs',
            'stok_sistem' => $stokSistem,
            'stok_fisik' => $stokSistem,
            'catatan' => '',
            'kemasan' => $produk->kemasan,
            'isi_per_kemasan' => $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null,
            'panjang_meter' => $produk->panjang_meter ? (float) $produk->panjang_meter : null,
            'kategori' => $produk->kategori?->nama_kategori ?? '-',
            'merek' => $produk->merek?->nama_merek ?? '-',
            'foto_url' => $produk->foto_url,
        ];

        $this->selectedProdukId = null;
    }

    public function addQuickProduct(int $produkId): void
    {
        if (! $this->gudang_id) {
            return;
        }

        $produk = Produk::with(['kategori', 'merek'])->find($produkId);
        $gudang = Gudang::find($this->gudang_id);

        if (! $produk || ! $gudang) {
            return;
        }

        foreach ($this->items as $item) {
            if ($item['produk_id'] === $produk->id) {
                $this->quickSearchQuery = '';

                return;
            }
        }

        $stokSistem = (float) app(StockService::class)->getStock($produk, $gudang);

        $this->items[] = [
            'produk_id' => $produk->id,
            'nama_produk' => $produk->nama,
            'kode_produk' => $produk->kode_katalog ?? '-',
            'satuan' => $produk->satuan ? (is_object($produk->satuan) ? $produk->satuan->label() : (string) $produk->satuan) : 'Pcs',
            'stok_sistem' => $stokSistem,
            'stok_fisik' => $stokSistem,
            'catatan' => '',
            'kemasan' => $produk->kemasan,
            'isi_per_kemasan' => $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null,
            'panjang_meter' => $produk->panjang_meter ? (float) $produk->panjang_meter : null,
            'kategori' => $produk->kategori?->nama_kategori ?? '-',
            'merek' => $produk->merek?->nama_merek ?? '-',
            'foto_url' => $produk->foto_url,
        ];

        $this->quickSearchQuery = '';
    }

    #[Computed]
    public function quickSearchResults()
    {
        if (blank($this->quickSearchQuery) || strlen(trim($this->quickSearchQuery)) < 1) {
            return collect();
        }

        $query = trim($this->quickSearchQuery);
        $existingIds = array_column($this->items, 'produk_id');

        return Produk::whereNull('deleted_at')
            ->whereNotIn('id', $existingIds)
            ->where(function ($q) use ($query) {
                $q->where('nama', 'like', "%{$query}%")
                    ->orWhere('kode_katalog', 'like', "%{$query}%");
            })
            ->with(['kategori', 'merek'])
            ->limit(8)
            ->get();
    }

    // --- Bulk Catalog Modal ---
    public function openCatalogModal(): void
    {
        $this->catalogSearch = '';
        $this->catalogKategoriId = null;
        $this->catalogMerekId = null;
        $this->catalogSelectedIds = [];
        $this->isCatalogModalOpen = true;
    }

    public function closeCatalogModal(): void
    {
        $this->isCatalogModalOpen = false;
        $this->catalogSelectedIds = [];
        $this->catalogKategoriId = null;
        $this->catalogMerekId = null;
        $this->catalogSearch = '';
    }

    public function setCatalogMerek(?int $merekId): void
    {
        $this->catalogMerekId = ($this->catalogMerekId === $merekId) ? null : $merekId;
    }

    public function toggleCatalogProduct(int $produkId): void
    {
        if (in_array($produkId, $this->catalogSelectedIds, true)) {
            $this->catalogSelectedIds = array_values(array_diff($this->catalogSelectedIds, [$produkId]));
        } else {
            $this->catalogSelectedIds[] = $produkId;
        }
    }

    public function selectAllFoundCatalogProducts(): void
    {
        $products = $this->catalogProducts;
        $ids = $products->pluck('id')->all();

        $alreadySelected = true;
        foreach ($ids as $id) {
            if (! in_array($id, $this->catalogSelectedIds, true)) {
                $alreadySelected = false;
                break;
            }
        }

        if ($alreadySelected) {
            $this->catalogSelectedIds = array_values(array_diff($this->catalogSelectedIds, $ids));
        } else {
            $this->catalogSelectedIds = array_values(array_unique(array_merge($this->catalogSelectedIds, $ids)));
        }
    }

    public function addSelectedCatalogProducts(): void
    {
        if (! $this->gudang_id || empty($this->catalogSelectedIds)) {
            $this->closeCatalogModal();

            return;
        }

        $gudang = Gudang::find($this->gudang_id);
        if (! $gudang) {
            $this->closeCatalogModal();

            return;
        }

        $stockService = app(StockService::class);
        $products = Produk::whereIn('id', $this->catalogSelectedIds)->with(['kategori', 'merek'])->get();

        $existingIds = array_column($this->items, 'produk_id');

        $addedCount = 0;
        foreach ($products as $produk) {
            if (in_array($produk->id, $existingIds, true)) {
                continue;
            }

            $stokSistem = (float) $stockService->getStock($produk, $gudang);

            $this->items[] = [
                'produk_id' => $produk->id,
                'nama_produk' => $produk->nama,
                'kode_produk' => $produk->kode_katalog ?? '-',
                'satuan' => $produk->satuan ? (is_object($produk->satuan) ? $produk->satuan->label() : (string) $produk->satuan) : 'Pcs',
                'stok_sistem' => $stokSistem,
                'stok_fisik' => $stokSistem,
                'catatan' => '',
                'kemasan' => $produk->kemasan,
                'isi_per_kemasan' => $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null,
                'panjang_meter' => $produk->panjang_meter ? (float) $produk->panjang_meter : null,
                'kategori' => $produk->kategori?->nama_kategori ?? '-',
                'merek' => $produk->merek?->nama_merek ?? '-',
                'foto_url' => $produk->foto_url,
            ];
            $addedCount++;
        }

        $this->closeCatalogModal();

        if ($addedCount > 0) {
            session()->flash('info', "Berhasil menambahkan {$addedCount} produk terpilih ke daftar opname.");
        }
    }

    #[Computed]
    public function catalogProducts()
    {
        $existingIds = array_column($this->items, 'produk_id');

        $query = Produk::whereNull('deleted_at')
            ->whereNotIn('id', $existingIds)
            ->with(['kategori', 'merek']);

        if (filled($this->catalogSearch)) {
            $search = trim($this->catalogSearch);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_katalog', 'like', "%{$search}%");
            });
        }

        if (filled($this->catalogKategoriId)) {
            $selectedKat = Kategori::with('children')->find($this->catalogKategoriId);
            $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                : [$this->catalogKategoriId];
            $query->whereIn('kategori_id', $catIds);
        }

        if (filled($this->catalogMerekId)) {
            $query->where('merek_id', $this->catalogMerekId);
        }

        return $query->orderBy('nama')->limit(50)->get();
    }

    // --- Poin 1: Smart Packaging Calculator Methods ---
    public function openPackagingCalculator(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $pId = (int) ($item['produk_id'] ?? 0);
        $prod = $pId > 0 ? Produk::find($pId) : null;
        $isRoll = $prod ? $prod->isRollProduct() : (strcasecmp((string) ($item['kemasan'] ?? ''), 'roll') === 0 || (! empty($item['panjang_per_roll']) && (float) $item['panjang_per_roll'] > 0));

        $this->calcItemIndex = $index;
        $this->calcItemName = $item['nama_produk'];
        $this->calcKemasanLabel = $item['kemasan'] ?: ($isRoll ? 'Roll' : 'Dus / Kemasan Pabrik');
        $this->calcIsiPerKemasan = $item['isi_per_kemasan'] !== null ? (float) $item['isi_per_kemasan'] : ($prod && $prod->panjang_per_roll ? (float) $prod->panjang_per_roll : null);
        $this->calcPanjangMeter = $isRoll ? null : ($item['panjang_meter'] !== null ? (float) $item['panjang_meter'] : null);
        $this->calcSatuan = $item['satuan'];

        $this->calcJmlKemasan = null;
        $this->calcJmlEceran = null;
        $this->isPackagingCalcOpen = true;
    }

    public function closePackagingCalculator(): void
    {
        $this->isPackagingCalcOpen = false;
        $this->calcItemIndex = null;
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
    public function computedPhysicalStock(): float
    {
        if ($this->calcItemIndex === null || ! isset($this->items[$this->calcItemIndex])) {
            return 0.0;
        }

        $item = $this->items[$this->calcItemIndex];
        $pId = (int) ($item['produk_id'] ?? 0);
        $prod = $pId > 0 ? Produk::find($pId) : null;

        $jmlKemasan = (float) ($this->calcJmlKemasan ?: 0);
        $rawEceran = is_string($this->calcJmlEceran) ? str_replace(',', '.', $this->calcJmlEceran) : $this->calcJmlEceran;
        $jmlEceran = is_numeric($rawEceran) ? (float) $rawEceran : 0.0;
        $isiPerKemasan = (float) ($item['isi_per_kemasan'] ?: 0);
        $panjangMeter = (float) ($item['panjang_meter'] ?: 0);
        $satuan = strtolower((string) ($item['satuan'] ?? ''));
        $kemasan = strtolower((string) ($item['kemasan'] ?? ''));

        $isRoll = $prod ? $prod->isRollProduct() : (strcasecmp($kemasan, 'roll') === 0 || str_contains($satuan, 'roll') || (! empty($item['panjang_per_roll']) && (float) $item['panjang_per_roll'] > 0));

        // Case 0: Roll items with length in meters (e.g. Kasa Gypsum 90m, Bubble Foil 25m)
        if ($isRoll) {
            $rollLen = (float) ($item['panjang_per_roll'] ?? ($prod?->panjang_per_roll ?: ($isiPerKemasan ?: 25.0)));

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

        // Case 3: Items whose system unit IS the packaging itself (e.g. Satuan = 'Dus (dus/ktk)', Kemasan = 'Dus', Isi = 100 pcs)
        // 1 Kemasan = 1 System Unit! The loose items ($jmlEceran in pcs) are fractional: $jmlEceran / $isiPerKemasan
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

    public function applyPackagingCalculation(): void
    {
        if ($this->calcItemIndex !== null && isset($this->items[$this->calcItemIndex])) {
            $this->items[$this->calcItemIndex]['stok_fisik'] = $this->computedPhysicalStock;
        }

        $this->closePackagingCalculator();
    }

    // --- Poin 2: Smart Bulk Load Methods ---
    public function openBulkLoadModal(): void
    {
        $this->bulkLoadKategoriId = null;
        $this->bulkLoadOnlyWithStock = false;
        $this->bulkLoadMode = 'replace';
        $this->isBulkLoadModalOpen = true;
    }

    public function closeBulkLoadModal(): void
    {
        $this->isBulkLoadModalOpen = false;
    }

    public function executeBulkLoad(): void
    {
        if (! $this->gudang_id) {
            $this->addError('gudang_id', 'Pilih gudang terlebih dahulu sebelum memuat barang.');

            return;
        }

        $gudang = Gudang::find($this->gudang_id);
        if (! $gudang) {
            return;
        }

        $stockService = app(StockService::class);
        $query = Produk::whereNull('deleted_at')->with(['kategori.parent', 'merek']);

        if (filled($this->bulkLoadKategoriId)) {
            $selectedKat = Kategori::with('children')->find($this->bulkLoadKategoriId);
            $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                : [$this->bulkLoadKategoriId];
            $query->whereIn('kategori_id', $catIds);
        }

        $produks = $query->orderBy('nama')->get();

        if ($this->bulkLoadMode === 'replace') {
            $this->items = [];
        }

        $existingIds = array_column($this->items, 'produk_id');
        $addedCount = 0;

        foreach ($produks as $produk) {
            if (in_array($produk->id, $existingIds, true)) {
                continue;
            }

            $stokSistem = (float) $stockService->getStock($produk, $gudang);

            if ($this->bulkLoadOnlyWithStock && $stokSistem <= 0) {
                continue;
            }

            $this->items[] = [
                'produk_id' => $produk->id,
                'nama_produk' => $produk->nama,
                'kode_produk' => $produk->kode_katalog ?? '-',
                'satuan' => $produk->satuan ? (is_object($produk->satuan) ? $produk->satuan->label() : (string) $produk->satuan) : 'Pcs',
                'stok_sistem' => $stokSistem,
                'stok_fisik' => $stokSistem,
                'catatan' => '',
                'kemasan' => $produk->kemasan,
                'isi_per_kemasan' => $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null,
                'panjang_meter' => $produk->panjang_meter ? (float) $produk->panjang_meter : null,
                'kategori' => $produk->kategori?->nama_kategori ?? '-',
                'merek' => $produk->merek?->nama_merek ?? '-',
                'foto_url' => $produk->foto_url,
            ];
            $addedCount++;
        }

        $this->isBulkLoadModalOpen = false;
        session()->flash('info', "Berhasil memuat {$addedCount} produk ke tabel opname.");
    }

    // --- Poin 3: Physical Count Sheet Print Methods ---
    public function openPrintCountSheetModal(): void
    {
        $this->isPrintCountSheetModalOpen = true;
    }

    public function closePrintCountSheetModal(): void
    {
        $this->isPrintCountSheetModalOpen = false;
    }

    // --- Poin 5: Excel Import & Export Methods ---
    public function exportTemplate(StockOpnameExcelService $service, bool $blind = false)
    {
        if (empty($this->items)) {
            session()->flash('error', 'Tabel opname masih kosong. Tambahkan atau muat produk terlebih dahulu sebelum mengunduh template Excel.');

            return;
        }

        $gudang = Gudang::find($this->gudang_id);
        $gudangNama = $gudang ? $gudang->nama_gudang : 'Gudang';

        return $service->exportCountSheetTemplate($gudangNama, $this->items, $blind);
    }

    public function openImportExcelModal(): void
    {
        $this->excelCountFile = null;
        $this->importSummary = null;
        $this->isImportExcelModalOpen = true;
    }

    public function closeImportExcelModal(): void
    {
        $this->isImportExcelModalOpen = false;
        $this->excelCountFile = null;
        $this->importSummary = null;
    }

    public function importExcelCountSheet(StockOpnameExcelService $service): void
    {
        $this->validate([
            'excelCountFile' => 'required|file|mimes:xlsx|max:10240',
        ], [
            'excelCountFile.required' => 'Pilih file spreadsheet (.xlsx) terlebih dahulu.',
            'excelCountFile.mimes' => 'Format file harus berupa Excel (.xlsx).',
            'excelCountFile.max' => 'Ukuran file Excel maksimal 10 MB.',
        ]);

        $result = $service->parseImportCountSheet($this->excelCountFile, $this->items);

        $this->items = $result['items'];
        $this->importSummary = [
            'updated' => $result['updated_count'],
            'unmatched' => $result['unmatched_codes'],
            'errors' => $result['errors'],
        ];

        $this->excelCountFile = null;

        if ($result['updated_count'] > 0 && empty($result['errors'])) {
            session()->flash('success', "Import berhasil! {$result['updated_count']} item fisik berhasil diperbarui dari Excel.");
            $this->isImportExcelModalOpen = false;
        } elseif ($result['updated_count'] > 0) {
            session()->flash('warning', "Import selesai: {$result['updated_count']} item diperbarui, terdapat beberapa catatan.");
        } else {
            session()->flash('error', 'Tidak ada data stok fisik yang berhasil diimpor. Pastikan Kode Barang cocok dengan tabel opname.');
        }
    }

    // --- Batch Helpers ---
    public function setAllMatched(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['stok_fisik'] = $item['stok_sistem'];
        }
        session()->flash('info', 'Semua stok fisik telah disamakan dengan stok sistem.');
    }

    public function clearAllPhysicalStock(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['stok_fisik'] = 0;
        }
        session()->flash('info', 'Semua stok fisik telah diatur ke angka 0.');
    }

    public function clearAllItems(): void
    {
        $this->items = [];
        session()->flash('info', 'Semua produk telah dihapus dari tabel opname.');
    }

    public function loadAllProductsFromWarehouse(): void
    {
        if (! $this->gudang_id) {
            return;
        }

        $gudang = Gudang::find($this->gudang_id);
        if (! $gudang) {
            return;
        }

        $stockService = app(StockService::class);
        $produks = Produk::whereNull('deleted_at')->with(['kategori', 'merek'])->orderBy('nama')->get();

        $this->items = [];
        foreach ($produks as $produk) {
            $stokSistem = (float) $stockService->getStock($produk, $gudang);
            $this->items[] = [
                'produk_id' => $produk->id,
                'nama_produk' => $produk->nama,
                'kode_produk' => $produk->kode_katalog ?? '-',
                'satuan' => $produk->satuan ? (is_object($produk->satuan) ? $produk->satuan->label() : (string) $produk->satuan) : 'Pcs',
                'stok_sistem' => $stokSistem,
                'stok_fisik' => $stokSistem,
                'catatan' => '',
                'kemasan' => $produk->kemasan,
                'isi_per_kemasan' => $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null,
                'panjang_meter' => $produk->panjang_meter ? (float) $produk->panjang_meter : null,
                'kategori' => $produk->kategori?->nama_kategori ?? '-',
                'merek' => $produk->merek?->nama_merek ?? '-',
                'foto_url' => $produk->foto_url,
            ];
        }

        session()->flash('info', 'Memuat '.count($this->items).' produk dari katalog ke tabel opname.');
    }

    public function removeProduct(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function refreshStockSnapshots(): void
    {
        if (! $this->gudang_id) {
            return;
        }

        $gudang = Gudang::find($this->gudang_id);
        if (! $gudang) {
            return;
        }

        $stockService = app(StockService::class);
        foreach ($this->items as $index => $item) {
            $produk = Produk::find($item['produk_id']);
            if ($produk) {
                $this->items[$index]['stok_sistem'] = (float) $stockService->getStock($produk, $gudang);
            }
        }
    }

    #[Computed]
    public function availableProducts()
    {
        $existingIds = array_column($this->items, 'produk_id');

        return Produk::whereNull('deleted_at')
            ->whereNotIn('id', $existingIds)
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function filteredItems(): array
    {
        $results = [];
        foreach ($this->items as $index => $item) {
            $diff = (float) ($item['stok_fisik'] ?? 0) - (float) ($item['stok_sistem'] ?? 0);
            if ($this->tableFilter === 'diff' && $diff == 0.0) {
                continue;
            }
            if ($this->tableFilter === 'match' && $diff != 0.0) {
                continue;
            }
            $results[] = [
                'index' => $index,
                'item' => $item,
            ];
        }

        return $results;
    }

    #[Computed]
    public function countMatchedItems(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $diff = (float) ($item['stok_fisik'] ?? 0) - (float) ($item['stok_sistem'] ?? 0);
            if ($diff == 0.0) {
                $count++;
            }
        }

        return $count;
    }

    #[Computed]
    public function countDiffItems(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $diff = (float) ($item['stok_fisik'] ?? 0) - (float) ($item['stok_sistem'] ?? 0);
            if ($diff != 0.0) {
                $count++;
            }
        }

        return $count;
    }

    #[Computed]
    public function totalSelisihPlus(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $diff = (float) ($item['stok_fisik'] ?? 0) - (float) ($item['stok_sistem'] ?? 0);
            if ($diff > 0) {
                $total += $diff;
            }
        }

        return $total;
    }

    #[Computed]
    public function totalSelisihMinus(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $diff = (float) ($item['stok_fisik'] ?? 0) - (float) ($item['stok_sistem'] ?? 0);
            if ($diff < 0) {
                $total += $diff;
            }
        }

        return $total;
    }

    #[Computed]
    public function totalSelisihNet(): float
    {
        return $this->totalSelisihPlus + $this->totalSelisihMinus;
    }

    // --- Confirmation Modal Controls ---
    public function promptConfirmation(): void
    {
        $this->validate();
        $this->isConfirmModalOpen = true;
    }

    public function closeConfirmationModal(): void
    {
        $this->isConfirmModalOpen = false;
    }

    // --- Lightbox Image Preview Modal Controls ---
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

    public function saveDraft(CreateStockOpnameAction $action)
    {
        Gate::authorize('create', StockOpname::class);
        $this->validate();

        try {
            $stockOpname = $action->execute([
                'gudang_id' => (int) $this->gudang_id,
                'tanggal' => $this->tanggal,
                'catatan' => $this->catatan,
                'status' => StatusStockOpname::Draft,
                'items' => $this->items,
            ]);

            session()->flash('success', 'Draft Stok Opname '.$stockOpname->no_stock_opname.' berhasil disimpan.');

            return redirect()->route('inventory.stock-opname.detail', $stockOpname->id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function saveComplete(CreateStockOpnameAction $action)
    {
        Gate::authorize('create', StockOpname::class);
        $this->validate();

        try {
            $stockOpname = $action->execute([
                'gudang_id' => (int) $this->gudang_id,
                'tanggal' => $this->tanggal,
                'catatan' => $this->catatan,
                'status' => StatusStockOpname::Selesai,
                'items' => $this->items,
            ]);

            $this->isConfirmModalOpen = false;
            session()->flash('success', 'Stok Opname '.$stockOpname->no_stock_opname.' berhasil diselesaikan dan stok telah disesuaikan.');

            return redirect()->route('inventory.stock-opname.detail', $stockOpname->id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('create', StockOpname::class);

        return view('livewire.inventory.stock-opname-create', [
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'rootKategoris' => Kategori::root()->with('children')->orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
            'availableProducts' => $this->availableProducts,
        ]);
    }
}
