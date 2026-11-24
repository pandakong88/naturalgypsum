<?php

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Pelanggan;
use App\Livewire\Catalog\PelangganIndex;
use App\Livewire\Sales\PenjualanCreate;
use App\Models\User;
use Database\Seeders\ProfilPerusahaanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(ProfilPerusahaanSeeder::class);

    $this->staffPenjualan = User::factory()->create();
    $this->staffPenjualan->assignRole('Staff Penjualan');

    $this->gudang = Gudang::factory()->create(['nama_gudang' => 'Toko Utama']);

    $this->ahmad = Pelanggan::factory()->create([
        'nama' => 'Bpk. Ahmad (Rumah Pribadi)',
        'kontak' => '0812-3344-5566',
        'tipe' => TipePelanggan::Umum,
        'limit_piutang' => 0,
    ]);

    $this->hendra = Pelanggan::factory()->create([
        'nama' => 'Bpk. Hendra (Kontraktor Mitra)',
        'kontak' => '0813-1122-3344',
        'tipe' => TipePelanggan::Pemborong,
        'limit_piutang' => 10000000,
    ]);
});

test('Pelanggan::search dapat menemukan pelanggan via nomor telepon tanpa tanda strip (-)', function () {
    // 1. Cari nomor lengkap tanpa tanda strip sama sekali
    $res1 = Pelanggan::search('081233445566')->get();
    expect($res1->pluck('id'))->toContain($this->ahmad->id)
        ->and($res1->pluck('id'))->not->toContain($this->hendra->id);

    // 2. Cari potongan prefix tanpa tanda strip
    $res2 = Pelanggan::search('08123344')->get();
    expect($res2->pluck('id'))->toContain($this->ahmad->id);

    // 3. Cari potongan suffix tanpa tanda strip
    $res3 = Pelanggan::search('33445566')->get();
    expect($res3->pluck('id'))->toContain($this->ahmad->id);

    // 4. Cari dengan format internasional +62
    $res4 = Pelanggan::search('+6281233445566')->get();
    expect($res4->pluck('id'))->toContain($this->ahmad->id);

    // 5. Cari dengan format internasional 628...
    $res5 = Pelanggan::search('6281233445566')->get();
    expect($res5->pluck('id'))->toContain($this->ahmad->id);

    // 6. Cari dengan format spasi
    $res6 = Pelanggan::search('0812 3344 5566')->get();
    expect($res6->pluck('id'))->toContain($this->ahmad->id);

    // 7. Cari tetap bekerja jika user mengetik dengan tanda strip
    $res7 = Pelanggan::search('0812-3344')->get();
    expect($res7->pluck('id'))->toContain($this->ahmad->id);

    // 8. Cari berdasarkan nama
    $res8 = Pelanggan::search('Ahmad')->get();
    expect($res8->pluck('id'))->toContain($this->ahmad->id);
});

test('Pencarian modal Daftar Lengkap Pelanggan di Kasir Penjualan menemukan pelanggan tanpa tanda strip', function () {
    $this->actingAs($this->staffPenjualan);

    // Buka modal pelanggan di kasir penjualan
    Livewire::test(PenjualanCreate::class)
        ->set('isPelangganModalOpen', true)
        // Ketik nomor tanpa strip: 08123344
        ->set('pelangganModalSearch', '08123344')
        ->assertSee('Bpk. Ahmad (Rumah Pribadi)')
        ->assertSee('0812-3344-5566')
        ->assertDontSee('Bpk. Hendra (Kontraktor Mitra)')
        // Ketik nomor lengkap tanpa strip: 081233445566
        ->set('pelangganModalSearch', '081233445566')
        ->assertSee('Bpk. Ahmad (Rumah Pribadi)')
        // Ketik nomor akhiran tanpa strip: 33445566
        ->set('pelangganModalSearch', '33445566')
        ->assertSee('Bpk. Ahmad (Rumah Pribadi)');
});

test('Pencarian dropdown cepat pelanggan di kasir menemukan pelanggan tanpa tanda strip', function () {
    $this->actingAs($this->staffPenjualan);

    Livewire::test(PenjualanCreate::class)
        ->set('pelangganSearchQuery', '081233445566')
        ->assertSee('Bpk. Ahmad (Rumah Pribadi)')
        ->assertSee('0812-3344-5566');
});

test('Pencarian di Master Pelanggan menemukan pelanggan tanpa tanda strip', function () {
    $this->actingAs($this->staffPenjualan);

    Livewire::test(PelangganIndex::class)
        ->set('search', '081233445566')
        ->assertSee('Bpk. Ahmad (Rumah Pribadi)')
        ->assertSee('0812-3344-5566')
        ->assertDontSee('Bpk. Hendra (Kontraktor Mitra)');
});
