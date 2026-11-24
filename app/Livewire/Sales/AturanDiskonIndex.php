<?php

namespace App\Livewire\Sales;

use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Sales\Models\AturanDiskon;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Aturan Diskon & Promosi')]
class AturanDiskonIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $filterTargetPelanggan = '';

    public string $filterTargetProduk = '';

    public string $filterStatus = ''; // '', 'active', 'inactive', 'expired'

    public bool $showTrashed = false;

    // Form Modal State
    public bool $isFormOpen = false;

    public ?int $aturanId = null;

    public string $nama = '';

    public ?string $keterangan = '';

    public string $target_pelanggan = 'semua';

    public ?string $pelanggan_id = '';

    public string $target_produk = 'semua';

    public ?string $kategori_id = '';

    public ?string $merek_id = '';

    public ?string $produk_id = '';

    // Multi-select Arrays
    /** @var array<int, int> */
    public array $selected_kategori_ids = [];

    /** @var array<int, int> */
    public array $selected_merek_ids = [];

    /** @var array<int, int> */
    public array $selected_produk_ids = [];

    // Search queries for pickers
    public string $searchProdukQuery = '';

    public string $searchKategoriQuery = '';

    public string $searchMerekQuery = '';

    public string $jenis_diskon = 'persentase';

    public float|int|string $nilai_diskon = 0;

    public float|int|string $min_kuantiti = 0;

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public bool $is_active = true;

    public int $priority = 0;

    // Delete Confirmation Modal State
    public bool $isDeleteModalOpen = false;

    public ?int $aturanToDeleteId = null;

    public string $aturanToDeleteName = '';

    // Restore Modal State
    public bool $isRestoreModalOpen = false;

    public ?int $aturanToRestoreId = null;

    public string $aturanToRestoreName = '';

    // Force Delete Modal State
    public bool $isForceDeleteModalOpen = false;

    public ?int $aturanToForceDeleteId = null;

    public string $aturanToForceDeleteName = '';

    // Toggle Active Confirmation Modal State
    public bool $isToggleModalOpen = false;

    public ?int $aturanToToggleId = null;

    public string $aturanToToggleName = '';

    public bool $aturanToToggleTargetActive = false;

    public function toggleShowTrashed(): void
    {
        $this->showTrashed = ! $this->showTrashed;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTargetPelanggan(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTargetProduk(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function toggleSelectedProduk(int $id): void
    {
        if (in_array($id, $this->selected_produk_ids, true)) {
            $this->selected_produk_ids = array_values(array_filter($this->selected_produk_ids, fn ($item) => $item !== $id));
        } else {
            $this->selected_produk_ids[] = $id;
        }
    }

    public function selectAllFoundProduks(): void
    {
        $term = trim($this->searchProdukQuery);
        $query = Produk::query();
        if (filled($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                    ->orWhere('kode_katalog', 'like', "%{$term}%");
            });
        } else {
            $query->latest('id')->limit(20);
        }
        $foundIds = $query->pluck('id')->all();
        $this->selected_produk_ids = array_values(array_unique(array_merge($this->selected_produk_ids, $foundIds)));
    }

    public function clearSelectedProduks(): void
    {
        $this->selected_produk_ids = [];
    }

    public function addSelectedProduk(int $id): void
    {
        if (! in_array($id, $this->selected_produk_ids, true)) {
            $this->selected_produk_ids[] = $id;
        }
    }

    public function removeSelectedProduk(int $id): void
    {
        $this->selected_produk_ids = array_values(array_filter($this->selected_produk_ids, fn ($item) => $item !== $id));
    }

    public function toggleSelectedKategori(int $id): void
    {
        if (in_array($id, $this->selected_kategori_ids, true)) {
            $this->selected_kategori_ids = array_values(array_filter($this->selected_kategori_ids, fn ($item) => $item !== $id));
        } else {
            $this->selected_kategori_ids[] = $id;
        }
    }

    public function selectAllKategoris(): void
    {
        $term = trim($this->searchKategoriQuery);
        $query = Kategori::query();
        if (filled($term)) {
            $query->where('nama_kategori', 'like', "%{$term}%");
        }
        $foundIds = $query->pluck('id')->all();
        $this->selected_kategori_ids = array_values(array_unique(array_merge($this->selected_kategori_ids, $foundIds)));
    }

    public function selectAllFoundKategoris(): void
    {
        $this->selectAllKategoris();
    }

    public function clearSelectedKategoris(): void
    {
        $this->selected_kategori_ids = [];
    }

    public function addSelectedKategori(int $id): void
    {
        if (! in_array($id, $this->selected_kategori_ids, true)) {
            $this->selected_kategori_ids[] = $id;
        }
    }

    public function removeSelectedKategori(int $id): void
    {
        $this->selected_kategori_ids = array_values(array_filter($this->selected_kategori_ids, fn ($item) => $item !== $id));
    }

    public function toggleSelectedMerek(int $id): void
    {
        if (in_array($id, $this->selected_merek_ids, true)) {
            $this->selected_merek_ids = array_values(array_filter($this->selected_merek_ids, fn ($item) => $item !== $id));
        } else {
            $this->selected_merek_ids[] = $id;
        }
    }

    public function selectAllMereks(): void
    {
        $term = trim($this->searchMerekQuery);
        $query = Merek::query();
        if (filled($term)) {
            $query->where('nama_merek', 'like', "%{$term}%");
        }
        $foundIds = $query->pluck('id')->all();
        $this->selected_merek_ids = array_values(array_unique(array_merge($this->selected_merek_ids, $foundIds)));
    }

    public function selectAllFoundMereks(): void
    {
        $this->selectAllMereks();
    }

    public function clearSelectedMereks(): void
    {
        $this->selected_merek_ids = [];
    }

    public function addSelectedMerek(int $id): void
    {
        if (! in_array($id, $this->selected_merek_ids, true)) {
            $this->selected_merek_ids[] = $id;
        }
    }

    public function removeSelectedMerek(int $id): void
    {
        $this->selected_merek_ids = array_values(array_filter($this->selected_merek_ids, fn ($item) => $item !== $id));
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $aturan = AturanDiskon::with(['kategoris', 'mereks', 'produks'])->findOrFail($id);

        $this->resetForm();
        $this->aturanId = $aturan->id;
        $this->nama = $aturan->nama;
        $this->keterangan = $aturan->keterangan ?? '';
        $this->target_pelanggan = $aturan->target_pelanggan;
        $this->pelanggan_id = $aturan->pelanggan_id ? (string) $aturan->pelanggan_id : '';
        $this->target_produk = $aturan->target_produk;

        // Legacy & Pivot IDs
        $this->selected_kategori_ids = $aturan->kategoris->pluck('id')->all();
        if (empty($this->selected_kategori_ids) && $aturan->kategori_id) {
            $this->selected_kategori_ids = [(int) $aturan->kategori_id];
        }

        $this->selected_merek_ids = $aturan->mereks->pluck('id')->all();
        if (empty($this->selected_merek_ids) && $aturan->merek_id) {
            $this->selected_merek_ids = [(int) $aturan->merek_id];
        }

        $this->selected_produk_ids = $aturan->produks->pluck('id')->all();
        if (empty($this->selected_produk_ids) && $aturan->produk_id) {
            $this->selected_produk_ids = [(int) $aturan->produk_id];
        }

        $this->kategori_id = $aturan->kategori_id ? (string) $aturan->kategori_id : '';
        $this->merek_id = $aturan->merek_id ? (string) $aturan->merek_id : '';
        $this->produk_id = $aturan->produk_id ? (string) $aturan->produk_id : '';

        $this->jenis_diskon = $aturan->jenis_diskon;
        $this->nilai_diskon = (float) $aturan->nilai_diskon;
        $this->min_kuantiti = (float) $aturan->min_kuantiti;
        $this->tanggal_mulai = $aturan->tanggal_mulai?->toDateString();
        $this->tanggal_selesai = $aturan->tanggal_selesai?->toDateString();
        $this->is_active = (bool) $aturan->is_active;
        $this->priority = (int) $aturan->priority;

        $this->isFormOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->aturanId = null;
        $this->nama = '';
        $this->keterangan = '';
        $this->target_pelanggan = 'semua';
        $this->pelanggan_id = '';
        $this->target_produk = 'semua';
        $this->kategori_id = '';
        $this->merek_id = '';
        $this->produk_id = '';
        $this->selected_kategori_ids = [];
        $this->selected_merek_ids = [];
        $this->selected_produk_ids = [];
        $this->searchProdukQuery = '';
        $this->searchKategoriQuery = '';
        $this->searchMerekQuery = '';
        $this->jenis_diskon = 'persentase';
        $this->nilai_diskon = 0;
        $this->min_kuantiti = 0;
        $this->tanggal_mulai = null;
        $this->tanggal_selesai = null;
        $this->is_active = true;
        $this->priority = 0;
        $this->resetValidation();
    }

    public function confirmToggleActive(int $id): void
    {
        $aturan = AturanDiskon::findOrFail($id);
        $this->aturanToToggleId = $aturan->id;
        $this->aturanToToggleName = $aturan->nama;
        $this->aturanToToggleTargetActive = ! $aturan->is_active;
        $this->isToggleModalOpen = true;
    }

    public function toggleActive(?int $id = null): void
    {
        $targetId = $id ?? $this->aturanToToggleId;
        if (! $targetId) {
            return;
        }

        $aturan = AturanDiskon::findOrFail($targetId);
        $oldActive = $aturan->is_active;
        $newActive = ! $oldActive;

        $aturan->update([
            'is_active' => $newActive,
            'updated_by' => Auth::id(),
        ]);

        $actionText = $newActive ? 'mengaktifkan' : 'menonaktifkan';
        $statusText = $newActive ? 'diaktifkan' : 'dinonaktifkan';

        app(ActivityLogService::class)->log(
            action: 'update',
            description: "Pengguna {$actionText} aturan diskon: {$aturan->nama}",
            subject: $aturan,
            oldValues: ['is_active' => $oldActive],
            newValues: ['is_active' => $newActive]
        );

        session()->flash('success', "Aturan diskon '{$aturan->nama}' berhasil {$statusText}.");

        $this->isToggleModalOpen = false;
        $this->aturanToToggleId = null;
        $this->aturanToToggleName = '';
    }

    public function save(): void
    {
        // Kompatibilitas jika dipanggil via test atau form lama
        if ($this->target_produk === 'kategori' && empty($this->selected_kategori_ids) && filled($this->kategori_id)) {
            $this->selected_kategori_ids = [(int) $this->kategori_id];
        }
        if ($this->target_produk === 'merek' && empty($this->selected_merek_ids) && filled($this->merek_id)) {
            $this->selected_merek_ids = [(int) $this->merek_id];
        }
        if ($this->target_produk === 'spesifik' && empty($this->selected_produk_ids) && filled($this->produk_id)) {
            $this->selected_produk_ids = [(int) $this->produk_id];
        }

        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'target_pelanggan' => ['required', 'in:semua,umum,pemborong,langganan,spesifik'],
            'pelanggan_id' => ['nullable', 'required_if:target_pelanggan,spesifik', 'exists:pelanggan,id'],
            'target_produk' => ['required', 'in:semua,kategori,merek,spesifik'],
            'jenis_diskon' => ['required', 'in:persentase,nominal'],
            'nilai_diskon' => $this->jenis_diskon === 'persentase'
                ? ['required', 'numeric', 'gt:0', 'lte:100']
                : ['required', 'numeric', 'gt:0'],
            'min_kuantiti' => ['nullable', 'numeric', 'min:0'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'is_active' => ['boolean'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
        ];

        if ($this->target_produk === 'kategori') {
            $rules['selected_kategori_ids'] = ['required', 'array', 'min:1'];
            $rules['selected_kategori_ids.*'] = ['integer', 'exists:kategori,id'];
        } elseif ($this->target_produk === 'merek') {
            $rules['selected_merek_ids'] = ['required', 'array', 'min:1'];
            $rules['selected_merek_ids.*'] = ['integer', 'exists:merek,id'];
        } elseif ($this->target_produk === 'spesifik') {
            $rules['selected_produk_ids'] = ['required', 'array', 'min:1'];
            $rules['selected_produk_ids.*'] = ['integer', 'exists:produk,id'];
        }

        $this->validate($rules, [
            'nama.required' => 'Nama aturan diskon wajib diisi.',
            'nilai_diskon.required' => 'Nilai diskon wajib diisi.',
            'nilai_diskon.gt' => 'Nilai diskon harus lebih besar dari 0.',
            'nilai_diskon.lte' => 'Diskon persentase tidak boleh lebih dari 100%.',
            'priority.required' => 'Tingkat prioritas wajib diisi.',
            'priority.min' => 'Prioritas minimal 0.',
            'priority.max' => 'Prioritas maksimal 999.',
            'pelanggan_id.required_if' => 'Pilih pelanggan spesifik penerima diskon.',
            'selected_kategori_ids.required_if' => 'Pilih minimal satu kategori produk target diskon.',
            'selected_kategori_ids.min' => 'Pilih minimal satu kategori produk target diskon.',
            'selected_merek_ids.required_if' => 'Pilih minimal satu merek produk target diskon.',
            'selected_merek_ids.min' => 'Pilih minimal satu merek produk target diskon.',
            'selected_produk_ids.required_if' => 'Pilih minimal satu produk target diskon.',
            'selected_produk_ids.min' => 'Pilih minimal satu produk target diskon.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $payload = [
            'nama' => $this->nama,
            'keterangan' => filled($this->keterangan) ? $this->keterangan : null,
            'target_pelanggan' => $this->target_pelanggan,
            'pelanggan_id' => $this->target_pelanggan === 'spesifik' && filled($this->pelanggan_id) ? $this->pelanggan_id : null,
            'target_produk' => $this->target_produk,
            // Simpan primary ID pertama ke kolom legacy untuk backward compatibility
            'kategori_id' => $this->target_produk === 'kategori' && ! empty($this->selected_kategori_ids) ? $this->selected_kategori_ids[0] : null,
            'merek_id' => $this->target_produk === 'merek' && ! empty($this->selected_merek_ids) ? $this->selected_merek_ids[0] : null,
            'produk_id' => $this->target_produk === 'spesifik' && ! empty($this->selected_produk_ids) ? $this->selected_produk_ids[0] : null,
            'jenis_diskon' => $this->jenis_diskon,
            'nilai_diskon' => $this->nilai_diskon,
            'min_kuantiti' => filled($this->min_kuantiti) ? $this->min_kuantiti : 0,
            'tanggal_mulai' => filled($this->tanggal_mulai) ? $this->tanggal_mulai : null,
            'tanggal_selesai' => filled($this->tanggal_selesai) ? $this->tanggal_selesai : null,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
        ];

        if ($this->aturanId) {
            $aturan = AturanDiskon::findOrFail($this->aturanId);
            $oldValues = $aturan->toArray();
            $payload['updated_by'] = Auth::id();
            $aturan->update($payload);

            // Sync pivot tables
            $aturan->kategoris()->sync($this->target_produk === 'kategori' ? $this->selected_kategori_ids : []);
            $aturan->mereks()->sync($this->target_produk === 'merek' ? $this->selected_merek_ids : []);
            $aturan->produks()->sync($this->target_produk === 'spesifik' ? $this->selected_produk_ids : []);

            $newValues = $aturan->fresh()->toArray();

            app(ActivityLogService::class)->log(
                action: 'update',
                description: "Memperbarui aturan diskon: {$aturan->nama}",
                subject: $aturan,
                oldValues: $oldValues,
                newValues: $newValues,
                properties: [
                    'target_pelanggan' => $aturan->target_pelanggan,
                    'target_produk' => $aturan->target_produk,
                    'kategori_ids' => $this->selected_kategori_ids,
                    'merek_ids' => $this->selected_merek_ids,
                    'produk_ids' => $this->selected_produk_ids,
                ]
            );

            session()->flash('success', "Aturan diskon '{$aturan->nama}' berhasil diperbarui.");
        } else {
            $payload['created_by'] = Auth::id();
            $payload['updated_by'] = Auth::id();
            $aturan = AturanDiskon::create($payload);

            // Sync pivot tables
            $aturan->kategoris()->sync($this->target_produk === 'kategori' ? $this->selected_kategori_ids : []);
            $aturan->mereks()->sync($this->target_produk === 'merek' ? $this->selected_merek_ids : []);
            $aturan->produks()->sync($this->target_produk === 'spesifik' ? $this->selected_produk_ids : []);

            $diskonFormatted = $aturan->jenis_diskon === 'persentase'
                ? "{$aturan->nilai_diskon}%"
                : 'Rp '.number_format((float) $aturan->nilai_diskon, 0, ',', '.');

            app(ActivityLogService::class)->log(
                action: 'create',
                description: "Menambahkan aturan diskon baru: {$aturan->nama} ({$diskonFormatted})",
                subject: $aturan,
                newValues: $aturan->toArray(),
                properties: [
                    'target_pelanggan' => $aturan->target_pelanggan,
                    'target_produk' => $aturan->target_produk,
                    'kategori_ids' => $this->selected_kategori_ids,
                    'merek_ids' => $this->selected_merek_ids,
                    'produk_ids' => $this->selected_produk_ids,
                ]
            );

            session()->flash('success', 'Aturan diskon baru berhasil ditambahkan.');
        }

        $this->closeFormModal();
    }

    public function confirmDelete(int $id): void
    {
        $aturan = AturanDiskon::findOrFail($id);
        $this->aturanToDeleteId = $aturan->id;
        $this->aturanToDeleteName = $aturan->nama;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if (! $this->aturanToDeleteId) {
            return;
        }

        $aturan = AturanDiskon::findOrFail($this->aturanToDeleteId);
        $aturanName = $aturan->nama;
        $oldValues = $aturan->toArray();
        $aturan->delete();

        app(ActivityLogService::class)->log(
            action: 'delete',
            description: "Menghapus aturan diskon: {$aturanName} (ke tempat sampah)",
            subject: $aturan,
            oldValues: $oldValues
        );

        session()->flash('success', "Aturan diskon '{$aturanName}' berhasil dihapus.");
        $this->isDeleteModalOpen = false;
        $this->aturanToDeleteId = null;
        $this->aturanToDeleteName = '';
    }

    public function confirmRestore(int $id): void
    {
        $aturan = AturanDiskon::onlyTrashed()->findOrFail($id);
        $this->aturanToRestoreId = $aturan->id;
        $this->aturanToRestoreName = $aturan->nama;
        $this->isRestoreModalOpen = true;
    }

    public function restore(): void
    {
        if (! $this->aturanToRestoreId) {
            return;
        }

        $aturan = AturanDiskon::onlyTrashed()->findOrFail($this->aturanToRestoreId);
        $aturanName = $aturan->nama;
        $aturan->restore();

        app(ActivityLogService::class)->log(
            action: 'restore',
            description: "Memulihkan aturan diskon dari tempat sampah: {$aturanName}",
            subject: $aturan,
            newValues: $aturan->toArray()
        );

        session()->flash('success', "Aturan diskon '{$aturanName}' berhasil dipulihkan.");
        $this->isRestoreModalOpen = false;
        $this->aturanToRestoreId = null;
        $this->aturanToRestoreName = '';
    }

    public function confirmForceDelete(int $id): void
    {
        $aturan = AturanDiskon::onlyTrashed()->findOrFail($id);
        $this->aturanToForceDeleteId = $aturan->id;
        $this->aturanToForceDeleteName = $aturan->nama;
        $this->isForceDeleteModalOpen = true;
    }

    public function forceDelete(): void
    {
        if (! $this->aturanToForceDeleteId) {
            return;
        }

        $aturan = AturanDiskon::onlyTrashed()->findOrFail($this->aturanToForceDeleteId);
        $aturanName = $aturan->nama;
        $oldValues = $aturan->toArray();

        // Detach pivot tables
        $aturan->kategoris()->detach();
        $aturan->mereks()->detach();
        $aturan->produks()->detach();

        $aturan->forceDelete();

        app(ActivityLogService::class)->log(
            action: 'force_delete',
            description: "Menghapus permanen aturan diskon: {$aturanName}",
            subject: $aturan,
            oldValues: $oldValues
        );

        session()->flash('success', "Aturan diskon '{$aturanName}' berhasil dihapus permanen.");
        $this->isForceDeleteModalOpen = false;
        $this->aturanToForceDeleteId = null;
        $this->aturanToForceDeleteName = '';
    }

    public function render(): View
    {
        $today = Carbon::today()->toDateString();

        // Summary KPI Counts
        $totalCount = AturanDiskon::count();
        $activeCount = AturanDiskon::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today))
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today))
            ->count();
        $upcomingCount = AturanDiskon::where('is_active', true)
            ->whereNotNull('tanggal_mulai')
            ->where('tanggal_mulai', '>', $today)
            ->count();
        $expiredOrInactiveCount = AturanDiskon::where('is_active', false)
            ->orWhere(fn ($q) => $q->whereNotNull('tanggal_selesai')->where('tanggal_selesai', '<', $today))
            ->count();
        $trashedCount = AturanDiskon::onlyTrashed()->count();

        $query = $this->showTrashed
            ? AturanDiskon::onlyTrashed()
            : AturanDiskon::query();

        $query->with(['kategori', 'merek', 'produk', 'pelanggan', 'kategoris', 'mereks', 'produks'])
            ->latest('id');

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where('nama', 'like', "%{$search}%");
        }

        if (filled($this->filterTargetPelanggan)) {
            $query->where('target_pelanggan', $this->filterTargetPelanggan);
        }

        if (filled($this->filterTargetProduk)) {
            $query->where('target_produk', $this->filterTargetProduk);
        }

        if (! $this->showTrashed) {
            if ($this->filterStatus === 'active') {
                $query->where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today))
                    ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today));
            } elseif ($this->filterStatus === 'inactive') {
                $query->where('is_active', false);
            } elseif ($this->filterStatus === 'expired') {
                $query->whereNotNull('tanggal_selesai')->where('tanggal_selesai', '<', $today);
            }
        }

        $aturans = $query->paginate(10);

        // Search and available items for pickers
        $availableKategoris = collect();
        if ($this->isFormOpen && $this->target_produk === 'kategori') {
            $katQuery = Kategori::query()->with('parent')->withCount('produks')->orderByRaw('COALESCE(parent_id, id) ASC, parent_id IS NOT NULL ASC, id ASC');
            if (filled(trim($this->searchKategoriQuery))) {
                $term = trim($this->searchKategoriQuery);
                $katQuery->where('nama_kategori', 'like', "%{$term}%");
            }
            $availableKategoris = $katQuery->get();
        }

        $availableMereks = collect();
        if ($this->isFormOpen && $this->target_produk === 'merek') {
            $mrkQuery = Merek::query()->withCount('produks')->orderBy('nama_merek');
            if (filled(trim($this->searchMerekQuery))) {
                $term = trim($this->searchMerekQuery);
                $mrkQuery->where('nama_merek', 'like', "%{$term}%");
            }
            $availableMereks = $mrkQuery->get();
        }

        $searchProdukResults = collect();
        if ($this->isFormOpen && $this->target_produk === 'spesifik') {
            $prodQuery = Produk::query()->with(['kategori', 'merek']);
            if (filled(trim($this->searchProdukQuery))) {
                $term = trim($this->searchProdukQuery);
                $prodQuery->where(function ($q) use ($term) {
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode_katalog', 'like', "%{$term}%");
                })->orderBy('nama')->limit(30);
            } else {
                $prodQuery->latest('id')->limit(20);
            }
            $searchProdukResults = $prodQuery->get();
        }

        // Selected models for badge displays
        $selectedProduks = ! empty($this->selected_produk_ids)
            ? Produk::with(['kategori', 'merek'])->whereIn('id', $this->selected_produk_ids)->get()
            : collect();

        $selectedKategoris = ! empty($this->selected_kategori_ids)
            ? Kategori::whereIn('id', $this->selected_kategori_ids)->get()
            : collect();

        $selectedMereks = ! empty($this->selected_merek_ids)
            ? Merek::whereIn('id', $this->selected_merek_ids)->get()
            : collect();

        return view('livewire.sales.aturan-diskon-index', [
            'aturans' => $aturans,
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'mereks' => Merek::orderBy('nama_merek')->get(),
            'pelanggans' => Pelanggan::orderBy('nama')->get(),
            'availableKategoris' => $availableKategoris,
            'availableMereks' => $availableMereks,
            'searchProdukResults' => $searchProdukResults,
            'searchKategoriResults' => $availableKategoris,
            'searchMerekResults' => $availableMereks,
            'selectedProduks' => $selectedProduks,
            'selectedKategoris' => $selectedKategoris,
            'selectedMereks' => $selectedMereks,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'upcomingCount' => $upcomingCount,
            'expiredOrInactiveCount' => $expiredOrInactiveCount,
            'trashedCount' => $trashedCount,
        ]);
    }
}
