<?php

namespace App\Livewire\Production;

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Production\Actions\CreateProduksiAction;
use App\Domains\Production\Models\Produksi;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tambah Catatan Produksi')]
class ProduksiCreate extends Component
{
    public ?int $gudang_id = null;

    public string $tanggal = '';

    public string $catatan = '';

    /**
     * @var array<int, array{
     *     produk_id: int|string|null,
     *     jumlah: float|int|string|null
     * }>
     */
    public array $items = [];

    // Roll converter modal helper
    public bool $isRollModalOpen = false;

    public ?int $activeItemIndex = null;

    public float|int|string $rollCount = 1;

    public float|int|string $rollLength = 0;

    protected function rules(): array
    {
        return [
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'tanggal' => ['required', 'date'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'integer', 'exists:produk,id'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'gudang_id.required' => 'Pilih gudang tujuan hasil produksi.',
            'tanggal.required' => 'Tanggal produksi wajib diisi.',
            'items.required' => 'Minimal harus ada satu baris hasil produksi.',
            'items.min' => 'Minimal harus ada satu baris hasil produksi.',
            'items.*.produk_id.required' => 'Pilih produk.',
            'items.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'items.*.jumlah.gt' => 'Jumlah harus lebih besar dari 0.',
        ];
    }

    public function mount(): void
    {
        Gate::authorize('create', Produksi::class);
        $this->tanggal = Carbon::now()->toDateString();
        $this->gudang_id = Gudang::first()?->id;
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'produk_id' => null,
            'jumlah' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function openRollConverter(int $index): void
    {
        $this->activeItemIndex = $index;
        $produkId = (int) ($this->items[$index]['produk_id'] ?? 0);
        $produk = Produk::find($produkId);

        $this->rollLength = ($produk && $produk->panjang_per_roll) ? (float) $produk->panjang_per_roll : 25;
        $this->rollCount = 1;
        $this->isRollModalOpen = true;
    }

    public function applyRollConversion(): void
    {
        if ($this->activeItemIndex !== null && isset($this->items[$this->activeItemIndex])) {
            $calculatedMeters = (float) $this->rollCount * (float) $this->rollLength;
            $this->items[$this->activeItemIndex]['jumlah'] = $calculatedMeters;
        }

        $this->closeRollConverter();
    }

    public function closeRollConverter(): void
    {
        $this->isRollModalOpen = false;
        $this->activeItemIndex = null;
    }

    public function save(CreateProduksiAction $action): mixed
    {
        Gate::authorize('create', Produksi::class);
        $this->validate();

        try {
            $produksi = $action->execute([
                'gudang_id' => (int) $this->gudang_id,
                'tanggal' => $this->tanggal,
                'catatan' => $this->catatan,
                'items' => $this->items,
            ]);

            session()->flash('message', 'Catatan Produksi '.$produksi->no_produksi.' berhasil dicatat dan stok barang jadi bertambah.');

            return redirect()->route('produksi.show', $produksi->id);
        } catch (Exception $e) {
            $this->addError('general', $e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        Gate::authorize('create', Produksi::class);

        return view('livewire.production.produksi-create', [
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'produks' => Produk::with(['kategori', 'merek'])->orderBy('nama')->get(),
        ]);
    }
}
