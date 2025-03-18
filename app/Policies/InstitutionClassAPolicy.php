<?php

namespace App\Policies;

use App\Models\TviClass;
use App\Models\User;

class InstitutionClassAPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function view(User $user, TviClass $tviClass): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function update(User $user, TviClass $tviClass): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function delete(User $user, TviClass $tviClass): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function restore(User $user, TviClass $tviClass): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, TviClass $tviClass): bool
    {
        return $user->hasRole('Super Admin');
    }
}
