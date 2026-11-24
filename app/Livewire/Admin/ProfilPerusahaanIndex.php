<?php

namespace App\Livewire\Admin;

use App\Domains\Administration\Models\ProfilPerusahaan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Profil Perusahaan & Pengaturan Toko')]
class ProfilPerusahaanIndex extends Component
{
    use WithFileUploads;

    public string $nama_toko = '';

    public string $slogan = '';

    public string $alamat = '';

    public string $telepon = '';

    public string $email = '';

    public string $website = '';

    public string $catatan_kaki_faktur = '';

    public ?string $logo_path = null;

    /** @var mixed */
    public $logo;

    /**
     * @var array<int, array{
     *     nama_bank: string,
     *     nomor_rekening: string,
     *     atas_nama: string
     * }>
     */
    public array $rekening_bank = [];

    public function mount(): void
    {
        $settings = ProfilPerusahaan::getSettings();

        $this->nama_toko = (string) $settings->nama_toko;
        $this->slogan = (string) ($settings->slogan ?? '');
        $this->alamat = (string) ($settings->alamat ?? '');
        $this->telepon = (string) ($settings->telepon ?? '');
        $this->email = (string) ($settings->email ?? '');
        $this->website = (string) ($settings->website ?? '');
        $this->catatan_kaki_faktur = (string) ($settings->catatan_kaki_faktur ?? '');
        $this->logo_path = $settings->logo_path;

        $banks = $settings->rekening_bank;
        if (is_array($banks) && ! empty($banks)) {
            $this->rekening_bank = array_values($banks);
        } else {
            $this->rekening_bank = [
                ['nama_bank' => '', 'nomor_rekening' => '', 'atas_nama' => ''],
            ];
        }
    }

    public function addRekening(): void
    {
        $this->rekening_bank[] = [
            'nama_bank' => '',
            'nomor_rekening' => '',
            'atas_nama' => '',
        ];
    }

    public function removeRekening(int $index): void
    {
        unset($this->rekening_bank[$index]);
        $this->rekening_bank = array_values($this->rekening_bank);

        if (empty($this->rekening_bank)) {
            $this->rekening_bank = [
                ['nama_bank' => '', 'nomor_rekening' => '', 'atas_nama' => ''],
            ];
        }
    }

    public function deleteLogo(): void
    {
        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
            $this->logo_path = null;

            $record = ProfilPerusahaan::first();
            if ($record) {
                $record->update(['logo_path' => null]);
                ProfilPerusahaan::clearSettingsCache();
            }

            session()->flash('success', 'Logo toko berhasil dihapus.');
        }
    }

    public function save(): void
    {
        $this->validate([
            'nama_toko' => ['required', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:100'],
            'catatan_kaki_faktur' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'rekening_bank.*.nama_bank' => ['nullable', 'string', 'max:50'],
            'rekening_bank.*.nomor_rekening' => ['nullable', 'string', 'max:50'],
            'rekening_bank.*.atas_nama' => ['nullable', 'string', 'max:100'],
        ], [
            'nama_toko.required' => 'Nama toko wajib diisi.',
            'logo.image' => 'File logo harus berupa gambar (JPG, PNG, GIF, WebP).',
            'logo.max' => 'Ukuran file logo maksimal 2MB.',
        ]);

        if ($this->logo) {
            if ($this->logo_path) {
                Storage::disk('public')->delete($this->logo_path);
            }
            $this->logo_path = $this->logo->store('logos', 'public');
            $this->logo = null;
        }

        // Clean empty bank rows
        $cleanedBanks = array_values(array_filter($this->rekening_bank, function ($item) {
            return filled($item['nama_bank'] ?? null) || filled($item['nomor_rekening'] ?? null);
        }));

        $record = ProfilPerusahaan::first() ?? new ProfilPerusahaan;
        $record->fill([
            'nama_toko' => $this->nama_toko,
            'slogan' => $this->slogan ?: null,
            'alamat' => $this->alamat ?: null,
            'telepon' => $this->telepon ?: null,
            'email' => $this->email ?: null,
            'website' => $this->website ?: null,
            'catatan_kaki_faktur' => $this->catatan_kaki_faktur ?: null,
            'logo_path' => $this->logo_path,
            'rekening_bank' => $cleanedBanks,
        ]);
        $record->save();

        ProfilPerusahaan::clearSettingsCache();

        session()->flash('success', 'Profil perusahaan dan pengaturan toko berhasil diperbarui.');
    }

    public function render(): View
    {
        return view('livewire.admin.profil-perusahaan-index');
    }
}
