<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\XlsxImport;

class XlsxImportPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, XlsxImport $xlsxImport): bool
    {
        return $user->id === $xlsxImport->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, XlsxImport $xlsxImport): bool
    {
        return $user->id === $xlsxImport->user_id;
    }
}
