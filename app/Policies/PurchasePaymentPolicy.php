<?php

namespace App\Policies;

use App\Models\PurchasePayment;
use App\Models\User;

class PurchasePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payables.view');
    }

    public function view(User $user, PurchasePayment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && $user->can('payables.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payables.create');
    }

    public function confirm(User $user, PurchasePayment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && ($user->isAdmin() || $user->can('payables.create'));
    }

    public function cancel(User $user, PurchasePayment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && ($user->isAdmin() || $user->can('payables.create'));
    }

    public function delete(User $user, PurchasePayment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && $user->isAdmin();
    }
}