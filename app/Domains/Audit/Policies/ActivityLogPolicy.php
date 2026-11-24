<?php

namespace App\Domains\Audit\Policies;

use App\Domains\Audit\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any activity logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_activity_logs');
    }

    /**
     * Determine whether the user can view a specific activity log.
     */
    public function view(User $user, ActivityLog $log): bool
    {
        return $user->can('view_activity_logs');
    }

    /**
     * Immutability enforcement: creation via policy is disabled.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Immutability enforcement: updates are strictly forbidden.
     */
    public function update(User $user, ActivityLog $log): bool
    {
        return false;
    }

    /**
     * Immutability enforcement: deletions are strictly forbidden.
     */
    public function delete(User $user, ActivityLog $log): bool
    {
        return false;
    }
}
