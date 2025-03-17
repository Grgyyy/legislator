<?php

namespace App\Policies;

use App\Models\Recognition;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecognitionTitlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function view(User $user, Recognition $recognition): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function update(User $user, Recognition $recognition): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }
    public function delete(User $user, Recognition $recognition): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Recognition $recognition): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Recognition $recognition): bool
    {
        return $user->hasRole('Super Admin');
    }
}
