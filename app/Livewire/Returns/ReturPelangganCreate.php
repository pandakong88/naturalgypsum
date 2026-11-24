<?php

namespace App\Livewire\Returns;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Returns\Actions\CreateReturPelangganAction;
use App\Domains\Returns\Models\ReturPelanggan;
use App\Domains\Sales\Models\Penjualan;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tambah Retur dari Pelanggan')]
class ReturPelangganCreate extends Component
{
    public ?int $pelanggan_id = null;

    public ?int $produk_id = null;

    public ?int $gudang_id = null;

    public ?int $penjualan_id = null;

    public string $tanggal = '';

    public float|int|string $jumlah = '';

    public float|int|string $harga_retur = '';

    public string $alasan = '';

    protected function rules(): array
    {
        return [
            'pelanggan_id' => ['required', 'integer', 'exists:pelanggan,id'],
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'penjualan_id' => ['nullable', 'integer', 'exists:penjualan,id'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'harga_retur' => ['nullable', 'numeric', 'min:0'],
            'alasan' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'pelanggan_id.required' => 'Pilih pelanggan yang melakukan retur.',
            'produk_id.required' => 'Pilih produk yang diretur.',
            'gudang_id.required' => 'Pilih gudang penerima retur.',
            'tanggal.required' => 'Tanggal retur wajib diisi.',
            'jumlah.required' => 'Jumlah barang wajib diisi.',
            'jumlah.gt' => 'Jumlah barang harus lebih besar dari 0.',
            'harga_retur.min' => 'Harga retur tidak boleh bernilai negatif.',
        ];
    }

    public function mount(): void
    {
        Gate::authorize('create', ReturPelanggan::class);
        $this->tanggal = Carbon::now()->toDateString();
        $this->gudang_id = Gudang::first()?->id;
    }

    public function updatedPelangganId(): void
    {
        $this->penjualan_id = null;
    }

    public function updatedProdukId(): void
    {
        if ($this->produk_id) {
            $produk = Produk::find($this->produk_id);
            if ($produk && (empty($this->harga_retur) || (float) $this->harga_retur === 0.0)) {
                $this->harga_retur = (float) $produk->harga_jual;
            }
        }
    }

    public function save(CreateReturPelangganAction $action): mixed
    {
        Gate::authorize('create', ReturPelanggan::class);
        $this->validate();

        try {
            $retur = $action->execute([
                'pelanggan_id' => (int) $this->pelanggan_id,
                'produk_id' => (int) $this->produk_id,
                'gudang_id' => (int) $this->gudang_id,
                'penjualan_id' => $this->penjualan_id ? (int) $this->penjualan_id : null,
                'tanggal' => $this->tanggal,
                'jumlah' => (float) $this->jumlah,
                'harga_retur' => ! empty($this->harga_retur) ? (float) $this->harga_retur : 0.0,
                'alasan' => $this->alasan,
            ]);

            session()->flash('message', 'Retur '.$retur->no_retur.' berhasil dicatat dan stok barang masuk ke gudang.');

            return redirect()->route('retur-pelanggan.show', $retur->id);
        } catch (Exception $e) {
            $this->addError('general', $e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        Gate::authorize('create', ReturPelanggan::class);

        $penjualans = collect();
        if ($this->pelanggan_id) {
            $penjualans = Penjualan::where('pelanggan_id', $this->pelanggan_id)->latest('tanggal')->limit(20)->get();
        }

        return view('livewire.returns.retur-pelanggan-create', [
            'pelanggans' => Pelanggan::orderBy('nama_pelanggan')->get(),
            'produks' => Produk::with(['kategori', 'merek'])->orderBy('nama')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'penjualans' => $penjualans,
        ]);
    }
}
