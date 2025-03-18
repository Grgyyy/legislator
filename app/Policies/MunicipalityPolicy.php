<?php

namespace App\Policies;

use App\Models\Municipality;
use App\Models\User;

class MunicipalityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function view(User $user, Municipality $municipality): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function update(User $user, Municipality $municipality): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function delete(User $user, Municipality $municipality): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function restore(User $user, Municipality $municipality): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Municipality $municipality): bool
    {
        return $user->hasRole('Super Admin');
    }
}
