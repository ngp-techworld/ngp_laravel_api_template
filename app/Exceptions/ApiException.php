<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Base class for expected, user-facing domain errors (wrong password,
 * invalid order transition...). ApiExceptionRenderer turns them into the
 * NGP error shape with their own status and code; they are not logged.
 *
 * Subclasses pass an already translated message to the constructor.
 */
abstract class ApiException extends RuntimeException
{
    /** HTTP status, for example 409 for an invalid state change. */
    abstract public function status(): int;

    /** Stable machine string the clients switch on, for example "slot_unavailable". */
    abstract public function errorCode(): string;

    /**
     * Field errors. Only rendered when status() is 422.
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return [];
    }
}
