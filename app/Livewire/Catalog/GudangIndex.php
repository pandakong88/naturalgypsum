<?php

namespace App\Livewire\Catalog;

use App\Domains\Catalog\Actions\CreateGudangAction;
use App\Domains\Catalog\Actions\DeleteGudangAction;
use App\Domains\Catalog\Actions\RestoreGudangAction;
use App\Domains\Catalog\Actions\UpdateGudangAction;
use App\Domains\Catalog\Models\Gudang;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Master Gudang')]
class GudangIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public bool $showTrashed = false;

    // Form modal state
    public bool $isFormOpen = false;

    public ?int $gudangId = null;

    public string $nama_gudang = '';

    public ?string $lokasi = '';

    public ?string $kontak = '';

    // Delete confirmation modal state
    public bool $isDeleteModalOpen = false;

    public ?int $gudangToDeleteId = null;

    public string $gudangToDeleteName = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Gudang::class);

        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $gudang = Gudang::findOrFail($id);
        Gate::authorize('update', $gudang);

        $this->resetForm();
        $this->gudangId = $gudang->id;
        $this->nama_gudang = $gudang->nama_gudang;
        $this->lokasi = $gudang->lokasi ?? '';
        $this->kontak = $gudang->kontak ?? '';
        $this->isFormOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->gudangId = null;
        $this->nama_gudang = '';
        $this->lokasi = '';
        $this->kontak = '';
        $this->resetValidation();
    }

    public function save(CreateGudangAction $createAction, UpdateGudangAction $updateAction): void
    {
        $this->validate([
            'nama_gudang' => ['required', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            if ($this->gudangId) {
                $gudang = Gudang::findOrFail($this->gudangId);
                Gate::authorize('update', $gudang);

                $updateAction->execute($gudang, [
                    'nama_gudang' => $this->nama_gudang,
                    'lokasi' => $this->lokasi,
                    'kontak' => $this->kontak,
                ]);

                session()->flash('success', "Gudang '{$this->nama_gudang}' berhasil diperbarui.");
            } else {
                Gate::authorize('create', Gudang::class);

                $createAction->execute([
                    'nama_gudang' => $this->nama_gudang,
                    'lokasi' => $this->lokasi,
                    'kontak' => $this->kontak,
                ]);

                session()->flash('success', "Gudang '{$this->nama_gudang}' berhasil ditambahkan.");
            }

            $this->closeFormModal();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        }
    }

    public function confirmDelete(int $id): void
    {
        $gudang = Gudang::findOrFail($id);
        Gate::authorize('delete', $gudang);

        $this->gudangToDeleteId = $gudang->id;
        $this->gudangToDeleteName = $gudang->nama_gudang;
        $this->isDeleteModalOpen = true;
    }

    public function delete(DeleteGudangAction $deleteAction): void
    {
        if (! $this->gudangToDeleteId) {
            return;
        }

        $gudang = Gudang::findOrFail($this->gudangToDeleteId);
        Gate::authorize('delete', $gudang);

        try {
            $deleteAction->execute($gudang);
            session()->flash('success', "Gudang '{$gudang->nama_gudang}' berhasil dinonaktifkan.");
            $this->isDeleteModalOpen = false;
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('delete') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
            $this->isDeleteModalOpen = false;
        }
    }

    public function restore(int $id, RestoreGudangAction $restoreAction): void
    {
        $gudang = Gudang::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $gudang);

        try {
            $restoreAction->execute($gudang);
            session()->flash('success', "Gudang '{$gudang->nama_gudang}' berhasil dipulihkan.");
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('restore') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Gudang::class);

        $query = Gudang::query()
            ->withCount(['stoks as total_item_count' => fn ($q) => $q->where('jumlah', '>', 0)])
            ->withSum(['stoks as total_kuantitas_stok' => fn ($q) => $q->where('jumlah', '>', 0)], 'jumlah')
            ->with(['creator', 'updater']);

        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_gudang', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhere('kontak', 'like', "%{$search}%");
            });
        }

        $gudangs = $query->latest('id')->paginate(10);

        return view('livewire.catalog.gudang-index', [
            'gudangs' => $gudangs,
        ]);
    }
}
