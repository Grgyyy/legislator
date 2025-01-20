<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Target;

class LegislativeTargetPolicy
{
    public function view(User $user, Target $post): bool
    {
        return $user->hasRole(['Super Admin', 'Director']);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director']);
    }

    /**
     * Determine whether the user can export models.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director']);
    }
}
