<?php

namespace App\Policies;

use App\Models\Province;
use App\Models\User;

class ProvincePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function view(User $user, Province $province): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function update(User $user, Province $province): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function delete(User $user, Province $province): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function restore(User $user, Province $province): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Province $province): bool
    {
        return $user->hasRole('Super Admin');
    }
}
