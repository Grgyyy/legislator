<?php

namespace App\Policies;

use App\Models\Allocation;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function view(User $user, Allocation $allocation): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function update(User $user, Allocation $allocation): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function delete(User $user, Allocation $allocation): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Allocation $allocation): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, Allocation $allocation): bool
    {
        return $user->hasRole('Super Admin');
    }
}
