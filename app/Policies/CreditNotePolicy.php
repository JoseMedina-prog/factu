<?php

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->tenant_id === $creditNote->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        if ($user->tenant_id !== $creditNote->tenant_id) {
            return false;
        }

        return in_array($creditNote->status, ['draft'], true);
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return $user->tenant_id === $creditNote->tenant_id && $creditNote->status === 'draft';
    }

    public function approve(User $user, CreditNote $creditNote): bool
    {
        if ($user->tenant_id !== $creditNote->tenant_id) {
            return false;
        }

        return $creditNote->status === 'draft';
    }

    public function cancel(User $user, CreditNote $creditNote): bool
    {
        if ($user->tenant_id !== $creditNote->tenant_id) {
            return false;
        }

        return in_array($creditNote->status, ['draft', 'approved'], true);
    }
}
