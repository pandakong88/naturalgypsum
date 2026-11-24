<?php

namespace App\Livewire\Catalog;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Actions\CreateProdukAction;
use App\Domains\Catalog\Actions\DeleteProdukAction;
use App\Domains\Catalog\Actions\RestoreProdukAction;
use App\Domains\Catalog\Actions\UpdateProdukAction;
use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Services\ImageOptimizationService;
use App\Domains\Catalog\Services\ProdukBatchPhotoService;
use App\Domains\Catalog\Services\ProdukExcelService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Master Produk')]
class ProdukIndex extends Component
{
    use WithFileUploads, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    #[Url]
    public string $filterKategori = '';

    #[Url]
    public string $filterMerek = '';

    public string $filterSatuan = '';

    public bool $showTrashed = false;

    // Enhanced pagination, tabs, and sorting
    public int $perPage = 10;

    public string $tabFilter = 'all'; // 'all', 'critical', 'trashed'

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    // Excel / CSV Import & Batch Photo state
    public bool $isImportModalOpen = false;

    public string $importTab = 'excel'; // 'excel' or 'photos'

    public $excelFile = null;

    public $csvFile = null;

    public bool $updateExistingOnImport = true;

    public ?array $importSummary = null;

    public ?array $previewData = null;

    public $zipFile = null;

    public ?array $photoSyncSummary = null;

    // Form modal state
    public bool $isFormOpen = false;

    public ?int $produkId = null;

    public int|string|null $kategori_induk_id = null;

    public string $kategori_id = '';

    public ?string $merek_id = '';

    public string $kode_katalog = '';

    public string $nama = '';

    public ?string $ukuran = '';

    public string $satuan = 'meter';

    public float|int|string $harga_beli = 0;

    public float|int|string $harga_jual = 0;

    public float|int|string|null $harga_aplikator = null;

    public float|int|string|null $harga_grosir = null;

    public float|int|string $stok_minimum = 0;

    public ?float $panjang_meter = null;

    public ?string $kemasan = null;

    public ?float $isi_per_kemasan = null;

    public ?float $panjang_per_roll = null;

    public $foto = null;

    public ?string $existingFoto = null;

    public bool $removeFoto = false;

    // Delete confirmation modal state
    public bool $isDeleteModalOpen = false;

    public ?int $produkToDeleteId = null;

    public string $produkToDeleteName = '';

    public string $produkToDeleteCode = '';

    // Barcode / Shelf label modal state
    public bool $isBarcodeModalOpen = false;

    public ?Produk $barcodeProduk = null;

    public int $barcodeQty = 1;

    // Image Preview / Lightbox modal state
    public bool $isImageModalOpen = false;

    public ?Produk $imageModalProduk = null;

    public function mount(): void
    {
        if (request()->has('edit')) {
            $editId = (int) request()->query('edit');
            if ($editId > 0 && Produk::where('id', $editId)->exists()) {
                $this->openEditModal($editId);
            }
        }
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterKategori = '';
        $this->filterMerek = '';
        $this->filterSatuan = '';
        $this->tabFilter = 'all';
        $this->showTrashed = false;
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return filled($this->search)
            || filled($this->filterKategori)
            || filled($this->filterMerek)
            || filled($this->filterSatuan)
            || $this->tabFilter !== 'all'
            || $this->showTrashed;
    }

    public function updatedSearch(): void
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

