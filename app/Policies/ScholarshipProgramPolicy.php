<?php

namespace App\Policies;

use App\Models\ScholarshipProgram;
use App\Models\User;

class ScholarshipProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, ScholarshipProgram $scholarshipProgram): bool
    {
        return $user->hasRole('Super Admin');
    }
}
