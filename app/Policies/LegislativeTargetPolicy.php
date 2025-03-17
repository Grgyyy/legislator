<?php

namespace App\Policies;

use App\Models\Legislator;
use App\Models\Target;
use App\Models\User;

class LegislativeTargetPolicy
{
    public function viewTargetReport(User $user, target $target): bool
    {
        return $user->hasRole(['Super Admin', 'Director', 'TESDO', 'SMD Head', 'SMD Focal']);
    }
    public function viewAnyTargetReport(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director', 'TESDO', 'SMD Head', 'SMD Focal']);
    }

    public function exportTargetReport(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Director', 'TESDO', 'SMD Head', 'SMD Focal']);
    }
}
