<?php

namespace App\Livewire\Catalog;

use App\Domains\Catalog\Actions\CreateSupplierAction;
use App\Domains\Catalog\Actions\DeleteSupplierAction;
use App\Domains\Catalog\Actions\RestoreSupplierAction;
use App\Domains\Catalog\Actions\UpdateSupplierAction;
use App\Domains\Catalog\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Master Supplier')]
class SupplierIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public bool $showTrashed = false;

    // Form modal state
    public bool $isFormOpen = false;

    public ?int $supplierId = null;

    public string $nama_supplier = '';

    public ?string $kontak = '';

    public ?string $alamat = '';

    public ?string $email = '';

    public ?string $nama_bank = '';

    public ?string $no_rekening = '';

    public ?string $atas_nama_rekening = '';

    // Delete confirmation modal state
    public bool $isDeleteModalOpen = false;

    public ?int $supplierToDeleteId = null;

    public string $supplierToDeleteName = '';

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
        Gate::authorize('create', Supplier::class);

        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        Gate::authorize('update', $supplier);

        $this->resetForm();
        $this->supplierId = $supplier->id;
        $this->nama_supplier = $supplier->nama_supplier;
        $this->kontak = $supplier->kontak ?? '';
        $this->alamat = $supplier->alamat ?? '';
        $this->email = $supplier->email ?? '';
        $this->nama_bank = $supplier->nama_bank ?? '';
        $this->no_rekening = $supplier->no_rekening ?? '';
        $this->atas_nama_rekening = $supplier->atas_nama_rekening ?? '';
        $this->isFormOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->supplierId = null;
        $this->nama_supplier = '';
        $this->kontak = '';
        $this->alamat = '';
        $this->email = '';
        $this->nama_bank = '';
        $this->no_rekening = '';
        $this->atas_nama_rekening = '';
        $this->resetValidation();
    }

    public function save(CreateSupplierAction $createAction, UpdateSupplierAction $updateAction): void
    {
        $this->validate([
            'nama_supplier' => ['required', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'nama_bank' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:100'],
            'atas_nama_rekening' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $data = [
                'nama_supplier' => $this->nama_supplier,
                'kontak' => $this->kontak,
                'alamat' => $this->alamat,
                'email' => $this->email,
                'nama_bank' => $this->nama_bank,
                'no_rekening' => $this->no_rekening,
                'atas_nama_rekening' => $this->atas_nama_rekening,
            ];

            if ($this->supplierId) {
                $supplier = Supplier::findOrFail($this->supplierId);
                Gate::authorize('update', $supplier);

                $updateAction->execute($supplier, $data);

                session()->flash('success', "Supplier '{$this->nama_supplier}' berhasil diperbarui.");
            } else {
                Gate::authorize('create', Supplier::class);

                $createAction->execute($data);

                session()->flash('success', "Supplier '{$this->nama_supplier}' berhasil ditambahkan.");
            }

            $this->closeFormModal();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        }
    }

    public function confirmDelete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        Gate::authorize('delete', $supplier);

        $this->supplierToDeleteId = $supplier->id;
        $this->supplierToDeleteName = $supplier->nama_supplier;
        $this->isDeleteModalOpen = true;
    }

    public function delete(DeleteSupplierAction $deleteAction): void
    {
        if (! $this->supplierToDeleteId) {
            return;
        }

        $supplier = Supplier::findOrFail($this->supplierToDeleteId);
        Gate::authorize('delete', $supplier);

        try {
            $deleteAction->execute($supplier);
            session()->flash('success', "Supplier '{$supplier->nama_supplier}' berhasil dinonaktifkan.");
            $this->isDeleteModalOpen = false;
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('delete') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
            $this->isDeleteModalOpen = false;
        }
    }

    public function restore(int $id, RestoreSupplierAction $restoreAction): void
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $supplier);

        try {
            $restoreAction->execute($supplier);
            session()->flash('success', "Supplier '{$supplier->nama_supplier}' berhasil dipulihkan.");
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('restore') ?? $e->getMessage();
            session()->flash('error', $errorMsg);
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Supplier::class);

        $query = Supplier::query()
            ->with(['creator', 'updater'])
            ->withCount('pembelians');

        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->search($search)
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nama_bank', 'like', "%{$search}%")
                    ->orWhere('no_rekening', 'like', "%{$search}%")
                    ->orWhere('atas_nama_rekening', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest('id')->paginate(10);

        return view('livewire.catalog.supplier-index', [
            'suppliers' => $suppliers,
        ]);
    }
}
