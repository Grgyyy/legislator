<?php

namespace App\Policies;

use App\Models\TviType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstitutionTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function view(User $user, TviType $tviType): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function update(User $user, TviType $tviType): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function delete(User $user, TviType $tviType): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function restore(User $user, TviType $tviType): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, TviType $tviType): bool
    {
        return $user->hasRole('Super Admin');
    }
}
