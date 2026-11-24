<?php

namespace App\Livewire\Purchasing;

use App\Domains\Catalog\Actions\CreateSupplierAction;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Inventory\Models\Stok;
use App\Domains\Purchasing\Actions\CreatePembelianAction;
use App\Domains\Purchasing\Enums\StatusPembelian;
use App\Domains\Purchasing\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tambah Pembelian')]
class PembelianCreate extends Component
{
    public ?int $supplier_id = null;

    public ?int $default_gudang_id = null;

    public string $tanggal = '';

    public string $no_faktur_supplier = '';

    public string $no_surat_jalan = '';

    public string $no_kontainer = '';

    public string $no_polisi = '';

    public string $status = 'received'; // 'received' (Diterima di Gudang) or 'ordered' (PO Dipesan)

    public string $status_pembayaran = 'lunas'; // 'lunas', 'tempo', 'sebagian'

    public string $tanggal_jatuh_tempo = '';

    public float|int|string $nominal_bayar_awal = '';

    public string $metode_pembayaran_awal = 'transfer';

    public string $keterangan = '';

    public bool $update_master_harga_beli = true;

    /**
     * @var array<int, array{
     *     produk_id: int|string|null,
     *     nama_produk?: string,
     *     kode_katalog?: string|null,
     *     gudang_id: int|string|null,
     *     jumlah: float|int|string|null,
     *     harga_beli_satuan: float|int|string|null,
     *     master_harga_beli?: float,
     *     subtotal: float|int|string,
     *     current_stock?: float,
     *     satuan?: string,
     *     satuan_value?: string,
     *     panjang_per_roll?: float|null
     * }>
     */
    public array $items = [];

    // Quick Product Search
    public string $quickSearchQuery = '';

    // Multi-Select Catalog Modal
    public bool $isCatalogModalOpen = false;

    public string $catalogSearchQuery = '';

    public ?int $catalogKategoriId = null;

    public ?int $catalogMerekId = null;

    public string $catalogStatusStok = '';

    public string $catalogViewTab = 'all'; // 'all' or 'selected'

    public int $catalogLimit = 50;

    /** @var array<int, int> */
    public array $catalogSelectedProductIds = [];

    // Lightbox Image Preview Modal
    public bool $isImageModalOpen = false;

    public ?int $imageModalProdukId = null;

    // Quick Add Supplier Modal
    public bool $isQuickSupplierModalOpen = false;

    public string $new_nama_supplier = '';

    public string $new_kontak_supplier = '';

    // Multifunctional Packaging / Unit Calculator Modal
    public bool $isCalculatorModalOpen = false;

    public ?int $activeItemIndex = null;

    public string $calcMode = 'kemasan'; // 'kemasan', 'dus_pvc', or 'roll'

    public float|int|string $packCount = 1;

    public float|int|string $packSize = 20;

    public string $packLabel = 'Kemasan';

    public float|int|string $pvcPanjang = 4.0;

    public float|int|string $rollCount = 1;

    public float|int|string $rollLength = 25;

    public string $calcTargetUnit = '';

    public string $calcProductName = '';

    // Backwards-compatible property for roll modal
    public bool $isRollModalOpen = false;

    // Smart Packaging Calculator Properties
    public ?string $calcItemName = null;

    public ?string $calcKemasanLabel = null;

    public ?float $calcIsiPerKemasan = null;

    public ?float $calcPanjangMeter = null;

    public ?string $calcSatuan = null;

    public ?float $calcJmlKemasan = null;

