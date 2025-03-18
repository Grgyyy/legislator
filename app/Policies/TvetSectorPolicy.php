<?php

namespace App\Policies;

use App\Models\Tvet;
use App\Models\User;

class TvetSectorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function view(User $user, Tvet $tvet): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, Tvet $tvet): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, Tvet $tvet): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Tvet $tvet): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Tvet $tvet): bool
    {
        return $user->hasRole('Super Admin');
    }
}
