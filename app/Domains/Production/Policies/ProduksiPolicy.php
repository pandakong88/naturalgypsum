<?php

namespace App\Domains\Production\Policies;

use App\Domains\Production\Models\Produksi;
use App\Models\User;

class ProduksiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_production');
    }

    public function view(User $user, Produksi $produksi): bool
    {
        return $user->can('view_production');
    }

    public function create(User $user): bool
    {
        return $user->can('create_production');
    }

    public function update(User $user, Produksi $produksi): bool
    {
        return $user->can('update_production');
    }

    public function cancel(User $user, Produksi $produksi): bool
    {
        return $user->can('cancel_production');
    }

    public function delete(User $user, Produksi $produksi): bool
    {
        return $user->can('cancel_production');
    }
}
