<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Section $section): bool
    {
        if (!$user) {
            return $section->course->status === 'published';
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            if ($section->course->instructors->contains($user)) {
                return true;
            }
            return false;
        }

        return $section->course->status === 'published';
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin', 'instructor']);
    }

    public function update(?User $user, Section $section): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $section->course->instructors->contains($user)) {
            return true;
        }

        return false;
    }

    public function delete(?User $user, Section $section): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor' && $section->course->instructors->contains($user)) {
            return true;
        }

        return false;
    }
}
