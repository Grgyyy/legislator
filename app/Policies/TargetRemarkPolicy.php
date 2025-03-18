<?php

namespace App\Policies;

use App\Models\TargetRemark;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TargetRemarkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, TargetRemark $targetRemark): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, TargetRemark $targetRemark): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, TargetRemark $targetRemark): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, TargetRemark $targetRemark): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, TargetRemark $targetRemark): bool
    {
        return $user->hasRole('Super Admin');
    }
}
