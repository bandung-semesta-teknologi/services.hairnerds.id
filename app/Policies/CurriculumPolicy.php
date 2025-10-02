<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class CurriculumPolicy
{
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
}
