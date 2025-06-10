<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Blade;

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
        Blade::component('layouts.profile-dashboard', 'profile-dashboard');

        RedirectIfAuthenticated::redirectUsing(function ($request) {
            return RouteServiceProvider::HOME;
        });
    }
}
