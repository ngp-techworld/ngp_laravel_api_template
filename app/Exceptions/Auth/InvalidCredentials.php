<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wrong email or password. Same message for both, so nobody can probe which
 * emails have an account.
 */
final class InvalidCredentials extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('auth.failed'));
    }

    public function status(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public function errorCode(): string
    {
        return 'invalid_credentials';
    }

    public function errors(): array
    {
        return ['email' => [$this->getMessage()]];
    }
}
