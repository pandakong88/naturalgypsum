<?php

namespace App\Domains\Inventory\Policies;

use App\Domains\Inventory\Models\MutasiStok;
use App\Models\User;

class MutasiStokPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock_mutations');
    }

    public function view(User $user, MutasiStok $mutasiStok): bool
    {
        return $user->can('view_stock_mutations');
    }
}
