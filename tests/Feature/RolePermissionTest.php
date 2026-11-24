<?php

use App\Livewire\Admin\RoleIndex;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('role and permission seeder creates default roles and permissions', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    expect(Role::where('name', 'Owner')->exists())->toBeTrue()
        ->and(Role::where('name', 'Manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'Staff Penjualan')->exists())->toBeTrue()
        ->and(Role::where('name', 'Staff Gudang')->exists())->toBeTrue()
        ->and(Role::where('name', 'Staff Produksi')->exists())->toBeTrue()
        ->and(Permission::where('name', 'view_dashboard')->exists())->toBeTrue()
        ->and(Permission::where('name', 'view_products')->exists())->toBeTrue()
        ->and(Permission::where('name', 'view_stock')->exists())->toBeTrue();
});

test('role and permission seeder is idempotent and safe to run multiple times', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $initialRoleCount = Role::count();
    $initialPermissionCount = Permission::count();

    // Run a second time
    $this->seed(RoleAndPermissionSeeder::class);

    expect(Role::count())->toBe($initialRoleCount)
        ->and(Permission::count())->toBe($initialPermissionCount);
});

test('user can be assigned roles and permissions', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Staff Penjualan');

    expect($user->hasRole('Staff Penjualan'))->toBeTrue()
        ->and($user->hasPermissionTo('view_sales'))->toBeTrue()
        ->and($user->hasPermissionTo('view_dashboard'))->toBeTrue()
        ->and($user->hasRole('Owner'))->toBeFalse();
});

test('owner dapat memberikan dan mencabut izin cancel_sales pada role via RoleIndex component', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $owner = User::factory()->create();
    $owner->assignRole('Owner');

    $roleStaff = Role::where('name', 'Staff Penjualan')->first();
    expect($roleStaff->hasPermissionTo('cancel_sales'))->toBeFalse();

    $this->actingAs($owner);

    // Toggle ON cancel_sales for Staff Penjualan
    Livewire::test(RoleIndex::class)
        ->call('togglePermission', $roleStaff->id, 'cancel_sales');

    expect($roleStaff->fresh()->hasPermissionTo('cancel_sales'))->toBeTrue();

    // Toggle OFF cancel_sales
    Livewire::test(RoleIndex::class)
        ->call('togglePermission', $roleStaff->id, 'cancel_sales');

    expect($roleStaff->fresh()->hasPermissionTo('cancel_sales'))->toBeFalse();
});
