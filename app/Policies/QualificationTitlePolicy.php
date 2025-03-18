<?php

namespace App\Policies;

use App\Models\TrainingProgram;
use App\Models\User;

class QualificationTitlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, TrainingProgram $trainingProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, TrainingProgram $trainingProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, TrainingProgram $trainingProgram): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, TrainingProgram $trainingProgram): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, TrainingProgram $trainingProgram): bool
    {
        return $user->hasRole('Super Admin');
    }
}
