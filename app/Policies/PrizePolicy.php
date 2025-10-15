<?php

namespace App\Policies;

use App\Models\Prize;
use App\Models\User;

class PrizePolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'merchant']);
    }

    public function view(?User $user, Prize $prize): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'merchant']);
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'merchant']);
    }

    public function update(?User $user, Prize $prize): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'merchant']);
    }

    public function delete(?User $user, Prize $prize): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'merchant']);
    }
}
