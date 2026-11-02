<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\User;

it('seeds the demo user with 30 items for local development', function (): void {
    $this->seed();

    $demo = User::query()->where('email', 'demo@example.com')->sole();

    expect($demo->name)->toBe('Demo User')
        ->and($demo->items()->count())->toBe(30);

    $this->postJson('/api/v1/auth/token', ['email' => 'demo@example.com', 'password' => 'password', 'device_name' => 'test'])
        ->assertCreated();
});

it('can run twice without duplicating the demo data', function (): void {
    $this->seed();
    $this->seed();

    expect(User::query()->count())->toBe(1)
        ->and(Item::query()->count())->toBe(30);
});

it('seeds nothing outside local and testing', function (): void {
    app()->instance('env', 'production');

    $this->seed();

    expect(User::query()->count())->toBe(0);
});
