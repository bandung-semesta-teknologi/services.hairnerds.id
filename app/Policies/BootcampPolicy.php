<?php

namespace App\Policies;

use App\Models\Bootcamp;
use App\Models\User;

class BootcampPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Bootcamp $bootcamp): bool
    {
        if (!$user) {
            return $bootcamp->status === 'publish';
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $bootcamp->user_id === $user->id) {
            return true;
        }

        return $bootcamp->status === 'publish';
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'instructor']);
    }

    public function update(?User $user, Bootcamp $bootcamp): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $bootcamp->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(?User $user, Bootcamp $bootcamp): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $bootcamp->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function verify(?User $user, Bootcamp $bootcamp): bool
    {
        return $user && $user->role === 'admin';
    }

    public function reject(?User $user, Bootcamp $bootcamp): bool
    {
        return $user && $user->role === 'admin';
    }
}
