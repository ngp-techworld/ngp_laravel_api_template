<?php

declare(strict_types=1);

it('serves the OpenAPI document in local development', function (): void {
    app()->instance('env', 'local');

    $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('info.title', 'NGP starter API')
        ->assertJsonStructure(['paths' => ['/v1/auth/login', '/v1/auth/token', '/v1/auth/me', '/v1/items']]);
});

it('hides the API docs outside local development', function (): void {
    $this->getJson('/docs/api.json')
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
});
