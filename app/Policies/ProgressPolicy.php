<?php

namespace App\Policies;

use App\Models\Progress;
use App\Models\User;

class ProgressPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, Progress $progress): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $progress->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $progress->user_id === $user->id;
        }

        return false;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'instructor']);
    }

    public function update(?User $user, Progress $progress): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $progress->course->instructors->contains($user);
        }

        return false;
    }

    public function delete(?User $user, Progress $progress): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $progress->course->instructors->contains($user);
        }

        return false;
    }

    public function complete(?User $user, Progress $progress): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $progress->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $progress->user_id === $user->id;
        }

        return false;
    }
}
