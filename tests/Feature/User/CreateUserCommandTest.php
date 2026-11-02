<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a user with a hashed password and a ULID', function (): void {
    $this->artisan('app:create-user', ['--name' => 'Sara Ali', '--email' => ' Sara@Example.com '])
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    $user = User::query()->sole();

    expect($user->email)->toBe('sara@example.com')
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->id)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/')
        ->and(Hash::check('a-strong-password', $user->password))->toBeTrue();
});

it('creates an admin with --admin', function (): void {
    $this->artisan('app:create-user', ['--name' => 'Ops', '--email' => 'ops@example.com', '--admin' => true])
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    expect(User::query()->sole()->role)->toBe(UserRole::Admin);
});

it('asks for the name and email when they are not given', function (): void {
    $this->artisan('app:create-user')
        ->expectsQuestion('Name', 'Sara Ali')
        ->expectsQuestion('Email', 'sara@example.com')
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    expect(User::query()->where('email', 'sara@example.com')->exists())->toBeTrue();
});

it('refuses an email that is already taken', function (): void {
    User::factory()->create(['email' => 'sara@example.com']);

    $this->artisan('app:create-user', ['--name' => 'Sara Ali', '--email' => 'SARA@example.com'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsOutputToContain('The email has already been taken.')
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});

it('refuses a short password', function (): void {
    $this->artisan('app:create-user', ['--name' => 'Sara Ali', '--email' => 'sara@example.com'])
        ->expectsQuestion('Password', 'short')
        ->expectsOutputToContain('at least 8 characters')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});
