<?php

namespace App\Domains\Sales\Policies;

use App\Domains\Sales\Models\Penjualan;
use App\Models\User;

class PenjualanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_sales');
    }

    public function view(User $user, Penjualan $penjualan): bool
    {
        return $user->can('view_sales');
    }

    public function create(User $user): bool
    {
        return $user->can('create_sales');
    }

    public function update(User $user, Penjualan $penjualan): bool
    {
        return $user->can('update_sales');
    }

    public function cancel(User $user, Penjualan $penjualan): bool
    {
        return $user->can('cancel_sales');
    }

    public function delete(User $user, Penjualan $penjualan): bool
    {
        return $user->can('cancel_sales');
    }
}
