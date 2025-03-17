<?php

namespace App\Policies;

use App\Models\InstitutionRecognition;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstitutionRecognitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function view(User $user, InstitutionRecognition $institutionRecognition): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function update(User $user, InstitutionRecognition $institutionRecognition): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head', 'SMD Focal']);
    }

    public function delete(User $user, InstitutionRecognition $institutionRecognition): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, InstitutionRecognition $institutionRecognition): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, InstitutionRecognition $institutionRecognition): bool
    {
        return $user->hasRole('Super Admin');
    }
}
