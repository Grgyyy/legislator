<?php

namespace App\Policies;

use App\Models\InstitutionClass;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstitutionClassBPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function view(User $user, InstitutionClass $institutionClass): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function update(User $user, InstitutionClass $institutionClass): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function delete(User $user, InstitutionClass $institutionClass): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function restore(User $user, InstitutionClass $institutionClass): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, InstitutionClass $institutionClass): bool
    {
        return $user->hasRole('Super Admin');
    }
}
