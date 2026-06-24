<?php

namespace App\Policies;

use App\Models\PurchaseInvoice;
use App\Models\User;

class PurchaseInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.view') || $user->can('payables.view');
    }

    public function view(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && ($user->can('purchases.view') || $user->can('payables.view'));
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.create');
    }

    public function update(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && $invoice->status === PurchaseInvoice::STATUS_DRAFT
            && $user->can('purchases.update');
    }

    public function delete(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && $invoice->status === PurchaseInvoice::STATUS_DRAFT
            && $user->isAdmin();
    }

    public function cancel(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && $user->can('purchases.cancel')
            && $invoice->status === PurchaseInvoice::STATUS_RECEIVED;
    }
}