<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Kategori;
use App\Models\User;

class KategoriPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_categories');
    }

    public function view(User $user, Kategori $kategori): bool
    {
        return $user->can('view_categories');
    }

    public function create(User $user): bool
    {
        return $user->can('create_categories');
    }

    public function update(User $user, Kategori $kategori): bool
    {
        return $user->can('update_categories');
    }

    public function delete(User $user, Kategori $kategori): bool
    {
        return $user->can('delete_categories');
    }

    public function restore(User $user, Kategori $kategori): bool
    {
        return $user->can('restore_categories');
    }
}
