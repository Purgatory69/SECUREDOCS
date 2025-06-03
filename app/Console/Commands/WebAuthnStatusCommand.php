<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class WebAuthnStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webauthn:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of WebAuthn integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking WebAuthn Integration Status...');
        $this->newLine();

        // Check if the package is installed
        $this->checkPackageInstallation();
        
        // Check database tables
        $this->checkDatabaseTables();
        
        // Check User model relationship
        $this->checkUserModelRelationship();
        
        // Check routes
        $this->checkRoutes();
        
        // Check JavaScript assets
        $this->checkJavaScriptAssets();
        
        $this->newLine();
        $this->info('WebAuthn status check completed.');
    }

    /**
     * Check if the WebAuthn package is installed
     */
    private function checkPackageInstallation()
    {
        $this->components->task('Checking WebAuthn package installation', function () {
            return class_exists('Asbiin\LaravelWebauthn\WebauthnServiceProvider');
        });
    }

    /**
     * Check if the necessary database tables exist
     */
    private function checkDatabaseTables()
    {
        $this->components->task('Checking WebAuthn database tables', function () {
            return Schema::hasTable('webauthn_keys');
        });

        if (Schema::hasTable('webauthn_keys')) {
            $keyCount = DB::table('webauthn_keys')->count();
            $this->line("  <fg=gray>→</> Total registered WebAuthn keys: {$keyCount}");
        }
    }

    /**
     * Check if the User model has the webauthnKeys relationship
     */
    private function checkUserModelRelationship()
    {
        $this->components->task('Checking User model relationship', function () {
            return method_exists(\App\Models\User::class, 'webauthnKeys');
        });
    }

    /**
     * Check if the necessary routes are registered
     */
    private function checkRoutes()
    {
        $this->components->task('Checking WebAuthn routes', function () {
            $routes = app('router')->getRoutes();
            $hasLoginOptions = $routes->hasNamedRoute('webauthn.login.options');
            $hasLoginVerify = $routes->hasNamedRoute('webauthn.login.verify');
            $hasRegisterOptions = $routes->hasNamedRoute('webauthn.register.options');
            $hasRegisterVerify = $routes->hasNamedRoute('webauthn.register.verify');
            
            return $hasLoginOptions && $hasLoginVerify && $hasRegisterOptions && $hasRegisterVerify;
        });
    }

    /**
     * Check if the JavaScript assets are published
     */
    private function checkJavaScriptAssets()
    {
        $this->components->task('Checking WebAuthn JavaScript assets', function () {
            $vendorJsExists = File::exists(public_path('vendor/webauthn/webauthn.js'));
            $customJsExists = File::exists(public_path('js/webauthn-handler.js'));
            
            return $vendorJsExists && $customJsExists;
        });
        
        if (!File::exists(public_path('vendor/webauthn/webauthn.js'))) {
            $this->warn('  WebAuthn vendor JavaScript file is missing. Run: php artisan vendor:publish --tag=webauthn-assets');
        }
        
        if (!File::exists(public_path('js/webauthn-handler.js'))) {
            $this->warn('  Custom WebAuthn handler JavaScript file is missing.');
        }
    }
}
