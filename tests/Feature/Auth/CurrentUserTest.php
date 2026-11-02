<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns the signed-in user with a ULID and a UTC timestamp', function (): void {
    $this->travelTo(now()->setDateTime(2026, 11, 2, 9, 44));
    $user = Sanctum::actingAs(User::factory()->create(['name' => 'Demo User', 'email' => 'demo@example.com']));

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'created_at' => '2026-11-02T09:44:00Z',
            ],
        ]);

    expect($user->id)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
});

it('answers 401 without a sign-in', function (): void {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'يجب تسجيل الدخول أولاً.',
            'code' => 'unauthenticated',
        ]);
});

it('answers 401 for a bearer token that does not exist', function (): void {
    $this->getJson('/api/v1/auth/me', bearer('1|not-a-real-token'))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated');
});
