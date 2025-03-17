<?php

namespace App\Policies;

use App\Models\Allocation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'TESDO', 'SMD Focal']);
    }

    public function view(User $user, Allocation $allocation): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'TESDO', 'SMD Focal']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'TESDO']);
    }

    public function update(User $user, Allocation $allocation): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'TESDO']);
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
