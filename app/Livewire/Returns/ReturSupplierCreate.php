<?php

namespace App\Livewire\Returns;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Returns\Actions\CreateReturSupplierAction;
use App\Domains\Returns\Models\ReturSupplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tambah Retur ke Supplier')]
class ReturSupplierCreate extends Component
{
    public ?int $supplier_id = null;

    public ?int $produk_id = null;

    public ?int $gudang_id = null;

    public ?int $pembelian_id = null;

    public string $tanggal = '';

    public float|int|string $jumlah = '';

    public string $alasan = '';

    protected function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:supplier,id'],
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'pembelian_id' => ['nullable', 'integer', 'exists:pembelian,id'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'alasan' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'supplier_id.required' => 'Pilih supplier tujuan retur.',
            'produk_id.required' => 'Pilih produk yang diretur.',
            'gudang_id.required' => 'Pilih gudang asal barang.',
            'tanggal.required' => 'Tanggal retur wajib diisi.',
            'jumlah.required' => 'Jumlah barang wajib diisi.',
            'jumlah.gt' => 'Jumlah barang harus lebih besar dari 0.',
        ];
    }

    public function mount(): void
    {
        Gate::authorize('create', ReturSupplier::class);
        $this->tanggal = Carbon::now()->toDateString();
        $this->gudang_id = Gudang::first()?->id;
    }

    public function updatedSupplierId(): void
    {
        $this->pembelian_id = null;
    }

    public function save(CreateReturSupplierAction $action): mixed
    {
        Gate::authorize('create', ReturSupplier::class);
        $this->validate();

        try {
            $retur = $action->execute([
                'supplier_id' => (int) $this->supplier_id,
                'produk_id' => (int) $this->produk_id,
                'gudang_id' => (int) $this->gudang_id,
                'pembelian_id' => $this->pembelian_id ? (int) $this->pembelian_id : null,
                'tanggal' => $this->tanggal,
                'jumlah' => (float) $this->jumlah,
                'alasan' => $this->alasan,
            ]);

            session()->flash('message', 'Retur '.$retur->no_retur.' berhasil dicatat dan stok gudang berkurang.');

            return redirect()->route('retur-supplier.show', $retur->id);
        } catch (Exception $e) {
            $this->addError('general', $e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        Gate::authorize('create', ReturSupplier::class);

        $pembelians = collect();
        if ($this->supplier_id) {
            $pembelians = Pembelian::where('supplier_id', $this->supplier_id)->latest('tanggal')->limit(20)->get();
        }

        return view('livewire.returns.retur-supplier-create', [
            'suppliers' => Supplier::orderBy('nama_supplier')->get(),
            'produks' => Produk::with(['kategori', 'merek'])->orderBy('nama')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'pembelians' => $pembelians,
        ]);
    }
}
