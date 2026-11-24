<?php

namespace App\Livewire\Sales;

use App\Domains\Catalog\Actions\CreatePelangganAction;
use App\Domains\Catalog\Actions\UpdatePelangganAction;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Actions\CreateTransferGudangAction;
use App\Domains\Inventory\Services\StockService;
use App\Domains\Receivables\Actions\CreatePembayaranAction;
use App\Domains\Receivables\Enums\MetodePembayaran;
use App\Domains\Sales\Actions\CreatePenjualanAction;
use App\Domains\Sales\Enums\JenisDiskon;
use App\Domains\Sales\Models\Penjualan;
use App\Domains\Sales\Services\DiscountCalculationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tambah Penjualan')]
class PenjualanCreate extends Component
{
    #[Url]
    public ?int $duplicate_id = null;

    public ?int $pelanggan_id = null;

    public ?int $gudang_id = null;

    public string $tanggal = '';

    public float|int|string $biaya_ongkir = 0;

    public string $keterangan = '';

    // Informasi Pengiriman / Sopir (Opsional untuk Surat Jalan)
    public string $nama_sopir = '';

    public string $plat_nomor = '';

    public string $alamat_pengiriman = '';

    // POS Cashier & Payment State
    public string $tipe_pembayaran = 'tunai'; // 'tunai', 'transfer', 'belum_bayar'

    public float|int|string $jumlah_bayar = 0;

    public bool $isManualJumlahBayar = false;

    public string $pasca_simpan = 'lihat'; // 'lihat', 'baru', 'cetak_kilat'

    // Quick Print Modal State (Tablet / POS)
    public bool $isPrintModalOpen = false;

    public ?int $lastSavedPenjualanId = null;

    public ?string $lastSavedNoPenjualan = null;

    public float $lastSavedTotal = 0.0;

    public float $lastSavedKembalian = 0.0;

    public string $lastSavedMetode = '';

    // Explicit Calculation Properties (Prevents Livewire 3 computed getter memoization stale bugs)
    public float $subtotalPenjualan = 0.0;

    public float $totalKuantitasFisik = 0.0;

    public float $totalAkhir = 0.0;

    public float $kembalian = 0.0;

    public float $sisaPiutangBaru = 0.0;

    /**
     * @var array<int, array{
     *     produk_id: int|string|null,
     *     gudang_id: int|string|null,
     *     harga_satuan: float|int|string|null,
     *     harga_normal?: float|int|string|null,
     *     jumlah: float|int|string|null,
     *     jenis_diskon: string|null,
     *     nilai_diskon: float|int|string|null,
     *     subtotal: float|int|string,
     *     current_stock: float
     * }>
     */
    public array $items = [];

    // Negative stock shortage warning modal
    public bool $isShortageModalOpen = false;

    public array $shortageWarnings = [];

    public bool $confirmedShortage = false;

    public string $shortageAlasan = 'barang_fisik_tiba'; // 'barang_fisik_tiba', 'izin_supervisor', 'lainnya'

    public string $shortageCatatanLainnya = '';

    // Packaging Calculator State
    public bool $isCalculatorModalOpen = false;

    public ?int $activeItemIndex = null;

    public string $calcProductName = '';

    public string $calcItemName = '';

    public string $calcTargetUnit = '';

    public string $calcSatuan = '';

    public string $calcMode = 'kemasan'; // 'dus_pvc', 'kemasan', 'kemasan_dus', 'roll'

    public string $packLabel = 'Kemasan';

    public float|int|string|null $packCount = 1;

    public float|int|string|null $packSize = 1;

    public float|int|string|null $pvcPanjang = 4.0;

    public float|int|string|null $rollCount = 1;

    public float|int|string|null $rollLength = 25.0;

    public ?float $calcPanjangMeter = null;

    public ?float $calcIsiPerKemasan = null;

    public ?string $calcKemasanLabel = null;

    public float|int|string|null $calcJmlKemasan = null;

