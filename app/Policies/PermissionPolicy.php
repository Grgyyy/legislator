<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);

    }
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);

    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);

    }
    public function update(User $user, Permission $permission): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);

    }
    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);

    }
    public function restore(User $user, Permission $permission): bool
    {
        return $user->hasRole('Super Admin');

    }
    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->hasRole('Super Admin');
    }
}
