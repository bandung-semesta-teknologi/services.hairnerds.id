<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, Enrollment $enrollment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $enrollment->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $enrollment->user_id === $user->id;
        }

        return false;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function update(?User $user, Enrollment $enrollment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $enrollment->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $enrollment->user_id === $user->id;
        }

        return false;
    }

    public function delete(?User $user, Enrollment $enrollment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $enrollment->course->instructors->contains($user);
        }

        return false;
    }

    public function finish(?User $user, Enrollment $enrollment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $enrollment->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $enrollment->user_id === $user->id;
        }

        return false;
    }
}
