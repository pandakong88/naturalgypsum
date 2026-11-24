<?php

use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Pelanggan;
use App\Livewire\Catalog\PelangganIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->unauthorizedUser = User::factory()->create();
});

test('guest cannot access pelanggan index', function () {
    $this->get(route('master.pelanggan.index'))
        ->assertRedirect(route('login'));
});

test('authorized user can view pelanggan page', function () {
    $this->actingAs($this->owner)
        ->get(route('master.pelanggan.index'))
        ->assertOk()
        ->assertSee('Master Pelanggan');
});

test('user without permission cannot access pelanggan page', function () {
    $this->actingAs($this->unauthorizedUser)
        ->get(route('master.pelanggan.index'))
        ->assertForbidden();
});

test('authorized user can create a new pelanggan with tipe and limit piutang', function () {
    $this->actingAs($this->owner);

    Livewire::test(PelangganIndex::class)
        ->set('nama', 'Toko Bangunan Sejahtera')
        ->set('kontak', '085712345678')
        ->set('tipe', 'pemborong')
        ->set('limit_piutang', '50000000')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pelanggan', [
        'nama' => 'Toko Bangunan Sejahtera',
        'kontak' => '085712345678',
        'tipe' => TipePelanggan::Pemborong->value,
        'limit_piutang' => 50000000.00,
        'created_by' => $this->owner->id,
    ]);
});

test('pelanggan validates tipe selection and limit piutang format', function () {
    $this->actingAs($this->owner);

    Livewire::test(PelangganIndex::class)
        ->set('nama', 'Pelanggan Uji')
        ->set('tipe', 'invalid_tipe')
        ->set('limit_piutang', '-1000')
        ->call('save')
        ->assertHasErrors(['tipe', 'limit_piutang']);
});

test('authorized user can edit pelanggan', function () {
    $this->actingAs($this->owner);
    $pelanggan = Pelanggan::factory()->create([
        'nama' => 'Pelanggan Reguler',
        'tipe' => TipePelanggan::Umum,
        'limit_piutang' => 0,
    ]);

    Livewire::test(PelangganIndex::class)
        ->call('openEditModal', $pelanggan->id)
        ->set('tipe', 'langganan')
        ->set('limit_piutang', '25000000')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pelanggan', [
        'id' => $pelanggan->id,
        'tipe' => TipePelanggan::Langganan->value,
        'limit_piutang' => 25000000.00,
        'updated_by' => $this->owner->id,
    ]);
});

test('pelanggan can be soft deleted and restored', function () {
    $this->actingAs($this->owner);
    $pelanggan = Pelanggan::factory()->create();

    Livewire::test(PelangganIndex::class)
        ->call('confirmDelete', $pelanggan->id)
        ->call('delete');

    $this->assertSoftDeleted('pelanggan', [
        'id' => $pelanggan->id,
    ]);

    Livewire::test(PelangganIndex::class)
        ->call('restore', $pelanggan->id);

    $this->assertDatabaseHas('pelanggan', [
        'id' => $pelanggan->id,
        'deleted_at' => null,
    ]);
});

test('pelanggan cannot be registered with duplicate nama or duplicate kontak', function () {
    $this->actingAs($this->owner);
    Pelanggan::factory()->create([
        'nama' => 'CV Sumber Makmur',
        'kontak' => '081234567899',
    ]);

    // Test duplicate nama (case-insensitive)
    Livewire::test(PelangganIndex::class)
        ->set('nama', 'cv sumber makmur')
        ->set('kontak', '089999999999')
        ->set('tipe', 'umum')
        ->set('limit_piutang', 0)
        ->call('save')
        ->assertHasErrors(['nama']);

    // Test duplicate kontak
    Livewire::test(PelangganIndex::class)
        ->set('nama', 'Toko Baru Beda')
        ->set('kontak', '081234567899')
        ->set('tipe', 'umum')
        ->set('limit_piutang', 0)
        ->call('save')
        ->assertHasErrors(['kontak']);
});
