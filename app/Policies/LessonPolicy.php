<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, Lesson $lesson): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $lesson->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            if ($lesson->course->status !== 'published') {
                return false;
            }

            return $user->enrollments()
                ->where('course_id', $lesson->course_id)
                ->exists();
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

    public function update(?User $user, Lesson $lesson): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $lesson->course->instructors->contains($user);
        }

        return false;
    }

    public function delete(?User $user, Lesson $lesson): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $lesson->course->instructors->contains($user);
        }

        return false;
    }
}
