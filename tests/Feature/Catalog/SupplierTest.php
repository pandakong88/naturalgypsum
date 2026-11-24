<?php

use App\Domains\Catalog\Models\Supplier;
use App\Domains\Purchasing\Models\Pembelian;
use App\Domains\Returns\Models\ReturSupplier;
use App\Livewire\Catalog\SupplierIndex;
use App\Livewire\Purchasing\PembelianIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Owner');

    $this->unauthorizedUser = User::factory()->create();
});

test('guest cannot access supplier index', function () {
    $this->get(route('master.supplier.index'))
        ->assertRedirect(route('login'));
});

test('authorized user can view supplier page', function () {
    $this->actingAs($this->owner)
        ->get(route('master.supplier.index'))
        ->assertOk()
        ->assertSee('Master Supplier');
});

test('user without permission cannot access supplier page', function () {
    $this->actingAs($this->unauthorizedUser)
        ->get(route('master.supplier.index'))
        ->assertForbidden();
});

test('authorized user can create a new supplier with full details', function () {
    $this->actingAs($this->owner);

    Livewire::test(SupplierIndex::class)
        ->set('nama_supplier', 'PT Vifon Perkasa Abadi')
        ->set('kontak', '0812-3456-7890 (Ibu Linda)')
        ->set('alamat', 'Kawasan Industri Cikupa Mas Blok C-12, Tangerang')
        ->set('email', 'sales@vifonpvc.co.id')
        ->set('nama_bank', 'BCA')
        ->set('no_rekening', '8830192831')
        ->set('atas_nama_rekening', 'PT Vifon Perkasa Abadi')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil ditambahkan');

    $this->assertDatabaseHas('supplier', [
        'nama_supplier' => 'PT Vifon Perkasa Abadi',
        'kontak' => '0812-3456-7890 (Ibu Linda)',
        'alamat' => 'Kawasan Industri Cikupa Mas Blok C-12, Tangerang',
        'email' => 'sales@vifonpvc.co.id',
        'nama_bank' => 'BCA',
        'no_rekening' => '8830192831',
        'atas_nama_rekening' => 'PT Vifon Perkasa Abadi',
        'created_by' => $this->owner->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'create',
        'subject_type' => Supplier::class,
        'subject_label' => 'PT Vifon Perkasa Abadi',
        'user_id' => $this->owner->id,
    ]);
});

test('supplier validates unique active name', function () {
    $this->actingAs($this->owner);
    Supplier::factory()->create(['nama_supplier' => 'PT Jayaboard Indonesia']);

    Livewire::test(SupplierIndex::class)
        ->set('nama_supplier', 'PT Jayaboard Indonesia')
        ->call('save')
        ->assertHasErrors(['nama_supplier']);
});

