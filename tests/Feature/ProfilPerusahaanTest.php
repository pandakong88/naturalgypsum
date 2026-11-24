<?php

use App\Domains\Administration\Models\ProfilPerusahaan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Sales\Models\Penjualan;
use App\Livewire\Admin\ProfilPerusahaanIndex;
use App\Models\User;
use Database\Seeders\ProfilPerusahaanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(ProfilPerusahaanSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner System', 'email' => 'owner@example.com']);
    $this->owner->assignRole('Owner');

    $this->staff = User::factory()->create(['name' => 'Staff Biasa', 'email' => 'staff@example.com']);
    $this->staff->assignRole('Staff Gudang');
});

test('Authorized user can view company profile page', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProfilPerusahaanIndex::class)
        ->assertStatus(200)
        ->assertSee('Profil Perusahaan')
        ->assertSee('NATURAL GYPSUM & PVC')
        ->assertSee('BCA');
});

test('Authorized user can update company profile and bank accounts', function () {
    $this->actingAs($this->owner);

    Livewire::test(ProfilPerusahaanIndex::class)
        ->set('nama_toko', 'CV NATURAL GYPSUM SEJAHTERA')
        ->set('telepon', '0812-9999-8888')
        ->set('alamat', 'Jl. Pergudangan Gypsum No. 100')
        ->set('rekening_bank', [
            [
                'nama_bank' => 'BCA',
                'nomor_rekening' => '888-777-666',
                'atas_nama' => 'CV Natural Gypsum',
            ],
            [
                'nama_bank' => 'BRI',
                'nomor_rekening' => '111-222-333',
                'atas_nama' => 'CV Natural Gypsum',
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Profil perusahaan dan pengaturan toko berhasil diperbarui.');

    $settings = ProfilPerusahaan::getSettings();
    expect($settings->nama_toko)->toBe('CV NATURAL GYPSUM SEJAHTERA')
        ->and($settings->telepon)->toBe('0812-9999-8888')
        ->and($settings->alamat)->toBe('Jl. Pergudangan Gypsum No. 100')
        ->and(count($settings->rekening_bank))->toBe(2)
        ->and($settings->rekening_bank[1]['nama_bank'])->toBe('BRI');
});

test('Unauthenticated user cannot access profil perusahaan route', function () {
    $response = $this->get(route('admin.profil-perusahaan.index'));
    $response->assertRedirect(route('login'));
});

test('surat jalan and faktur print routes render successfully with company profile settings', function () {
    $pelanggan = Pelanggan::factory()->create();
    $gudang = Gudang::factory()->create();
    $penjualan = Penjualan::factory()->create([
        'pelanggan_id' => $pelanggan->id,
        'gudang_id' => $gudang->id,
        'user_id' => $this->owner->id,
    ]);

    $this->actingAs($this->owner);

    $resSuratJalan = $this->get(route('penjualan.print.surat-jalan', $penjualan));
    $resSuratJalan->assertStatus(200)
        ->assertSee('SURAT JALAN', false)
        ->assertSee('NATURAL GYPSUM', false);

    $resFaktur = $this->get(route('penjualan.print.faktur', $penjualan));
    $resFaktur->assertStatus(200)
        ->assertSee('FAKTUR PENJUALAN', false)
        ->assertSee('NATURAL GYPSUM', false);

    $resThermal = $this->get(route('penjualan.print.thermal', $penjualan));
    $resThermal->assertStatus(200)
        ->assertSee('Struk Kasir', false)
        ->assertSee('NATURAL GYPSUM', false);
});
