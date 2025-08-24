<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileSharingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\FileVersionController;
use App\Http\Controllers\BlockchainTestController;
use App\Http\Controllers\BlockchainController;
use App\Http\Controllers\WebAuthnController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

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

// Blockchain Storage Test Routes (Development Only - No Auth Required)
Route::prefix('blockchain-test')->group(function () {
    Route::get('/pinata', [App\Http\Controllers\BlockchainTestController::class, 'testPinata'])->name('blockchain.test.pinata');
    Route::get('/providers', [App\Http\Controllers\BlockchainTestController::class, 'getProviders'])->name('blockchain.test.providers');
    Route::get('/config', [App\Http\Controllers\BlockchainTestController::class, 'getConfig'])->name('blockchain.test.config');
    Route::post('/upload', [App\Http\Controllers\BlockchainTestController::class, 'testUpload'])->name('blockchain.test.upload');
});

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
    
    // Files routes
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files/upload', [FileController::class, 'store']);
    Route::get('/files/trash', [FileController::class, 'getTrashItems']);
    Route::post('/files/create-folder', [FileController::class, 'createFolder']);
    Route::get('/files/{id}', [FileController::class, 'show'])->whereNumber('id');
    Route::get('/files/{id}/preview', [FileController::class, 'preview'])->whereNumber('id');
    Route::delete('/files/{id}', [FileController::class, 'destroy'])->whereNumber('id');
    Route::patch('/files/{id}/restore', [FileController::class, 'restore'])->whereNumber('id');
    Route::delete('/files/{id}/force-delete', [FileController::class, 'forceDelete'])->whereNumber('id');


    // Search routes
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/search/filters', [SearchController::class, 'getSearchFilters'])->name('search.filters');
    Route::post('/search/save', [SearchController::class, 'saveSearch'])->name('search.save');
    Route::get('/search/saved', [SearchController::class, 'getSavedSearches'])->name('search.saved');
    Route::delete('/search/saved/{id}', [SearchController::class, 'deleteSavedSearch'])->name('search.delete');

    // Blockchain routes (authenticated)
    Route::prefix('blockchain')->name('blockchain.')->group(function () {
        Route::get('/providers', [BlockchainController::class, 'getProviders'])->name('providers');
        Route::get('/stats', [BlockchainController::class, 'getStats'])->name('stats');
        Route::get('/files', [BlockchainController::class, 'getFiles'])->name('files');
        Route::post('/upload', [BlockchainController::class, 'upload'])->name('upload');
        Route::delete('/unpin/{file}', [BlockchainController::class, 'unpinFile'])->name('unpin.file');
        Route::post('/unpin-by-hash', [BlockchainController::class, 'unpinByHash'])->name('unpin.hash');
    });

    // File version and history routes
    Route::get('/files/{file}/versions', [FileVersionController::class, 'getVersionHistory'])->name('files.versions');
    Route::get('/files/{file}/activity', [FileVersionController::class, 'getActivityTimeline'])->name('files.activity');
    Route::post('/files/{file}/versions', [FileVersionController::class, 'createVersion'])->name('files.versions.create');
    Route::post('/files/{file}/versions/{version}/restore', [FileVersionController::class, 'restoreVersion'])->name('files.versions.restore');
    Route::get('/files/{file}/versions/{version}/download', [FileVersionController::class, 'downloadVersion'])->name('files.version.download');
    Route::delete('/files/{file}/versions/{version}', [FileVersionController::class, 'deleteVersion'])->name('files.versions.delete');


    // Activity Tracking & Audit Logs routes
    Route::get('/activities', [App\Http\Controllers\ActivityController::class, 'getUserActivities'])->name('activities.user');
    Route::get('/activities/dashboard-stats', [App\Http\Controllers\ActivityController::class, 'getDashboardStats'])->name('activities.dashboard.stats');
    Route::get('/activities/timeline', [App\Http\Controllers\ActivityController::class, 'getActivityTimeline'])->name('activities.timeline');
    Route::get('/activities/export', [App\Http\Controllers\ActivityController::class, 'exportActivities'])->name('activities.export');
    Route::get('/files/{file}/activities', [App\Http\Controllers\ActivityController::class, 'getFileActivities'])->name('files.activities');
    
    // User sessions management routes
    Route::get('/sessions', [App\Http\Controllers\ActivityController::class, 'getUserSessions'])->name('sessions.user');
    Route::delete('/sessions/{session}', [App\Http\Controllers\ActivityController::class, 'revokeSession'])->name('sessions.revoke');
    
    // Security events routes
    Route::get('/security-events', [App\Http\Controllers\ActivityController::class, 'getSecurityEvents'])->name('security.events');
    
    // System analytics (admin-only)
    Route::get('/admin/analytics', [App\Http\Controllers\ActivityController::class, 'getSystemAnalytics'])->name('admin.analytics');


    // Security routes
    Route::prefix('security')->name('security.')->group(function () {
        // Security policies
        Route::get('/policies', [App\Http\Controllers\SecurityController::class, 'getPolicies'])->name('policies.index');
        Route::post('/policies', [App\Http\Controllers\SecurityController::class, 'createPolicy'])->name('policies.create');
        Route::put('/policies/{policy}', [App\Http\Controllers\SecurityController::class, 'updatePolicy'])->name('policies.update');
        Route::delete('/policies/{policy}', [App\Http\Controllers\SecurityController::class, 'deletePolicy'])->name('policies.delete');
        
        // Security violations
        Route::get('/violations', [App\Http\Controllers\SecurityController::class, 'getViolations'])->name('violations.index');
        Route::put('/violations/{violation}/resolve', [App\Http\Controllers\SecurityController::class, 'resolveViolation'])->name('violations.resolve');
        
        // Trusted devices
        Route::get('/devices', [App\Http\Controllers\SecurityController::class, 'getTrustedDevices'])->name('devices.index');
        Route::post('/devices/trust', [App\Http\Controllers\SecurityController::class, 'trustDevice'])->name('devices.trust');
        Route::put('/devices/{device}/revoke', [App\Http\Controllers\SecurityController::class, 'revokeDevice'])->name('devices.revoke');
        
        // File encryption
        Route::get('/encryption', [App\Http\Controllers\SecurityController::class, 'getFileEncryption'])->name('encryption.index');
        Route::post('/files/{file}/encrypt', [App\Http\Controllers\SecurityController::class, 'encryptFile'])->name('files.encrypt');
        Route::post('/encryption/{encryption}/rotate-key', [App\Http\Controllers\SecurityController::class, 'rotateEncryptionKey'])->name('encryption.rotate-key');
        
        // DLP scans
        Route::get('/dlp-scans', [App\Http\Controllers\SecurityController::class, 'getDlpScans'])->name('dlp.index');
        Route::put('/dlp-scans/{scan}/review', [App\Http\Controllers\SecurityController::class, 'reviewDlpScan'])->name('dlp.review');
        
        // Security dashboard
        Route::get('/stats', [App\Http\Controllers\SecurityController::class, 'getSecurityStats'])->name('stats');
    });

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

    // Debug blockchain endpoints (temporary)
    Route::get('/debug/blockchain/stats', [App\Http\Controllers\DebugController::class, 'debugBlockchainStats'])->name('debug.blockchain.stats');
    Route::get('/debug/blockchain/files', [App\Http\Controllers\DebugController::class, 'debugBlockchainFiles'])->name('debug.blockchain.files');

    // File proxy for CORS-free access
    Route::get('/file-proxy/{id}', [FileController::class, 'proxyFile'])->whereNumber('id')->name('file.proxy');
    
    // User public info for chat widget
    Route::get('/user/{id}', [UserController::class, 'showPublic'])->name('user.show_public');
    
});