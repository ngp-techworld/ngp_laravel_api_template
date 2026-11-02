<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('hands out the CSRF cookie the SPA needs before signing in', function (): void {
    $this->getJson('/sanctum/csrf-cookie', spaHeaders())
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');
});

it('signs in with email and password and starts a session', function (): void {
    $user = User::factory()->create(['email' => 'demo@example.com']);

    $login = $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'password'], spaHeaders())
        ->assertOk()
        ->assertCookie(config('session.cookie'))
        ->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => 'demo@example.com',
                'created_at' => $user->created_at->toIso8601ZuluString(),
            ],
        ]);

    $this->assertAuthenticatedAs($user, 'web');

    // A fresh guard proves the session cookie alone signs the user in.
    Auth::forgetGuards();

    $this->withCookie(config('session.cookie'), sessionCookie($login))
        ->getJson('/api/v1/auth/me', spaHeaders())
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('accepts the email in any letter case and with surrounding spaces', function (): void {
    $user = User::factory()->create(['email' => 'demo@example.com']);

    $this->postJson('/api/v1/auth/login', ['email' => '  Demo@Example.COM ', 'password' => 'password'], spaHeaders())
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('sets a remember-me cookie when asked to remember the user', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);

    $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'password', 'remember' => true], spaHeaders())
        ->assertOk()
        ->assertCookie(Auth::guard('web')->getRecallerName());
});

it('rejects a wrong password with invalid_credentials', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);

    $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'wrong-password'], spaHeaders())
        ->assertUnprocessable()
        ->assertExactJson([
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            'code' => 'invalid_credentials',
            'errors' => ['email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.']],
        ]);

    $this->assertGuest('web');
});

it('rejects an unknown email exactly like a wrong password', function (): void {
    $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'password'], spaHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_credentials');
});

it('validates the sign-in form', function (): void {
    $this->postJson('/api/v1/auth/login', ['email' => 'not-an-email', 'remember' => 'maybe'], spaHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('message', 'البيانات المدخلة غير صالحة.')
        ->assertJsonPath('errors.email.0', 'يجب أن يكون حقل البريد الإلكتروني بريداً إلكترونياً صالحاً.')
        ->assertJsonPath('errors.password.0', 'حقل كلمة المرور مطلوب.')
        ->assertJsonValidationErrors(['remember']);
});

it('allows 5 sign-in attempts per minute for an email and IP, then answers 429', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);
    $attempt = ['email' => 'demo@example.com', 'password' => 'wrong-password'];

    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/auth/login', $attempt, spaHeaders())->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', $attempt, spaHeaders())
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('code', 'too_many_requests')
        ->assertJsonMissingPath('errors');

    // Another email from the same IP has its own budget.
    $this->postJson('/api/v1/auth/login', ['email' => 'other@example.com', 'password' => 'x'], spaHeaders())
        ->assertUnprocessable();
});

it('refuses cookie sign-in from a client that is not our SPA', function (): void {
    User::factory()->create(['email' => 'demo@example.com']);

    $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'password'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'session_required');

    $this->assertGuest('web');
});
