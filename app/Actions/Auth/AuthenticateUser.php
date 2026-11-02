<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Exceptions\Auth\InvalidCredentials;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use SensitiveParameter;

/**
 * Checks an email/password pair. Shared by cookie login and token issuing.
 */
final class AuthenticateUser
{
    public function __construct(private readonly Hasher $hasher) {}

    /**
     * @throws InvalidCredentials
     */
    public function handle(string $email, #[SensitiveParameter] string $password): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! $this->hasher->check($password, $user->password)) {
            throw new InvalidCredentials;
        }

        return $user;
    }
}
