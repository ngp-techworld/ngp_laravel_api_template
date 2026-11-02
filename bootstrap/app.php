<?php

declare(strict_types=1);

use App\Exceptions\ApiException;
use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // First global middleware, so every response (errors included) is translated.
        $middleware->prepend(SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Expected domain errors (wrong password, invalid state...) are not bugs.
        $exceptions->dontReport(ApiException::class);

        // Pure API: every error, on every route, uses the one NGP error shape.
        $exceptions->shouldRenderJsonWhen(static fn (): bool => true);
        $exceptions->render(
            static fn (Throwable $exception): ?JsonResponse => app(ApiExceptionRenderer::class)->render($exception),
        );
    })->create();
