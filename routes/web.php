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
use App\Http\Controllers\SchemaController;

Route::get('/', function () {
    return view('welcome');
});

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
    
    // Admin routes - grouped with direct middleware class protection
    Route::prefix('admin')->middleware(['auth:sanctum', 'verified', \App\Http\Middleware\RoleMiddleware::class.':admin'])->name('admin.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/approve/{id}', [AdminController::class, 'approveUser'])->name('approve');
        Route::post('/revoke/{id}', [AdminController::class, 'revokeUser'])->name('revoke');
        Route::post('/users/{user}/premium-settings', [AdminController::class, 'updatePremiumSettings'])->name('users.premium-settings');
        Route::get('/analytics', [ActivityController::class, 'getSystemAnalytics'])->name('analytics');
        Route::get('/db-schema.json', [SchemaController::class, 'get'])->name('db-schema.json');
    });

    // User dashboard - protected by direct middleware class
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])
        ->middleware(['auth:sanctum', 'verified', \App\Http\Middleware\RoleMiddleware::class.':user'])
        ->name('user.dashboard');

    // Record Admin dashboard
    Route::get('/record-admin/dashboard', function () {
        return view('record-admin-dashboard');
    })->name('record-admin.dashboard');


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
    Route::prefix('blockchain')->group(function () {
        Route::get('/providers', [BlockchainController::class, 'getProviders'])->name('blockchain.providers');
        Route::get('/stats', [BlockchainController::class, 'getStats'])->name('blockchain.stats');
        Route::get('/files', [BlockchainController::class, 'getFiles'])->name('blockchain.files');
        Route::post('/upload', [BlockchainController::class, 'upload'])->name('blockchain.upload');
        Route::post('/upload-existing', [BlockchainController::class, 'uploadExistingFile'])->name('upload.existing');
        Route::post('/preflight-validation', [BlockchainController::class, 'preflightValidation'])->name('preflight');
        Route::delete('/unpin/{file}', [BlockchainController::class, 'unpinFile'])->name('unpin.file');
        Route::post('/unpin-by-hash', [BlockchainController::class, 'unpinByHash'])->name('unpin.hash');
    });

    // File vector and blockchain management routes
    Route::delete('/files/{file}/remove-from-vector', [FileController::class, 'removeFromVector'])->name('files.remove-from-vector');
    Route::post('/files/{file}/add-to-vector', [FileController::class, 'addToVector'])->name('files.add-to-vector');
    Route::patch('/files/{file}/restore-vectors', [FileController::class, 'restoreVectors'])->name('files.restore-vectors');
    Route::delete('/files/{file}/remove-from-blockchain', [FileController::class, 'removeFromBlockchain'])->name('files.blockchain.remove');
    Route::get('/files/{file}/processing-status', [FileController::class, 'getProcessingStatus'])->name('files.processing.status');
    Route::post('/files/{file}/download-from-blockchain', [FileController::class, 'downloadFromBlockchain'])->name('files.download-from-blockchain');

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

    // Database Schema Documentation (admin only)
    Route::get('/db-schema', function () {
        return view('db-schema');
    })->middleware(['auth:sanctum', 'verified', \App\Http\Middleware\RoleMiddleware::class.':admin'])->name('db-schema');

    

    // Notifications routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('unread_count');
        Route::patch('/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark_read');
        Route::patch('/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark_all_read');
        Route::post('/', [App\Http\Controllers\NotificationController::class, 'store'])->name('store');
        Route::delete('/{id}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    });
});