<?php

namespace App\Policies;

use App\Models\SubParticular;
use App\Models\User;

class ParticularTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, SubParticular $subParticular): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, SubParticular $subParticular): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, SubParticular $subParticular): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, SubParticular $subParticular): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, SubParticular $subParticular): bool
    {
        return $user->hasRole('Super Admin');
    }
}
