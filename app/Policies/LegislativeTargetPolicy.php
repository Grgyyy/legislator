<?php

namespace App\Policies;

use App\Models\Legislator;
use App\Models\Target;
use App\Models\User;

class LegislativeTargetPolicy
{
    /**
     * Determine whether the user can view a specific target.
     */
    public function viewTargetReport(User $user, target $target): bool
    {
        return $user->hasRole(['Super Admin', 'Director', 'TESDO', 'SMD Head', 'SMD Focal']);
    }

    /**
     * Determine whether the user can view any legislative targets.
     */
    public function viewAnyTargetReport(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director', 'TESDO', 'SMD Head', 'SMD Focal']);
    }

    /**
     * Determine whether the user can export legislative targets.
     */
    public function exportTargetReport(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director', 'TESDO', 'SMD Head', 'SMD Focal']);
    }
}
