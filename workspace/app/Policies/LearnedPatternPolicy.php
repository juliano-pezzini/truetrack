<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearnedCategoryPattern;
use App\Models\User;

class LearnedPatternPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LearnedCategoryPattern $pattern): bool
    {
        return $user->id === $pattern->user_id;
    }

    /**
     * Determine whether the user can view all patterns.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LearnedCategoryPattern $pattern): bool
    {
        return $user->id === $pattern->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LearnedCategoryPattern $pattern): bool
    {
        return $user->id === $pattern->user_id;
    }

    /**
     * Determine whether the user can convert pattern to rule.
     */
    public function convert(User $user, LearnedCategoryPattern $pattern): bool
    {
        return $user->id === $pattern->user_id;
    }

    /**
     * Determine whether the user can disable/enable patterns.
     */
    public function toggle(User $user, LearnedCategoryPattern $pattern): bool
    {
        return $user->id === $pattern->user_id;
    }

    /**
     * Determine whether the user can clear all learning data.
     */
    public function clearAll(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view learning statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return true;
    }
}
