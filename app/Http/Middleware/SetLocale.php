<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the response language from the Accept-Language header.
 *
 * Only locales in config('app.supported_locales') are used; anything else,
 * or no header at all, falls back to config('app.locale') (Arabic).
 */
final class SetLocale
{
    public function __construct(
        private readonly Application $app,
        private readonly Config $config,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->preferredLocale($request);

        $this->app->setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    private function preferredLocale(Request $request): string
    {
        $default = $this->config->string('app.locale');

        /** @var list<string> $supported */
        $supported = $this->config->array('app.supported_locales');

        // Symfony returns the first entry when nothing matches, so the default goes first.
        $candidates = [$default, ...array_values(array_diff($supported, [$default]))];

        return $request->getPreferredLanguage($candidates) ?? $default;
    }
}
