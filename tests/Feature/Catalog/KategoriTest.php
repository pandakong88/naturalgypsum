<?php

use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Produk;
use App\Livewire\Catalog\KategoriIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('Staff Gudang');

    $this->unauthorizedUser = User::factory()->create();
});

test('guest cannot access kategori index', function () {
    $this->get(route('master.kategori.index'))
        ->assertRedirect(route('login'));
});

test('authorized user can view kategori page', function () {
    $this->actingAs($this->owner)
        ->get(route('master.kategori.index'))
        ->assertOk()
        ->assertSee('Master Kategori');
});

test('user without view_categories permission cannot view kategori page', function () {
    $this->actingAs($this->unauthorizedUser)
        ->get(route('master.kategori.index'))
        ->assertForbidden();
});

test('authorized user can create a new kategori', function () {
    $this->actingAs($this->owner);

    Livewire::test(KategoriIndex::class)
        ->set('nama_kategori', 'PVC Plafon Board')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('kategori', [
        'nama_kategori' => 'PVC Plafon Board',
        'created_by' => $this->owner->id,
    ]);
});

test('kategori creation requires valid name and unique active name', function () {
    $this->actingAs($this->owner);

    Kategori::factory()->create(['nama_kategori' => 'Gypsum Board']);

    Livewire::test(KategoriIndex::class)
        ->set('nama_kategori', '')
        ->call('save')
        ->assertHasErrors(['nama_kategori' => 'required']);

    Livewire::test(KategoriIndex::class)
        ->set('nama_kategori', 'Gypsum Board')
        ->call('save')
        ->assertHasErrors(['nama_kategori']);
});

test('authorized user can edit existing kategori', function () {
    $this->actingAs($this->owner);
    $kategori = Kategori::factory()->create(['nama_kategori' => 'Nama Lama']);

    Livewire::test(KategoriIndex::class)
        ->call('openEditModal', $kategori->id)
        ->set('nama_kategori', 'Nama Baru')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('kategori', [
        'id' => $kategori->id,
        'nama_kategori' => 'Nama Baru',
        'updated_by' => $this->owner->id,
    ]);
});

test('kategori cannot be deleted if referenced by active products', function () {
    $this->actingAs($this->owner);
    $kategori = Kategori::factory()->create();
    $merek = Merek::factory()->create();

    Produk::factory()->create([
        'kategori_id' => $kategori->id,
        'merek_id' => $merek->id,
    ]);

    Livewire::test(KategoriIndex::class)
        ->call('confirmDelete', $kategori->id)
        ->call('delete');

    $this->assertDatabaseHas('kategori', [
        'id' => $kategori->id,
        'deleted_at' => null,
    ]);
});

test('unused kategori can be soft deleted and restored', function () {
    $this->actingAs($this->owner);
    $kategori = Kategori::factory()->create();

    Livewire::test(KategoriIndex::class)
        ->call('confirmDelete', $kategori->id)
        ->call('delete');

    $this->assertSoftDeleted('kategori', [
        'id' => $kategori->id,
    ]);

    Livewire::test(KategoriIndex::class)
        ->call('restore', $kategori->id);

    $this->assertDatabaseHas('kategori', [
        'id' => $kategori->id,
        'deleted_at' => null,
    ]);
});

