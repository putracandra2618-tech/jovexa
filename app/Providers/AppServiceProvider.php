<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Paginator::useBootstrapFive();

        $this->configureRateLimiters();
    }

    /**
     * Rate limit the sensitive endpoints.
     *
     * The callback is deliberately generous: the endpoint is CSRF exempt so its
     * signature is the only authentication, but the gateway legitimately retries
     * notifications and a tight limit would drop real payment confirmations.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('tripay-callback', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->ip()));

        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) $request->user()?->getKey() ?: $request->ip()));

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perHour(5)
            ->by($request->ip()));

        RateLimiter::for('password-email', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip()));

        RateLimiter::for('orders', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) $request->user()?->getKey() ?: $request->ip()));

        RateLimiter::for('uploads', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) $request->user()?->getKey() ?: $request->ip()));
    }
}
