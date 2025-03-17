<?php

namespace App\Policies;

use App\Models\Tvi;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstitutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function view(User $user, Tvi $tvi): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function update(User $user, Tvi $tvi): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function delete(User $user, Tvi $tvi): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function restore(User $user, Tvi $tvi): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, Tvi $tvi): bool
    {
        return $user->hasRole('Super Admin');
    }
}
