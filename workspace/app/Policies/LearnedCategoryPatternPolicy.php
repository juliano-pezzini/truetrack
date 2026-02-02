<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearnedCategoryPattern;
use App\Models\User;

class LearnedCategoryPatternPolicy
{
    /**
     * Determine whether the user can view any learned patterns.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the learned pattern.
     */
    public function view(?User $user, LearnedCategoryPattern $learnedCategoryPattern): bool
    {
        return $learnedCategoryPattern->user_id === $user->id;
    }

    /**
     * Determine whether the user can create learned patterns.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the learned pattern.
     */
    public function update(User $user, LearnedCategoryPattern $learnedCategoryPattern): bool
    {
        return $learnedCategoryPattern->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the learned pattern.
     */
    public function delete(User $user, LearnedCategoryPattern $learnedCategoryPattern): bool
    {
        return $learnedCategoryPattern->user_id === $user->id;
    }

    /**
     * Determine whether the user can toggle the learned pattern.
     */
    public function toggle(User $user, LearnedCategoryPattern $learnedCategoryPattern): bool
    {
        return $learnedCategoryPattern->user_id === $user->id;
    }

    /**
     * Determine whether the user can convert the learned pattern to rule.
     */
    public function convert(User $user, LearnedCategoryPattern $learnedCategoryPattern): bool
    {
        return $learnedCategoryPattern->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the learned pattern.
     */
    public function restore(User $user, LearnedCategoryPattern $learnedCategoryPattern): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the learned pattern.
     */
    public function forceDelete(User $user, LearnedCategoryPattern $learnedCategoryPattern): bool
    {
        return true;
    }
}
