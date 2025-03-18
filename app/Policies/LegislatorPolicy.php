<?php

namespace App\Policies;

use App\Models\Legislator;
use App\Models\User;

class LegislatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, Legislator $legislator): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, Legislator $legislator): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, Legislator $legislator): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Legislator $legislator): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Legislator $legislator): bool
    {
        return $user->hasRole('Super Admin');
    }
}