test('authorized user can edit supplier and bank details', function () {
    $this->actingAs($this->owner);
    $supplier = Supplier::factory()->create([
        'nama_supplier' => 'CV Sumber Lama',
        'kontak' => '0811111111',
    ]);

    Livewire::test(SupplierIndex::class)
        ->call('openEditModal', $supplier->id)
        ->set('nama_supplier', 'CV Sumber Makmur Baru')
        ->set('nama_bank', 'Bank Mandiri')
        ->set('no_rekening', '1240009988776')
        ->set('atas_nama_rekening', 'CV Sumber Makmur Baru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('berhasil diperbarui');

    $this->assertDatabaseHas('supplier', [
        'id' => $supplier->id,
        'nama_supplier' => 'CV Sumber Makmur Baru',
        'nama_bank' => 'Bank Mandiri',
        'no_rekening' => '1240009988776',
        'atas_nama_rekening' => 'CV Sumber Makmur Baru',
        'updated_by' => $this->owner->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'update',
        'subject_type' => Supplier::class,
        'subject_id' => $supplier->id,
        'user_id' => $this->owner->id,
    ]);
});

test('cannot delete supplier that has purchase transactions', function () {
    $this->actingAs($this->owner);
    $supplier = Supplier::factory()->create(['nama_supplier' => 'PT Suplai Aktif']);

    Pembelian::factory()->create([
        'supplier_id' => $supplier->id,
    ]);

    Livewire::test(SupplierIndex::class)
        ->call('confirmDelete', $supplier->id)
        ->call('delete')
        ->assertSee('masih memiliki 1 data riwayat pembelian');

    $this->assertDatabaseHas('supplier', [
        'id' => $supplier->id,
        'deleted_at' => null,
    ]);
});

test('cannot delete supplier that has returns', function () {
    $this->actingAs($this->owner);
    $supplier = Supplier::factory()->create(['nama_supplier' => 'PT Suplai Retur']);

    ReturSupplier::factory()->create([
        'supplier_id' => $supplier->id,
    ]);

    Livewire::test(SupplierIndex::class)
        ->call('confirmDelete', $supplier->id)
        ->call('delete')
        ->assertSee('masih memiliki 1 data retur pembelian');

    $this->assertDatabaseHas('supplier', [
        'id' => $supplier->id,
        'deleted_at' => null,
    ]);
});

test('supplier without transactions can be soft deleted and restored', function () {
    $this->actingAs($this->owner);
    $supplier = Supplier::factory()->create(['nama_supplier' => 'PT Bebas Transaksi']);

    Livewire::test(SupplierIndex::class)
        ->call('confirmDelete', $supplier->id)
        ->call('delete')
        ->assertHasNoErrors()
        ->assertSee('berhasil dinonaktifkan');

    $this->assertSoftDeleted('supplier', [
        'id' => $supplier->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'delete',
        'subject_type' => Supplier::class,
        'subject_id' => $supplier->id,
    ]);

    Livewire::test(SupplierIndex::class)
        ->call('restore', $supplier->id)
        ->assertHasNoErrors()
        ->assertSee('berhasil dipulihkan');

    $this->assertDatabaseHas('supplier', [
        'id' => $supplier->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'restore',
        'subject_type' => Supplier::class,
        'subject_id' => $supplier->id,
    ]);
});

test('cannot restore supplier if an active supplier with the same name already exists', function () {
    $this->actingAs($this->owner);
    $supplier1 = Supplier::factory()->create(['nama_supplier' => 'PT Duplikat Supplier']);
    $supplier1->delete();

    // Create another active supplier with the same name
    Supplier::factory()->create(['nama_supplier' => 'PT Duplikat Supplier']);

    Livewire::test(SupplierIndex::class)
        ->call('restore', $supplier1->id)
        ->assertSee('sudah ada supplier aktif dengan nama yang sama');

    expect($supplier1->fresh()->trashed())->toBeTrue();
});

test('supplier purchase count is a clickable link that filters pembelian index page', function () {
    $this->actingAs($this->owner);
    $supplierA = Supplier::factory()->create(['nama_supplier' => 'PT Alpha Material']);
    $supplierB = Supplier::factory()->create(['nama_supplier' => 'PT Beta Material']);

    $pembelianA = Pembelian::factory()->create([
        'supplier_id' => $supplierA->id,
        'no_pembelian' => 'PB-ALPHA-001',
    ]);
    $pembelianB = Pembelian::factory()->create([
        'supplier_id' => $supplierB->id,
        'no_pembelian' => 'PB-BETA-002',
    ]);

    // Check link in SupplierIndex
    Livewire::test(SupplierIndex::class)
        ->assertSee('1 Pembelian')
        ->assertSee(route('pembelian.index', ['supplierFilter' => $supplierA->id]));

    // Check filter in PembelianIndex via query params
    Livewire::withQueryParams(['supplierFilter' => (string) $supplierA->id])
        ->test(PembelianIndex::class)
        ->assertSee('PB-ALPHA-001')
        ->assertDontSee('PB-BETA-002');
});
