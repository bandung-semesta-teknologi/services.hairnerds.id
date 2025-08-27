<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Review $review): bool
    {
        if (!$user || $user->role === 'student') {
            return $review->course->status === 'published' && $review->is_visible;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $review->course->instructors->contains($user);
        }

        return false;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'student']);
    }

    public function update(?User $user, Review $review): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'student') {
            return $review->user_id === $user->id;
        }

        return false;
    }

    public function delete(?User $user, Review $review): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'student') {
            return $review->user_id === $user->id;
        }

        return false;
    }
}
