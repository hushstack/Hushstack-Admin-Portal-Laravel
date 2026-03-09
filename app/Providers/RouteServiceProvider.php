<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth.register', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($this->emailThrottleKey($request));
        });

        RateLimiter::for('auth.login', function (Request $request) {
            return Limit::perMinute(5)->by($this->emailThrottleKey($request));
        });

        RateLimiter::for('auth.otp.verify', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($this->emailThrottleKey($request));
        });

        RateLimiter::for('auth.otp.resend', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($this->emailThrottleKey($request));
        });

        RateLimiter::for('auth.password.forgot', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($this->emailThrottleKey($request));
        });

        RateLimiter::for('public.contact', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->ip());
        });

        RateLimiter::for('public.member-request', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    private function emailThrottleKey(Request $request): string
    {
        $email = Str::lower(trim((string) $request->input('email', '')));

        return $email . '|' . $request->ip();
    }
}
