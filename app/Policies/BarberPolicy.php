<?php

namespace App\Policies;

use App\Models\Barber;
use App\Models\User;

class BarberPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Barber $barber): bool
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

    public function update(?User $user, Barber $barber): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }

    public function delete(?User $user, Barber $barber): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }
}
