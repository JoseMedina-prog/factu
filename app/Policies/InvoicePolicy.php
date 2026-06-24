<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        if ($invoice->reference_code || $invoice->is_validated) {
            return false;
        }

        return !in_array($invoice->status, ['approved', 'cancelled']);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        if ($invoice->reference_code || $invoice->is_validated) {
            return false;
        }

        return $invoice->status === 'draft';
    }

    public function send(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        if ($invoice->status === 'cancelled') {
            return false;
        }

        if ($invoice->status === 'sent' && !$invoice->reference_code) {
            return true;
        }

        return in_array($invoice->status, ['draft', 'pending', 'sent']);
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        if (!$invoice->reference_code && !$invoice->is_validated) {
            return false;
        }

        return $invoice->status !== 'cancelled';
    }
}
