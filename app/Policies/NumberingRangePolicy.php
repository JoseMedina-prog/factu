<?php

namespace App\Policies;

use App\Models\NumberingRange;
use App\Models\User;

class NumberingRangePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.numbering');
    }

    public function view(User $user, NumberingRange $numberingRange): bool
    {
        return $user->tenant_id === $numberingRange->tenant_id
            && $user->can('settings.numbering');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.numbering');
    }

    public function update(User $user, NumberingRange $numberingRange): bool
    {
        return $user->tenant_id === $numberingRange->tenant_id
            && $user->can('settings.numbering');
    }

    public function delete(User $user, NumberingRange $numberingRange): bool
    {
        return $user->tenant_id === $numberingRange->tenant_id
            && $user->can('settings.numbering');
    }
}