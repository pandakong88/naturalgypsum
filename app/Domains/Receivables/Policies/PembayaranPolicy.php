<?php

namespace App\Domains\Receivables\Policies;

use App\Domains\Receivables\Models\Pembayaran;
use App\Models\User;

class PembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_payments');
    }

    public function view(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('view_payments');
    }

    public function create(User $user): bool
    {
        return $user->can('record_payments');
    }

    public function update(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('update_payments');
    }

    public function cancel(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('cancel_payments');
    }

    public function delete(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('cancel_payments');
    }

    public function allocate(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('allocate_payments');
    }
}
