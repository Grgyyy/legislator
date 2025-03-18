<?php

namespace App\Policies;

use App\Models\DeliveryMode;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeliveryModePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function view(User $user, DeliveryMode $deliveryMode): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function update(User $user, DeliveryMode $deliveryMode): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'SMD Head']);
    }

    public function delete(User $user, DeliveryMode $deliveryMode): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function restore(User $user, DeliveryMode $deliveryMode): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function forceDelete(User $user, DeliveryMode $deliveryMode): bool
    {
        return $user->hasRole('Super Admin');
    }
}