    public function updatedFilterSatuan(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->tabFilter = $this->showTrashed ? 'trashed' : 'all';
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tabFilter = $tab;
        $this->showTrashed = ($tab === 'trashed');
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowed = ['id', 'nama', 'kode_katalog', 'harga_jual', 'stok_minimum', 'created_at'];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSatuan(string $value): void
    {
        if ($value !== SatuanProduk::Meter->value) {
            $this->panjang_per_roll = null;
        }
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Produk::class);

        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function generateKodeKatalog(): void
    {
        $kategoriPrefix = 'PRD';
        if (filled($this->kategori_id)) {
            $kat = Kategori::find($this->kategori_id);
            if ($kat) {
                $words = preg_split('/[\s\/\-]+/', trim($kat->nama_kategori));
                if (count($words) >= 2) {
                    $kategoriPrefix = strtoupper(substr($words[0], 0, 2).substr($words[1], 0, 1));
                } else {
                    $kategoriPrefix = strtoupper(substr($words[0], 0, 3));
                }
            }
        }

        $merekPrefix = 'GEN';
        if (filled($this->merek_id)) {
            $mrk = Merek::find($this->merek_id);
            if ($mrk) {
                $words = preg_split('/[\s\/\-]+/', trim($mrk->nama_merek));
                if (count($words) >= 2) {
                    $merekPrefix = strtoupper(substr($words[0], 0, 2).substr($words[1], 0, 1));
                } else {
                    $merekPrefix = strtoupper(substr($words[0], 0, 2));
                }
            }
        }

        $baseCode = "{$kategoriPrefix}-{$merekPrefix}";
        $counter = 1;
        do {
            $candidate = sprintf('%s-%03d', $baseCode, $counter);
            $exists = Produk::where('kode_katalog', $candidate)
                ->when($this->produkId, fn ($q) => $q->where('id', '!=', $this->produkId))
                ->exists();
            $counter++;
        } while ($exists && $counter < 1000);

        $this->kode_katalog = $candidate;
    }

    public function updatedKategoriIndukId(mixed $value): void
    {
        $val = (int) $value;
        if ($val > 0) {
            $parent = Kategori::with('children')->find($val);
            if ($parent && $parent->children->isNotEmpty()) {
                $childIds = $parent->children->pluck('id')->all();
                if (! in_array((int) $this->kategori_id, $childIds, true)) {
                    $this->kategori_id = (string) $parent->children->first()->id;
                }
            } else {
                $this->kategori_id = (string) $val;
            }
        } else {
            $this->kategori_id = '';
        }

        $this->generateKodeKatalog();
    }

    public function openEditModal(int $id): void
    {
        $produk = Produk::with('kategori.parent')->findOrFail($id);
        Gate::authorize('update', $produk);

        $this->resetForm();
        $this->produkId = $produk->id;
        $this->kategori_induk_id = $produk->kategori?->parent_id ?? $produk->kategori_id;
        $this->kategori_id = (string) $produk->kategori_id;
        $this->merek_id = $produk->merek_id ? (string) $produk->merek_id : '';
        $this->kode_katalog = $produk->kode_katalog;
        $this->nama = $produk->nama;
        $this->ukuran = $produk->ukuran ?? '';
        $this->satuan = $produk->satuan->value;
        $this->harga_beli = Auth::user()?->can('view_purchase_prices') ? (float) $produk->harga_beli : 0;
        $this->harga_jual = (float) $produk->harga_jual;
        $this->harga_aplikator = $produk->harga_aplikator ? (float) $produk->harga_aplikator : null;
        $this->harga_grosir = $produk->harga_grosir ? (float) $produk->harga_grosir : null;
        $this->stok_minimum = (float) $produk->stok_minimum;
        $this->panjang_meter = $produk->panjang_meter !== null ? (float) $produk->panjang_meter : null;
        $this->kemasan = $produk->kemasan;
        $this->isi_per_kemasan = $produk->isi_per_kemasan !== null ? (float) $produk->isi_per_kemasan : null;
        $this->panjang_per_roll = $produk->panjang_per_roll ? (float) $produk->panjang_per_roll : null;
        $this->existingFoto = $produk->foto;
        $this->removeFoto = false;
        $this->foto = null;
        $this->isFormOpen = true;
    }

    public function duplicate(int $id, ActivityLogService $activityLog): void
    {
        Gate::authorize('create', Produk::class);

        $source = Produk::with('kategori.parent')->findOrFail($id);

        $this->resetForm();
        $this->kategori_induk_id = $source->kategori?->parent_id ?? $source->kategori_id;
        $this->kategori_id = (string) $source->kategori_id;
        $this->merek_id = $source->merek_id ? (string) $source->merek_id : '';
        $this->nama = $source->nama.' (Salinan)';
        $this->ukuran = $source->ukuran ?? '';
        $this->satuan = $source->satuan instanceof SatuanProduk ? $source->satuan->value : (string) $source->satuan;
        $this->harga_beli = Auth::user()?->can('view_purchase_prices') ? (float) $source->harga_beli : 0;
        $this->harga_jual = (float) $source->harga_jual;
        $this->harga_aplikator = $source->harga_aplikator !== null ? (float) $source->harga_aplikator : null;
        $this->harga_grosir = $source->harga_grosir !== null ? (float) $source->harga_grosir : null;
        $this->stok_minimum = (float) $source->stok_minimum;
        $this->panjang_meter = $source->panjang_meter !== null ? (float) $source->panjang_meter : null;
        $this->kemasan = $source->kemasan;
        $this->isi_per_kemasan = $source->isi_per_kemasan !== null ? (float) $source->isi_per_kemasan : null;
        $this->panjang_per_roll = $source->panjang_per_roll !== null ? (float) $source->panjang_per_roll : null;

        // Auto generate fresh unique code
        $this->generateKodeKatalog();

        $this->isFormOpen = true;
        session()->flash('info', "Menyalin data dari '{$source->nama}'. Sesuaikan kode katalog/nama motif dan simpan.");

        $activityLog->log(
            action: 'duplicate',
            description: "Menyiapkan duplikasi dari produk '{$source->nama}' ({$source->kode_katalog})",
            subject: $source
        );
    }

    public function markRemoveFoto(): void
    {
        $this->removeFoto = true;
        $this->foto = null;
    }

    public function closeFormModal(): void
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function setImportTab(string $tab): void
    {
        $this->importTab = $tab;
    }

    public function openImportModal(): void
    {
        Gate::authorize('create', Produk::class);
        $this->resetImportFile();
        $this->resetPhotoSync();
        $this->importTab = 'excel';
        $this->isImportModalOpen = true;
    }

    public function closeImportModal(): void
    {
        $this->isImportModalOpen = false;
        $this->resetImportFile();
        $this->resetPhotoSync();
        $this->importTab = 'excel';
    }

    public function resetImportFile(): void
    {
        $this->excelFile = null;
        $this->csvFile = null;
        $this->previewData = null;
        $this->importSummary = null;
        $this->resetErrorBag(['excelFile', 'csvFile']);
    }

    public function resetPhotoSync(): void
    {
        $this->zipFile = null;
        $this->photoSyncSummary = null;
        $this->resetErrorBag(['zipFile']);
    }

    public function syncPhotos(ProdukBatchPhotoService $batchService): void
    {
        Gate::authorize('create', Produk::class);

        $this->validate([
            'zipFile' => ['required', 'file', 'mimes:zip', 'max:102400'], // max 100MB
        ], [
            'zipFile.required' => 'Pilih file arsip .zip terlebih dahulu.',
            'zipFile.mimes' => 'Format file harus berupa file arsip .zip.',
            'zipFile.max' => 'Ukuran file ZIP maksimal 100 MB.',
        ]);

        try {
            $this->photoSyncSummary = $batchService->syncFromZip($this->zipFile, Auth::id());

            $matched = $this->photoSyncSummary['matched'];
            $unmatched = $this->photoSyncSummary['unmatched'];

            if ($matched > 0 && empty($this->photoSyncSummary['errors'])) {
                session()->flash('success', "Sinkronisasi foto berhasil: {$matched} foto produk berhasil ditautkan.");
            } elseif ($matched > 0) {
                session()->flash('warning', "Sinkronisasi selesai: {$matched} foto berhasil ditautkan, namun terdapat {$unmatched} foto yang SKU-nya tidak ditemukan.");
            } else {
                session()->flash('error', 'Tidak ada foto yang cocok dengan kode katalog/SKU di database. Periksa penamaan file foto.');
            }

            $this->zipFile = null;
        } catch (\Throwable $e) {
            $this->addError('zipFile', 'Terjadi kesalahan saat memproses file ZIP: '.$e->getMessage());
        }
    }

    public function updatedExcelFile(): void
    {
        $this->loadPreview(app(ProdukExcelService::class));
    }

    public function updatedCsvFile(): void
    {
        $this->loadPreview(app(ProdukExcelService::class));
    }

    public function updatedUpdateExistingOnImport(): void
    {
        if ($this->excelFile || $this->csvFile) {
            $this->loadPreview(app(ProdukExcelService::class));
        }
    }

    public function loadPreview(ProdukExcelService $excelService): void
    {
        $targetFile = $this->excelFile ?? $this->csvFile;
        if (! $targetFile) {
            $this->previewData = null;

            return;
        }

        $extension = strtolower($targetFile->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            $this->addError('excelFile', 'Format file harus berupa Excel (.xlsx) atau CSV (.csv).');
            $this->previewData = null;

            return;
        }

        try {
            $this->previewData = $excelService->preview(
                file: $targetFile,
                updateExisting: $this->updateExistingOnImport
            );
        } catch (\Throwable $e) {
            $this->addError('excelFile', 'Gagal membaca pratinjau file: '.$e->getMessage());
            $this->previewData = null;
        }
    }

    public function exportExcel(ProdukExcelService $excelService, ActivityLogService $activityLog): BinaryFileResponse
    {
        Gate::authorize('viewAny', Produk::class);

        $query = $this->buildQuery();
        $total = (clone $query)->count();

        $activityLog->log(
            action: 'export',
            description: "Mengekspor data katalog produk ke format Excel ({$total} produk)",
            properties: [
                'format' => 'xlsx',
                'total_exported' => $total,
                'search' => $this->search ?: null,
                'kategori_id' => $this->filterKategori ?: null,
                'merek_id' => $this->filterMerek ?: null,
            ]
        );

        return $excelService->export($query, 'katalog_produk_'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportCsv(ProdukExcelService $excelService, ActivityLogService $activityLog): BinaryFileResponse
    {
        return $this->exportExcel($excelService, $activityLog);
    }

    public function downloadTemplate(ProdukExcelService $excelService): BinaryFileResponse
    {
        Gate::authorize('create', Produk::class);

        return $excelService->generateTemplateExcel();
    }

    public function downloadSkuPhotoCheatSheet(ProdukExcelService $excelService, ActivityLogService $activityLog): BinaryFileResponse
    {
        Gate::authorize('viewAny', Produk::class);

        $activityLog->log(
            action: 'export',
            description: 'Mengunduh daftar SKU & status foto produk (Cheat-sheet foto massal)',
        );

        return $excelService->generateSkuPhotoCheatSheet();
    }

    public function importExcel(ProdukExcelService $excelService): void
    {
        Gate::authorize('create', Produk::class);

        $targetFile = $this->excelFile ?? $this->csvFile;

        if (! $targetFile) {
            $this->addError('excelFile', 'Pilih file Excel (.xlsx) atau CSV terlebih dahulu.');

            return;
        }

        $extension = strtolower($targetFile->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            $this->addError('excelFile', 'Format file harus berupa Excel (.xlsx) atau CSV (.csv).');

            return;
        }

        $this->importSummary = $excelService->import(
            file: $targetFile,
            updateExisting: $this->updateExistingOnImport,
            userId: Auth::id()
        );

        $created = $this->importSummary['created'];
        $updated = $this->importSummary['updated'];
        $skipped = $this->importSummary['skipped'];

        if (empty($this->importSummary['errors'])) {
            session()->flash('success', "Import berhasil: {$created} produk baru ditambahkan, {$updated} produk diperbarui, {$skipped} dilewati.");
            $this->closeImportModal();
        } else {
            session()->flash('warning', "Import selesai dengan catatan: {$created} baru, {$updated} diperbarui. Periksa daftar kendala.");
            // Refresh preview if still open
            $this->loadPreview($excelService);
        }
    }

    public function importCsv(ProdukExcelService $excelService): void
    {
        $this->importExcel($excelService);
    }

    public function resetForm(): void
    {
        $this->produkId = null;
        $this->kategori_induk_id = null;
        $this->kategori_id = '';
        $this->merek_id = '';
        $this->kode_katalog = '';
        $this->nama = '';
        $this->ukuran = '';
        $this->satuan = 'meter';
        $this->harga_beli = 0;
        $this->harga_jual = 0;
        $this->harga_aplikator = null;
        $this->harga_grosir = null;
        $this->stok_minimum = 0;
        $this->panjang_meter = null;
        $this->kemasan = null;
        $this->isi_per_kemasan = null;
        $this->panjang_per_roll = null;
        $this->foto = null;
        $this->existingFoto = null;
        $this->removeFoto = false;
        $this->resetValidation();
    }

    public function save(
        CreateProdukAction $createAction,
        UpdateProdukAction $updateAction,
        ImageOptimizationService $imageService
    ): void {
        $validSatuans = implode(',', SatuanProduk::values());
        $canViewPrice = Auth::user()?->can('view_purchase_prices') ?? false;

        $rules = [
            'kategori_id' => ['required', 'exists:kategori,id'],
            'merek_id' => ['nullable', 'exists:merek,id'],
            'kode_katalog' => ['required', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:255'],
            'ukuran' => ['nullable', 'string', 'max:100'],
            'satuan' => ['required', 'string', "in:{$validSatuans}"],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'harga_aplikator' => ['nullable', 'numeric', 'min:0'],
            'harga_grosir' => ['nullable', 'numeric', 'min:0'],
            'stok_minimum' => ['required', 'numeric', 'min:0'],
            'panjang_meter' => ['nullable', 'numeric', 'min:0.01', 'max:20'],
            'kemasan' => ['nullable', 'string', 'max:50'],
            'isi_per_kemasan' => ['nullable', 'numeric', 'min:0.01'],
            'panjang_per_roll' => ['nullable', 'numeric', 'min:0.01'],
            'foto' => ['nullable', 'image', 'max:10240'],
        ];

        if ($canViewPrice) {
            $rules['harga_beli'] = ['required', 'numeric', 'min:0'];
        }

        $this->validate($rules);

        try {
            $fotoPath = $this->existingFoto;
            if ($this->removeFoto) {
                $imageService->delete($this->existingFoto);
                $fotoPath = null;
            }

            if ($this->foto) {
                if ($this->existingFoto) {
                    $imageService->delete($this->existingFoto);
                }
                $fotoPath = $imageService->optimizeAndStore($this->foto);
            }

            $data = [
                'kategori_id' => $this->kategori_id,
                'merek_id' => filled($this->merek_id) ? $this->merek_id : null,
                'kode_katalog' => $this->kode_katalog,
                'nama' => $this->nama,
                'ukuran' => $this->ukuran,
                'satuan' => $this->satuan,
                'harga_jual' => $this->harga_jual,
                'harga_aplikator' => filled($this->harga_aplikator) ? $this->harga_aplikator : null,
                'harga_grosir' => filled($this->harga_grosir) ? $this->harga_grosir : null,
                'stok_minimum' => $this->stok_minimum,
                'panjang_meter' => filled($this->panjang_meter) ? $this->panjang_meter : null,
                'kemasan' => filled($this->kemasan) ? trim($this->kemasan) : null,
                'isi_per_kemasan' => filled($this->isi_per_kemasan) ? $this->isi_per_kemasan : null,
                'panjang_per_roll' => filled($this->panjang_per_roll) ? $this->panjang_per_roll : null,
                'foto' => $fotoPath,
            ];

            if ($canViewPrice) {
                $data['harga_beli'] = $this->harga_beli;
            } elseif (! $this->produkId) {
                $data['harga_beli'] = 0;
            }

            if ($this->produkId) {
                $produk = Produk::findOrFail($this->produkId);
                Gate::authorize('update', $produk);

                $updateAction->execute($produk, $data);
                session()->flash('success', 'Produk berhasil diperbarui.');
            } else {
                Gate::authorize('create', Produk::class);

                $createAction->execute($data);
                session()->flash('success', 'Produk baru berhasil ditambahkan.');
            }

            $this->closeFormModal();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        }
    }

    public function confirmDelete(int $id): void
    {
        $produk = Produk::findOrFail($id);
        Gate::authorize('delete', $produk);

        $this->produkToDeleteId = $produk->id;
        $this->produkToDeleteName = $produk->nama;
        $this->produkToDeleteCode = $produk->kode_katalog;
        $this->isDeleteModalOpen = true;
    }

    public function delete(DeleteProdukAction $deleteAction): void
    {
        if (! $this->produkToDeleteId) {
            return;
        }

        $produk = Produk::findOrFail($this->produkToDeleteId);
        Gate::authorize('delete', $produk);

        $deleteAction->execute($produk);
        session()->flash('success', "Produk '{$produk->nama}' ({$produk->kode_katalog}) berhasil dinonaktifkan.");
        $this->isDeleteModalOpen = false;
    }

    public function restore(int $id, RestoreProdukAction $restoreAction): void
    {
        $produk = Produk::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $produk);

        try {
            $restoreAction->execute($produk);
            session()->flash('success', "Produk '{$produk->nama}' ({$produk->kode_katalog}) berhasil dipulihkan.");
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('restore') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
        }
    }

    public function openBarcodeModal(int $id, ActivityLogService $activityLog): void
    {
        $this->barcodeProduk = Produk::with(['kategori', 'merek'])->findOrFail($id);
        $this->barcodeQty = 1;
        $this->isBarcodeModalOpen = true;

        $activityLog->log(
            action: 'barcode_view',
            description: "Melihat/mencetak label barcode untuk produk '{$this->barcodeProduk->nama}' ({$this->barcodeProduk->kode_katalog})",
            subject: $this->barcodeProduk
        );
    }

    public function closeBarcodeModal(): void
    {
        $this->isBarcodeModalOpen = false;
        $this->barcodeProduk = null;
        $this->barcodeQty = 1;
    }

    protected function buildQuery(): Builder
    {
        $query = Produk::query()
            ->with(['kategori.parent', 'merek', 'creator', 'updater'])
            ->withSum('stok', 'jumlah');

        if ($this->tabFilter === 'trashed' || $this->showTrashed) {
            $query->onlyTrashed();
        } elseif ($this->tabFilter === 'critical') {
            $query->whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) <= produk.stok_minimum');
        } elseif ($this->tabFilter === 'empty') {
            $query->whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) <= 0');
        } elseif ($this->tabFilter === 'safe') {
            $query->whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) > produk.stok_minimum');
        }

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_katalog', 'like', "%{$search}%")
                    ->orWhere('ukuran', 'like', "%{$search}%");
            });
        }

        if (filled($this->filterKategori)) {
            $selectedCategory = Kategori::with('children')->find($this->filterKategori);
            if ($selectedCategory && $selectedCategory->children->isNotEmpty()) {
                $categoryIds = $selectedCategory->children->pluck('id')->push($selectedCategory->id);
                $query->whereIn('kategori_id', $categoryIds);
            } else {
                $query->where('kategori_id', $this->filterKategori);
            }
        }

        if (filled($this->filterMerek)) {
            $query->where('merek_id', $this->filterMerek);
        }

        if (filled($this->filterSatuan)) {
            $query->where('satuan', $this->filterSatuan);
        }

        return $query;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Produk::class);

        $query = $this->buildQuery();

        if ($this->sortField === 'stok_total') {
            $query->orderBy('stok_sum_jumlah', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $produks = $query->paginate($this->perPage);

        $criticalCount = Produk::whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) <= produk.stok_minimum')->count();
        $emptyCount = Produk::whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) <= 0')->count();
        $safeCount = Produk::whereRaw('COALESCE((SELECT SUM(jumlah) FROM stok WHERE stok.produk_id = produk.id), 0) > produk.stok_minimum')->count();
        $totalCount = Produk::count();
        $trashedCount = Produk::onlyTrashed()->count();

        $rootKategoris = Kategori::root()
            ->with(['children' => fn ($q) => $q->orderBy('nama_kategori')])
            ->orderBy('nama_kategori')
            ->get();

        $selectedRoot = filled($this->kategori_induk_id)
            ? $rootKategoris->firstWhere('id', (int) $this->kategori_induk_id)
            : null;

        $subKategoris = $selectedRoot?->children ?? collect();

        return view('livewire.catalog.produk-index', [
            'produks' => $produks,
            'rootKategoris' => $rootKategoris,
            'subKategoris' => $subKategoris,
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
            'satuanOptions' => SatuanProduk::cases(),
            'criticalCount' => $criticalCount,
            'emptyCount' => $emptyCount,
            'safeCount' => $safeCount,
            'totalCount' => $totalCount,
            'trashedCount' => $trashedCount,
        ]);
    }
}
