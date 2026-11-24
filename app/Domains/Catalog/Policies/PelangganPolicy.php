<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Pelanggan;
use App\Models\User;

class PelangganPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_customers');
    }

    public function view(User $user, Pelanggan $pelanggan): bool
    {
        return $user->can('view_customers');
    }

    public function create(User $user): bool
    {
        return $user->can('create_customers');
    }

    public function update(User $user, Pelanggan $pelanggan): bool
    {
        return $user->can('update_customers');
    }

    public function delete(User $user, Pelanggan $pelanggan): bool
    {
        return $user->can('delete_customers');
    }

    public function restore(User $user, Pelanggan $pelanggan): bool
    {
        return $user->can('restore_customers');
    }
}