    public float|int|string|null $calcJmlEceran = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:supplier,id'],
            'tanggal' => ['required', 'date'],
            'no_faktur_supplier' => ['nullable', 'string', 'max:100'],
            'no_surat_jalan' => ['nullable', 'string', 'max:100'],
            'no_kontainer' => ['nullable', 'string', 'max:100'],
            'no_polisi' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:received,ordered'],
            'status_pembayaran' => ['required', 'string', 'in:lunas,tempo,sebagian'],
            'tanggal_jatuh_tempo' => ['nullable', 'date'],
            'nominal_bayar_awal' => ['nullable', 'numeric', 'min:0'],
            'metode_pembayaran_awal' => ['nullable', 'string', 'in:tunai,transfer,giro'],
            'keterangan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'integer', 'exists:produk,id'],
            'items.*.gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'items.*.harga_beli_satuan' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'supplier_id.required' => 'Pilih supplier terlebih dahulu.',
            'supplier_id.exists' => 'Supplier tidak valid.',
            'tanggal.required' => 'Tanggal pembelian wajib diisi.',
            'items.required' => 'Minimal harus ada satu baris barang.',
            'items.min' => 'Minimal harus ada satu baris barang.',
            'items.*.produk_id.required' => 'Pilih produk.',
            'items.*.gudang_id.required' => 'Pilih gudang tujuan.',
            'items.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'items.*.jumlah.gt' => 'Jumlah harus lebih besar dari 0.',
            'items.*.harga_beli_satuan.required' => 'Harga beli satuan wajib diisi.',
            'items.*.harga_beli_satuan.min' => 'Harga beli tidak boleh negatif.',
        ];
    }

    public function mount(): void
    {
        Gate::authorize('create', Pembelian::class);

        $this->tanggal = Carbon::now()->toDateString();
        $this->default_gudang_id = Gudang::first()?->id;
    }

    public function addItem(?int $produkId = null): void
    {
        $defaultGudang = $this->default_gudang_id ?? Gudang::first()?->id;

        $newItem = [
            'produk_id' => $produkId,
            'nama_produk' => '',
            'kode_katalog' => '',
            'gudang_id' => $defaultGudang,
            'qty_lembar' => null,
            'panjang_meter' => null,
            'kemasan' => null,
            'isi_per_kemasan' => null,
            'jumlah' => 1,
            'harga_bruto' => 0.0,
            'diskon_satuan' => 0.0,
            'harga_beli_satuan' => 0.0,
            'master_harga_beli' => 0.0,
            'subtotal' => 0.00,
            'current_stock' => 0.00,
            'satuan' => '',
            'satuan_value' => '',
            'panjang_per_roll' => null,
        ];

        if ($produkId) {
            $p = Produk::find($produkId);
            if ($p) {
                $isPvcSheet = $this->isPvcSheetProduct($p);
                $detectedLength = $isPvcSheet ? ($this->parseLengthFromProduct($p) ?? 4.0) : null;
                $initialLembar = $isPvcSheet ? 1.0 : null;
                $initialJumlah = $isPvcSheet ? round(1.0 * $detectedLength, 3) : 1.0;

                $newItem['nama_produk'] = $p->nama;
                $newItem['kode_katalog'] = $p->kode_katalog;
                $newItem['qty_lembar'] = $initialLembar;
                $newItem['panjang_meter'] = $detectedLength;
                $newItem['kemasan'] = $p->kemasan;
                $newItem['isi_per_kemasan'] = $p->isi_per_kemasan ? (float) $p->isi_per_kemasan : null;
                $newItem['jumlah'] = $initialJumlah;
                $newItem['harga_bruto'] = (float) $p->harga_beli;
                $newItem['harga_beli_satuan'] = (float) $p->harga_beli;
                $newItem['master_harga_beli'] = (float) $p->harga_beli;
                $newItem['subtotal'] = round($initialJumlah * (float) $p->harga_beli, 2);
                $newItem['satuan'] = $p->satuan?->label() ?? 'Unit';
                $newItem['satuan_value'] = $p->satuan?->value ?? '';
                $newItem['panjang_per_roll'] = $p->panjang_per_roll ? (float) $p->panjang_per_roll : null;
                $newItem['current_stock'] = $this->getStockFor($p->id, $defaultGudang);
            }
        }

        $this->items[] = $newItem;
    }

    public function duplicateItem(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $source = $this->items[$index];
        $cloned = $source;
        $this->items[] = $cloned;
        $this->recalculateSubtotals();

        $nama = $source['nama_produk'] ?? 'Barang';
        session()->flash('info_item', "Baris '{$nama}' berhasil diduplikasi.");
    }

    public function clearAllItems(): void
    {
        $this->items = [];
        $this->recalculateSubtotals();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateSubtotals();
    }

    public function getStockFor(int $produkId, ?int $gudangId): float
    {
        if ($produkId <= 0 || ! $gudangId) {
            return 0.0;
        }

        return (float) (Stok::where('produk_id', $produkId)->where('gudang_id', $gudangId)->value('jumlah') ?? 0.0);
    }

    public function setTempoDays(int $days): void
    {
        $baseDate = filled($this->tanggal) ? Carbon::parse($this->tanggal) : Carbon::now();
        $this->tanggal_jatuh_tempo = $baseDate->copy()->addDays($days)->toDateString();
    }

    public function updatedStatusPembayaran(string $value): void
    {
        if ($value === 'tempo' && blank($this->tanggal_jatuh_tempo)) {
            $this->setTempoDays(14);
        }
    }

    protected function isPvcSheetProduct(Produk $produk): bool
    {
        $nama = strtolower($produk->nama);
        if (str_contains($nama, 'gypsum') || str_contains($nama, 'papan') || str_contains($nama, 'semen') || str_contains($nama, 'hollow') || str_contains($nama, 'sekrup')) {
            return false;
        }

        if ($produk->satuan?->value === 'meter' && $produk->panjang_meter && (float) $produk->panjang_meter > 0) {
            return true;
        }

        $isPvc = str_contains($nama, 'pvc') || str_contains(strtolower($produk->kategori?->nama_kategori ?? ''), 'pvc');
        $isMeter = ($produk->satuan?->value === 'meter');

        return $isPvc && $isMeter;
    }

    protected function parseLengthFromProduct(Produk $produk): ?float
    {
        if ($produk->panjang_meter && (float) $produk->panjang_meter > 0) {
            return (float) $produk->panjang_meter;
        }

        $text = ($produk->ukuran ?? '').' '.$produk->nama.' '.($produk->kode_katalog ?? '');
        if (preg_match('/(\d+(?:\.\d+)?)\s*m\b/i', $text, $matches)) {
            $val = (float) $matches[1];
            if ($val >= 1 && $val <= 12) {
                return $val;
            }
        }

        return null;
    }

    public function updatedItems(mixed $value, ?string $key = null): void
    {
        if ($key && str_ends_with($key, '.produk_id')) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $produkId = (int) $value;

            if ($produkId > 0) {
                $produk = Produk::find($produkId);
                if ($produk) {
                    $isPvcSheet = $this->isPvcSheetProduct($produk);
                    $detectedLength = $isPvcSheet ? ($this->parseLengthFromProduct($produk) ?? 4.0) : null;
                    $initialLembar = $isPvcSheet ? 1.0 : null;
                    $initialJumlah = $isPvcSheet ? round(1.0 * $detectedLength, 3) : 1.0;

                    $this->items[$index]['nama_produk'] = $produk->nama;
                    $this->items[$index]['kode_katalog'] = $produk->kode_katalog;
                    $this->items[$index]['panjang_meter'] = $detectedLength;
                    $this->items[$index]['kemasan'] = $produk->kemasan;
                    $this->items[$index]['isi_per_kemasan'] = $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null;
                    $this->items[$index]['qty_lembar'] = $initialLembar;
                    $this->items[$index]['jumlah'] = $initialJumlah;
                    $this->items[$index]['harga_bruto'] = (float) $produk->harga_beli;
                    $this->items[$index]['master_harga_beli'] = (float) $produk->harga_beli;
                    if (empty($this->items[$index]['harga_beli_satuan']) || (float) $this->items[$index]['harga_beli_satuan'] === 0.0) {
                        $this->items[$index]['harga_beli_satuan'] = (float) $produk->harga_beli;
                    }
                    $this->items[$index]['satuan'] = $produk->satuan?->label() ?? 'Unit';
                    $this->items[$index]['satuan_value'] = $produk->satuan?->value ?? '';
                    $this->items[$index]['panjang_per_roll'] = $produk->panjang_per_roll ? (float) $produk->panjang_per_roll : null;
                    $this->items[$index]['foto_url'] = $produk->foto_url;
                    $gudangId = (int) ($this->items[$index]['gudang_id'] ?? $this->default_gudang_id);
                    $this->items[$index]['current_stock'] = $this->getStockFor($produkId, $gudangId);
                }
            }
        } elseif ($key && str_ends_with($key, '.gudang_id')) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $gudangId = (int) $value;
            $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);

            if ($produkId > 0 && $gudangId > 0) {
                $this->items[$index]['current_stock'] = $this->getStockFor($produkId, $gudangId);
            }
        } elseif ($key && (str_ends_with($key, '.qty_lembar') || str_ends_with($key, '.panjang_meter'))) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $qtyLembar = filled($this->items[$index]['qty_lembar'] ?? null) ? (float) $this->items[$index]['qty_lembar'] : 0.0;
            $panjang = filled($this->items[$index]['panjang_meter'] ?? null) ? (float) $this->items[$index]['panjang_meter'] : 0.0;
            if ($qtyLembar > 0 && $panjang > 0) {
                $this->items[$index]['jumlah'] = round($qtyLembar * $panjang, 3);
            }
        } elseif ($key && str_ends_with($key, '.jumlah')) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $panjang = filled($this->items[$index]['panjang_meter'] ?? null) ? (float) $this->items[$index]['panjang_meter'] : 0.0;
            $jumlah = filled($this->items[$index]['jumlah'] ?? null) ? (float) $this->items[$index]['jumlah'] : 0.0;
            if ($panjang > 0 && $jumlah > 0) {
                $this->items[$index]['qty_lembar'] = round($jumlah / $panjang, 2);
            }
        } elseif ($key && (str_ends_with($key, '.harga_bruto') || str_ends_with($key, '.diskon_satuan'))) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $bruto = filled($this->items[$index]['harga_bruto'] ?? null) ? (float) $this->items[$index]['harga_bruto'] : 0.0;
            $diskon = filled($this->items[$index]['diskon_satuan'] ?? null) ? (float) $this->items[$index]['diskon_satuan'] : 0.0;
            if ($bruto > 0) {
                $this->items[$index]['harga_beli_satuan'] = max(0.0, round($bruto - $diskon, 2));
            }
        }

        $this->recalculateSubtotals();
    }

    public function applyDefaultGudangToAll(): void
    {
        if (! $this->default_gudang_id) {
            return;
        }

        foreach ($this->items as $index => $item) {
            $this->items[$index]['gudang_id'] = $this->default_gudang_id;
            $produkId = (int) ($item['produk_id'] ?? 0);
            if ($produkId > 0) {
                $this->items[$index]['current_stock'] = $this->getStockFor($produkId, $this->default_gudang_id);
            }
        }

        session()->flash('info_gudang', 'Gudang tujuan berhasil diterapkan ke seluruh baris barang.');
    }

    public function addQuickProduct(int $produkId): void
    {
        $produk = Produk::find($produkId);
        if ($produk) {
            $this->addProductToItems($produk, 1.0);
        }
        $this->quickSearchQuery = '';
    }

    public function addProductToItems(Produk $produk, float $qty = 1.0): void
    {
        $targetGudangId = (int) ($this->default_gudang_id ?: Gudang::first()?->id);

        // Check if item with same product and warehouse already exists in items
        foreach ($this->items as $idx => $item) {
            if ((int) ($item['produk_id'] ?? 0) === $produk->id && (int) ($item['gudang_id'] ?? 0) === $targetGudangId) {
                $existingQty = (float) ($item['jumlah'] ?? 0);
                $this->items[$idx]['jumlah'] = $existingQty + $qty;
                $this->recalculateSubtotals();
                session()->flash('info_item', "Kuantitas '{$produk->nama}' ditambahkan menjadi ".($existingQty + $qty));

                return;
            }
        }

        // If the first row is empty, fill it
        $targetIndex = null;
        foreach ($this->items as $idx => $item) {
            if (empty($item['produk_id'])) {
                $targetIndex = $idx;
                break;
            }
        }

        $isPvcSheet = $this->isPvcSheetProduct($produk);
        $detectedLength = $isPvcSheet ? ($this->parseLengthFromProduct($produk) ?? 4.0) : null;
        $initialLembar = $isPvcSheet ? 1.0 : null;
        $initialJumlah = $isPvcSheet ? round(1.0 * $detectedLength, 3) : $qty;

        $newItem = [
            'produk_id' => $produk->id,
            'nama_produk' => $produk->nama,
            'kode_katalog' => $produk->kode_katalog,
            'gudang_id' => $targetGudangId,
            'qty_lembar' => $initialLembar,
            'panjang_meter' => $detectedLength,
            'kemasan' => $produk->kemasan,
            'isi_per_kemasan' => $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null,
            'jumlah' => $initialJumlah,
            'harga_bruto' => (float) $produk->harga_beli,
            'diskon_satuan' => 0.0,
            'harga_beli_satuan' => (float) $produk->harga_beli,
            'master_harga_beli' => (float) $produk->harga_beli,
            'subtotal' => round($initialJumlah * (float) $produk->harga_beli, 2),
            'current_stock' => $this->getStockFor($produk->id, $targetGudangId),
            'satuan' => $produk->satuan?->label() ?? 'Unit',
            'satuan_value' => $produk->satuan?->value ?? '',
            'panjang_per_roll' => $produk->panjang_per_roll ? (float) $produk->panjang_per_roll : null,
            'foto_url' => $produk->foto_url,
        ];

        if ($targetIndex !== null) {
            $this->items[$targetIndex] = $newItem;
        } else {
            $this->items[] = $newItem;
        }

        $this->recalculateSubtotals();
    }

    public function updatedStatus(string $value): void
    {
        if ($value === 'ordered') {
            $this->status_pembayaran = 'tempo';
            if (empty($this->tanggal_jatuh_tempo)) {
                $this->tanggal_jatuh_tempo = Carbon::now()->addDays(14)->toDateString();
            }
            $this->nominal_bayar_awal = '';
            $this->no_surat_jalan = '';
            $this->no_faktur_supplier = '';
            $this->no_polisi = '';
            $this->no_kontainer = '';
        } else {
            $this->status_pembayaran = 'lunas';
        }
    }

    public function updatedCatalogSearchQuery(): void
    {
        $this->catalogLimit = 50;
    }

    public function updatedCatalogKategoriId(): void
    {
        $this->catalogLimit = 50;
    }

    public function updatedCatalogMerekId(): void
    {
        $this->catalogLimit = 50;
    }

    public function setCatalogMerek(?int $merekId): void
    {
        $this->catalogMerekId = $merekId;
        $this->catalogLimit = 50;
    }

    public function setCatalogKategori(?int $kategoriId): void
    {
        $this->catalogKategoriId = $kategoriId;
        $this->catalogLimit = 50;
    }

    public function setCatalogViewTab(string $tab): void
    {
        $this->catalogViewTab = $tab;
    }

    public function loadMoreCatalog(): void
    {
        $this->catalogLimit += 50;
    }

    public function filterByCurrentSupplierBrands(): void
    {
        if (! $this->supplier_id) {
            return;
        }

        $supplier = Supplier::find($this->supplier_id);
        if (! $supplier) {
            return;
        }

        $supplierName = strtolower($supplier->nama_supplier);

        $merek = Merek::where(function ($q) use ($supplierName) {
            $q->whereRaw('LOWER(?) LIKE CONCAT("%", LOWER(nama_merek), "%")', [$supplierName]);
        })->first();

        if ($merek) {
            $this->catalogMerekId = $merek->id;
            $this->catalogLimit = 50;
        }
    }

    public function openCatalogModal(): void
    {
        $this->catalogSearchQuery = '';
        $this->catalogKategoriId = null;
        $this->catalogMerekId = null;
        $this->catalogStatusStok = '';
        $this->catalogViewTab = 'all';
        $this->catalogLimit = 50;
        $this->isCatalogModalOpen = true;

        $this->filterByCurrentSupplierBrands();
    }

    public function closeCatalogModal(): void
    {
        $this->isCatalogModalOpen = false;
        $this->catalogSelectedProductIds = [];
        $this->catalogViewTab = 'all';
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

        $this->applyCatalogStatusStokFilter($query);

        $foundIds = $query->limit(500)->pluck('id')->all();
        $this->catalogSelectedProductIds = array_values(array_unique(array_merge($this->catalogSelectedProductIds, $foundIds)));
    }

    protected function applyCatalogStatusStokFilter($query): void
    {
        if ($this->catalogStatusStok === 'kritis') {
            $query->whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) <= produk.stok_minimum');
        } elseif ($this->catalogStatusStok === 'menipis') {
            $query->whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) > 0 AND COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) <= produk.stok_minimum');
        } elseif ($this->catalogStatusStok === 'habis') {
            $query->whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) <= 0');
        } elseif ($this->catalogStatusStok === 'aman') {
            $query->whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) > produk.stok_minimum');
        }
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

        $produks = Produk::whereIn('id', $this->catalogSelectedProductIds)->get();
        foreach ($produks as $produk) {
            $this->addProductToItems($produk, 1.0);
        }

        $this->closeCatalogModal();
    }

    public function openQuickSupplierModal(): void
    {
        $this->new_nama_supplier = '';
        $this->new_kontak_supplier = '';
        $this->isQuickSupplierModalOpen = true;
    }

    public function closeQuickSupplierModal(): void
    {
        $this->isQuickSupplierModalOpen = false;
        $this->new_nama_supplier = '';
        $this->new_kontak_supplier = '';
    }

    public function saveQuickSupplier(CreateSupplierAction $action): void
    {
        $this->validate([
            'new_nama_supplier' => ['required', 'string', 'max:255', 'unique:supplier,nama_supplier'],
            'new_kontak_supplier' => ['nullable', 'string', 'max:100'],
        ], [
            'new_nama_supplier.required' => 'Nama supplier wajib diisi.',
            'new_nama_supplier.unique' => 'Nama supplier sudah terdaftar.',
        ]);

        $supplier = $action->execute([
            'nama_supplier' => $this->new_nama_supplier,
            'kontak' => $this->new_kontak_supplier,
        ]);

        $this->supplier_id = $supplier->id;
        $this->closeQuickSupplierModal();
        session()->flash('message_supplier', "Supplier '{$supplier->nama_supplier}' berhasil ditambahkan dan dipilih.");
    }

    public function recalculateSubtotals(): void
    {
        foreach ($this->items as $index => $item) {
            $qty = (float) ($item['jumlah'] ?? 0);
            $price = (float) ($item['harga_beli_satuan'] ?? 0);
            $this->items[$index]['subtotal'] = round($qty * $price, 2);
        }
    }

    public function getTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $qty = (float) ($item['jumlah'] ?? 0);
            $price = (float) ($item['harga_beli_satuan'] ?? 0);
            $total += round($qty * $price, 2);
        }

        return $total;
    }

    /**
     * @return array<string, float>
     */
    public function getPhysicalSummaryProperty(): array
    {
        $summary = [];
        foreach ($this->items as $item) {
            $qty = (float) ($item['jumlah'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $unit = ! empty($item['satuan']) ? $item['satuan'] : 'Unit';
            $summary[$unit] = ($summary[$unit] ?? 0.0) + $qty;
        }

        return $summary;
    }

    public function hasDuplicateRow(int $index): bool
    {
        if (! isset($this->items[$index])) {
            return false;
        }

        $currentProduk = $this->items[$index]['produk_id'] ?? null;
        $currentGudang = $this->items[$index]['gudang_id'] ?? null;

        if (! $currentProduk || ! $currentGudang) {
            return false;
        }

        foreach ($this->items as $idx => $item) {
            if ($idx !== $index && ($item['produk_id'] ?? null) == $currentProduk && ($item['gudang_id'] ?? null) == $currentGudang) {
                return true;
            }
        }

        return false;
    }

    public function openCalculator(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $this->activeItemIndex = $index;
        $item = $this->items[$index];
        $produkId = (int) ($item['produk_id'] ?? 0);
        $produk = $produkId > 0 ? Produk::find($produkId) : null;

        $this->calcProductName = $produk?->nama ?? ($item['nama_produk'] ?? 'Produk');
        $this->calcItemName = $this->calcProductName;
        $this->calcTargetUnit = $item['satuan'] ?? ($produk?->satuan->label() ?? 'Unit');
        $this->calcSatuan = $this->calcTargetUnit;
        $satuanVal = $item['satuan_value'] ?? ($produk?->satuan->value ?? '');

        $panjangMeter = filled($item['panjang_meter'] ?? null) ? (float) $item['panjang_meter'] : ($produk?->panjang_meter ? (float) $produk->panjang_meter : null);
        $isiPerKemasan = filled($item['isi_per_kemasan'] ?? null) ? (float) $item['isi_per_kemasan'] : ($produk?->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null);
        $kemasanLabel = $item['kemasan'] ?? ($produk?->kemasan ?: 'Kemasan');

        $this->calcPanjangMeter = $panjangMeter;
        $this->calcIsiPerKemasan = $isiPerKemasan;
        $this->calcKemasanLabel = $kemasanLabel;

        $isRollProduct = ($satuanVal === 'roll') ||
            ($satuanVal === 'meter' && (
                ($produk?->panjang_per_roll && (float) $produk->panjang_per_roll > 0) ||
                (! empty($item['panjang_per_roll']) && (float) $item['panjang_per_roll'] > 0) ||
                strcasecmp((string) $kemasanLabel, 'roll') === 0 ||
                str_contains(strtolower($this->calcProductName), 'foil') ||
                str_contains(strtolower($this->calcProductName), 'lakban') ||
                str_contains(strtolower($this->calcProductName), 'kasa')
            ));

        $isDusPackProduct = ($satuanVal === 'dus' || $satuanVal === 'pack' || $satuanVal === 'box' || strcasecmp($satuanVal, (string) $kemasanLabel) === 0);

        if ($satuanVal === 'meter' && $panjangMeter && $panjangMeter > 0 && ! $isRollProduct) {
            $this->calcMode = 'dus_pvc';
            $this->packLabel = $kemasanLabel ?: 'Dus';
            $this->packSize = $isiPerKemasan ?: 15;
            $this->pvcPanjang = $panjangMeter;

            $currentLembar = filled($item['qty_lembar'] ?? null) ? (float) $item['qty_lembar'] : 0.0;
            if ($currentLembar > 0 && (float) $this->packSize > 0) {
                $this->calcJmlKemasan = (float) floor($currentLembar / (float) $this->packSize);
                $this->calcJmlEceran = (float) ($currentLembar - ($this->calcJmlKemasan * (float) $this->packSize));
                $this->packCount = max(1, (int) $this->calcJmlKemasan);
            } else {
                $this->calcJmlKemasan = 1.0;
                $this->calcJmlEceran = 0.0;
                $this->packCount = 1;
            }
        } elseif ($isRollProduct) {
            $this->calcMode = 'roll';
            $panjangRoll = (float) ($item['panjang_per_roll'] ?? ($produk?->panjang_per_roll ?: ($isiPerKemasan ?: 25)));
            $this->rollLength = $panjangRoll;
            $this->rollCount = 1;
            $this->calcPanjangMeter = null;
            $this->calcIsiPerKemasan = $panjangRoll;
            $this->calcKemasanLabel = 'Roll';

            $currentJumlah = (float) ($item['jumlah'] ?? 0);
            if ($currentJumlah > 0 && $panjangRoll > 0) {
                $this->calcJmlKemasan = (float) floor($currentJumlah / $panjangRoll);
                $this->calcJmlEceran = (float) fmod($currentJumlah, $panjangRoll);
                $this->rollCount = max(1, (int) $this->calcJmlKemasan);
            } else {
                $this->calcJmlKemasan = 1.0;
                $this->calcJmlEceran = 0.0;
                $this->rollCount = 1;
            }
        } elseif ($isDusPackProduct) {
            $this->calcMode = 'kemasan_dus';
            $this->packLabel = $kemasanLabel ?: 'Dus';
            $this->packSize = $isiPerKemasan ?: 100;
            $currentJumlah = (float) ($item['jumlah'] ?? 0);
            $this->calcJmlKemasan = $currentJumlah > 0 ? $currentJumlah : 1.0;
            $this->calcJmlEceran = 0.0;
            $this->packCount = $this->calcJmlKemasan;
        } else {
            $this->calcMode = 'kemasan';
            $this->packLabel = $kemasanLabel ?: 'Kemasan';
            $this->packSize = $isiPerKemasan ?: match ($satuanVal) {
                'batang' => 20,
                'lembar' => 10,
                'pcs' => 1000,
                default => 10,
            };
            $currentJumlah = (float) ($item['jumlah'] ?? 0);
            if ($currentJumlah > 0 && (float) $this->packSize > 0) {
                $this->calcJmlKemasan = (float) floor($currentJumlah / (float) $this->packSize);
                $this->calcJmlEceran = (float) fmod($currentJumlah, (float) $this->packSize);
                $this->packCount = max(1, (int) $this->calcJmlKemasan);
            } else {
                $this->calcJmlKemasan = 1.0;
                $this->calcJmlEceran = 0.0;
                $this->packCount = 1;
            }
        }

        $this->isCalculatorModalOpen = true;
        $this->isRollModalOpen = true;
    }

    public function updatedPackCount(mixed $value): void
    {
        $this->calcJmlKemasan = (float) $value;
        $this->calcJmlEceran = 0.0;
    }

    public function updatedPackSize(mixed $value): void
    {
        $this->calcIsiPerKemasan = (float) $value;
    }

    public function updatedRollCount(mixed $value): void
    {
        $this->calcJmlKemasan = (float) $value;
        $this->calcJmlEceran = 0.0;
    }

    public function updatedRollLength(mixed $value): void
    {
        $this->calcIsiPerKemasan = (float) $value;
    }

    public function updatedCalcJmlKemasan(mixed $value): void
    {
        $this->packCount = (float) $value;
        $this->rollCount = (float) $value;
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
        if ($this->activeItemIndex === null || ! isset($this->items[$this->activeItemIndex])) {
            return 0.0;
        }

        $jmlKemasan = (float) ($this->calcJmlKemasan ?: ($this->calcMode === 'roll' ? $this->rollCount : 0));
        $rawEceran = is_string($this->calcJmlEceran) ? str_replace(',', '.', $this->calcJmlEceran) : $this->calcJmlEceran;
        $jmlEceran = is_numeric($rawEceran) ? (float) $rawEceran : 0.0;
        $isiPerKemasan = (float) ($this->calcIsiPerKemasan ?: 0);
        $panjangMeter = (float) ($this->calcPanjangMeter ?: 0);
        $satuan = strtolower((string) ($this->calcSatuan ?? ''));
        $kemasan = strtolower((string) ($this->calcKemasanLabel ?? ''));

        // Case 0: Roll items with length in meters (e.g. Kasa Gypsum 90m, Bubble Foil 25m)
        if ($this->calcMode === 'roll' || strcasecmp($kemasan, 'roll') === 0 || str_contains($satuan, 'roll')) {
            $rollLen = (float) ($this->rollLength ?: ($isiPerKemasan ?: 25.0));

            return (float) (($jmlKemasan * $rollLen) + $jmlEceran);
        }

        // Case 1: Plafon PVC sold in meters (dus of sheets with length)
        if ($this->calcMode === 'dus_pvc' && str_contains($satuan, 'meter') && $panjangMeter > 0 && $isiPerKemasan > 0) {
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

    public function applyCalculation(): void
    {
        if ($this->activeItemIndex === null || ! isset($this->items[$this->activeItemIndex])) {
            $this->closeCalculator();

            return;
        }

        $index = $this->activeItemIndex;

        $jmlKemasan = (float) ($this->calcJmlKemasan ?: 0);
        $rawEceran = is_string($this->calcJmlEceran) ? str_replace(',', '.', $this->calcJmlEceran) : $this->calcJmlEceran;
        $jmlEceran = is_numeric($rawEceran) ? (float) $rawEceran : 0.0;
        $isiPerKemasan = (float) ($this->calcIsiPerKemasan ?: 0);
        $panjangMeter = (float) ($this->calcPanjangMeter ?: 0);
        $satuan = strtolower((string) ($this->calcSatuan ?? ''));
        $kemasan = strtolower((string) ($this->calcKemasanLabel ?? ''));

        $totalQty = $this->computedPhysicalStock;

        // If Roll item:
        if ($this->calcMode === 'roll' || strcasecmp($kemasan, 'roll') === 0) {
            $this->items[$index]['jumlah'] = $totalQty;
            $this->items[$index]['qty_lembar'] = null;
            $this->items[$index]['panjang_meter'] = null;
        } elseif ($this->calcMode === 'dus_pvc' && str_contains($satuan, 'meter') && $panjangMeter > 0 && $isiPerKemasan > 0) {
            $totalLembar = (float) (($jmlKemasan * $isiPerKemasan) + $jmlEceran);
            $this->items[$index]['qty_lembar'] = $totalLembar;
            $this->items[$index]['panjang_meter'] = $panjangMeter;
            $this->items[$index]['jumlah'] = $totalQty;
        } else {
            $this->items[$index]['jumlah'] = $totalQty;
        }

        $this->recalculateSubtotals();
        $this->closeCalculator();
    }

    public function getItemPhysicalBadge(array $item): ?string
    {
        $produkId = (int) ($item['produk_id'] ?? 0);
        $jumlah = (float) ($item['jumlah'] ?? 0);
        if ($jumlah <= 0) {
            return null;
        }

        $panjangMeter = filled($item['panjang_meter'] ?? null) ? (float) $item['panjang_meter'] : null;
        $qtyLembar = filled($item['qty_lembar'] ?? null) ? (float) $item['qty_lembar'] : null;
        $kemasan = ! empty($item['kemasan']) ? (string) $item['kemasan'] : null;
        $isiPerKemasan = filled($item['isi_per_kemasan'] ?? null) ? (float) $item['isi_per_kemasan'] : null;
        $satuan = $item['satuan'] ?? '';
        $satuanVal = strtolower($item['satuan_value'] ?? $satuan);

        if ($produkId > 0 && ($panjangMeter === null && $isiPerKemasan === null)) {
            $p = Produk::find($produkId);
            if ($p) {
                $konv = $p->getKonversiFisik($jumlah);

                return $konv['has_conversion'] ? $konv['badge_text'] : null;
            }
        }

        $panjangRoll = filled($item['panjang_per_roll'] ?? null) ? (float) $item['panjang_per_roll'] : null;
        $isRollItem = (strcasecmp($kemasan ?? '', 'roll') === 0) || ($panjangRoll && $panjangRoll > 0);
        if ($isRollItem) {
            if ($produkId > 0) {
                $p = Produk::find($produkId);
                if ($p) {
                    $konv = $p->getKonversiFisik($jumlah);
                    if ($konv['has_conversion']) {
                        return $konv['badge_text'];
                    }
                }
            }
            $len = $panjangRoll ?: ($isiPerKemasan ?: 1.0);
            if ($len > 0) {
                $rollUtuh = (int) floor($jumlah / $len);
                $sisaMeter = (int) round($jumlah - ($rollUtuh * $len));
                if ($rollUtuh > 0) {
                    $detail = "{$rollUtuh} roll".($sisaMeter > 0 ? " {$sisaMeter}m" : ' pas');

                    return "± {$rollUtuh} roll ({$detail})";
                }

                return "± 0 roll ({$sisaMeter}m)";
            }
        }

        if ($panjangMeter && $panjangMeter > 0) {
            $lembar = $qtyLembar ?: round($jumlah / $panjangMeter, 2);
            if ($isiPerKemasan && $isiPerKemasan > 0) {
                $kemasanName = strtolower($kemasan ?: 'dus');
                $kemasanUtuh = (int) floor($lembar / $isiPerKemasan);
                $sisa = (int) ($lembar - ($kemasanUtuh * $isiPerKemasan));
                if ($kemasanUtuh > 0) {
                    $detail = "{$kemasanUtuh} {$kemasanName}".($sisa > 0 ? " {$sisa} lbr" : ' pas');

                    return "± {$lembar} lbr ({$detail})";
                }

                return "± {$lembar} lbr ({$sisa} lbr)";
            }

            return "± {$lembar} lbr ({$panjangMeter}m)";
        }

        if ($isiPerKemasan && $isiPerKemasan > 0) {
            $kemasanName = $kemasan ?: 'Kemasan';

            // Jika satuan pembelian adalah Dus / Pack / Box (kemasan dasar)
            if ($satuanVal === 'dus' || $satuanVal === 'pack' || $satuanVal === 'box' || strcasecmp($satuanVal, $kemasanName) === 0) {
                $totalPcs = round($jumlah * $isiPerKemasan, 0);

                return '± '.number_format($totalPcs, 0, ',', '.').' pcs (@ '.number_format($isiPerKemasan, 0, ',', '.').' pcs/'.strtolower($kemasanName).')';
            }

            // Jika satuan pembelian adalah eceran (Batang, Pcs, Lembar) yang dikemas per Dus / Ikat
            $jmlKemasan = round($jumlah / $isiPerKemasan, 1);

            return "± {$jmlKemasan} {$kemasanName} (@ ".number_format($isiPerKemasan, 0, ',', '.')." {$satuan})";
        }

        $panjangRoll = filled($item['panjang_per_roll'] ?? null) ? (float) $item['panjang_per_roll'] : null;
        if ($panjangRoll && $panjangRoll > 0) {
            $rolls = round($jumlah / $panjangRoll, 1);

            return "± {$rolls} roll (@ ".number_format($panjangRoll, 0, ',', '.').'m)';
        }

        return null;
    }

    public function closeCalculator(): void
    {
        $this->isCalculatorModalOpen = false;
        $this->isRollModalOpen = false;
        $this->activeItemIndex = null;
        $this->calcItemName = null;
        $this->calcKemasanLabel = null;
        $this->calcIsiPerKemasan = null;
        $this->calcPanjangMeter = null;
        $this->calcSatuan = null;
        $this->calcJmlKemasan = null;
        $this->calcJmlEceran = null;
    }

    public function openRollConverter(int $index): void
    {
        $this->openCalculator($index);
        $this->calcMode = 'roll';
    }

    public function applyRollConversion(): void
    {
        $this->applyCalculation();
    }

    public function closeRollConverter(): void
    {
        $this->closeCalculator();
    }

    public function save(CreatePembelianAction $action): mixed
    {
        Gate::authorize('create', Pembelian::class);

        $this->validate();

        $pembelian = $action->execute([
            'supplier_id' => (int) $this->supplier_id,
            'tanggal' => $this->tanggal,
            'no_faktur_supplier' => $this->no_faktur_supplier,
            'no_surat_jalan' => $this->no_surat_jalan,
            'no_kontainer' => $this->no_kontainer,
            'no_polisi' => $this->no_polisi,
            'status' => $this->status,
            'status_pembayaran' => $this->status_pembayaran,
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
            'nominal_bayar_awal' => filled($this->nominal_bayar_awal) ? (float) $this->nominal_bayar_awal : null,
            'metode_pembayaran_awal' => $this->metode_pembayaran_awal,
            'keterangan' => $this->keterangan,
            'items' => $this->items,
            'update_master_harga_beli' => $this->update_master_harga_beli,
        ]);

        $statusMsg = $pembelian->status === StatusPembelian::Received
            ? "Pembelian {$pembelian->no_pembelian} berhasil dicatat dan stok telah ditambahkan ke gudang."
            : "Surat Pesanan Pembelian (PO) {$pembelian->no_pembelian} berhasil dibuat. Stok akan bertambah saat barang diterima.";

        session()->flash('message', $statusMsg);

        return redirect()->route('pembelian.show', $pembelian->id);
    }

    public function render(): View
    {
        Gate::authorize('create', Pembelian::class);

        $quickSearchResults = filled($this->quickSearchQuery)
            ? Produk::with(['kategori', 'merek'])
                ->where(function ($q) {
                    $term = trim($this->quickSearchQuery);
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode_katalog', 'like', "%{$term}%");
                })
                ->take(8)
                ->get()
            : collect();

        $catalogBaseQuery = Produk::with(['kategori', 'merek'])
            ->withSum('stok', 'jumlah')
            ->when(filled($this->catalogSearchQuery), function ($q) {
                $term = trim($this->catalogSearchQuery);
                $q->where(function ($sub) use ($term) {
                    $sub->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode_katalog', 'like', "%{$term}%");
                });
            })
            ->when($this->catalogKategoriId, function ($q) {
                $selectedKat = Kategori::with('children')->find($this->catalogKategoriId);
                $catIds = ($selectedKat && $selectedKat->children->isNotEmpty())
                    ? $selectedKat->children->pluck('id')->push($selectedKat->id)
                    : [$this->catalogKategoriId];
                $q->whereIn('kategori_id', $catIds);
            })
            ->when($this->catalogMerekId, fn ($q) => $q->where('merek_id', $this->catalogMerekId));

        $this->applyCatalogStatusStokFilter($catalogBaseQuery);

        if ($this->catalogViewTab === 'selected') {
            $catalogBaseQuery->whereIn('id', $this->catalogSelectedProductIds);
        }

        $catalogTotalCount = $this->isCatalogModalOpen ? (clone $catalogBaseQuery)->count() : 0;
        $catalogProducts = $this->isCatalogModalOpen
            ? $catalogBaseQuery->orderBy('nama')->take($this->catalogLimit)->get()
            : collect();

        return view('livewire.purchasing.pembelian-create', [
            'suppliers' => Supplier::orderBy('nama_supplier')->get(),
            'produks' => Produk::with(['kategori', 'merek'])->orderBy('nama')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'rootKategoris' => Kategori::root()->with('children')->orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
            'quickSearchResults' => $quickSearchResults,
            'catalogProducts' => $catalogProducts,
            'catalogTotalCount' => $catalogTotalCount,
        ]);
    }
}
