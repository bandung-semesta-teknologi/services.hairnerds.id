<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, Attachment $attachment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $attachment->lesson->course->instructors->contains($user);
        }

        if ($user->role === 'student') {
            if ($attachment->lesson->course->status !== 'published') {
                return false;
            }

            return $user->enrollments()
                ->where('course_id', $attachment->lesson->course_id)
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

    public function update(?User $user, Attachment $attachment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $attachment->lesson->course->instructors->contains($user);
        }

        return false;
    }

    public function delete(?User $user, Attachment $attachment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            return $attachment->lesson->course->instructors->contains($user);
        }

        return false;
    }
}
