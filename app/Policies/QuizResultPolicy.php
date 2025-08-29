<?php

namespace App\Policies;

use App\Models\QuizResult;
use App\Models\User;

class QuizResultPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, QuizResult $quizResult): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $quizResult->quiz->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $quizResult->user_id === $user->id;
        }

        return false;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'instructor', 'student']);
    }

    public function update(?User $user, QuizResult $quizResult): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $quizResult->quiz->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $quizResult->user_id === $user->id;
        }

        return false;
    }

    public function delete(?User $user, QuizResult $quizResult): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $quizResult->quiz->course->instructors->contains($user);
        }

        return false;
    }

    public function submit(?User $user, QuizResult $quizResult): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $quizResult->quiz->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            return $quizResult->user_id === $user->id;
        }

        return false;
    }
}
