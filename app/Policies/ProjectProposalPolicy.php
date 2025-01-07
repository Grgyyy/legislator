<?php
namespace App\Policies;

use App\Models\User;
use App\Models\QualificationTitle;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectProposalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function view(User $user, QualificationTitle $qualificationTitle)
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }


    public function create(User $user)
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }


    public function update(User $user, QualificationTitle $qualificationTitle)
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function delete(User $user, QualificationTitle $qualificationTitle)
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }


    public function restore(User $user, QualificationTitle $qualificationTitle): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function forceDelete(User $user, QualificationTitle $qualificationTitle): bool
    {
        return $user->hasRole('Super Admin');
    }
}
