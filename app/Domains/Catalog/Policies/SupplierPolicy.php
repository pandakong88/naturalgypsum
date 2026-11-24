<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_suppliers');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('view_suppliers');
    }

    public function create(User $user): bool
    {
        return $user->can('create_suppliers');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('update_suppliers');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('delete_suppliers');
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->can('restore_suppliers');
    }
}
