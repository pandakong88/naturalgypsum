<?php

use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Inventory\Models\Stok;
use App\Livewire\Catalog\GudangIndex;
use App\Livewire\Inventory\StokIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->unauthorizedUser = User::factory()->create();
});

test('guest cannot access gudang index', function () {
    $this->get(route('master.gudang.index'))
        ->assertRedirect(route('login'));
});

test('authorized user can view gudang page', function () {
    $this->actingAs($this->owner)
        ->get(route('master.gudang.index'))
        ->assertOk()
        ->assertSee('Master Gudang');
});

test('user without permission cannot access gudang page', function () {
    $this->actingAs($this->unauthorizedUser)
        ->get(route('master.gudang.index'))
        ->assertForbidden();
});

test('authorized user can create a new gudang with kontak', function () {
    $this->actingAs($this->owner);

    Livewire::test(GudangIndex::class)
        ->set('nama_gudang', 'Gudang Pusat Cikupa')
        ->set('lokasi', 'Kawasan Industri Cikupa Tangerang')
        ->set('kontak', 'Bpk. Budi - 081234567890')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil ditambahkan');

    $this->assertDatabaseHas('gudang', [
        'nama_gudang' => 'Gudang Pusat Cikupa',
        'lokasi' => 'Kawasan Industri Cikupa Tangerang',
        'kontak' => 'Bpk. Budi - 081234567890',
        'created_by' => $this->owner->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'create',
        'subject_type' => Gudang::class,
        'subject_label' => 'Gudang Pusat Cikupa',
        'user_id' => $this->owner->id,
    ]);
});

test('gudang validates unique active name', function () {
    $this->actingAs($this->owner);
    Gudang::factory()->create([
        'nama_gudang' => 'Gudang Timur',
    ]);

    Livewire::test(GudangIndex::class)
        ->set('nama_gudang', 'Gudang Timur')
        ->call('save')
        ->assertHasErrors(['nama_gudang']);
});

test('authorized user can edit gudang and kontak', function () {
    $this->actingAs($this->owner);
    $gudang = Gudang::factory()->create([
        'nama_gudang' => 'Gudang Lama',
        'lokasi' => 'Lokasi Lama',
        'kontak' => 'PIC Lama',
    ]);

    Livewire::test(GudangIndex::class)
        ->call('openEditModal', $gudang->id)
        ->set('nama_gudang', 'Gudang Baru Direnovasi')
        ->set('kontak', 'PIC Baru - 0899999999')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil diperbarui');

    $this->assertDatabaseHas('gudang', [
        'id' => $gudang->id,
        'nama_gudang' => 'Gudang Baru Direnovasi',
        'kontak' => 'PIC Baru - 0899999999',
        'updated_by' => $this->owner->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'update',
        'subject_type' => Gudang::class,
        'subject_id' => $gudang->id,
        'user_id' => $this->owner->id,
    ]);
});

test('cannot delete the last remaining active gudang', function () {
    $this->actingAs($this->owner);
    // Ensure only 1 active warehouse exists
    Gudang::query()->forceDelete();
    $onlyGudang = Gudang::factory()->create(['nama_gudang' => 'Satu-satunya Gudang']);

    Livewire::test(GudangIndex::class)
        ->call('confirmDelete', $onlyGudang->id)
        ->call('delete')
        ->assertSee('satu-satunya gudang aktif yang tersisa');

    $this->assertDatabaseHas('gudang', [
        'id' => $onlyGudang->id,
        'deleted_at' => null,
    ]);
});

test('cannot delete gudang that has active physical stock', function () {
    $this->actingAs($this->owner);
    // Create 2 warehouses so it passes the single warehouse check
    $gudang1 = Gudang::factory()->create(['nama_gudang' => 'Gudang Utama']);
    $gudang2 = Gudang::factory()->create(['nama_gudang' => 'Gudang Cadangan']);

    $kategori = Kategori::factory()->create();
    $merek = Merek::factory()->create();
    $produk = Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'merek_id' => $merek->id,
    ]);

    Stok::factory()->create([
        'produk_id' => $produk->id,
        'gudang_id' => $gudang1->id,
        'jumlah' => 45.000,
    ]);

    Livewire::test(GudangIndex::class)
        ->call('confirmDelete', $gudang1->id)
        ->call('delete')
        ->assertSee('masih menyimpan 1 item produk');

    $this->assertDatabaseHas('gudang', [
        'id' => $gudang1->id,
        'deleted_at' => null,
    ]);
});

test('gudang without stock can be soft deleted and restored', function () {
    $this->actingAs($this->owner);
    $gudang1 = Gudang::factory()->create(['nama_gudang' => 'Gudang Kosong']);
    $gudang2 = Gudang::factory()->create(['nama_gudang' => 'Gudang Lainnya']);

    Livewire::test(GudangIndex::class)
        ->call('confirmDelete', $gudang1->id)
        ->call('delete')
        ->assertHasNoErrors()
        ->assertSee('berhasil dinonaktifkan');

    $this->assertSoftDeleted('gudang', [
        'id' => $gudang1->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'delete',
        'subject_type' => Gudang::class,
        'subject_id' => $gudang1->id,
    ]);

    Livewire::test(GudangIndex::class)
        ->call('restore', $gudang1->id)
        ->assertHasNoErrors()
        ->assertSee('berhasil dipulihkan');

    $this->assertDatabaseHas('gudang', [
        'id' => $gudang1->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'restore',
        'subject_type' => Gudang::class,
        'subject_id' => $gudang1->id,
    ]);
});

test('cannot restore gudang if an active gudang with the same name already exists', function () {
    $this->actingAs($this->owner);
    $gudang1 = Gudang::factory()->create(['nama_gudang' => 'Gudang Duplikat']);
    $gudangOther = Gudang::factory()->create(['nama_gudang' => 'Gudang Penyangga']);
    $gudang1->delete();

    // Create another active gudang with the same name
    Gudang::factory()->create(['nama_gudang' => 'Gudang Duplikat']);

    Livewire::test(GudangIndex::class)
        ->call('restore', $gudang1->id)
        ->assertSee('sudah ada gudang aktif dengan nama yang sama');

    expect($gudang1->fresh()->trashed())->toBeTrue();
});

test('gudang stock count and volume is a clickable link to filter inventory stok page', function () {
    $this->actingAs($this->owner);
    $gudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Display']);
    $otherGudang = Gudang::factory()->create(['nama_gudang' => 'Gudang Transit']);

    $kategori = Kategori::factory()->create();
    $merek = Merek::factory()->create();
    $produk1 = Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'merek_id' => $merek->id,
        'nama' => 'Papan Plafon 9mm',
    ]);
    $produk2 = Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'merek_id' => $merek->id,
        'nama' => 'Hollow Galvanis 4x4',
    ]);

    Stok::factory()->create([
        'produk_id' => $produk1->id,
        'gudang_id' => $gudang->id,
        'jumlah' => 100,
    ]);
    Stok::factory()->create([
        'produk_id' => $produk2->id,
        'gudang_id' => $otherGudang->id,
        'jumlah' => 50,
    ]);

    // Check link in GudangIndex
    Livewire::test(GudangIndex::class)
        ->assertSee('1 Produk')
        ->assertSee(route('stok.index', ['filterGudang' => $gudang->id]));

    // Check filter in StokIndex via query params
    Livewire::withQueryParams(['filterGudang' => (string) $gudang->id])
        ->test(StokIndex::class)
        ->assertSee('Papan Plafon 9mm')
        ->assertDontSee('Hollow Galvanis 4x4');
});
