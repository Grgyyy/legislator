<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Target;
use App\Models\Legislator;

class LegislativeTargetPolicy
{
    /**
     * Determine whether the user can view a specific target.
     */
    public function viewTargetReport(User $user, target $target): bool
    {
        return $user->hasRole(['Super Admin', 'Director']);
    }

    /**
     * Determine whether the user can view any legislative targets.
     */
    public function viewAnyTargetReport(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director']);
    }

    /**
     * Determine whether the user can export legislative targets.
     */
    public function exportTargetReport(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director']);
    }
}
