<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('ends the SPA session', function (): void {
    $user = User::factory()->create(['email' => 'demo@example.com']);
    $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'password'], spaHeaders())
        ->assertOk();
    $this->assertAuthenticatedAs($user, 'web');

    $this->postJson('/api/v1/auth/logout', [], spaHeaders())->assertNoContent();

    $this->assertGuest('web');
});

it('revokes the token when a bearer client logs out', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Pixel 9')->plainTextToken;

    $this->postJson('/api/v1/auth/logout', [], bearer($token))->assertNoContent();

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('answers 401 when nobody is signed in', function (): void {
    $this->postJson('/api/v1/auth/logout', [], spaHeaders())
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated');
});
