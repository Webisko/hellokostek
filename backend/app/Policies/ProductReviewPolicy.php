<?php

namespace App\Policies;

use App\Models\User;

class ProductReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isEmployee();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isEmployee();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
