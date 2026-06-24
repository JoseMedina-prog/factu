<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        return $user->tenant_id === $model->tenant_id || $user->isAdmin() && $user->tenant_id === null;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->tenant_id === $model->tenant_id;
    }

    public function delete(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->tenant_id === $model->tenant_id;
    }
}