    public float|int|string|null $calcJmlEceran = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'pelanggan_id' => ['required', 'integer', 'exists:pelanggan,id'],
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'tanggal' => ['required', 'date'],
            'biaya_ongkir' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'nama_sopir' => ['nullable', 'string', 'max:255'],
            'plat_nomor' => ['nullable', 'string', 'max:50'],
            'alamat_pengiriman' => ['nullable', 'string', 'max:500'],
            'tipe_pembayaran' => ['required', 'string', 'in:belum_bayar,tunai,transfer'],
            'jumlah_bayar' => ['nullable', 'numeric', 'min:0'],
            'pasca_simpan' => ['required', 'string', 'in:lihat,baru,cetak_kilat'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'integer', 'exists:produk,id'],
            'items.*.gudang_id' => ['nullable', 'integer', 'exists:gudang,id'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'items.*.jenis_diskon' => ['nullable', 'string', 'in:nominal,persentase'],
            'items.*.nilai_diskon' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'pelanggan_id.required' => 'Pilih pelanggan terlebih dahulu.',
            'gudang_id.required' => 'Pilih gudang sumber barang.',
            'tanggal.required' => 'Tanggal penjualan wajib diisi.',
            'biaya_ongkir.min' => 'Biaya ongkir tidak boleh negatif.',
            'items.required' => 'Minimal harus ada satu baris barang.',
            'items.min' => 'Minimal harus ada satu baris barang.',
            'items.*.produk_id.required' => 'Pilih produk.',
            'items.*.harga_satuan.required' => 'Harga satuan wajib diisi.',
            'items.*.harga_satuan.min' => 'Harga satuan tidak boleh negatif.',
            'items.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'items.*.jumlah.gt' => 'Jumlah barang harus lebih besar dari 0.',
            'items.*.nilai_diskon.min' => 'Nilai diskon tidak boleh negatif.',
        ];
    }

    // Quick Search Bar state
    public string $quickSearchQuery = '';

    // Multi-Add Catalog Modal state
    public bool $isCatalogModalOpen = false;

    public string $catalogSearchQuery = '';

    public ?int $catalogKategoriId = null;

    public ?int $catalogMerekId = null;

    public string $catalogStockFilter = 'all'; // 'all', 'ready'

    /** @var array<int, int> */
    public array $catalogSelectedProductIds = [];

    // Lightbox Image Preview Modal
    public bool $isImageModalOpen = false;

    public ?int $imageModalProdukId = null;

    // Searchable Pelanggan state
    public string $pelangganSearchQuery = '';

    // Directory Pelanggan Modal state
    public bool $isPelangganModalOpen = false;

    public string $pelangganModalSearch = '';

    public string $pelangganModalTipe = '';

    // Quick Add Pelanggan Modal state
    public bool $isQuickPelangganModalOpen = false;

    public string $new_pelanggan_nama = '';

    public string $new_pelanggan_kontak = '';

    public string $new_pelanggan_tipe = 'umum';

    public float|int|string $new_pelanggan_limit = 0;

    // Quick Edit Pelanggan Limit Modal state
    public bool $isQuickLimitModalOpen = false;

    public float|int|string $quickLimitAmount = 0;

    public function mount(?int $duplicate_id = null): void
    {
        Gate::authorize('create', Penjualan::class);

        $this->tanggal = Carbon::now()->toDateString();
        $this->gudang_id = Gudang::first()?->id;

        // Auto-select Pelanggan Umum by default for rapid POS checkout
        $defaultPelanggan = Pelanggan::where('tipe', 'umum')->first() ?? Pelanggan::first();
        if ($defaultPelanggan) {
            $this->pelanggan_id = $defaultPelanggan->id;
        }

        $sourceId = $duplicate_id ?: $this->duplicate_id ?: (int) request()->query('duplicate_id');
        if ($sourceId) {
            $this->loadFromDuplicate($sourceId);
        }

        $this->recalculateSubtotals();
    }

    protected function loadFromDuplicate(int $duplicateId): void
    {
        $source = Penjualan::withTrashed()
            ->with(['details.produk', 'pelanggan', 'gudang'])
            ->find($duplicateId);

        if (! $source) {
            return;
        }

        $this->pelanggan_id = $source->pelanggan_id;
        if ($source->gudang_id) {
            $this->gudang_id = $source->gudang_id;
        }
        $this->biaya_ongkir = (float) $source->biaya_ongkir;
        $this->nama_sopir = $source->nama_sopir ?? '';
        $this->plat_nomor = $source->plat_nomor ?? '';
        $this->alamat_pengiriman = $source->alamat_pengiriman ?? '';
        $this->keterangan = $source->keterangan
            ? 'Duplikat dari '.$source->no_penjualan.': '.$source->keterangan
            : 'Duplikat dari '.$source->no_penjualan;

        if ($source->metode_pembayaran) {
            $this->tipe_pembayaran = in_array($source->metode_pembayaran, ['tempo', 'belum_bayar'])
                ? 'belum_bayar'
                : (in_array($source->metode_pembayaran, ['tunai', 'transfer']) ? $source->metode_pembayaran : 'tunai');
        }

        $pelanggan = $source->pelanggan;
        $this->items = [];

        foreach ($source->details as $detail) {
            $produk = $detail->produk;
            if (! $produk || $produk->trashed()) {
                continue;
            }

            $gudangId = $detail->gudang_id ?: $this->gudang_id;
            $hargaNormal = (float) $produk->getHargaUntukPelanggan($pelanggan?->tipe);
            $hargaSatuan = (float) $detail->harga_satuan;

            $itemRow = [
                'produk_id' => $produk->id,
                'gudang_id' => $gudangId,
                'harga_satuan' => $hargaSatuan > 0 ? $hargaSatuan : $hargaNormal,
                'harga_normal' => $hargaNormal,
                'jumlah' => (float) $detail->jumlah,
                'jenis_diskon' => $detail->jenis_diskon?->value ?? 'nominal',
                'nilai_diskon' => (float) $detail->nilai_diskon,
                'subtotal' => (float) $detail->subtotal,
                'current_stock' => 0.00,
                'kemasan_deskripsi' => null,
            ];
            $itemRow['kemasan_deskripsi'] = $this->getItemPhysicalBadge($itemRow);
            $this->items[] = $itemRow;
        }

        $this->updateAllStockDisplay();
        session()->flash('info_duplikat', "Data pesanan dari faktur {$source->no_penjualan} berhasil disalin ke faktur baru.");
    }

    public function openQuickPelangganModal(): void
    {
        $this->new_pelanggan_nama = filled($this->pelangganSearchQuery) ? trim($this->pelangganSearchQuery) : '';
        $this->new_pelanggan_kontak = '';
        $this->new_pelanggan_tipe = 'umum';
        $this->new_pelanggan_limit = 0;
        $this->resetErrorBag(['new_pelanggan_nama', 'new_pelanggan_kontak', 'new_pelanggan_tipe', 'new_pelanggan_limit']);
        $this->isQuickPelangganModalOpen = true;
    }

    public function closeQuickPelangganModal(): void
    {
        $this->isQuickPelangganModalOpen = false;
    }

    public function saveQuickPelanggan(CreatePelangganAction $action): void
    {
        Gate::authorize('create', Pelanggan::class);

        $this->validate([
            'new_pelanggan_nama' => ['required', 'string', 'max:255'],
            'new_pelanggan_kontak' => ['nullable', 'string', 'max:50'],
            'new_pelanggan_tipe' => ['required', 'in:umum,pemborong,langganan'],
            'new_pelanggan_limit' => ['required', 'numeric', 'min:0'],
        ], [
            'new_pelanggan_nama.required' => 'Nama pelanggan wajib diisi.',
            'new_pelanggan_tipe.required' => 'Pilih jenis/tipe pelanggan.',
            'new_pelanggan_limit.min' => 'Limit piutang tidak boleh negatif.',
        ]);

        try {
            $pelanggan = $action->execute([
                'nama' => $this->new_pelanggan_nama,
                'kontak' => $this->new_pelanggan_kontak,
                'tipe' => $this->new_pelanggan_tipe,
                'limit_piutang' => (float) $this->new_pelanggan_limit,
            ]);

            $this->selectPelanggan($pelanggan->id);
            $this->isQuickPelangganModalOpen = false;
            session()->flash('pelanggan_created', "Pelanggan '{$pelanggan->nama}' berhasil ditambahkan dan otomatis dipilih.");
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $targetField = match ($field) {
                    'nama' => 'new_pelanggan_nama',
                    'kontak' => 'new_pelanggan_kontak',
                    'tipe' => 'new_pelanggan_tipe',
                    'limit_piutang' => 'new_pelanggan_limit',
                    default => $field,
                };
                foreach ($messages as $msg) {
                    $this->addError($targetField, $msg);
                }
            }
        }
    }

    public function openQuickLimitModal(): void
    {
        if (! $this->pelanggan_id) {
            return;
        }

        $pelanggan = Pelanggan::find($this->pelanggan_id);
        if (! $pelanggan) {
            return;
        }

        Gate::authorize('update', $pelanggan);

        $this->quickLimitAmount = (float) $pelanggan->limit_piutang;
        $this->resetErrorBag(['quickLimitAmount']);
        $this->isQuickLimitModalOpen = true;
    }

    public function closeQuickLimitModal(): void
    {
        $this->isQuickLimitModalOpen = false;
        $this->resetErrorBag(['quickLimitAmount']);
    }

    public function saveQuickLimit(UpdatePelangganAction $action): void
    {
        if (! $this->pelanggan_id) {
            return;
        }

        $pelanggan = Pelanggan::findOrFail($this->pelanggan_id);
        Gate::authorize('update', $pelanggan);

        $this->validate([
            'quickLimitAmount' => ['required', 'numeric', 'min:0'],
        ], [
            'quickLimitAmount.required' => 'Nominal limit piutang wajib diisi.',
            'quickLimitAmount.numeric' => 'Nominal limit piutang harus berupa angka.',
            'quickLimitAmount.min' => 'Limit piutang tidak boleh bernilai negatif.',
        ]);

        $action->execute($pelanggan, [
            'nama' => $pelanggan->nama,
            'kontak' => $pelanggan->kontak,
            'tipe' => $pelanggan->tipe,
            'limit_piutang' => (float) $this->quickLimitAmount,
        ]);

        $this->isQuickLimitModalOpen = false;
        $this->recalculateKembalianDanPiutang();
        session()->flash('limit_updated', "Limit piutang '{$pelanggan->nama}' berhasil diubah menjadi Rp ".number_format((float) $this->quickLimitAmount, 0, ',', '.').'.');
    }

    public function openPelangganModal(): void
    {
        $this->pelangganModalSearch = filled($this->pelangganSearchQuery) ? trim($this->pelangganSearchQuery) : '';
        $this->pelangganModalTipe = '';
        $this->isPelangganModalOpen = true;
    }

    public function closePelangganModal(): void
    {
        $this->isPelangganModalOpen = false;
        $this->pelangganModalSearch = '';
        $this->pelangganModalTipe = '';
    }

    public function selectPelangganFromModal(int $pelangganId): void
    {
        $this->selectPelanggan($pelangganId);
        $this->closePelangganModal();
    }

    public function openQuickPelangganModalFromDirectory(): void
    {
        $search = trim($this->pelangganModalSearch);
        $this->closePelangganModal();
        $this->openQuickPelangganModal();
        if (filled($search)) {
            $this->new_pelanggan_nama = $search;
        }
    }

    public function selectPelanggan(int $pelangganId): void
    {
        $this->pelanggan_id = $pelangganId;
        $this->pelangganSearchQuery = '';
        $this->updatedPelangganId();
    }

    public function clearPelanggan(): void
    {
        $this->pelanggan_id = null;
        $this->pelangganSearchQuery = '';
        $this->updatedPelangganId();
    }

    public function setUangPas(): void
    {
        $this->jumlah_bayar = $this->totalAkhir;
        $this->isManualJumlahBayar = false;
        $this->recalculateKembalianDanPiutang();
    }

    public function addNominalBayar(float $amount): void
    {
        $current = is_numeric($this->jumlah_bayar) ? (float) $this->jumlah_bayar : 0.0;
        $this->jumlah_bayar = $current + $amount;
        $this->isManualJumlahBayar = true;
        $this->recalculateKembalianDanPiutang();
    }

    public function setExactNominalBayar(float $amount): void
    {
        $this->jumlah_bayar = $amount;
        $this->isManualJumlahBayar = true;
        $this->recalculateKembalianDanPiutang();
    }

    public function clearNominalBayar(): void
    {
        $this->jumlah_bayar = 0;
        $this->isManualJumlahBayar = true;
        $this->recalculateKembalianDanPiutang();
    }

    public function updatedJumlahBayar(): void
    {
        $this->isManualJumlahBayar = true;
        $this->recalculateKembalianDanPiutang();
    }

    public function updatedBiayaOngkir(): void
    {
        $this->recalculateSubtotals();
    }

    public function updatedTipePembayaran(): void
    {
        if ($this->tipe_pembayaran === 'tunai' || $this->tipe_pembayaran === 'transfer') {
            $this->jumlah_bayar = $this->totalAkhir;
            $this->isManualJumlahBayar = false;
        } else {
            $this->jumlah_bayar = 0;
            $this->isManualJumlahBayar = false;
        }

        $this->recalculateKembalianDanPiutang();
    }

    public function syncJumlahBayarIfNotManual(): void
    {
        if (! $this->isManualJumlahBayar && in_array($this->tipe_pembayaran, ['tunai', 'transfer'], true)) {
            $this->jumlah_bayar = $this->totalAkhir;
        }
    }

    public function addProductToItems(Produk $produk, float $qty = 1.0, ?int $gudangId = null): void
    {
        $targetGudangId = $gudangId ?: (int) $this->gudang_id;
        $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
        $hargaSatuan = (float) $produk->getHargaUntukPelanggan($pelanggan?->tipe);

        // Jika produk memiliki satuan meter dan panjang lembar (misal PVC 4m) dan qty default 1, inisialisasi 1 lembar utuh
        $isPvcMeter = ($produk->satuan === SatuanProduk::Meter && $produk->panjang_meter && (float) $produk->panjang_meter > 0);
        $actualQty = ($qty === 1.0 && $isPvcMeter)
            ? (float) $produk->panjang_meter
            : $qty;

        // Cari jika produk dengan gudang yang sama sudah ada di baris tabel, cukup tambahkan kuantitinya
        $existingIndex = null;
        foreach ($this->items as $idx => $item) {
            $rowGudangId = (int) ($item['gudang_id'] ?? $this->gudang_id);
            if ((int) ($item['produk_id'] ?? 0) === $produk->id && $rowGudangId === $targetGudangId) {
                $existingIndex = $idx;
                break;
            }
        }

        if ($existingIndex !== null) {
            $newQty = (float) ($this->items[$existingIndex]['jumlah'] ?? 0) + $actualQty;
            $this->items[$existingIndex]['jumlah'] = $newQty;
            $this->items[$existingIndex]['kemasan_deskripsi'] = $this->getItemPhysicalBadge($this->items[$existingIndex]);
            $this->applyAutoDiscount($existingIndex, $produk, $pelanggan);
            $this->recalculateSubtotals();
            $this->dispatch('focus-qty-row', index: $existingIndex);

            return;
        }

        // Cari baris kosong pertama jika ada
        $targetIndex = null;
        foreach ($this->items as $idx => $item) {
            if (empty($item['produk_id'])) {
                $targetIndex = $idx;
                break;
            }
        }

        $newItem = [
            'produk_id' => $produk->id,
            'gudang_id' => $targetGudangId,
            'harga_satuan' => $hargaSatuan,
            'harga_normal' => $hargaSatuan,
            'jumlah' => $actualQty,
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 0,
            'subtotal' => 0.00,
            'current_stock' => 0.00,
            'kemasan_deskripsi' => null,
        ];
        $newItem['kemasan_deskripsi'] = $this->getItemPhysicalBadge($newItem);

        if ($targetIndex !== null) {
            $this->items[$targetIndex] = $newItem;
            $idx = $targetIndex;
        } else {
            $this->items[] = $newItem;
            $idx = count($this->items) - 1;
        }

        $this->updateRowStockDisplay($idx, $produk);
        $this->applyAutoDiscount($idx, $produk, $pelanggan);
        $this->recalculateSubtotals();

        if ($this->tipe_pembayaran === 'tunai' || $this->tipe_pembayaran === 'transfer') {
            $this->jumlah_bayar = $this->totalAkhir;
        }

        $this->dispatch('focus-qty-row', index: $idx);
    }

    public function addQuickProduct(int $produkId): void
    {
        $produk = Produk::find($produkId);
        if ($produk) {
            $this->addProductToItems($produk, 1.0);
        }
        $this->quickSearchQuery = '';
    }

    public function openCatalogModal(): void
    {
        $this->catalogSearchQuery = '';
        $this->catalogKategoriId = null;
        $this->catalogMerekId = null;
        $this->catalogStockFilter = 'all';
        $this->catalogSelectedProductIds = [];
        $this->isCatalogModalOpen = true;
    }

    public function closeCatalogModal(): void
    {
        $this->isCatalogModalOpen = false;
        $this->catalogSelectedProductIds = [];
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
        if ($this->catalogStockFilter === 'ready') {
            if ($this->gudang_id) {
                $query->whereHas('stok', fn ($sq) => $sq->where('gudang_id', $this->gudang_id)->where('jumlah', '>', 0));
            } else {
                $query->whereHas('stok', fn ($sq) => $sq->where('jumlah', '>', 0));
            }
        }

        $foundIds = $query->limit(50)->pluck('id')->all();
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

        $produks = Produk::whereIn('id', $this->catalogSelectedProductIds)->get();
        foreach ($produks as $produk) {
            $this->addProductToItems($produk, 1.0);
        }

        $this->closeCatalogModal();
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

    public function addItem(): void
    {
        $this->items[] = [
            'produk_id' => null,
            'gudang_id' => (int) $this->gudang_id,
            'harga_satuan' => '',
            'jumlah' => '',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 0,
            'subtotal' => 0.00,
            'current_stock' => 0.00,
        ];

        $this->recalculateSubtotals();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }

        $this->recalculateSubtotals();
    }

    public function incrementQty(int $index, float $delta = 1.0): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);
        $produk = $produkId > 0 ? Produk::find($produkId) : null;

        $deltaQty = $delta;
        if ($produk && $produk->satuan === SatuanProduk::Meter && $produk->panjang_meter && (float) $produk->panjang_meter > 0 && abs($delta - 1.0) < 0.001) {
            $deltaQty = (float) $produk->panjang_meter;
        }

        $current = (float) ($this->items[$index]['jumlah'] ?? 0);
        $this->items[$index]['jumlah'] = $current + $deltaQty;
        $this->items[$index]['kemasan_deskripsi'] = $this->getItemPhysicalBadge($this->items[$index]);

        if ($produk) {
            $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
            $this->applyAutoDiscount($index, $produk, $pelanggan);
        }

        $this->recalculateSubtotals();
    }

    public function decrementQty(int $index, float $delta = 1.0): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);
        $produk = $produkId > 0 ? Produk::find($produkId) : null;

        $deltaQty = $delta;
        if ($produk && $produk->satuan === SatuanProduk::Meter && $produk->panjang_meter && (float) $produk->panjang_meter > 0 && abs($delta - 1.0) < 0.001) {
            $deltaQty = (float) $produk->panjang_meter;
        }

        $minQty = ($produk && $produk->satuan === SatuanProduk::Meter && $produk->panjang_meter && (float) $produk->panjang_meter > 0)
            ? (float) $produk->panjang_meter
            : 1.0;

        $current = (float) ($this->items[$index]['jumlah'] ?? 0);
        $newQty = max($minQty, $current - $deltaQty);
        $this->items[$index]['jumlah'] = $newQty;
        $this->items[$index]['kemasan_deskripsi'] = $this->getItemPhysicalBadge($this->items[$index]);

        if ($produk) {
            $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
            $this->applyAutoDiscount($index, $produk, $pelanggan);
        }

        $this->recalculateSubtotals();
    }

    /**
     * Opsi 1: Pecah pengambilan barang antar gudang (Split Warehouse).
     * Baris saat ini dibatasi sejumlah stok yang ada di gudang asal, dan sisa kekurangan dialokasikan ke gudang lain.
     */
    public function splitItemToWarehouse(int $index, int $targetGudangId): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $produkId = (int) ($item['produk_id'] ?? 0);
        $produk = Produk::find($produkId);
        $targetGudang = Gudang::find($targetGudangId);

        if (! $produk || ! $targetGudang) {
            return;
        }

        $reqQty = (float) ($item['jumlah'] ?? 0);
        $availStock = max(0.0, (float) ($item['current_stock'] ?? 0));
        $deficit = $reqQty - $availStock;

        if ($deficit <= 0) {
            return;
        }

        // Jika gudang asal sama sekali tidak ada stok, langsung pindahkan gudang baris ini
        if ($availStock <= 0) {
            $this->items[$index]['gudang_id'] = $targetGudangId;
            $this->updateRowStockDisplay($index, $produk);
            $this->recalculateSubtotals();

            return;
        }

        // Batasi baris saat ini dengan stok yang tersedia
        $this->items[$index]['jumlah'] = $availStock;
        $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
        $this->applyAutoDiscount($index, $produk, $pelanggan);

        // Siapkan baris baru untuk sisa kekurangan dengan gudang target
        $hargaSatuan = (float) $produk->getHargaUntukPelanggan($pelanggan?->tipe);
        $newItem = [
            'produk_id' => $produk->id,
            'gudang_id' => $targetGudangId,
            'harga_satuan' => $hargaSatuan,
            'harga_normal' => $hargaSatuan,
            'jumlah' => $deficit,
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 0,
            'subtotal' => 0.00,
            'current_stock' => 0.00,
        ];

        // Sisipkan baris baru tepat di bawah baris saat ini ($index + 1)
        array_splice($this->items, $index + 1, 0, [$newItem]);
        $this->items = array_values($this->items);

        $this->updateAllStockDisplay();
        foreach ($this->items as $i => $itm) {
            $pId = (int) ($itm['produk_id'] ?? 0);
            if ($pId > 0) {
                $p = Produk::find($pId);
                if ($p) {
                    $this->applyAutoDiscount($i, $p, $pelanggan);
                }
            }
        }

        $this->recalculateSubtotals();
        $this->syncJumlahBayarIfNotManual();

        session()->flash('info_split', "Baris berhasil dipecah: {$availStock} dari gudang asal & {$deficit} dialihkan ke {$targetGudang->nama_gudang}.");
    }

    /**
     * Opsi 2: Tarik / Transfer stok instan dari gudang lain ke gudang baris ini (Instant Transfer).
     */
    public function transferStockForDeficit(int $index, int $fromGudangId, CreateTransferGudangAction $transferAction): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $produkId = (int) ($item['produk_id'] ?? 0);
        $produk = Produk::find($produkId);
        $fromGudang = Gudang::find($fromGudangId);
        $toGudangId = (int) ($item['gudang_id'] ?? $this->gudang_id);
        $toGudang = Gudang::find($toGudangId);

        if (! $produk || ! $fromGudang || ! $toGudang || $fromGudang->id === $toGudang->id) {
            return;
        }

        $reqQty = (float) ($item['jumlah'] ?? 0);
        $availStock = max(0.0, (float) ($item['current_stock'] ?? 0));
        $deficit = $reqQty - $availStock;

        if ($deficit <= 0) {
            return;
        }

        $stockService = app(StockService::class);
        $sourceStock = $stockService->getStock($produk, $fromGudang);
        $transferQty = min($deficit, $sourceStock);

        if ($transferQty <= 0) {
            session()->flash('error', "Stok di gudang {$fromGudang->nama_gudang} tidak mencukupi untuk transfer.");

            return;
        }

        try {
            $transfer = $transferAction->execute([
                'gudang_asal_id' => $fromGudang->id,
                'gudang_tujuan_id' => $toGudang->id,
                'tanggal' => $this->tanggal ?: Carbon::now()->toDateString(),
                'keterangan' => "Auto transfer untuk pemenuhan pesanan kasir {$produk->nama}",
                'items' => [
                    [
                        'produk_id' => $produk->id,
                        'jumlah' => $transferQty,
                    ],
                ],
            ]);

            $this->updateAllStockDisplay();
            session()->flash('info_split', "Berhasil transfer {$transferQty} {$produk->satuan->label()} dari {$fromGudang->nama_gudang} ke {$toGudang->nama_gudang} (No Transfer: {$transfer->no_transfer}).");
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal melakukan transfer stok: '.$e->getMessage());
        }
    }

    public function updatedGudangId(): void
    {
        $this->updateAllStockDisplay();
    }

    public function updatedPelangganId(): void
    {
        $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
        foreach ($this->items as $index => $item) {
            $produkId = (int) ($item['produk_id'] ?? 0);
            if ($produkId > 0) {
                $produk = Produk::find($produkId);
                if ($produk) {
                    $hargaNormal = (float) $produk->getHargaUntukPelanggan($pelanggan?->tipe);
                    $this->items[$index]['harga_satuan'] = $hargaNormal;
                    $this->items[$index]['harga_normal'] = $hargaNormal;
                    $this->applyAutoDiscount($index, $produk, $pelanggan);
                }
            }
        }
        $this->recalculateSubtotals();
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
                    $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
                    $hargaNormal = (float) $produk->getHargaUntukPelanggan($pelanggan?->tipe);
                    $this->items[$index]['harga_satuan'] = $hargaNormal;
                    $this->items[$index]['harga_normal'] = $hargaNormal;
                    $this->items[$index]['gudang_id'] = (int) ($this->items[$index]['gudang_id'] ?? $this->gudang_id);
                    $this->updateRowStockDisplay($index, $produk);
                    $this->applyAutoDiscount($index, $produk, $pelanggan);
                    $this->items[$index]['kemasan_deskripsi'] = $this->getItemPhysicalBadge($this->items[$index]);
                }
            }
        }

        if ($key && str_ends_with($key, '.gudang_id')) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);
            if ($produkId > 0) {
                $produk = Produk::find($produkId);
                if ($produk) {
                    $this->updateRowStockDisplay($index, $produk);
                }
            }
        }

        if ($key && (str_ends_with($key, '.jumlah') || str_ends_with($key, '.harga_satuan'))) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);

            if ($produkId > 0) {
                $produk = Produk::find($produkId);
                $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
                if ($produk) {
                    $this->applyAutoDiscount($index, $produk, $pelanggan);
                }
            }

            if (str_ends_with($key, '.jumlah')) {
                $this->items[$index]['kemasan_deskripsi'] = $this->getItemPhysicalBadge($this->items[$index]);
            }
        }

        $this->recalculateSubtotals();
    }

    /**
     * Restore a row's unit price to the system reference price for the selected customer.
     */
    public function resetHargaNormal(int $index): void
    {
        $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);
        $produk = $produkId > 0 ? Produk::find($produkId) : null;

        if (! $produk) {
            return;
        }

        $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
        $hargaNormal = (float) $produk->getHargaUntukPelanggan($pelanggan?->tipe);
        $this->items[$index]['harga_satuan'] = $hargaNormal;
        $this->items[$index]['harga_normal'] = $hargaNormal;
        $this->applyAutoDiscount($index, $produk, $pelanggan);
        $this->recalculateSubtotals();
    }

    protected function applyAutoDiscount(int $index, Produk $produk, ?Pelanggan $pelanggan): void
    {
        $qty = (float) ($this->items[$index]['jumlah'] ?? 1);
        $discountService = app(DiscountCalculationService::class);
        $result = $discountService->calculate($produk, $pelanggan, $qty, (float) $this->items[$index]['harga_satuan']);

        if ($result['has_discount']) {
            $this->items[$index]['jenis_diskon'] = $result['jenis_diskon'];
            $this->items[$index]['promo_nama'] = $result['aturan_nama'];
            if ($result['jenis_diskon'] === 'nominal') {
                $this->items[$index]['nilai_diskon'] = round($result['diskon_per_unit'] * $qty, 2);
            } else {
                $this->items[$index]['nilai_diskon'] = $result['nilai_diskon'];
            }
        } else {
            $this->items[$index]['jenis_diskon'] = 'nominal';
            $this->items[$index]['nilai_diskon'] = 0;
            $this->items[$index]['promo_nama'] = null;
        }
    }

    public function updateAllStockDisplay(): void
    {
        $stockService = app(StockService::class);

        foreach ($this->items as $index => $item) {
            $produkId = (int) ($item['produk_id'] ?? 0);
            if ($produkId > 0) {
                $produk = Produk::find($produkId);
                $targetGudangId = (int) ($item['gudang_id'] ?? $this->gudang_id);
                $gudang = $targetGudangId ? Gudang::find($targetGudangId) : null;
                if ($produk && $gudang) {
                    $this->items[$index]['current_stock'] = $stockService->getStock($produk, $gudang);
                }
            }
        }
    }

    protected function updateRowStockDisplay(int $index, Produk $produk): void
    {
        $targetGudangId = (int) ($this->items[$index]['gudang_id'] ?? $this->gudang_id);
        if (! $targetGudangId) {
            $this->items[$index]['current_stock'] = 0.00;

            return;
        }

        $gudang = Gudang::find($targetGudangId);
        if ($gudang) {
            $this->items[$index]['current_stock'] = app(StockService::class)->getStock($produk, $gudang);
        }
    }

    public function recalculateSubtotals(): void
    {
        $subtotal = 0.0;
        $totalQty = 0.0;

        foreach ($this->items as $index => $item) {
            $qty = (float) ($item['jumlah'] ?? 0);
            $price = (float) ($item['harga_satuan'] ?? 0);
            $gross = round($qty * $price, 2);

            $jenisDiskon = $item['jenis_diskon'] ?? 'nominal';
            $nilaiDiskon = (float) ($item['nilai_diskon'] ?? 0);
            $discountAmount = 0.00;

            if ($nilaiDiskon > 0) {
                if ($jenisDiskon === 'persentase') {
                    $discountAmount = round($gross * ($nilaiDiskon / 100), 2);
                } else {
                    $discountAmount = round($nilaiDiskon, 2);
                }
            }

            $lineSubtotal = max(0.00, round($gross - $discountAmount, 2));
            $this->items[$index]['subtotal'] = $lineSubtotal;

            $subtotal += $lineSubtotal;
            $totalQty += $qty;
        }

        $this->subtotalPenjualan = round($subtotal, 2);
        $this->totalKuantitasFisik = round($totalQty, 2);
        $this->totalAkhir = round($this->subtotalPenjualan + (float) $this->biaya_ongkir, 2);

        $this->syncJumlahBayarIfNotManual();
        $this->recalculateKembalianDanPiutang();
    }

    public function recalculateKembalianDanPiutang(): void
    {
        if ($this->tipe_pembayaran === 'belum_bayar') {
            $this->kembalian = 0.00;
            $this->sisaPiutangBaru = $this->totalAkhir;

            return;
        }

        $bayar = (float) $this->jumlah_bayar;
        $total = $this->totalAkhir;

        $this->kembalian = max(0.00, round($bayar - $total, 2));
        $this->sisaPiutangBaru = max(0.00, round($total - $bayar, 2));
    }

    public function getPiutangPelangganSaatIniProperty(): float
    {
        if (! $this->pelanggan_id) {
            return 0.00;
        }

        return (float) Penjualan::where('pelanggan_id', $this->pelanggan_id)->sum('sisa_piutang');
    }

    public function save(CreatePenjualanAction $action, ?CreatePembayaranAction $pembayaranAction = null): mixed
    {
        Gate::authorize('create', Penjualan::class);

        $this->validate();

        // Check for stock shortages if not already confirmed
        if (! $this->confirmedShortage) {
            $defaultGudang = Gudang::find($this->gudang_id);
            $stockService = app(StockService::class);
            $warnings = [];

            foreach ($this->items as $item) {
                $produk = Produk::find($item['produk_id']);
                $targetGudangId = (int) ($item['gudang_id'] ?? $this->gudang_id);
                $gudang = $targetGudangId ? Gudang::find($targetGudangId) : $defaultGudang;

                if ($produk && $gudang) {
                    $currentStock = $stockService->getStock($produk, $gudang);
                    $requestedQty = (float) $item['jumlah'];
                    if ($requestedQty > $currentStock) {
                        $warnings[] = [
                            'produk_nama' => $produk->nama.($gudang->id !== $this->gudang_id ? " ({$gudang->nama_gudang})" : ''),
                            'satuan' => $produk->satuan->label(),
                            'stok_saat_ini' => $currentStock,
                            'jumlah_jual' => $requestedQty,
                            'stok_setelah' => $currentStock - $requestedQty,
                        ];
                    }
                }
            }

            if (! empty($warnings)) {
                $this->shortageWarnings = $warnings;
                $this->isShortageModalOpen = true;

                return null;
            }
        }

        // Penegakan Limit Piutang / Kasbon (Opsi 1)
        $pelanggan = Pelanggan::find($this->pelanggan_id);
        $sisaPiutangTransaksi = $this->sisaPiutangBaru;

        if ($pelanggan && $sisaPiutangTransaksi > 0) {
            $limitPiutang = (float) $pelanggan->limit_piutang;
            $piutangLama = (float) Penjualan::where('pelanggan_id', $pelanggan->id)->sum('sisa_piutang');
            $proyeksiTotalPiutang = $piutangLama + $sisaPiutangTransaksi;

            // Aturan 1: Limit Rp 0 = Dilarang kasbon / tempo sama sekali
            if ($limitPiutang <= 0) {
                $this->addError(
                    'tipe_pembayaran',
                    "Pelanggan '{$pelanggan->nama}' memiliki limit piutang Rp 0 (tidak diizinkan kasbon/tempo). Harap lakukan pembayaran lunas Tunai/Transfer, atau naikkan limit piutang pelanggan terlebih dahulu."
                );

                return null;
            }

            // Aturan 2: Proyeksi total hutang tidak boleh melebihi batas limit
            if ($proyeksiTotalPiutang > $limitPiutang) {
                $formattedProyeksi = 'Rp '.number_format($proyeksiTotalPiutang, 0, ',', '.');
                $formattedLimit = 'Rp '.number_format($limitPiutang, 0, ',', '.');
                $this->addError(
                    'tipe_pembayaran',
                    "Total piutang pelanggan '{$pelanggan->nama}' akan menjadi {$formattedProyeksi}, melebihi batas limit piutang sebesar {$formattedLimit}. Pelanggan harus melunasi sebagian piutang lama atau limit piutang dinaikkan terlebih dahulu."
                );

                return null;
            }
        }

        $penjualan = $action->execute([
            'pelanggan_id' => (int) $this->pelanggan_id,
            'gudang_id' => (int) $this->gudang_id,
            'tanggal' => $this->tanggal,
            'biaya_ongkir' => (float) $this->biaya_ongkir,
            'keterangan' => $this->keterangan,
            'nama_sopir' => filled($this->nama_sopir) ? trim($this->nama_sopir) : null,
            'plat_nomor' => filled($this->plat_nomor) ? trim($this->plat_nomor) : null,
            'alamat_pengiriman' => filled($this->alamat_pengiriman) ? trim($this->alamat_pengiriman) : null,
            'metode_pembayaran' => $this->tipe_pembayaran === 'belum_bayar' ? 'tempo' : $this->tipe_pembayaran,
            'items' => $this->items,
        ]);

        // Proses pembayaran langsung kasir jika dipilih (tunai / transfer)
        $kembalian = $this->kembalian;
        if (in_array($this->tipe_pembayaran, ['tunai', 'transfer'], true)) {
            $pembayaranAction = $pembayaranAction ?? app(CreatePembayaranAction::class);
            $bayarInput = (float) $this->jumlah_bayar;
            // Jika user memilih tunai/transfer, alokasi bayar adalah min(bayarInput, total_akhir),
            // ATAU jika bayarInput <= 0 (misal kasir langsung klik simpan tanpa ketik uang pas),
            // kita anggap lunas (uang pas = total_akhir) agar penjualan kasir tunai tidak pernah berstatus belum_bayar!
            $alokasiBayar = $bayarInput > 0 ? min($bayarInput, (float) $penjualan->total_akhir) : (float) $penjualan->total_akhir;

            if ($alokasiBayar > 0) {
                $metode = $this->tipe_pembayaran === 'tunai'
                    ? MetodePembayaran::Tunai
                    : MetodePembayaran::Transfer;

                $pembayaranAction->execute([
                    'pelanggan_id' => (int) $this->pelanggan_id,
                    'tanggal' => $this->tanggal,
                    'jumlah_bayar' => $alokasiBayar,
                    'metode' => $metode,
                    'catatan' => 'Pembayaran kasir langsung untuk faktur '.$penjualan->no_penjualan,
                    'alokasi' => [
                        [
                            'penjualan_id' => $penjualan->id,
                            'jumlah_dialokasikan' => $alokasiBayar,
                        ],
                    ],
                ]);
            }
        }

        $pesanSukses = "Penjualan {$penjualan->no_penjualan} berhasil dicatat dan stok telah dikurangi.";
        if ($kembalian > 0) {
            $pesanSukses .= ' Uang Kembalian: Rp '.number_format($kembalian, 0, ',', '.');
        }

        session()->flash('message', $pesanSukses);

        if ($this->pasca_simpan === 'baru') {
            $this->reset([
                'keterangan',
                'biaya_ongkir',
                'nama_sopir',
                'plat_nomor',
                'alamat_pengiriman',
                'quickSearchQuery',
                'pelangganSearchQuery',
                'tipe_pembayaran',
                'jumlah_bayar',
                'isManualJumlahBayar',
                'subtotalPenjualan',
                'totalKuantitasFisik',
                'totalAkhir',
                'kembalian',
                'sisaPiutangBaru',
            ]);
            $this->items = [];
            $this->confirmedShortage = false;
            $this->addItem();
            $this->recalculateSubtotals();

            return null;
        }

        if ($this->pasca_simpan === 'cetak_kilat') {
            $this->lastSavedPenjualanId = $penjualan->id;
            $this->lastSavedNoPenjualan = $penjualan->no_penjualan;
            $this->lastSavedTotal = (float) $penjualan->total_akhir;
            $this->lastSavedKembalian = (float) $kembalian;
            $this->lastSavedMetode = $this->tipe_pembayaran;
            $this->isPrintModalOpen = true;

            return null;
        }

        return redirect()->route('penjualan.show', $penjualan->id);
    }

    public function startNewTransaction(): void
    {
        $this->isPrintModalOpen = false;
        $this->reset([
            'keterangan',
            'biaya_ongkir',
            'nama_sopir',
            'plat_nomor',
            'alamat_pengiriman',
            'quickSearchQuery',
            'pelangganSearchQuery',
            'tipe_pembayaran',
            'jumlah_bayar',
            'isManualJumlahBayar',
            'subtotalPenjualan',
            'totalKuantitasFisik',
            'totalAkhir',
            'kembalian',
            'sisaPiutangBaru',
            'lastSavedPenjualanId',
            'lastSavedNoPenjualan',
            'lastSavedTotal',
            'lastSavedKembalian',
            'lastSavedMetode',
        ]);
        $this->items = [];
        $this->confirmedShortage = false;
        $this->addItem();
        $this->recalculateSubtotals();
    }

    public function closePrintModal(): void
    {
        $this->isPrintModalOpen = false;
    }

    public function saveAndPrint(CreatePenjualanAction $action, ?CreatePembayaranAction $pembayaranAction = null): mixed
    {
        $this->pasca_simpan = 'cetak_kilat';

        return $this->save($action, $pembayaranAction);
    }

    public function confirmShortageAndSave(CreatePenjualanAction $action): mixed
    {
        $this->confirmedShortage = true;
        $this->isShortageModalOpen = false;

        $alasanText = match ($this->shortageAlasan) {
            'barang_fisik_tiba' => 'Barang fisik sudah tiba di toko (PO/surat jalan belum diinput)',
            'izin_supervisor' => 'Disetujui oleh Supervisor / Pemilik Toko',
            default => filled($this->shortageCatatanLainnya) ? $this->shortageCatatanLainnya : 'Stok minus disetujui kasir',
        };

        $prefix = "[Stok Minus: {$alasanText}]";
        $this->keterangan = filled($this->keterangan)
            ? $this->keterangan." {$prefix}"
            : $prefix;

        return $this->save($action);
    }

    public function cancelShortage(): void
    {
        $this->isShortageModalOpen = false;
        $this->confirmedShortage = false;
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

        if (! $produk) {
            return;
        }

        $this->calcProductName = $produk->nama;
        $this->calcTargetUnit = $produk->satuan->label();
        $this->calcSatuan = $produk->satuan->label();
        $satuanVal = $produk->satuan->value;

        $panjangMeter = $produk->panjang_meter ? (float) $produk->panjang_meter : null;
        $isiPerKemasan = $produk->isi_per_kemasan ? (float) $produk->isi_per_kemasan : null;
        $kemasanLabel = $produk->kemasan ?: 'Kemasan';

        $this->calcPanjangMeter = $panjangMeter;
        $this->calcIsiPerKemasan = $isiPerKemasan;
        $this->calcKemasanLabel = $kemasanLabel;

        $isRollProduct = ($satuanVal === 'roll') ||
            ($satuanVal === 'meter' && (
                ($produk->panjang_per_roll && (float) $produk->panjang_per_roll > 0) ||
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

            $currentJumlah = (float) ($item['jumlah'] ?? 0);
            $currentLembar = $panjangMeter > 0 ? ($currentJumlah / $panjangMeter) : 0;
            if ($currentLembar > 0 && (float) $this->packSize > 0) {
                $this->calcJmlKemasan = (float) floor($currentLembar / (float) $this->packSize);
                $this->calcJmlEceran = (float) round($currentLembar - ($this->calcJmlKemasan * (float) $this->packSize), 1);
                $this->packCount = max(1, (int) $this->calcJmlKemasan);
            } else {
                $this->calcJmlKemasan = 1.0;
                $this->calcJmlEceran = 0.0;
                $this->packCount = 1;
            }
        } elseif ($isRollProduct) {
            $this->calcMode = 'roll';
            $panjangRoll = (float) ($produk->panjang_per_roll ?: ($isiPerKemasan ?: 25));
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
            if ($currentJumlah > 0 && (float) $this->packSize > 0) {
                $this->calcJmlKemasan = (float) floor($currentJumlah);
                $fraction = $currentJumlah - $this->calcJmlKemasan;
                $this->calcJmlEceran = (float) round($fraction * (float) $this->packSize);
                $this->packCount = max(1, (int) $this->calcJmlKemasan);
            } else {
                $this->calcJmlKemasan = 1.0;
                $this->calcJmlEceran = 0.0;
                $this->packCount = 1;
            }
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
    }

    public function closeCalculator(): void
    {
        $this->isCalculatorModalOpen = false;
        $this->activeItemIndex = null;
        $this->calcJmlKemasan = null;
        $this->calcJmlEceran = null;
    }

    public function updatedCalcJmlKemasan(mixed $value): void
    {
        $this->packCount = filled($value) ? (float) $value : null;
        $this->rollCount = filled($value) ? (float) $value : null;
    }

    public function updatedPackCount(mixed $value): void
    {
        $this->calcJmlKemasan = filled($value) ? (float) $value : null;
    }

    public function updatedPackSize(mixed $value): void
    {
        $this->calcIsiPerKemasan = filled($value) ? (float) $value : null;
    }

    public function updatedRollCount(mixed $value): void
    {
        $this->calcJmlKemasan = filled($value) ? (float) $value : null;
    }

    public function updatedRollLength(mixed $value): void
    {
        $this->calcIsiPerKemasan = filled($value) ? (float) $value : null;
    }

    public function updatedCalcJmlEceran(mixed $value): void
    {
        if (is_string($value)) {
            $this->calcJmlEceran = str_replace(',', '.', trim($value));
        }
    }

    public function updatedPvcPanjang(mixed $value): void
    {
        $this->calcPanjangMeter = filled($value) ? (float) $value : null;
    }

    public function getComputedPhysicalStockProperty(): float
    {
        if ($this->activeItemIndex === null || ! isset($this->items[$this->activeItemIndex])) {
            return 0.0;
        }

        $jmlKemasan = (float) ($this->calcJmlKemasan ?: 0);
        $rawEceran = is_string($this->calcJmlEceran) ? str_replace(',', '.', $this->calcJmlEceran) : $this->calcJmlEceran;
        $jmlEceran = is_numeric($rawEceran) ? (float) $rawEceran : 0.0;
        $isiPerKemasan = (float) ($this->calcIsiPerKemasan ?: 0);
        $panjangMeter = (float) ($this->calcPanjangMeter ?: 0);
        $satuan = strtolower((string) ($this->calcSatuan ?? ''));
        $kemasan = strtolower((string) ($this->calcKemasanLabel ?? ''));

        // Roll items
        if ($this->calcMode === 'roll' || strcasecmp($kemasan, 'roll') === 0 || str_contains($satuan, 'roll')) {
            $rollLen = (float) ($this->rollLength ?: ($isiPerKemasan ?: 25.0));

            return (float) (($jmlKemasan * $rollLen) + $jmlEceran);
        }

        // Plafon PVC (dus of sheets with length)
        if ($this->calcMode === 'dus_pvc' && str_contains($satuan, 'meter') && $panjangMeter > 0 && $isiPerKemasan > 0) {
            $fromKemasan = $jmlKemasan * $isiPerKemasan * $panjangMeter;
            $fromEceran = $jmlEceran * $panjangMeter;

            return (float) ($fromKemasan + $fromEceran);
        }

        // Items whose unit is the packaging (e.g. Satuan Dus, Kemasan Dus, Isi 100 pcs)
        $firstWordSatuan = strtolower(strtok($satuan, ' (/'));
        $firstWordKemasan = strtolower(strtok($kemasan, ' (/'));
        $isUnitSameAsKemasan = ($this->calcMode === 'kemasan_dus') || ($firstWordKemasan !== '' && (
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

        // Standard packaging conversion (e.g. 5 ikat * 20 = 100 batang)
        if ($isiPerKemasan > 0) {
            return (float) (($jmlKemasan * $isiPerKemasan) + $jmlEceran);
        }

        return (float) ($jmlKemasan + $jmlEceran);
    }

    public function applyCalculation(): void
    {
        if ($this->activeItemIndex === null || ! isset($this->items[$this->activeItemIndex])) {
            $this->closeCalculator();

            return;
        }

        $index = $this->activeItemIndex;
        $totalQty = $this->computedPhysicalStock;
        $jmlKemasan = (float) ($this->calcJmlKemasan ?: 0);
        $rawEceran = is_string($this->calcJmlEceran) ? str_replace(',', '.', $this->calcJmlEceran) : $this->calcJmlEceran;
        $jmlEceran = is_numeric($rawEceran) ? (float) $rawEceran : 0.0;
        $isiPerKemasan = (float) ($this->calcIsiPerKemasan ?: 0);
        $packLabel = $this->packLabel ?: 'Kemasan';

        $this->items[$index]['jumlah'] = $totalQty;

        $satuan = strtolower((string) ($this->calcSatuan ?? ''));
        $kemasan = strtolower((string) ($this->calcKemasanLabel ?? ''));
        $firstWordSatuan = strtolower(strtok($satuan, ' (/'));
        $firstWordKemasan = strtolower(strtok($kemasan, ' (/'));
        $isUnitSameAsKemasan = ($this->calcMode === 'kemasan_dus') || ($firstWordKemasan !== '' && (
            $firstWordSatuan === $firstWordKemasan
            || (str_contains($satuan, 'dus') && str_contains($kemasan, 'dus'))
            || (str_contains($satuan, 'box') && str_contains($kemasan, 'box'))
            || (str_contains($satuan, 'pack') && str_contains($kemasan, 'pack'))
        ));

        // Build kemasan deskripsi badge
        if ($this->calcMode === 'dus_pvc') {
            $pvcM = (float) ($this->calcPanjangMeter ?: 4.0);
            $totalLbr = ($jmlKemasan * $isiPerKemasan) + $jmlEceran;

            $parts = [];
            if ($jmlKemasan > 0) {
                $parts[] = "{$jmlKemasan} {$packLabel} (@{$isiPerKemasan} lbr)";
            }
            if ($jmlEceran > 0) {
                $wholeEcr = (int) floor($jmlEceran);
                $fracMtr = round(($jmlEceran - $wholeEcr) * $pvcM, 2);
                if ($fracMtr > 0 && $wholeEcr > 0) {
                    $parts[] = "{$wholeEcr} lbr + {$fracMtr}m potongan";
                } elseif ($fracMtr > 0) {
                    $parts[] = "{$fracMtr}m potongan";
                } else {
                    $parts[] = "{$jmlEceran} lbr eceran";
                }
            }

            $deskripsi = ! empty($parts) ? implode(' + ', $parts) : '0 lbr';
            $deskripsi .= " = {$totalLbr} lbr ({$pvcM}m)";
            $this->items[$index]['kemasan_deskripsi'] = $deskripsi;
        } elseif ($this->calcMode === 'roll') {
            $rollLen = (float) ($this->rollLength ?: 25.0);
            $deskripsi = "{$jmlKemasan} Roll (@{$rollLen}m)";
            if ($jmlEceran > 0) {
                $deskripsi .= " + {$jmlEceran}m eceran";
            }
            $this->items[$index]['kemasan_deskripsi'] = $deskripsi;
        } elseif ($isUnitSameAsKemasan && $isiPerKemasan > 0) {
            $deskripsi = "{$jmlKemasan} {$packLabel}";
            if ($jmlEceran > 0) {
                $deskripsi .= ' + '.(int) $jmlEceran.' pcs';
            }
            $this->items[$index]['kemasan_deskripsi'] = $deskripsi;
        } elseif ($isiPerKemasan > 0 && $jmlKemasan > 0) {
            $deskripsi = "{$jmlKemasan} {$packLabel} (@{$isiPerKemasan})";
            if ($jmlEceran > 0) {
                $deskripsi .= " + {$jmlEceran} eceran";
            }
            $this->items[$index]['kemasan_deskripsi'] = $deskripsi;
        } else {
            $this->items[$index]['kemasan_deskripsi'] = null;
        }

        $produk = Produk::find($this->items[$index]['produk_id']);
        $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
        if ($produk) {
            $this->applyAutoDiscount($index, $produk, $pelanggan);
        }

        $this->recalculateSubtotals();
        $this->closeCalculator();
    }

    /**
     * Compute real-time physical packaging badge for a line item.
     *
     * @param  array<string, mixed>  $item
     */
    public function getItemPhysicalBadge(array $item): ?string
    {
        $produkId = (int) ($item['produk_id'] ?? 0);
        $jumlah = (float) ($item['jumlah'] ?? 0);

        if ($produkId <= 0 || $jumlah <= 0) {
            return null;
        }

        $produk = Produk::find($produkId);
        if (! $produk) {
            return null;
        }

        $konv = $produk->getKonversiFisik($jumlah);

        return $konv['has_conversion'] ? $konv['badge_text'] : null;
    }

    public function render(): View
    {
        Gate::authorize('create', Penjualan::class);

        $gudangAktif = $this->gudang_id ? Gudang::find($this->gudang_id) : null;
        $stockService = app(StockService::class);
        $pelanggan = $this->pelanggan_id ? Pelanggan::find($this->pelanggan_id) : null;
        $cartProductIds = collect($this->items)->pluck('produk_id')->filter()->map(fn ($id) => (int) $id)->all();

        // 1. Quick search results (debounce / on typing)
        $quickSearchResults = [];
        if (filled(trim($this->quickSearchQuery))) {
            $term = trim($this->quickSearchQuery);
            $quickSearchResults = Produk::query()
                ->where(function ($q) use ($term) {
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode_katalog', 'like', "%{$term}%");
                })
                ->with(['kategori', 'merek'])
                ->orderBy('nama')
                ->limit(10)
                ->get()
                ->map(function (Produk $p) use ($gudangAktif, $stockService, $pelanggan) {
                    $stock = $gudangAktif ? $stockService->getStock($p, $gudangAktif) : 0;

                    return [
                        'id' => $p->id,
                        'nama' => $p->nama,
                        'kode_katalog' => $p->kode_katalog,
                        'satuan' => $p->satuan->label(),
                        'kategori' => $p->kategori?->nama_kategori ?? '-',
                        'merek' => $p->merek?->nama_merek ?? null,
                        'ukuran' => $p->ukuran,
                        'harga_jual' => $p->getHargaUntukPelanggan($pelanggan?->tipe),
                        'stok' => $stock,
                        'foto_url' => $p->foto_url,
                    ];
                });
        }

        // 2. Catalog modal items with filter
        $catalogProducts = [];
        if ($this->isCatalogModalOpen) {
            $catQuery = Produk::query()->with(['kategori', 'merek']);
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
            if ($this->catalogStockFilter === 'ready') {
                if ($gudangAktif) {
                    $catQuery->whereHas('stok', fn ($sq) => $sq->where('gudang_id', $gudangAktif->id)->where('jumlah', '>', 0));
                } else {
                    $catQuery->whereHas('stok', fn ($sq) => $sq->where('jumlah', '>', 0));
                }
            }

            $catalogProducts = $catQuery->orderBy('nama')->limit(50)->get()->map(function (Produk $p) use ($gudangAktif, $stockService, $pelanggan, $cartProductIds) {
                $stock = $gudangAktif ? $stockService->getStock($p, $gudangAktif) : 0;
                $tierPrice = $p->getHargaUntukPelanggan($pelanggan?->tipe);
                $normalPrice = (float) $p->harga_jual;
                $tierLabel = match ($pelanggan?->tipe) {
                    TipePelanggan::Pemborong => 'Harga Pemborong',
                    TipePelanggan::Langganan => 'Harga Langganan',
                    default => 'Harga Umum',
                };
                $isTierDiscounted = abs($tierPrice - $normalPrice) > 0.01;

                return [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'kode_katalog' => $p->kode_katalog,
                    'satuan' => $p->satuan->label(),
                    'kategori' => $p->kategori?->nama_kategori ?? '-',
                    'merek' => $p->merek?->nama_merek ?? null,
                    'ukuran' => $p->ukuran,
                    'harga_jual' => $tierPrice,
                    'harga_normal' => $normalPrice,
                    'tier_label' => $tierLabel,
                    'is_tier_discounted' => $isTierDiscounted,
                    'stok' => $stock,
                    'is_in_cart' => in_array($p->id, $cartProductIds, true),
                    'foto_url' => $p->foto_url,
                ];
            });
        }

        // 3. Searchable Pelanggan list
        $pelangganQuery = Pelanggan::query();
        if (filled(trim($this->pelangganSearchQuery))) {
            $pelangganQuery->search($this->pelangganSearchQuery);
        }
        $pelanggans = $pelangganQuery->orderBy('nama')->limit(50)->get();

        // 4. Directory Pelanggan Modal items with filter
        $modalPelanggans = [];
        if ($this->isPelangganModalOpen) {
            $mQuery = Pelanggan::query();
            if (filled(trim($this->pelangganModalSearch))) {
                $mQuery->search($this->pelangganModalSearch);
            }
            if (filled($this->pelangganModalTipe)) {
                $mQuery->where('tipe', $this->pelangganModalTipe);
            }
            $modalPelanggans = $mQuery->orderBy('nama')->limit(100)->get();
        }

        return view('livewire.sales.penjualan-create', [
            'pelanggans' => $pelanggans,
            'selectedPelanggan' => $pelanggan,
            'modalPelanggans' => $modalPelanggans,
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'produks' => Produk::with(['kategori', 'merek'])->orderBy('nama')->get(),
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'rootKategoris' => Kategori::root()->with('children')->orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
            'diskonOptions' => JenisDiskon::cases(),
            'quickSearchResults' => $quickSearchResults,
            'catalogProducts' => $catalogProducts,
        ]);
    }
}
