<?php

namespace App\Policies;

use App\Models\ContentPage;
use App\Models\User;

class ContentPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, ContentPage $contentPage): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isManager() && ! $contentPage->isSystemPage();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
