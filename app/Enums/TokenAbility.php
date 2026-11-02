<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Abilities a bearer token can carry. First-party tokens get "*" (all of
 * them); give third-party tokens only what they need. Policies check them
 * with $user->tokenCan(...); cookie (SPA) sign-ins pass every check.
 */
enum TokenAbility: string
{
    case ReadItems = 'items:read';
}
