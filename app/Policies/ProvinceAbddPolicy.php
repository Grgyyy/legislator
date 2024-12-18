<?php

namespace App\Policies;

use App\Models\ProvinceAbdd;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProvinceAbddPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProvinceAbdd $provinceAbdd): bool
    {
        return $user->hasRole('Admin');
    }
}
