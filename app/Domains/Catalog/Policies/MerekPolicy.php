<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Merek;
use App\Models\User;

class MerekPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_brands');
    }

    public function view(User $user, Merek $merek): bool
    {
        return $user->can('view_brands');
    }

    public function create(User $user): bool
    {
        return $user->can('create_brands');
    }

    public function update(User $user, Merek $merek): bool
    {
        return $user->can('update_brands');
    }

    public function delete(User $user, Merek $merek): bool
    {
        return $user->can('delete_brands');
    }

    public function restore(User $user, Merek $merek): bool
    {
        return $user->can('restore_brands');
    }
}
