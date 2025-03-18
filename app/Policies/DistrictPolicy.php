<?php

namespace App\Policies;

use App\Models\District;
use App\Models\User;

class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function view(User $user, District $district): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function update(User $user, District $district): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function delete(User $user, District $district): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function restore(User $user, District $district): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, District $district): bool
    {
        return $user->hasRole('Super Admin');
    }
}
