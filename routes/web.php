<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

// WebAuthn authentication routes
Route::post('/webauthn/login/options', [App\Http\Controllers\WebAuthnController::class, 'loginOptions'])->name('webauthn.login.options');
Route::post('/webauthn/login/verify', [App\Http\Controllers\WebAuthnController::class, 'loginVerify'])->name('webauthn.login.verify');



Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Role-based redirect after login
    Route::get('/redirect-after-login', function () {
        // Middleware will handle the redirect
    })->middleware(['auth', 'redirect.role']);
    Route::get('/dashboard', function () {
        return view('dashboard');
    });

    // Admin dashboard
    Route::get('/admin/dashboard', function () {
        return view('admin-dashboard');
    })->name('admin.dashboard');

    // Record Admin dashboard
    Route::get('/record-admin/dashboard', function () {
        return view('record-admin-dashboard');
    })->name('record-admin.dashboard');

    // User dashboard
    Route::get('/user/dashboard', function () {
        return view('user-dashboard');
    })->name('user.dashboard');

    // Bucket test route
    Route::get('/bucket-test', function () {
        return view('bucket-test');
    })->name('bucket.test');
    
    // File routes
    Route::post('/files', [App\Http\Controllers\FileController::class, 'store'])->name('files.store');
    Route::get('/files', [App\Http\Controllers\FileController::class, 'index'])->name('files.index');
    Route::get('/files/{id}', [App\Http\Controllers\FileController::class, 'show'])->name('files.show');
    Route::delete('/files/{id}', [App\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');
    
    // WebAuthn routes
    Route::get('/webauthn', [App\Http\Controllers\WebAuthnController::class, 'index'])->name('webauthn.index');
    Route::delete('/webauthn/keys/{id}', [App\Http\Controllers\WebAuthnController::class, 'destroy'])->name('webauthn.keys.destroy');

    // WebAuthn registration routes
    Route::get('/webauthn/register', [App\Http\Controllers\WebAuthnController::class, 'registerShow'])->name('webauthn.register');
    Route::post('/webauthn/register/options', [App\Http\Controllers\WebAuthnController::class, 'registerOptions'])->name('webauthn.register.options');
    Route::post('/webauthn/register/verify', [App\Http\Controllers\WebAuthnController::class, 'registerVerify'])->name('webauthn.register.verify');

    // WebAuthn login routes
    Route::post('/webauthn/login/options', [App\Http\Controllers\WebAuthnController::class, 'loginOptions'])->name('webauthn.login.options');
    Route::post('/webauthn/login/verify', [App\Http\Controllers\WebAuthnController::class, 'loginVerify'])->name('webauthn.login.verify');

    // WebAuthn-protected routes
    Route::middleware(['auth', 'auth.webauthn'])->group(function () {
        Route::get('/secure-area', function () {
            return view('secure-area');
        })->name('secure-area');
    });
});
