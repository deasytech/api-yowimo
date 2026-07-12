<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Clerk\ClerkJwtVerifier;
use App\Services\Clerk\ClerkUserProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        Auth::viaRequest('clerk', function (Request $request): ?User {
            $token = $request->bearerToken();

            if (! $token) {
                return null;
            }

            $claims = app(ClerkJwtVerifier::class)->verify($token);

            return app(ClerkUserProvisioner::class)->resolve($claims);
        });
    }
}
