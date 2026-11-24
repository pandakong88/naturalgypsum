<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Layout('layouts.app')]
#[Title('Role & Hak Akses')]
class RoleIndex extends Component
{
    public ?int $selectedRoleId = null;

    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
    }

    public function togglePermission(int $roleId, string $permissionName): void
    {
        if (! auth()->user()->can('manage_roles')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah izin role.');
        }

        $role = Role::findOrFail($roleId);

        // Protect Owner from losing administrative permissions
        if ($role->name === 'Owner' && in_array($permissionName, ['manage_roles', 'view_roles'], true)) {
            session()->flash('error', "Izin '{$permissionName}' tidak dapat dicabut dari role Owner demi keamanan sistem.");

            return;
        }

        if ($role->hasPermissionTo($permissionName)) {
            $role->revokePermissionTo($permissionName);
            session()->flash('message', "Izin '{$permissionName}' berhasil dicabut dari role '{$role->name}'.");
        } else {
            $role->givePermissionTo($permissionName);
            session()->flash('message', "Izin '{$permissionName}' berhasil diberikan kepada role '{$role->name}'.");
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function render(): View
    {
        if (! auth()->user()->can('view_roles') && ! auth()->user()->can('manage_roles')) {
            abort(403, 'Unauthorized action.');
        }

        $roles = Role::with(['permissions', 'users'])->get();
        $selectedRole = $this->selectedRoleId ? Role::with('permissions')->find($this->selectedRoleId) : $roles->first();

        // Categorize all permissions for cleaner UI visualization
        $permissions = Permission::all()->groupBy(function ($perm) {
            $name = $perm->name;
            if (str_contains($name, 'categories') || str_contains($name, 'brands') || str_contains($name, 'warehouses') || str_contains($name, 'suppliers') || str_contains($name, 'customers') || str_contains($name, 'products') || str_contains($name, 'purchase_prices') || str_contains($name, 'price')) {
                return 'Master Data';
            }
            if (str_contains($name, 'stock') || str_contains($name, 'opname') || str_contains($name, 'transfer')) {
                return 'Inventory & Transfer';
            }
            if (str_contains($name, 'purchases') || str_contains($name, 'sales') || str_contains($name, 'payments') || str_contains($name, 'receivables') || str_contains($name, 'debt')) {
                return 'Transaksi & Piutang';
            }
            if (str_contains($name, 'production')) {
                return 'Produksi';
            }
            if (str_contains($name, 'return')) {
                return 'Retur';
            }
            if (str_contains($name, 'user') || str_contains($name, 'role') || str_contains($name, 'activity_log')) {
                return 'Administrasi & Audit';
            }

            return 'Lainnya';
        });

        return view('livewire.admin.role-index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'groupedPermissions' => $permissions,
        ]);
    }
}
