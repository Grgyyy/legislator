<?php

namespace App\Policies;

use App\Models\SkillPriority;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SkillPriorityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office', 'TESDO']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office', 'TESDO']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole('Super Admin');
    }
}
