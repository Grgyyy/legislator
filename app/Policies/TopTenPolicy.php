<?php

namespace App\Policies;

use App\Models\Priority;
use App\Models\User;

class TopTenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function view(User $user, Priority $priority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, Priority $priority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, Priority $priority): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Priority $priority): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Priority $priority): bool
    {
        return $user->hasRole('Super Admin');
    }
}
