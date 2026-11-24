<?php

namespace App\Livewire\Catalog;

use App\Domains\Catalog\Actions\CreateMerekAction;
use App\Domains\Catalog\Actions\DeleteMerekAction;
use App\Domains\Catalog\Actions\RestoreMerekAction;
use App\Domains\Catalog\Actions\UpdateMerekAction;
use App\Domains\Catalog\Models\Merek;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Master Merek')]
class MerekIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public bool $showTrashed = false;

    // Form modal state
    public bool $isFormOpen = false;

    public ?int $merekId = null;

    public string $nama_merek = '';

    // Delete confirmation modal state
    public bool $isDeleteModalOpen = false;

    public ?int $merekToDeleteId = null;

    public string $merekToDeleteName = '';

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
        Gate::authorize('create', Merek::class);

        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $merek = Merek::findOrFail($id);
        Gate::authorize('update', $merek);

        $this->resetForm();
        $this->merekId = $merek->id;
        $this->nama_merek = $merek->nama_merek;
        $this->isFormOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->merekId = null;
        $this->nama_merek = '';
        $this->resetValidation();
    }

    public function save(CreateMerekAction $createAction, UpdateMerekAction $updateAction): void
    {
        $this->validate([
            'nama_merek' => ['required', 'string', 'max:255'],
        ]);

        try {
            if ($this->merekId) {
                $merek = Merek::findOrFail($this->merekId);
                Gate::authorize('update', $merek);

                $updateAction->execute($merek, [
                    'nama_merek' => $this->nama_merek,
                ]);

                session()->flash('success', "Merek '{$this->nama_merek}' berhasil diperbarui.");
            } else {
                Gate::authorize('create', Merek::class);

                $createAction->execute([
                    'nama_merek' => $this->nama_merek,
                ]);

                session()->flash('success', "Merek '{$this->nama_merek}' berhasil ditambahkan.");
            }

            $this->closeFormModal();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        }
    }

    public function confirmDelete(int $id): void
    {
        $merek = Merek::findOrFail($id);
        Gate::authorize('delete', $merek);

        $this->merekToDeleteId = $merek->id;
        $this->merekToDeleteName = $merek->nama_merek;
        $this->isDeleteModalOpen = true;
    }

    public function delete(DeleteMerekAction $deleteAction): void
    {
        if (! $this->merekToDeleteId) {
            return;
        }

        $merek = Merek::findOrFail($this->merekToDeleteId);
        Gate::authorize('delete', $merek);

        try {
            $deleteAction->execute($merek);
            session()->flash('success', "Merek '{$merek->nama_merek}' berhasil dinonaktifkan.");
            $this->isDeleteModalOpen = false;
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('delete') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
            $this->isDeleteModalOpen = false;
        }
    }

    public function restore(int $id, RestoreMerekAction $restoreAction): void
    {
        $merek = Merek::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $merek);

        try {
            $restoreAction->execute($merek);
            session()->flash('success', "Merek '{$merek->nama_merek}' berhasil dipulihkan.");
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('restore') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Merek::class);

        $query = Merek::query()->withCount('produks')->with(['creator', 'updater']);

        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $query->where('nama_merek', 'like', '%'.trim($this->search).'%');
        }

        $mereks = $query->latest('id')->paginate(10);

        return view('livewire.catalog.merek-index', [
            'mereks' => $mereks,
        ]);
    }
}
