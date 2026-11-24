<?php

namespace App\Domains\Auth\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the user list.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_users');
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $target): bool
    {
        return $user->can('view_users') || $user->id === $target->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can('create_users');
    }

    /**
     * Determine whether the user can update a user.
     */
    public function update(User $user, User $target): bool
    {
        return $user->can('update_users');
    }

    /**
     * Determine whether the user can delete a user (self-deletion is forbidden).
     */
    public function delete(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('delete_users');
    }

    /**
     * Determine whether the user can restore a user.
     */
    public function restore(User $user, User $target): bool
    {
        return $user->can('restore_users');
    }

    /**
     * Determine whether the user can manage roles and permissions.
     */
    public function manageRoles(User $user): bool
    {
        return $user->can('manage_roles');
    }
}
