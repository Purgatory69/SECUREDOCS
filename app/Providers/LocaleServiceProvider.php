<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\App;

class LocaleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // This runs before every view is rendered
        View::composer('*', function ($view) {
            $sessionLanguage = session('app_locale');
            if ($sessionLanguage && in_array($sessionLanguage, ['en', 'fil'])) {
                App::setLocale($sessionLanguage);
                config(['app.locale' => $sessionLanguage]);
            }
        });
    }

    public function register()
    {
        //
    }
}