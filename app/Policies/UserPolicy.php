<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user && $user->role === 'admin';
    }

    public function view(?User $user, User $model): bool
    {
        return $user && $user->role === 'admin';
    }

    public function create(?User $user): bool
    {
        return $user && $user->role === 'admin';
    }

    public function update(?User $user, User $model): bool
    {
        return $user && $user->role === 'admin';
    }

    public function delete(?User $user, User $model): bool
    {
        if (!$user || $user->role !== 'admin') {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    public function resetPassword(?User $user, User $model): bool
    {
        return $user && $user->role === 'admin';
    }
}
