<?php

use App\Domains\Audit\Models\ActivityLog;
use App\Livewire\Admin\RoleIndex;
use App\Livewire\Admin\UserIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['name' => 'Owner System', 'email' => 'owner@example.com']);
    $this->owner->assignRole('Owner');

    $this->manager = User::factory()->create(['name' => 'Manager User', 'email' => 'manager@example.com']);
    $this->manager->assignRole('Manager');

    $this->staff = User::factory()->create(['name' => 'Budi Staff', 'email' => 'budi@example.com']);
    $this->staff->assignRole('Staff Penjualan');

    $this->unauthorizedUser = User::factory()->create(['name' => 'Plain User', 'email' => 'plain@example.com']);
});

test('TEST 1 — Authorized user can view user list, search, and filter', function () {
    $this->actingAs($this->owner);

    Livewire::test(UserIndex::class)
        ->assertStatus(200)
        ->assertSee('Owner System')
        ->assertSee('Budi Staff')
        ->set('search', 'Budi Staff')
        ->assertSee('Budi Staff')
        ->set('search', 'NonExistentUser')
        ->assertDontSee('Budi Staff');
});

test('TEST 2 — Authorized user can create a new user with role assignment and it gets audited', function () {
    $this->actingAs($this->owner);

    Livewire::test(UserIndex::class)
        ->call('openCreateModal')
        ->set('name', 'Operator Gudang Baru')
        ->set('email', 'gudang.baru@example.com')
        ->set('selectedRole', 'Staff Gudang')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    $newUser = User::where('email', 'gudang.baru@example.com')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole('Staff Gudang'))->toBeTrue()
        ->and(Hash::check('password123', $newUser->password))->toBeTrue();

    // Verify Audit Log
    $log = ActivityLog::where('action', 'create')
        ->where('subject_type', User::class)
        ->where('subject_id', $newUser->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->owner->id)
        ->and($log->new_values['name'])->toBe('Operator Gudang Baru')
        ->and($log->new_values['role'])->toBe('Staff Gudang')
        ->and(isset($log->new_values['password']))->toBeFalse(); // Password must NEVER be logged!
});

test('TEST 3 — Authorized user can edit user profile and changes get audited with diff', function () {
    $this->actingAs($this->owner);

    $target = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $target->assignRole('Staff Penjualan');

    Livewire::test(UserIndex::class)
        ->call('openEditModal', $target->id)
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->set('selectedRole', 'Staff Gudang')
        ->call('save')
        ->assertHasNoErrors();

    $target->refresh();
    expect($target->name)->toBe('Updated Name')
        ->and($target->email)->toBe('updated@example.com')
        ->and($target->hasRole('Staff Gudang'))->toBeTrue();

    // Verify Audit Log diff
    $log = ActivityLog::where('action', 'update')
        ->where('subject_type', User::class)
        ->where('subject_id', $target->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->old_values['name'])->toBe('Old Name')
        ->and($log->new_values['name'])->toBe('Updated Name')
        ->and($log->old_values['role'])->toBe('Staff Penjualan')
        ->and($log->new_values['role'])->toBe('Staff Gudang');
});

test('TEST 4 — Self-deletion is strictly forbidden for users', function () {
    $this->actingAs($this->owner);

    // Attempt to delete self
    expect(auth()->user()->can('delete', $this->owner))->toBeFalse();

    Livewire::test(UserIndex::class)
        ->call('deleteUser', $this->owner->id);

    expect($this->owner->fresh()->trashed())->toBeFalse();
});

test('TEST 5 — Authorized user can soft-delete and restore user and actions are audited', function () {
    $this->actingAs($this->owner);

    $target = User::factory()->create(['name' => 'To Delete', 'email' => 'todelete@example.com']);
    $target->assignRole('Staff Penjualan');

    // Delete
    Livewire::test(UserIndex::class)
        ->call('deleteUser', $target->id);

    expect($target->fresh()->trashed())->toBeTrue();

    $deleteLog = ActivityLog::where('action', 'delete')
        ->where('subject_type', User::class)
        ->where('subject_id', $target->id)
        ->first();
    expect($deleteLog)->not->toBeNull();

    // Restore
    Livewire::test(UserIndex::class)
        ->set('filterStatus', 'trashed')
        ->call('restoreUser', $target->id);

    expect($target->fresh()->trashed())->toBeFalse();

    $restoreLog = ActivityLog::where('action', 'restore')
        ->where('subject_type', User::class)
        ->where('subject_id', $target->id)
        ->first();
    expect($restoreLog)->not->toBeNull();
});

test('TEST 6 — Role & Permission matrix view renders properly for authorized users', function () {
    $this->actingAs($this->owner);

    Livewire::test(RoleIndex::class)
        ->assertStatus(200)
        ->assertSee('Owner')
        ->assertSee('Manager')
        ->assertSee('Staff Penjualan')
        ->assertSee('Staff Gudang')
        ->assertSee('Staff Produksi')
        ->assertSee('Master Data')
        ->assertSee('Inventory & Transfer');
});

test('TEST 7 — Unauthorized users cannot access User Management or Role Management', function () {
    $this->actingAs($this->unauthorizedUser);

    Livewire::test(UserIndex::class)
        ->assertForbidden();

    Livewire::test(RoleIndex::class)
        ->assertForbidden();
});
