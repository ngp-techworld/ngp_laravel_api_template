<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
|
| Feature tests boot the app and run on a fresh PostgreSQL "app_test"
| database (migrated once, each test wrapped in a transaction).
| Unit tests boot the app too (translator, config) but never touch the DB.
|
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Headers the React SPA sends (through the Vite proxy), so Sanctum treats
 * the request as stateful: session cookie auth instead of a bearer token.
 *
 * @return array<string, string>
 */
function spaHeaders(): array
{
    return [
        'Origin' => 'http://localhost:5173',
        'Referer' => 'http://localhost:5173/',
    ];
}

/**
 * @return array<string, string>
 */
function bearer(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

/**
 * The (decrypted) session id a login response set, to replay it with
 * withCookie() after Auth::forgetGuards() and prove the session really works.
 */
function sessionCookie(TestResponse $response): string
{
    return (string) $response->getCookie(config('session.cookie'))?->getValue();
}

/**
 * Signs in like our first-party clients do: a token with every ability.
 * Use Sanctum::actingAs($user, [...]) directly to test narrower tokens.
 */
function signIn(?User $user = null): User
{
    return Sanctum::actingAs($user ?? User::factory()->create(), ['*']);
}
