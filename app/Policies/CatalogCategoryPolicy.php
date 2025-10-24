<?php

namespace App\Policies;

use App\Models\CatalogCategory;
use App\Models\User;

class CatalogCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, CatalogCategory $catalogCategory): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }

    public function update(?User $user, CatalogCategory $catalogCategory): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }

    public function delete(?User $user, CatalogCategory $catalogCategory): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($user->role, ['admin']);
    }
}
