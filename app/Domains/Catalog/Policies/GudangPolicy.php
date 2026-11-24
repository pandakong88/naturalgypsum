<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Gudang;
use App\Models\User;

class GudangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_warehouses');
    }

    public function view(User $user, Gudang $gudang): bool
    {
        return $user->can('view_warehouses');
    }

    public function create(User $user): bool
    {
        return $user->can('create_warehouses');
    }

    public function update(User $user, Gudang $gudang): bool
    {
        return $user->can('update_warehouses');
    }

    public function delete(User $user, Gudang $gudang): bool
    {
        return $user->can('delete_warehouses');
    }

    public function restore(User $user, Gudang $gudang): bool
    {
        return $user->can('restore_warehouses');
    }
}