test('authorized user can create a sub-kategori under a parent kategori', function () {
    $this->actingAs($this->owner);
    $parent = Kategori::factory()->create(['nama_kategori' => 'PVC Plafon']);

    Livewire::test(KategoriIndex::class)
        ->call('openCreateModal', $parent->id)
        ->set('nama_kategori', 'Seri Dof')
        ->set('parent_id', $parent->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('kategori', [
        'nama_kategori' => 'Seri Dof',
        'parent_id' => $parent->id,
        'created_by' => $this->owner->id,
    ]);

    $child = Kategori::where('nama_kategori', 'Seri Dof')->first();
    expect($child->nama_lengkap)->toBe('PVC Plafon > Seri Dof')
        ->and($child->parent->id)->toBe($parent->id)
        ->and($parent->children->pluck('id'))->toContain($child->id);
});

test('kategori cannot set parent_id to itself', function () {
    $this->actingAs($this->owner);
    $kategori = Kategori::factory()->create(['nama_kategori' => 'Kategori Mandiri']);

    Livewire::test(KategoriIndex::class)
        ->call('openEditModal', $kategori->id)
        ->set('parent_id', $kategori->id)
        ->call('save')
        ->assertHasErrors(['parent_id']);
});

test('parent kategori cannot be deleted if it has active sub-kategoris', function () {
    $this->actingAs($this->owner);
    $parent = Kategori::factory()->create(['nama_kategori' => 'Induk Gypsum']);
    $child = Kategori::factory()->create([
        'nama_kategori' => 'Lis Moulding',
        'parent_id' => $parent->id,
    ]);

    Livewire::test(KategoriIndex::class)
        ->call('confirmDelete', $parent->id)
        ->call('delete');

    $this->assertDatabaseHas('kategori', [
        'id' => $parent->id,
        'deleted_at' => null,
    ]);
});

test('parent kategori correctly aggregates products count from all child categories', function () {
    $this->actingAs($this->owner);
    $merek = Merek::factory()->create();

    $parent = Kategori::factory()->create(['nama_kategori' => 'PVC Plafon & Dinding']);
    $child1 = Kategori::factory()->create(['nama_kategori' => 'Seri Glossy', 'parent_id' => $parent->id]);
    $child2 = Kategori::factory()->create(['nama_kategori' => 'Seri Dof', 'parent_id' => $parent->id]);

    Produk::factory()->count(3)->create(['kategori_id' => $child1->id, 'merek_id' => $merek->id]);
    Produk::factory()->count(2)->create(['kategori_id' => $child2->id, 'merek_id' => $merek->id]);

    $parentWithRelations = Kategori::with(['children' => fn ($q) => $q->withCount('produks')])
        ->withCount('produks')
        ->find($parent->id);

    expect($parentWithRelations->produks_count)->toBe(0)
        ->and($parentWithRelations->total_produk_count)->toBe(5)
        ->and($child1->total_produk_count)->toBe(3)
        ->and($child2->total_produk_count)->toBe(2);

    Livewire::test(KategoriIndex::class)
        ->assertSee('5 Produk')
        ->assertSee('3 Produk')
        ->assertSee('2 Produk')
        ->assertSee(route('master.produk.index', ['filterKategori' => $parent->id]));
});

test('kategori index displays flash notifications on create, update, delete, and restore', function () {
    $this->actingAs($this->owner);

    // Create flash notification
    Livewire::test(KategoriIndex::class)
        ->set('nama_kategori', 'Rangka Galvanis Baru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil ditambahkan');

    $kategori = Kategori::where('nama_kategori', 'Rangka Galvanis Baru')->first();

    // Update flash notification
    Livewire::test(KategoriIndex::class)
        ->call('openEditModal', $kategori->id)
        ->set('nama_kategori', 'Rangka Galvanis Update')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil diperbarui');

    // Delete flash notification
    Livewire::test(KategoriIndex::class)
        ->call('confirmDelete', $kategori->id)
        ->call('delete')
        ->assertHasNoErrors()
        ->assertSee('berhasil dinonaktifkan');

    // Restore flash notification
    Livewire::test(KategoriIndex::class)
        ->call('restore', $kategori->id)
        ->assertHasNoErrors()
        ->assertSee('berhasil dipulihkan');
});

test('authorized user can convert sub-kategori to root kategori (keluarkan ke induk utama)', function () {
    $this->actingAs($this->owner);

    $parent = Kategori::factory()->create(['nama_kategori' => 'PVC Utama']);
    $sub = Kategori::factory()->create([
        'nama_kategori' => 'Sub Yang Mau Dikeluarkan',
        'parent_id' => $parent->id,
    ]);

    expect($sub->parent_id)->toBe($parent->id);

    Livewire::test(KategoriIndex::class)
        ->call('openEditModal', $sub->id)
        ->set('parent_id', '') // Select "-- Jadikan Kategori Utama (Tanpa Induk) --"
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil dikeluarkan menjadi Kategori Utama');

    $sub->refresh();
    expect($sub->parent_id)->toBeNull()
        ->and($sub->nama_kategori)->toBe('Sub Yang Mau Dikeluarkan');
});

test('authorized user can convert standalone root kategori to sub-kategori (masukkan ke induk)', function () {
    $this->actingAs($this->owner);

    $parent = Kategori::factory()->create(['nama_kategori' => 'Gypsum Induk']);
    $standalone = Kategori::factory()->create(['nama_kategori' => 'Kompon Mandiri', 'parent_id' => null]);

    expect($standalone->parent_id)->toBeNull();

    Livewire::test(KategoriIndex::class)
        ->call('openEditModal', $standalone->id)
        ->set('parent_id', (string) $parent->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil dimasukkan menjadi Sub-kategori');

    $standalone->refresh();
    expect($standalone->parent_id)->toBe($parent->id)
        ->and($standalone->parent->id)->toBe($parent->id);
});

test('authorized user can move sub-kategori from one parent to another parent', function () {
    $this->actingAs($this->owner);

    $parent1 = Kategori::factory()->create(['nama_kategori' => 'Induk 1']);
    $parent2 = Kategori::factory()->create(['nama_kategori' => 'Induk 2']);
    $sub = Kategori::factory()->create([
        'nama_kategori' => 'Sub Pindahan',
        'parent_id' => $parent1->id,
    ]);

    Livewire::test(KategoriIndex::class)
        ->call('openEditModal', $sub->id)
        ->set('parent_id', (string) $parent2->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil dipindahkan ke Kategori Induk baru');

    $sub->refresh();
    expect($sub->parent_id)->toBe($parent2->id);
});

test('cannot set sub-kategori as parent to maintain max 1 level depth', function () {
    $this->actingAs($this->owner);

    $root = Kategori::factory()->create(['nama_kategori' => 'Grand Parent']);
    $sub = Kategori::factory()->create(['nama_kategori' => 'First Child', 'parent_id' => $root->id]);

    Livewire::test(KategoriIndex::class)
        ->set('nama_kategori', 'Grand Child Coba')
        ->set('parent_id', (string) $sub->id)
        ->call('save')
        ->assertHasErrors(['parent_id']);
});

test('cannot restore sub-kategori if its parent category is still nonaktif', function () {
    $this->actingAs($this->owner);

    $parent = Kategori::factory()->create(['nama_kategori' => 'Induk Nonaktif']);
    $child = Kategori::factory()->create(['nama_kategori' => 'Anak Nonaktif', 'parent_id' => $parent->id]);

    // Soft delete child first, then parent
    $child->delete();
    $parent->delete();

    // Try to restore child while parent is still soft-deleted
    Livewire::test(KategoriIndex::class)
        ->call('restore', $child->id)
        ->assertSee('masih berstatus nonaktif');

    expect($child->fresh()->trashed())->toBeTrue();

    // Restore parent first
    Livewire::test(KategoriIndex::class)
        ->call('restore', $parent->id)
        ->assertHasNoErrors();

    expect($parent->fresh()->trashed())->toBeFalse();

    // Now restoring child succeeds
    Livewire::test(KategoriIndex::class)
        ->call('restore', $child->id)
        ->assertHasNoErrors()
        ->assertSee('berhasil dipulihkan');

    expect($child->fresh()->trashed())->toBeFalse();
});
