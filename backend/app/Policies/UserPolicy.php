<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($model->isAdmin() || $model->is_admin === true) {
            return false;
        }

        return $user->isManager() || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->isAdmin() || $model->is_admin === true) {
            return false;
        }

        return $user->isAdmin() || $user->isManager();
    }

    public function restore(User $user, User $model): bool
    {
        if ($model->isAdmin() || $model->is_admin === true) {
            return false;
        }

        return $user->isAdmin() || $user->isManager();
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->isAdmin() || $model->is_admin === true) {
            return false;
        }

        return $user->isAdmin() || $user->isManager();
    }
}
