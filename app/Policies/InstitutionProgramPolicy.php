<?php

namespace App\Policies;

use App\Models\InstitutionProgram;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstitutionProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function view(User $user, InstitutionProgram $institutionProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function update(User $user, InstitutionProgram $institutionProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function delete(User $user, InstitutionProgram $institutionProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, InstitutionProgram $institutionProgram): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, InstitutionProgram $institutionProgram): bool
    {
        return $user->hasRole('Super Admin');
    }
}
