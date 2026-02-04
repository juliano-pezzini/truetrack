<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AutoCategoryRule;
use App\Models\User;

class AutoCategoryRulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, AutoCategoryRule $rule): bool
    {
        return $user && $user->id === $rule->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AutoCategoryRule $rule): bool
    {
        return $user->id === $rule->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AutoCategoryRule $rule): bool
    {
        return $user->id === $rule->user_id;
    }

    /**
     * Determine whether the user can archive/restore the model.
     */
    public function archive(User $user, AutoCategoryRule $rule): bool
    {
        return $user->id === $rule->user_id;
    }

    /**
     * Determine whether the user can reorder rules.
     */
    public function reorder(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can export rules.
     */
    public function export(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can import rules.
     */
    public function import(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can test rules.
     */
    public function testRules(User $user): bool
    {
        return true;
    }
}
