<?php

namespace App\Policies;

use App\Models\Toolkit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ToolkitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, Toolkit $toolkit): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, Toolkit $toolkit): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, Toolkit $toolkit): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Toolkit $toolkit): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Toolkit $toolkit): bool
    {
        return $user->hasRole('Super Admin');
    }
}
