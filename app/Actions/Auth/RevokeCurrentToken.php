<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final class RevokeCurrentToken
{
    /**
     * Deletes the bearer token of the current request. A cookie (session)
     * sign-in has no stored token, so there is nothing to revoke.
     */
    public function handle(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
