<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Produk;
use App\Models\User;

class ProdukPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_products');
    }

    public function view(User $user, Produk $produk): bool
    {
        return $user->can('view_products');
    }

    public function create(User $user): bool
    {
        return $user->can('create_products');
    }

    public function update(User $user, Produk $produk): bool
    {
        return $user->can('update_products');
    }

    public function delete(User $user, Produk $produk): bool
    {
        return $user->can('delete_products');
    }

    public function restore(User $user, Produk $produk): bool
    {
        return $user->can('restore_products');
    }
}
