<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('answers an unknown API route with the error shape in Arabic', function (): void {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertExactJson([
            'message' => 'العنصر المطلوب غير موجود.',
            'code' => 'not_found',
        ]);
});

it('answers in English when the client asks for it', function (): void {
    $this->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertExactJson([
            'message' => 'The requested resource was not found.',
            'code' => 'not_found',
        ]);
});

it('answers with JSON even when the client forgets the Accept header', function (): void {
    $this->get('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('code', 'not_found');
});

it('rejects a wrong HTTP method with method_not_allowed', function (): void {
    $this->postJson('/up')
        ->assertMethodNotAllowed()
        ->assertJsonPath('code', 'method_not_allowed')
        ->assertJsonMissingPath('errors');
});

it('hides internal details of a server error in production', function (): void {
    Route::get('/api/v1/test-crash', fn () => throw new RuntimeException('SQLSTATE: password for user "app" leaked'));
    app()->instance('env', 'production');

    $this->getJson('/api/v1/test-crash')
        ->assertInternalServerError()
        ->assertExactJson([
            'message' => 'حدث خطأ غير متوقع. حاول مرة أخرى لاحقاً.',
            'code' => 'server_error',
        ]);
});
