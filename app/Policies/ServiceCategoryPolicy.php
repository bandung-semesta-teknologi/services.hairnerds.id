<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ServiceCategory $serviceCategory): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }

    public function update(?User $user, ServiceCategory $serviceCategory): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }

    public function delete(?User $user, ServiceCategory $serviceCategory): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }
}
