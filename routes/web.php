<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WebAuthnController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/set-language/{language}', function ($language) {
    $validLanguages = ['en', 'fil'];
    
    if (in_array($language, $validLanguages)) {
        session(['app_locale' => $language]);
        return redirect()->back();
    }
    
    return redirect()->back();
})->name('language.switch');

// WebAuthn authentication routes
Route::post('/webauthn/login/options', [WebAuthnController::class, 'loginOptions'])->name('webauthn.login.options');
Route::post('/webauthn/login/verify', [WebAuthnController::class, 'loginVerify'])->name('webauthn.login.verify');


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Role-based redirect after login
    Route::get('/redirect-after-login', function () {
        // Middleware will handle the redirect
    })->middleware(['auth', \App\Http\Middleware\RedirectIfHasRole::class]);
    

    // Admin dashboard
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/admin/approve/{id}', [AdminController::class, 'approve'])->name('admin.approve');
    Route::post('/admin/revoke/{id}', [AdminController::class, 'revoke'])->name('admin.revoke');
    Route::post('/admin/users/{user}/premium-settings', [AdminController::class, 'updateUserPremiumSettings'])->name('admin.user.premium_settings');

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
    Route::post('/folders', [App\Http\Controllers\FileController::class, 'createFolder'])->name('folders.create');
    
    // WebAuthn routes
    Route::get('/webauthn', [WebAuthnController::class, 'index'])->name('webauthn.index');
    Route::delete('/webauthn/keys/{id}', [WebAuthnController::class, 'destroy'])->name('webauthn.keys.destroy');
    Route::post('/webauthn/register/options', [WebAuthnController::class, 'registerOptions'])->name('webauthn.register.options');
    Route::post('/webauthn/register/verify', [WebAuthnController::class, 'registerVerify'])->name('webauthn.register.verify');

    // WebAuthn-protected routes
    Route::middleware(['auth', 'auth.webauthn'])->group(function () {
        Route::get('/secure-area', function () {
            return view('secure-area');
        })->name('secure-area');
    });

    // User public info for chat widget
    Route::get('/user/{id}', [UserController::class, 'showPublic'])->name('user.show_public');
    
});