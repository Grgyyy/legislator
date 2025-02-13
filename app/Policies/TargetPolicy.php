<?php

namespace App\Policies;

use App\Models\Target;
use App\Models\User;

class TargetPolicy
{

    public function viewAny(User $user): bool
    {
        if ($user->hasRole('TESDO')) {
            return true;
        }

        return $user->hasRole(['SMD Head', 'SMD Focal', 'TESDO', 'Admin', 'Super Admin']);
    }

    public function view(User $user, Target $target): bool
    {
        return $user->hasRole(['TESDO', 'SMD Head', 'SMD Focal', 'TESDO', 'Admin', 'Super Admin']);
    }

    public function create(User $user): bool
    {
        if ($user->hasRole(['SMD Head', 'SMD Focal', 'TESDO', 'Admin', 'Super Admin'])) {
            return true;
        }
        return false;
    }

    public function update(User $user, Target $target): bool
    {
        if ($user->hasRole(['SMD Head', 'TESDO', 'Admin', 'Super Admin'])) {
            return true;
        }
        return false;
    }

    public function viewPending(User $user): bool
    {
        return $user->hasRole('TESDO') || $user->hasRole(['SMD Head', 'SMD Focal', 'Admin', 'Super Admin']);
    }

    public function viewActionable(User $user): bool
    {
        return $user->hasRole(['SMD Head', 'SMD Focal', 'TESDO', 'Admin', 'Super Admin']);
    }

    public function delete(User $user, Target $target): bool
    {
        return $user->hasRole(['Admin', 'Super Admin']);
    }

    public function restore(User $user, Target $target): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, Target $target): bool
    {
        return $user->hasRole('Super Admin');
    }
}
