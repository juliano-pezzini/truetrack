<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\XlsxColumnMapping;

class XlsxColumnMappingPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, XlsxColumnMapping $xlsxColumnMapping): bool
    {
        return $user->id === $xlsxColumnMapping->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, XlsxColumnMapping $xlsxColumnMapping): bool
    {
        return $user->id === $xlsxColumnMapping->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, XlsxColumnMapping $xlsxColumnMapping): bool
    {
        return $user->id === $xlsxColumnMapping->user_id;
    }
}
