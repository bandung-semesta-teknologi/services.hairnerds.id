<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        if (!$user) {
            return $course->status === 'published';
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $course->instructors->contains($user)) {
            return true;
        }

        return $course->status === 'published';
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'instructor']);
    }

    public function update(?User $user, Course $course): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $course->instructors->contains($user)) {
            return true;
        }

        return false;
    }

    public function delete(?User $user, Course $course): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $course->instructors->contains($user)) {
            return true;
        }

        return false;
    }

    public function verify(?User $user, Course $course): bool
    {
        return $user && $user->role === 'admin';
    }

    public function reject(?User $user, Course $course): bool
    {
        return $user && $user->role === 'admin';
    }

    public function viewStudentProgress(?User $user, Course $course): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $course->instructors->contains($user)) {
            return true;
        }

        return false;
    }
}
