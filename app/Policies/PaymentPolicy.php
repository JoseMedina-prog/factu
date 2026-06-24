<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create') || $user->can('payments.create');
    }

    public function confirm(User $user, Payment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && ($user->isAdmin() || $user->can('payments.create'));
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && ($user->isAdmin() || $user->can('payments.create'));
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && $user->isAdmin();
    }
}