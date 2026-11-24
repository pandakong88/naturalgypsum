<?php

namespace App\Domains\Returns\Policies;

use App\Domains\Returns\Models\ReturSupplier;
use App\Models\User;

class ReturSupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_supplier_returns');
    }

    public function view(User $user, ReturSupplier $retur): bool
    {
        return $user->can('view_supplier_returns');
    }

    public function create(User $user): bool
    {
        return $user->can('create_supplier_returns');
    }

    public function cancel(User $user, ReturSupplier $retur): bool
    {
        return $user->can('cancel_supplier_returns');
    }

    public function delete(User $user, ReturSupplier $retur): bool
    {
        return $user->can('cancel_supplier_returns');
    }
}
