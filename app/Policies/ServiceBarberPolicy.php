<?php

namespace App\Policies;

use App\Models\ServiceBarber;
use App\Models\User;

class ServiceBarberPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ServiceBarber $serviceBarber): bool
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

    public function update(?User $user, ServiceBarber $serviceBarber): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }

    public function delete(?User $user, ServiceBarber $serviceBarber): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }
}
