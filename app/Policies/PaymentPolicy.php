<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'student']);
    }

    public function view(?User $user, Payment $payment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'student') {
            return $payment->user_id === $user->id;
        }

        return false;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->role === 'student';
    }

    public function update(?User $user, Payment $payment): bool
    {
        if (!$user) {
            return false;
        }

        return $user->role === 'admin';
    }

    public function delete(?User $user, Payment $payment): bool
    {
        if (!$user) {
            return false;
        }

        return $user->role === 'admin';
    }
}
