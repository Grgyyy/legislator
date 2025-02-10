<?php

namespace App\Policies;

use App\Models\ScholarshipProgram;
use App\Models\User;

class ScholarshipProgramPolicy
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
    public function view(User $user, ScholarshipProgram $scholarshipProgram): bool
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
    public function update(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole('Super Admin');
    }
}