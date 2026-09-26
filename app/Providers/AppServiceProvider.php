<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AI\AIProvider;
use App\Services\AI\OpenAiProvider;
use App\Services\Clerk\ClerkJwtVerifier;
use App\Services\Clerk\ClerkUserProvisioner;
use App\Services\Purchase\ManualPaymentProvider;
use App\Services\Purchase\PaymentProvider;
use App\Services\Purchase\PaystackPaymentProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Falls back to the manual/test driver until PAYSTACK_SECRET_KEY is
        // set in any given environment (mirrors AIProvider's OpenAI-key gate
        // below), so purchases keep working locally/in CI with no real
        // gateway configured. Resolved lazily (not decided once at register()
        // time) so tests can toggle config('services.paystack.secret_key')
        // and get the matching driver.
        $this->app->bind(
            PaymentProvider::class,
            fn ($app) => config('services.paystack.secret_key')
                ? $app->make(PaystackPaymentProvider::class)
                : $app->make(ManualPaymentProvider::class),
        );
        $this->app->bind(AIProvider::class, OpenAiProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($credentialsPath = env('FIREBASE_CREDENTIALS')) {
            config(['firebase.projects.app.credentials' => storage_path('app/private/'.$credentialsPath)]);
        }

        Auth::viaRequest('clerk', function (Request $request): ?User {
            $token = $request->bearerToken();

            if (! $token) {
                return null;
            }

            $claims = app(ClerkJwtVerifier::class)->verify($token);

            return app(ClerkUserProvisioner::class)->resolve($claims);
        });

        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        RateLimiter::for('purchases', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('friend-requests', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('party-actions', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('push-tokens', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // Room codes are short (6 chars) and drawn from a constrained
        // charset — stricter than the general party-actions limit to slow
        // down brute-force guessing.
        RateLimiter::for('room-code-lookup', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // Each attempt makes an outbound Clerk Backend API call.
        RateLimiter::for('account-deletion', fn (Request $request) => Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()));
    }
}
