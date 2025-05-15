<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
        //
       // Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);


        Inertia::share('auth', function () {
            return [
                'user' => Auth::user(),
                // Add token only in dev/test if necessary
                'token' => optional(Auth::user())->tokens->first()?->plainTextToken,
            ];
        });

    }
}
