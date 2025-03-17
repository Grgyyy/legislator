<?php

namespace App\Policies;

use App\Models\ProvinceAbdd;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProvinceAbddPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function restore(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole('Super Admin');
    }
}
