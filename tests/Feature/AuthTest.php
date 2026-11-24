<?php

use App\Domains\Audit\Models\ActivityLog;
use App\Livewire\Auth\Login;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

test('users can authenticate using the login component', function () {
    $user = User::factory()->create([
        'email' => 'staff@naturalgypsum.test',
        'password' => Hash::make('password123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'staff@naturalgypsum.test')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);
});

test('users can not authenticate with invalid password', function () {
    User::factory()->create([
        'email' => 'staff@naturalgypsum.test',
        'password' => Hash::make('password123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'staff@naturalgypsum.test')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::check())->toBeFalse();
});

test('unauthenticated users are redirected from protected dashboard', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can access the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200)
        ->assertSee('Dashboard')
        ->assertSee($user->name);
});

test('users can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('login'));
    expect(Auth::check())->toBeFalse();
});

test('login screen displays quick login demo roles', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200)
        ->assertSee('Quick Login (Testing Role)')
        ->assertSee('Owner')
        ->assertSee('Manager')
        ->assertSee('Staff Penjualan')
        ->assertSee('Staff Gudang')
        ->assertSee('Staff Produksi')
        ->assertSee('owner@naturalgypsum.test');
});

test('users can authenticate via quick login for each role', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $roles = ['Owner', 'Manager', 'Staff Penjualan', 'Staff Gudang', 'Staff Produksi'];

    foreach ($roles as $role) {
        Auth::logout();

        Livewire::test(Login::class)
            ->call('quickLogin', $role)
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        expect(Auth::check())->toBeTrue();
        expect(Auth::user()->hasRole($role))->toBeTrue();

        $log = ActivityLog::where('action', 'login')
            ->where('user_id', Auth::id())
            ->latest()
            ->first();

        expect($log)->not->toBeNull();
        expect($log->description)->toContain($role);
    }
});

test('users can fill credentials with fillCredentials method', function () {
    Livewire::test(Login::class)
        ->call('fillCredentials', 'Staff Gudang')
        ->assertSet('email', 'gudang@naturalgypsum.test')
        ->assertSet('password', 'password');
});
