<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.view') || $user->can('payables.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->tenant_id === $supplier->tenant_id
            && ($user->can('suppliers.view') || $user->can('payables.view'));
    }

    public function create(User $user): bool
    {
        return $user->can('suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->tenant_id === $supplier->tenant_id
            && $user->can('suppliers.update');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->tenant_id === $supplier->tenant_id
            && $user->can('suppliers.delete');
    }
}