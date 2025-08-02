<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laragear\WebAuthn\Models\WebAuthnCredential as BaseWebAuthnCredential;

class WebAuthnServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // This is the crucial part. We are telling Laravel that whenever the package
        // asks for a class that implements WebAuthnCredentialContract, it should
        // receive an instance of our own App\Models\WebAuthnCredential model.
        $this->app->bind(
BaseWebAuthnCredential::class,
            \App\Models\WebAuthnCredential::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // The event listener has been removed as credential creation is now handled
        // manually in the WebAuthnController to ensure our custom model is used.
    }
}
