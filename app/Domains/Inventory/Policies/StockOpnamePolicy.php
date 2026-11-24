<?php

namespace App\Domains\Inventory\Policies;

use App\Domains\Inventory\Enums\StatusStockOpname;
use App\Domains\Inventory\Models\StockOpname;
use App\Models\User;

class StockOpnamePolicy
{
    /**
     * Determine whether the user can view any stock opnames.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock_opnames') || $user->can('perform_stock_opname');
    }

    /**
     * Determine whether the user can view the stock opname.
     */
    public function view(User $user, StockOpname $stockOpname): bool
    {
        return $user->can('view_stock_opnames') || $user->can('perform_stock_opname');
    }

    /**
     * Determine whether the user can create stock opnames.
     */
    public function create(User $user): bool
    {
        return $user->can('create_stock_opnames') || $user->can('perform_stock_opname');
    }

    /**
     * Determine whether the user can update the stock opname.
     */
    public function update(User $user, StockOpname $stockOpname): bool
    {
        if ($stockOpname->status !== StatusStockOpname::Draft) {
            return false;
        }

        return $user->can('update_stock_opnames') || $user->can('perform_stock_opname');
    }

    /**
     * Determine whether the user can cancel/delete the stock opname.
     */
    public function delete(User $user, StockOpname $stockOpname): bool
    {
        if ($stockOpname->status === StatusStockOpname::Dibatalkan) {
            return false;
        }

        if ($stockOpname->status === StatusStockOpname::Draft) {
            return $user->can('create_stock_opnames')
                || $user->can('perform_stock_opname')
                || $user->can('cancel_stock_opnames')
                || $user->can('adjust_stock');
        }

        return $user->can('cancel_stock_opnames') || $user->can('adjust_stock');
    }

    /**
     * Determine whether the user can cancel the stock opname.
     */
    public function cancel(User $user, StockOpname $stockOpname): bool
    {
        return $this->delete($user, $stockOpname);
    }
}
