<?php

namespace App\Domains\Returns\Policies;

use App\Domains\Returns\Models\ReturPelanggan;
use App\Models\User;

class ReturPelangganPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_customer_returns');
    }

    public function view(User $user, ReturPelanggan $retur): bool
    {
        return $user->can('view_customer_returns');
    }

    public function create(User $user): bool
    {
        return $user->can('create_customer_returns');
    }

    public function cancel(User $user, ReturPelanggan $retur): bool
    {
        return $user->can('cancel_customer_returns');
    }

    public function delete(User $user, ReturPelanggan $retur): bool
    {
        return $user->can('cancel_customer_returns');
    }
}
