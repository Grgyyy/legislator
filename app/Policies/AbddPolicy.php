<?php

namespace App\Policies;

use App\Models\Abdd;
use App\Models\User;

class AbddPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);

    }

    public function view(User $user, Abdd $abdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function update(User $user, Abdd $abdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function delete(User $user, Abdd $abdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function restore(User $user, Abdd $abdd): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, Abdd $abdd): bool
    {
        return $user->hasRole('Super Admin');
    }
}
