<?php

namespace App\Policies;

use App\Models\SkillPriority;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SkillPriorityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office', 'TESDO', 'SMD Focal']);
    }
    public function view(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office', 'TESDO', 'SMD Focal']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office', 'SMD Focal', 'SMD Focal']);
    }
    public function update(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'Planning Office', 'SMD Focal']);
    }
    public function delete(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, SkillPriority $skillPriority): bool
    {
        return $user->hasRole('Super Admin');
    }
}
