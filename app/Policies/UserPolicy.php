<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->role === 'admin';
    }

    public function view(?User $user, User $model): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $model->id;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->role === 'admin';
    }

    public function update(?User $user, User $model): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $model->id;
    }

    public function delete(?User $user, User $model): bool
    {
        if (!$user) {
            return false;
        }

        return $user->role === 'admin';
    }
}
