<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cookie sign-in was called by a client that Sanctum does not treat as our
 * SPA (its Origin/Referer is not in SANCTUM_STATEFUL_DOMAINS), so there is
 * no session to sign in to. Such clients use POST /api/v1/auth/token.
 */
final class SessionRequired extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('errors.session_required'));
    }

    public function status(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function errorCode(): string
    {
        return 'session_required';
    }
}
