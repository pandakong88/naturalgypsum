<?php

namespace App\Domains\Inventory\Policies;

use App\Domains\Inventory\Models\Stok;
use App\Models\User;

class StokPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock');
    }

    public function view(User $user, Stok $stok): bool
    {
        return $user->can('view_stock');
    }
}
