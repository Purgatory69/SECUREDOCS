<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Attestation\Validator\AttestationValidator;
use Laragear\WebAuthn\Attestation\Validator\AttestationValidation;
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

        $this->app->extend(AttestationValidator::class, function (AttestationValidator $validator) {
            return $validator->pipe(function (AttestationValidation $validation, \Closure $next) {
                Log::debug('WebAuthn Attestation Pipeline State', [
                    'credential_exists' => !is_null($validation->credential),
                    'clientDataJson' => $validation->clientDataJson,
                ]);
                return $next($validation);
            });
        });
    }
}
