<?php

namespace App\Policies;

use App\Models\FundSource;
use App\Models\User;

class FundSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function view(User $user, FundSource $fundSource): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function update(User $user, FundSource $fundSource): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function delete(User $user, FundSource $fundSource): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, FundSource $fundSource): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, FundSource $fundSource): bool
    {
        return $user->hasRole('Super Admin');
    }
}
