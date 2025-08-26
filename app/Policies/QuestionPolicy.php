<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, Question $question): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $question->quiz->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            if ($question->quiz->course->status !== 'published') {
                return false;
            }

            return $user->enrollments()
                ->where('course_id', $question->quiz->course_id)
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

    public function update(?User $user, Question $question): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $question->quiz->course->instructors->contains($user);
        }

        return false;
    }

    public function delete(?User $user, Question $question): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $question->quiz->course->instructors->contains($user);
        }

        return false;
    }
}
