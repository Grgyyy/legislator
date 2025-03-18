<?php

namespace App\Policies;

use App\Models\QualificationTitle;
use App\Models\User;

class ScheduleOfCostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, QualificationTitle $qualificationTitle): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, QualificationTitle $qualificationTitle): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, QualificationTitle $qualificationTitle): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, QualificationTitle $qualificationTitle): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, QualificationTitle $qualificationTitle): bool
    {
        return $user->hasRole('Super Admin');
    }
}
