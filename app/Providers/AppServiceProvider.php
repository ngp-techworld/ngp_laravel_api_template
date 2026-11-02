<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // N+1 queries fail loudly in development and tests; eager-load with `with()`.
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->configureRateLimiting();
    }

    /**
     * Named limiters, used as `throttle:<name>` on routes (ngp-laravel §5).
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)->by(
            Str::lower($request->string('email')->trim()->toString()).'|'.$request->ip(),
        ));
    }
}
