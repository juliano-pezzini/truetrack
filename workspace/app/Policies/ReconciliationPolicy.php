<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reconciliation;
use App\Models\User;

class ReconciliationPolicy
{
    /**
     * Determine whether the user can view any reconciliations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the reconciliation.
     */
    public function view(User $user, Reconciliation $reconciliation): bool
    {
        return $reconciliation->user_id === $user->id;
    }

    /**
     * Determine whether the user can create reconciliations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the reconciliation.
     */
    public function update(User $user, Reconciliation $reconciliation): bool
    {
        return $reconciliation->user_id === $user->id && $reconciliation->isPending();
    }

    /**
     * Determine whether the user can delete the reconciliation.
     */
    public function delete(User $user, Reconciliation $reconciliation): bool
    {
        return $reconciliation->user_id === $user->id && $reconciliation->isPending();
    }

    /**
     * Determine whether the user can restore the reconciliation.
     */
    public function restore(User $user, Reconciliation $reconciliation): bool
    {
        return $reconciliation->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the reconciliation.
     */
    public function forceDelete(User $user, Reconciliation $reconciliation): bool
    {
        return $reconciliation->user_id === $user->id;
    }
}
