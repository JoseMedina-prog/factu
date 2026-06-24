<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin() || $user->tenant_id === $tenant->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }
}
