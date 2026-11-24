<?php

namespace App\Livewire\Catalog;

use App\Domains\Catalog\Actions\CreateKategoriAction;
use App\Domains\Catalog\Actions\DeleteKategoriAction;
use App\Domains\Catalog\Actions\RestoreKategoriAction;
use App\Domains\Catalog\Actions\UpdateKategoriAction;
use App\Domains\Catalog\Models\Kategori;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Master Kategori')]
class KategoriIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $filterParent = '';

    public bool $showTrashed = false;

    // Form modal state
    public bool $isFormOpen = false;

    public ?int $kategoriId = null;

    public int|string|null $parent_id = null;

    public string $nama_kategori = '';

    public bool $hasChildren = false;

    public int $childrenCount = 0;

    // Delete confirmation modal state
    public bool $isDeleteModalOpen = false;

    public ?int $kategoriToDeleteId = null;

    public string $kategoriToDeleteName = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterParent(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(?int $parentId = null): void
    {
        Gate::authorize('create', Kategori::class);

        $this->resetForm();
        $this->parent_id = $parentId;
        $this->hasChildren = false;
        $this->childrenCount = 0;
        $this->isFormOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $kategori = Kategori::withCount('children')->findOrFail($id);
        Gate::authorize('update', $kategori);

        $this->resetForm();
        $this->kategoriId = $kategori->id;
        $this->parent_id = $kategori->parent_id;
        $this->nama_kategori = $kategori->nama_kategori;
        $this->hasChildren = $kategori->children_count > 0;
        $this->childrenCount = (int) $kategori->children_count;
        $this->isFormOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->kategoriId = null;
        $this->parent_id = null;
        $this->nama_kategori = '';
        $this->hasChildren = false;
        $this->childrenCount = 0;
        $this->resetValidation();
    }

    public function save(CreateKategoriAction $createAction, UpdateKategoriAction $updateAction): void
    {
        // Normalize empty string from select option to null
        $this->parent_id = filled($this->parent_id) ? (int) $this->parent_id : null;

        $this->validate([
            'nama_kategori' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:kategori,id'],
        ]);

        try {
            $parentIdVal = $this->parent_id;

            if ($this->kategoriId) {
                $kategori = Kategori::findOrFail($this->kategoriId);
                Gate::authorize('update', $kategori);

                $oldParentId = $kategori->parent_id;

                $updateAction->execute($kategori, [
                    'nama_kategori' => $this->nama_kategori,
                    'parent_id' => $parentIdVal,
                ]);

                if ($oldParentId !== null && $parentIdVal === null) {
                    session()->flash('success', "Kategori '{$this->nama_kategori}' berhasil dikeluarkan menjadi Kategori Utama.");
                } elseif ($oldParentId === null && $parentIdVal !== null) {
                    session()->flash('success', "Kategori '{$this->nama_kategori}' berhasil dimasukkan menjadi Sub-kategori.");
                } elseif ($oldParentId !== null && $parentIdVal !== null && $oldParentId !== $parentIdVal) {
                    session()->flash('success', "Sub-kategori '{$this->nama_kategori}' berhasil dipindahkan ke Kategori Induk baru.");
                } else {
                    session()->flash('success', "Kategori '{$this->nama_kategori}' berhasil diperbarui.");
                }
            } else {
                Gate::authorize('create', Kategori::class);

                $createAction->execute([
                    'nama_kategori' => $this->nama_kategori,
                    'parent_id' => $parentIdVal,
                ]);

                $label = $parentIdVal ? 'Sub-kategori' : 'Kategori';
                session()->flash('success', "{$label} '{$this->nama_kategori}' berhasil ditambahkan.");
            }

            $this->closeFormModal();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        }
    }

    public function confirmDelete(int $id): void
    {
        $kategori = Kategori::findOrFail($id);
        Gate::authorize('delete', $kategori);

        $this->kategoriToDeleteId = $kategori->id;
        $this->kategoriToDeleteName = $kategori->nama_kategori;
        $this->isDeleteModalOpen = true;
    }

    public function delete(DeleteKategoriAction $deleteAction): void
    {
        if (! $this->kategoriToDeleteId) {
            return;
        }

        $kategori = Kategori::findOrFail($this->kategoriToDeleteId);
        Gate::authorize('delete', $kategori);

        try {
            $deleteAction->execute($kategori);
            session()->flash('success', "Kategori '{$kategori->nama_kategori}' berhasil dinonaktifkan.");
            $this->isDeleteModalOpen = false;
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('delete') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
            $this->isDeleteModalOpen = false;
        }
    }

    public function restore(int $id, RestoreKategoriAction $restoreAction): void
    {
        $kategori = Kategori::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $kategori);

        try {
            $restoreAction->execute($kategori);
            session()->flash('success', "Kategori '{$kategori->nama_kategori}' berhasil dipulihkan.");
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('restore') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Kategori::class);

        $query = Kategori::query()
            ->withCount('produks')
            ->with([
                'parent',
                'children' => fn ($q) => $q->withCount('produks'),
                'creator',
                'updater',
            ]);

        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $query->where('nama_kategori', 'like', '%'.trim($this->search).'%');
        }

        if (filled($this->filterParent)) {
            if ($this->filterParent === 'root') {
                $query->whereNull('parent_id');
            } elseif ($this->filterParent === 'child') {
                $query->whereNotNull('parent_id');
            } else {
                $query->where(function ($q) {
                    $q->where('id', $this->filterParent)
                        ->orWhere('parent_id', $this->filterParent);
                });
            }
        }

        $kategoris = $query->orderByRaw('COALESCE(parent_id, id) ASC, parent_id IS NOT NULL ASC, id ASC')
            ->paginate(15);

        $parentOptions = Kategori::root()
            ->when($this->kategoriId, fn ($q) => $q->where('id', '!=', $this->kategoriId))
            ->orderBy('nama_kategori')
            ->get();

        return view('livewire.catalog.kategori-index', [
            'kategoris' => $kategoris,
            'parentOptions' => $parentOptions,
        ]);
    }
}
