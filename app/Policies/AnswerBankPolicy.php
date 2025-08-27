<?php

namespace App\Policies;

use App\Models\AnswerBank;
use App\Models\User;

class AnswerBankPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'instructor']);
    }

    public function view(?User $user, AnswerBank $answerBank): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $answerBank->question->quiz->course->instructors->contains($user);
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

    public function update(?User $user, AnswerBank $answerBank): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $answerBank->question->quiz->course->instructors->contains($user);
        }

        return false;
    }

    public function delete(?User $user, AnswerBank $answerBank): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $answerBank->question->quiz->course->instructors->contains($user);
        }

        return false;
    }
}
