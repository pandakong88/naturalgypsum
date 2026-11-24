<?php

use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Livewire\Catalog\MerekIndex;
use App\Livewire\Catalog\ProdukIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->unauthorizedUser = User::factory()->create();
});

test('guest cannot access merek index', function () {
    $this->get(route('master.merek.index'))
        ->assertRedirect(route('login'));
});

test('authorized user can view merek page', function () {
    $this->actingAs($this->owner)
        ->get(route('master.merek.index'))
        ->assertOk()
        ->assertSee('Master Merek');
});

test('user without permission cannot access merek page', function () {
    $this->actingAs($this->unauthorizedUser)
        ->get(route('master.merek.index'))
        ->assertForbidden();
});

test('authorized user can create a new merek', function () {
    $this->actingAs($this->owner);

    Livewire::test(MerekIndex::class)
        ->set('nama_merek', 'Jayaboard Premium')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('merek', [
        'nama_merek' => 'Jayaboard Premium',
        'created_by' => $this->owner->id,
    ]);
});

test('merek creation validates duplicate active name', function () {
    $this->actingAs($this->owner);
    Merek::factory()->create(['nama_merek' => 'Elephant Board']);

    Livewire::test(MerekIndex::class)
        ->set('nama_merek', 'Elephant Board')
        ->call('save')
        ->assertHasErrors(['nama_merek']);
});

test('authorized user can edit merek', function () {
    $this->actingAs($this->owner);
    $merek = Merek::factory()->create(['nama_merek' => 'Merek Asli']);

    Livewire::test(MerekIndex::class)
        ->call('openEditModal', $merek->id)
        ->set('nama_merek', 'Merek Sunting')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('merek', [
        'id' => $merek->id,
        'nama_merek' => 'Merek Sunting',
        'updated_by' => $this->owner->id,
    ]);
});

test('merek cannot be deleted if referenced by active products', function () {
    $this->actingAs($this->owner);
    $kategori = Kategori::factory()->create();
    $merek = Merek::factory()->create();

    Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'merek_id' => $merek->id,
    ]);

    Livewire::test(MerekIndex::class)
        ->call('confirmDelete', $merek->id)
        ->call('delete');

    $this->assertDatabaseHas('merek', [
        'id' => $merek->id,
        'deleted_at' => null,
    ]);
});

test('unused merek can be soft deleted and restored', function () {
    $this->actingAs($this->owner);
    $merek = Merek::factory()->create();

    Livewire::test(MerekIndex::class)
        ->call('confirmDelete', $merek->id)
        ->call('delete');

    $this->assertSoftDeleted('merek', [
        'id' => $merek->id,
    ]);

    Livewire::test(MerekIndex::class)
        ->call('restore', $merek->id);

    $this->assertDatabaseHas('merek', [
        'id' => $merek->id,
        'deleted_at' => null,
    ]);
});

test('merek index displays flash notifications on create, update, delete, and restore', function () {
    $this->actingAs($this->owner);

    // Create flash notification
    Livewire::test(MerekIndex::class)
        ->set('nama_merek', 'Merek Baru Test')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil ditambahkan');

    $merek = Merek::where('nama_merek', 'Merek Baru Test')->first();

    // Update flash notification
    Livewire::test(MerekIndex::class)
        ->call('openEditModal', $merek->id)
        ->set('nama_merek', 'Merek Update Test')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil diperbarui');

    // Delete flash notification
    Livewire::test(MerekIndex::class)
        ->call('confirmDelete', $merek->id)
        ->call('delete')
        ->assertHasNoErrors()
        ->assertSee('berhasil dinonaktifkan');

    // Restore flash notification
    Livewire::test(MerekIndex::class)
        ->call('restore', $merek->id)
        ->assertHasNoErrors()
        ->assertSee('berhasil dipulihkan');
});

test('merek product count is a clickable link that filters master produk page', function () {
    $this->actingAs($this->owner);
    $kategori = Kategori::factory()->create();
    $merek = Merek::factory()->create(['nama_merek' => 'Jayaboard Test']);
    $otherMerek = Merek::factory()->create(['nama_merek' => 'Other Merek Test']);

    Produk::factory()->count(2)->create([
        'kategori_id' => $kategori->id,
        'merek_id' => $merek->id,
        'nama' => 'Papan Gypsum Jayaboard',
    ]);

    Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'merek_id' => $otherMerek->id,
        'nama' => 'Papan Gypsum Lainnya',
    ]);

    // Check link in MerekIndex
    Livewire::test(MerekIndex::class)
        ->assertSee('2 Produk')
        ->assertSee(route('master.produk.index', ['filterMerek' => $merek->id]));

    // Check filter in ProdukIndex via query params
    Livewire::withQueryParams(['filterMerek' => (string) $merek->id])
        ->test(ProdukIndex::class)
        ->assertSee('Papan Gypsum Jayaboard')
        ->assertDontSee('Papan Gypsum Lainnya');
});

test('cannot restore merek if an active merek with the same name already exists', function () {
    $this->actingAs($this->owner);
    $merek = Merek::factory()->create(['nama_merek' => 'Merek Duplikat']);
    $merek->delete();

    // Create another active merek with the same name
    Merek::factory()->create(['nama_merek' => 'Merek Duplikat']);

    // Attempting to restore the soft-deleted one should fail
    Livewire::test(MerekIndex::class)
        ->call('restore', $merek->id)
        ->assertSee('sudah ada merek aktif dengan nama yang sama');

    expect($merek->fresh()->trashed())->toBeTrue();
});
