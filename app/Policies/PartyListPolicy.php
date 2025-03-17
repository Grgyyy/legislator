<?php

namespace App\Policies;

use App\Models\Partylist;
use App\Models\User;

class PartyListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function view(User $user, Partylist $partylist): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function update(User $user, Partylist $partylist): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }
    public function delete(User $user, Partylist $partylist): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
    public function restore(User $user, Partylist $partylist): bool
    {
        return $user->hasRole('Super Admin');
    }
    public function forceDelete(User $user, Partylist $partylist): bool
    {
        return $user->hasRole('Super Admin');
    }
}
