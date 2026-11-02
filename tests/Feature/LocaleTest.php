<?php

declare(strict_types=1);

it('answers in Arabic when the client sends no language preference', function (): void {
    $this->withHeader('Accept-Language', '')
        ->get('/up')
        ->assertOk()
        ->assertHeader('Content-Language', 'ar');

    expect(app()->getLocale())->toBe('ar');
});

it('answers in English when the client prefers English', function (string $header): void {
    $this->withHeader('Accept-Language', $header)
        ->get('/up')
        ->assertOk()
        ->assertHeader('Content-Language', 'en');
})->with([
    'plain' => 'en',
    'with region and weights' => 'en-US,en;q=0.9,ar;q=0.8',
]);

it('falls back to Arabic for a locale we do not support', function (): void {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get('/up')
        ->assertOk()
        ->assertHeader('Content-Language', 'ar');
});

it('translates validation messages into Arabic', function (): void {
    $validator = validator(['email' => ''], ['email' => 'required']);

    expect($validator->errors()->first('email'))->toBe('حقل البريد الإلكتروني مطلوب.');
});
