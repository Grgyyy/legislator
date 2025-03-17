<?php

namespace App\Policies;

use App\Models\Particular;
use App\Models\User;

class ParticularPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, Particular $particular): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, Particular $particular): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, Particular $particular): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Particular $particular): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Particular $particular): bool
    {
        return $user->hasRole('Super Admin');
    }
}
