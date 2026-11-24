<?php

namespace App\Livewire\Catalog;

use App\Domains\Catalog\Actions\CreatePelangganAction;
use App\Domains\Catalog\Actions\DeletePelangganAction;
use App\Domains\Catalog\Actions\RestorePelangganAction;
use App\Domains\Catalog\Actions\UpdatePelangganAction;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Pelanggan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Master Pelanggan')]
class PelangganIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $filterTipe = '';

    public bool $showTrashed = false;

    // Form modal state
    public bool $isFormOpen = false;

    public ?int $pelangganId = null;

    public string $nama = '';

    public ?string $kontak = '';

    public string $tipe = 'umum';

    public float|int|string $limit_piutang = 0;

    // Delete confirmation modal state
    public bool $isDeleteModalOpen = false;

    public ?int $pelangganToDeleteId = null;

    public string $pelangganToDeleteName = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTipe(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Pelanggan::class);

        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $pelanggan = Pelanggan::findOrFail($id);
        Gate::authorize('update', $pelanggan);

        $this->resetForm();
        $this->pelangganId = $pelanggan->id;
        $this->nama = $pelanggan->nama;
        $this->kontak = $pelanggan->kontak ?? '';
        $this->tipe = $pelanggan->tipe->value;
        $this->limit_piutang = (float) $pelanggan->limit_piutang;
        $this->isFormOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->pelangganId = null;
        $this->nama = '';
        $this->kontak = '';
        $this->tipe = 'umum';
        $this->limit_piutang = 0;
        $this->resetValidation();
    }

    public function save(CreatePelangganAction $createAction, UpdatePelangganAction $updateAction): void
    {
        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
            'tipe' => ['required', 'string', 'in:umum,pemborong,langganan'],
            'limit_piutang' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            if ($this->pelangganId) {
                $pelanggan = Pelanggan::findOrFail($this->pelangganId);
                Gate::authorize('update', $pelanggan);

                $updateAction->execute($pelanggan, [
                    'nama' => $this->nama,
                    'kontak' => $this->kontak,
                    'tipe' => $this->tipe,
                    'limit_piutang' => $this->limit_piutang,
                ]);

                session()->flash('success', 'Data pelanggan berhasil diperbarui.');
            } else {
                Gate::authorize('create', Pelanggan::class);

                $createAction->execute([
                    'nama' => $this->nama,
                    'kontak' => $this->kontak,
                    'tipe' => $this->tipe,
                    'limit_piutang' => $this->limit_piutang,
                ]);

                session()->flash('success', 'Pelanggan baru berhasil ditambahkan.');
            }

            $this->closeFormModal();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        }
    }

    public function confirmDelete(int $id): void
    {
        $pelanggan = Pelanggan::findOrFail($id);
        Gate::authorize('delete', $pelanggan);

        $this->pelangganToDeleteId = $pelanggan->id;
        $this->pelangganToDeleteName = $pelanggan->nama;
        $this->isDeleteModalOpen = true;
    }

    public function delete(DeletePelangganAction $deleteAction): void
    {
        if (! $this->pelangganToDeleteId) {
            return;
        }

        $pelanggan = Pelanggan::findOrFail($this->pelangganToDeleteId);
        Gate::authorize('delete', $pelanggan);

        $deleteAction->execute($pelanggan);
        session()->flash('success', "Pelanggan '{$pelanggan->nama}' berhasil dinonaktifkan.");
        $this->isDeleteModalOpen = false;
    }

    public function restore(int $id, RestorePelangganAction $restoreAction): void
    {
        $pelanggan = Pelanggan::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $pelanggan);

        $restoreAction->execute($pelanggan);
        session()->flash('success', "Pelanggan '{$pelanggan->nama}' berhasil dipulihkan.");
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Pelanggan::class);

        $query = Pelanggan::query()->with(['creator', 'updater']);

        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $query->search($this->search);
        }

        if (filled($this->filterTipe)) {
            $query->where('tipe', $this->filterTipe);
        }

        $pelanggans = $query->latest('id')->paginate(10);

        return view('livewire.catalog.pelanggan-index', [
            'pelanggans' => $pelanggans,
            'tipeOptions' => TipePelanggan::cases(),
        ]);
    }
}
