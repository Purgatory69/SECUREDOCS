<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        // Manual lockout notification in Fortify authentication pipeline
        \Laravel\Fortify\Fortify::authenticateUsing(function ($request) {
            $email = $request->input('email');
            $user = \App\Models\User::where('email', $email)->first();

            // Use same key as in RateLimiter
            $key = $email . $request->ip();
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
                if ($user) {
                    $user->notify(new \App\Notifications\TooManyLoginAttempts());
                }
                return null; // User is locked out, do not authenticate
            }

            if ($user && \Illuminate\Support\Facades\Hash::check($request->input('password'), $user->password)) {
                return $user;
            }

            return null;
        });

        // Custom login redirect based on user role
        app()->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            function () {
                return new class implements \Laravel\Fortify\Contracts\LoginResponse {
                    public function toResponse($request)
                    {
                        $user = $request->user();
                        if ($user->isAdmin()) {
                            return redirect()->intended('/admin/dashboard');
                        } elseif ($user->isRecordAdmin()) {
                            return redirect()->intended('/record-admin/dashboard');
                        } else {
                            return redirect()->intended('/user/dashboard');
                        }
                    }
                };
            }
        );
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(1)->by($request->email.$request->ip());
        });

        RateLimiter::for('custom-login', function ($request) {
            return Limit::perMinute(1)->by($request->email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
