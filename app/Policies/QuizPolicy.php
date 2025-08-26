<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, Quiz $quiz): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $quiz->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            if ($quiz->course->status !== 'published') {
                return false;
            }

            return $user->enrollments()
                ->where('course_id', $quiz->course_id)
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

    public function update(?User $user, Quiz $quiz): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $quiz->course->instructors->contains($user);
        }

        return false;
    }

    public function delete(?User $user, Quiz $quiz): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $quiz->course->instructors->contains($user);
        }

        return false;
    }
}
