<?php

namespace App\Livewire\Inventory;

use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Actions\CancelStockOpnameAction;
use App\Domains\Inventory\Actions\CompleteStockOpnameAction;
use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\DetailStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Inventory\Services\StockOpnameExcelService;
use App\Domains\Inventory\Services\StockService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Detail Stok Opname')]
class StockOpnameDetail extends Component
{
    use WithFileUploads;

    public StockOpname $stockOpname;

    public bool $isDraft = false;

    public ?int $selectedProdukId = null;

    public array $items = [];

    public string $alasan_batal = '';

    public bool $isCancelModalOpen = false;

    public bool $isDeleteDraftModalOpen = false;

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

    // --- Poin 3: Physical Count Sheet Print ---
    public bool $isPrintCountSheetModalOpen = false;

    public string $countSheetMode = 'blind'; // 'blind' | 'guided'

    // --- Poin 5: Excel Import & Export ---
    public bool $isImportExcelModalOpen = false;

    public $excelCountFile = null;

    public ?array $importSummary = null;

    public function promptCancelModal(): void
    {
        $this->resetErrorBag();
        $this->isCancelModalOpen = true;
    }

    public function closeCancelModal(): void
    {
        $this->isCancelModalOpen = false;
    }

    public function promptDeleteDraftModal(): void
    {
        $this->isDeleteDraftModalOpen = true;
    }

    public function closeDeleteDraftModal(): void
    {
        $this->isDeleteDraftModalOpen = false;
    }

    public function mount(int $id): void
    {
        $this->stockOpname = StockOpname::with([
            'gudang',
            'user',
            'dibatalkanBy',
            'details.produk.kategori',
            'details.produk.merek',
        ])->findOrFail($id);
        Gate::authorize('view', $this->stockOpname);

        $this->isDraft = ($this->stockOpname->status === StatusStockOpname::Draft);

        if ($this->isDraft) {
            $this->items = [];
            foreach ($this->stockOpname->details as $d) {
                $p = $d->produk;
                $this->items[] = [
                    'id' => $d->id,
                    'produk_id' => $d->produk_id,
                    'nama_produk' => $p->nama ?? '-',
                    'kode_produk' => $p->kode_katalog ?? '-',
                    'satuan' => $p?->satuan ? (is_object($p->satuan) ? $p->satuan->label() : (string) $p->satuan) : 'Pcs',
                    'stok_sistem' => (float) $d->stok_sistem,
                    'stok_fisik' => (float) $d->stok_fisik,
                    'catatan' => $d->catatan ?? '',
                    'kemasan' => $p?->kemasan,
                    'isi_per_kemasan' => $p?->isi_per_kemasan ? (float) $p->isi_per_kemasan : null,
                    'panjang_meter' => $p?->panjang_meter ? (float) $p->panjang_meter : null,
                    'kategori' => $p?->kategori?->nama_kategori ?? '-',
                    'merek' => $p?->merek?->nama_merek ?? '-',
                ];
            }
        }
    }

    #[Computed]
    public function availableProducts()
    {
        $existingIds = array_column($this->items, 'produk_id');

        return Produk::whereNull('deleted_at')
            ->whereNotIn('id', $existingIds)
            ->with(['kategori', 'merek'])
            ->orderBy('nama')
            ->get();
    }

    public function addProduct(): void
    {
        if (! $this->isDraft || ! $this->selectedProdukId) {
            return;
        }

        $produk = Produk::with(['kategori', 'merek'])->find($this->selectedProdukId);
        if (! $produk) {
            return;
        }

        $stokSistem = (float) app(StockService::class)->getStock($produk, $this->stockOpname->gudang);

        $this->items[] = [
            'id' => null,
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
        ];

        $this->selectedProdukId = null;
    }

