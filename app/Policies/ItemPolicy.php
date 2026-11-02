<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TokenAbility;
use App\Models\User;

final class ItemPolicy
{
    /**
     * Listing is always scoped to the user's own items (see ListItems), so
     * the only question is whether this sign-in may read items at all.
     */
    public function viewAny(User $user): bool
    {
        return $user->tokenCan(TokenAbility::ReadItems->value);
    }
}
