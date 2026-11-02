<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

function issueToken(string $email = 'demo@example.com', string $password = 'password'): string
{
    return test()->postJson('/api/v1/auth/token', [
        'email' => $email,
        'password' => $password,
        'device_name' => 'Pixel 9',
    ])->assertCreated()->json('data.token');
}

it('issues a bearer token that expires in 30 days', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create(['email' => 'demo@example.com']);

    $this->postJson('/api/v1/auth/token', ['email' => 'demo@example.com', 'password' => 'password', 'device_name' => 'Pixel 9'])
        ->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.expires_at', now()->addDays(30)->toIso8601ZuluString())
        ->assertJsonPath('data.user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => 'demo@example.com',
            'created_at' => $user->created_at->toIso8601ZuluString(),
        ])
        ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at', 'user']]);

    expect(PersonalAccessToken::query()->sole())
        ->name->toBe('Pixel 9')
        ->tokenable_id->toBe($user->id);
});

it('signs in with the bearer token', function (): void {
    $user = User::factory()->create(['email' => 'demo@example.com']);

    $this->getJson('/api/v1/auth/me', bearer(issueToken()))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('rejects wrong credentials with invalid_credentials', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);

    $this->postJson('/api/v1/auth/token', ['email' => 'demo@example.com', 'password' => 'wrong-password', 'device_name' => 'Pixel 9'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_credentials')
        ->assertJsonPath('errors.email.0', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('validates the token request', function (): void {
    $this->postJson('/api/v1/auth/token', ['email' => 'demo@example.com'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors(['password', 'device_name']);
});

it('rate limits token requests with the same login limiter', function (): void {
    $attempt = ['email' => 'demo@example.com', 'password' => 'wrong-password', 'device_name' => 'Pixel 9'];

    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/auth/token', $attempt)->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/token', $attempt)
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'too_many_requests');
});

it('accepts the token until it expires', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);
    $token = issueToken();

    $this->travel(30)->days();
    $this->travel(-1)->minutes();

    $this->getJson('/api/v1/auth/me', bearer($token))->assertOk();
});

it('rejects the token once it has expired', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);
    $token = issueToken();

    $this->travel(30)->days();
    $this->travel(1)->minutes();

    $this->getJson('/api/v1/auth/me', bearer($token))
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'يجب تسجيل الدخول أولاً.',
            'code' => 'unauthenticated',
        ]);
});

it('revokes the current token and keeps the others', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);
    $phoneToken = issueToken();
    $tabletToken = issueToken();

    $this->deleteJson('/api/v1/auth/token', [], bearer($phoneToken))->assertNoContent();
    Auth::forgetGuards();

    $this->getJson('/api/v1/auth/me', bearer($phoneToken))->assertUnauthorized();
    Auth::forgetGuards();

    $this->getJson('/api/v1/auth/me', bearer($tabletToken))->assertOk();
    expect(PersonalAccessToken::query()->count())->toBe(1);
});

it('requires a sign-in to revoke a token', function (): void {
    $this->deleteJson('/api/v1/auth/token')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated');
});
