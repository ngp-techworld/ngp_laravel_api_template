<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Container\Attributes\Config;
use Laravel\Sanctum\NewAccessToken;
use SensitiveParameter;

/**
 * Bearer-token sign-in for mobile apps and server-side clients (Next.js).
 *
 * First-party tokens get every ability ("*"); give third-party tokens a
 * narrower list. Expiry comes from config('sanctum.expiration') (minutes).
 */
final class IssueAccessToken
{
    public function __construct(
        private readonly AuthenticateUser $authenticateUser,
        #[Config('sanctum.expiration')] private readonly int $expirationMinutes,
    ) {}

    public function handle(string $email, #[SensitiveParameter] string $password, string $deviceName): NewAccessToken
    {
        $user = $this->authenticateUser->handle($email, $password);

        $token = $user->createToken($deviceName, ['*'], now()->addMinutes($this->expirationMinutes));

        // The response shows the owner; set it so rendering needs no extra query.
        $token->accessToken->setRelation('tokenable', $user);

        return $token;
    }
}
