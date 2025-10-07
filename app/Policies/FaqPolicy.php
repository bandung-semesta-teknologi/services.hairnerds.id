<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Faq $faq): bool
    {
        if (!$user) {
            $faqableType = class_basename($faq->faqable_type);

            if ($faqableType === 'Course') {
                return $faq->faqable && $faq->faqable->status === 'published';
            }

            if ($faqableType === 'Bootcamp') {
                return $faq->faqable && $faq->faqable->status === 'publish';
            }

            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            $faqableType = class_basename($faq->faqable_type);

            if ($faqableType === 'Course') {
                return $faq->faqable && $faq->faqable->instructors->contains($user);
            }

            if ($faqableType === 'Bootcamp') {
                return $faq->faqable && $faq->faqable->user_id === $user->id;
            }
        }

        $faqableType = class_basename($faq->faqable_type);

        if ($faqableType === 'Course') {
            return $faq->faqable && $faq->faqable->status === 'published';
        }

        if ($faqableType === 'Bootcamp') {
            return $faq->faqable && $faq->faqable->status === 'publish';
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

    public function update(?User $user, Faq $faq): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            $faqableType = class_basename($faq->faqable_type);

            if ($faqableType === 'Course') {
                return $faq->faqable && $faq->faqable->instructors->contains($user);
            }

            if ($faqableType === 'Bootcamp') {
                return $faq->faqable && $faq->faqable->user_id === $user->id;
            }
        }

        return false;
    }

    public function delete(?User $user, Faq $faq): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'instructor') {
            $faqableType = class_basename($faq->faqable_type);

            if ($faqableType === 'Course') {
                return $faq->faqable && $faq->faqable->instructors->contains($user);
            }

            if ($faqableType === 'Bootcamp') {
                return $faq->faqable && $faq->faqable->user_id === $user->id;
            }
        }

        return false;
    }
}
