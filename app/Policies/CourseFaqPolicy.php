<?php

namespace App\Policies;

use App\Models\CourseFaq;
use App\Models\User;

class CourseFaqPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, CourseFaq $courseFaq): bool
    {
        if (!$user || $user->role === 'student') {
            return $courseFaq->course->status === 'published';
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $courseFaq->course->instructors->contains($user);
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

    public function update(?User $user, CourseFaq $courseFaq): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $courseFaq->course->instructors->contains($user);
        }

        return false;
    }

    public function delete(?User $user, CourseFaq $courseFaq): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $courseFaq->course->instructors->contains($user);
        }

        return false;
    }
}
