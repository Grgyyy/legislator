<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function view(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function update(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function delete(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function restore(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Region $region): bool
    {
        return $user->hasRole('Super Admin');
    }
}
