<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Item;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Creates $count items for $user, one minute apart, oldest first.
 */
function itemsFor(User $user, int $count, array $attributes = []): void
{
    foreach (range(1, $count) as $minute) {
        Item::factory()->for($user)->create([
            ...$attributes,
            'created_at' => now()->subMinutes($count - $minute),
        ]);
    }
}

it('lists the user\'s items newest first in the paginated resource shape', function (): void {
    $this->freezeSecond();
    $user = signIn();
    itemsFor($user, 20);
    $newest = $user->items()->latest()->first();

    $response = $this->getJson('/api/v1/items')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'description', 'created_at']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'],
        ])
        ->assertJsonPath('data.0', [
            'id' => $newest->id,
            'name' => $newest->name,
            'description' => $newest->description,
            'created_at' => now()->toIso8601ZuluString(),
        ])
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('links.prev', null);

    $dates = collect($response->json('data'))->pluck('created_at');
    expect($dates->all())->toBe($dates->sortDesc()->values()->all());
});

it('returns a null description as null', function (): void {
    $user = signIn();
    Item::factory()->for($user)->withoutDescription()->create();

    $this->getJson('/api/v1/items')
        ->assertOk()
        ->assertJsonPath('data.0.description', null);
});

it('never shows another user\'s items', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    itemsFor($userA, 2, ['name' => 'Belongs to A']);
    itemsFor($userB, 3, ['name' => 'Belongs to B']);

    signIn($userA);

    $this->getJson('/api/v1/items?search=belongs')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonMissing(['name' => 'Belongs to B']);
});

it('pages with page and per_page and keeps them in the links', function (): void {
    $user = signIn();
    itemsFor($user, 12);

    $this->getJson('/api/v1/items?page=2&per_page=5&search=')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.from', 6)
        ->assertJsonPath('meta.to', 10)
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('links.next', fn (string $url): bool => str_contains($url, 'per_page=5') && str_contains($url, 'page=3'));
});

it('searches names case-insensitively, in Arabic too', function (): void {
    $user = signIn();
    Item::factory()->for($user)->create(['name' => 'Fresh MILK']);
    Item::factory()->for($user)->create(['name' => 'Bread']);
    Item::factory()->for($user)->create(['name' => 'حليب طازج']);

    $this->getJson('/api/v1/items?search=milk')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Fresh MILK');

    $this->getJson('/api/v1/items?search='.urlencode('حليب'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'حليب طازج');
});

it('treats % and _ in the search as plain text', function (): void {
    $user = signIn();
    Item::factory()->for($user)->create(['name' => '50% off']);
    Item::factory()->for($user)->create(['name' => '500 grams']);

    $this->getJson('/api/v1/items?search='.urlencode('50%'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', '50% off');
});

it('rejects invalid paging and search values', function (array $query, string $field): void {
    signIn();

    $this->getJson('/api/v1/items?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonValidationErrors([$field]);
})->with([
    'per_page above 100' => [['per_page' => 500], 'per_page'],
    'per_page of 0' => [['per_page' => 0], 'per_page'],
    'page of 0' => [['page' => 0], 'page'],
    'page that is not a number' => [['page' => 'two'], 'page'],
    'search longer than 100 characters' => [['search' => str_repeat('a', 101)], 'search'],
]);

it('explains a validation failure in Arabic', function (): void {
    signIn();

    $this->getJson('/api/v1/items?per_page=500')
        ->assertUnprocessable()
        ->assertExactJson([
            'message' => 'البيانات المدخلة غير صالحة.',
            'code' => 'validation_failed',
            'errors' => ['per_page' => ['يجب أن تكون قيمة حقل عدد العناصر في الصفحة بين 1 و 100.']],
        ]);
});

it('answers 401 without a sign-in', function (): void {
    $this->getJson('/api/v1/items')
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'يجب تسجيل الدخول أولاً.',
            'code' => 'unauthenticated',
        ]);
});

it('answers 403 for a token without the items:read ability', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['profile:read']);

    $this->getJson('/api/v1/items')
        ->assertForbidden()
        ->assertExactJson([
            'message' => 'ليس لديك صلاحية للقيام بهذا الإجراء.',
            'code' => 'forbidden',
        ]);
});

it('works with a real bearer token and with the SPA session', function (): void {
    $user = User::factory()->create(['email' => 'demo@example.com']);
    itemsFor($user, 3);

    $token = $user->createToken('Pixel 9', [TokenAbility::ReadItems->value])->plainTextToken;
    $this->getJson('/api/v1/items', bearer($token))->assertOk()->assertJsonPath('meta.total', 3);

    $login = $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'password'], spaHeaders())->assertOk();
    Auth::forgetGuards();

    $this->withCookie(config('session.cookie'), sessionCookie($login))
        ->getJson('/api/v1/items', spaHeaders())
        ->assertOk()
        ->assertJsonPath('meta.total', 3);
});