    public function removeProduct(int $index): void
    {
        if (! $this->isDraft) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    // --- Poin 1: Packaging Calculator ---
    public function openPackagingCalculator(int $index): void
    {
        if (! $this->isDraft || ! isset($this->items[$index])) {
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
            $fromEceran = $jmlEceran * $panjangMeter;

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

        // Case 4: Standard items with packaging capacity (Sekrup 1000 pcs/dus, Hollow 20 btg/ikat)
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

    // --- Poin 3: Physical Count Sheet Print ---
    public function openPrintCountSheetModal(): void
    {
        $this->isPrintCountSheetModalOpen = true;
    }

    public function closePrintCountSheetModal(): void
    {
        $this->isPrintCountSheetModalOpen = false;
    }

    // --- Poin 5: Excel Export & Import ---
    public function exportTemplate(StockOpnameExcelService $service, bool $blind = false)
    {
        if (! $this->isDraft) {
            return $service->exportHasilOpname($this->stockOpname);
        }

        if (empty($this->items)) {
            session()->flash('error', 'Dokumen opname tidak memiliki baris produk untuk diekspor.');

            return;
        }

        $gudangNama = $this->stockOpname->gudang->nama_gudang ?? 'Gudang';

        return $service->exportCountSheetTemplate($gudangNama, $this->items, $blind);
    }

    public function openImportExcelModal(): void
    {
        if (! $this->isDraft) {
            return;
        }
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
        if (! $this->isDraft) {
            return;
        }

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
            $this->updateDraft();
            session()->flash('success', "Import berhasil! {$result['updated_count']} item fisik berhasil diperbarui dari Excel dan draft telah disimpan.");
            $this->isImportExcelModalOpen = false;
        } elseif ($result['updated_count'] > 0) {
            $this->updateDraft();
            session()->flash('warning', "Import selesai: {$result['updated_count']} item diperbarui, terdapat beberapa catatan.");
        } else {
            session()->flash('error', 'Tidak ada data stok fisik yang berhasil diimpor. Pastikan Kode Barang cocok dengan item pada draft opname.');
        }
    }

    public function updateDraft(): void
    {
        Gate::authorize('update', $this->stockOpname);

        if (empty($this->items)) {
            $this->addError('items', 'Minimal satu item harus dimasukkan.');

            return;
        }

        DB::transaction(function () {
            // Delete existing detail records and re-create from draft state
            $this->stockOpname->details()->delete();

            foreach ($this->items as $item) {
                $stokFisik = (float) ($item['stok_fisik'] ?? 0);
                $stokSistem = (float) ($item['stok_sistem'] ?? 0);

                DetailStockOpname::create([
                    'stock_opname_id' => $this->stockOpname->id,
                    'produk_id' => $item['produk_id'],
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $stokFisik - $stokSistem,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }
        });

        $this->stockOpname->load('details.produk');
        session()->flash('success', 'Draft Stok Opname berhasil diperbarui.');
    }

    public function refreshDraftSnapshots(StockService $stockService): void
    {
        Gate::authorize('update', $this->stockOpname);

        foreach ($this->items as $index => $item) {
            $produk = Produk::find($item['produk_id']);
            if ($produk) {
                $stokSistem = (float) $stockService->getStock($produk, $this->stockOpname->gudang);
                $this->items[$index]['stok_sistem'] = $stokSistem;
            }
        }

        $this->updateDraft();
        session()->flash('success', 'Snapshot stok draft berhasil diperbarui dengan data live inventory saat ini.');
    }

    public function completeDraft(CompleteStockOpnameAction $action)
    {
        Gate::authorize('update', $this->stockOpname);

        if (empty($this->items)) {
            $this->addError('items', 'Minimal satu item harus dimasukkan.');

            return;
        }

        try {
            $action->execute($this->stockOpname, $this->items, auth()->id());

            session()->flash('success', 'Stok Opname '.$this->stockOpname->no_stock_opname.' berhasil diselesaikan dan mutasi stok telah diterapkan.');

            return redirect()->route('inventory.stock-opname.detail', $this->stockOpname->id);
        } catch (Exception $e) {
            session()->flash('error', 'Gagal menyelesaikan stok opname: '.$e->getMessage());
        }
    }

    public function cancelStockOpname(CancelStockOpnameAction $action)
    {
        Gate::authorize('delete', $this->stockOpname);

        $this->validate([
            'alasan_batal' => 'required|string|min:3|max:500',
        ]);

        try {
            $action->execute($this->stockOpname, $this->alasan_batal);
            $this->isCancelModalOpen = false;
            session()->flash('success', 'Stok Opname '.$this->stockOpname->no_stock_opname.' berhasil dibatalkan dan penyesuaian stok telah dikembalikan.');

            return redirect()->route('inventory.stock-opname.detail', $this->stockOpname->id);
        } catch (Exception $e) {
            $this->isCancelModalOpen = false;
            session()->flash('error', 'Gagal membatalkan stok opname: '.$e->getMessage());
        }
    }

    public function deleteDraft()
    {
        Gate::authorize('delete', $this->stockOpname);

        if ($this->stockOpname->status !== StatusStockOpname::Draft) {
            session()->flash('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');

            return;
        }

        $noOpname = $this->stockOpname->no_stock_opname;

        DB::transaction(function () {
            $this->stockOpname->details()->delete();
            $this->stockOpname->delete();
        });

        $this->isDeleteDraftModalOpen = false;
        session()->flash('success', 'Draft Stok Opname '.$noOpname.' berhasil dihapus secara permanen.');

        return redirect()->route('inventory.stock-opname.index');
    }

    public function render(): View
    {
        Gate::authorize('view', $this->stockOpname);

        return view('livewire.inventory.stock-opname-detail', [
            'availableProducts' => $this->availableProducts,
        ]);
    }
}
