<?php

namespace App\Domains\Inventory\Policies;

use App\Domains\Inventory\Models\TransferGudang;
use App\Models\User;

class TransferGudangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_transfers');
    }

    public function view(User $user, TransferGudang $transfer): bool
    {
        return $user->can('view_transfers');
    }

    public function create(User $user): bool
    {
        return $user->can('create_transfers') || $user->can('transfer_stock');
    }

    public function update(User $user, TransferGudang $transfer): bool
    {
        return $user->can('update_transfers');
    }

    public function cancel(User $user, TransferGudang $transfer): bool
    {
        return $user->can('cancel_transfers');
    }

    public function delete(User $user, TransferGudang $transfer): bool
    {
        return $user->can('cancel_transfers');
    }
}
