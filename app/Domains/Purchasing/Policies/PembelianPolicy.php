<?php

namespace App\Domains\Purchasing\Policies;

use App\Domains\Purchasing\Models\Pembelian;
use App\Models\User;

class PembelianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_purchases');
    }

    public function view(User $user, Pembelian $pembelian): bool
    {
        return $user->can('view_purchases');
    }

    public function create(User $user): bool
    {
        return $user->can('create_purchases');
    }

    public function update(User $user, Pembelian $pembelian): bool
    {
        return $user->can('update_purchases');
    }

    public function cancel(User $user, Pembelian $pembelian): bool
    {
        return $user->can('cancel_purchases');
    }

    public function delete(User $user, Pembelian $pembelian): bool
    {
        return $user->can('cancel_purchases');
    }
}
