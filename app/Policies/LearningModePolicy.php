<?php

namespace App\Policies;

use App\Models\LearningMode;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LearningModePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, LearningMode $learningMode): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, LearningMode $learningMode): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, LearningMode $learningMode): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, LearningMode $learningMode): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, LearningMode $learningMode): bool
    {
        return $user->hasRole('Super Admin');
    }
}
