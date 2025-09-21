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
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Lightweight keepalive endpoint to prevent session/CSRF from going stale on public pages like /login
Route::get('/keepalive', function (Request $request) {
    if ($request->boolean('regen')) {
        try { $request->session()->regenerateToken(); } catch (\Throwable $t) {}
        return response()->json(['token' => csrf_token()]);
    }
    // Touch the session without leaking data
    try { $request->session()->put('_last_keepalive', now()->toISOString()); } catch (\Throwable $t) {}
    return response('', 204);
})->name('keepalive');

Route::get('/set-language/{language}', action: function ($language) {
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
    
    // Admin routes - grouped with direct middleware class protection
    Route::prefix('admin')->middleware(['auth:sanctum', 'verified', \App\Http\Middleware\RoleMiddleware::class.':admin'])->name('admin.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'usersList'])->name('users');
        Route::post('/approve/{id}', [AdminController::class, 'approve'])->name('approve');
        Route::post('/revoke/{id}', [AdminController::class, 'revoke'])->name('revoke');
        Route::post('/users/{user}/premium-settings', [AdminController::class, 'updatePremiumSettings'])->name('users.premium-settings');
        Route::get('/analytics', [\App\Http\Controllers\ActivityController::class, 'getSystemAnalytics'])->name('analytics');
        Route::get('/db-schema.json', [SchemaController::class, 'get'])->name('db-schema.json');
        // Admin JSON endpoints for metrics and predictive user search
        Route::get('/metrics/users', [AdminController::class, 'metricsUsers'])->name('metrics.users');
        Route::get('/users.json', [AdminController::class, 'usersJson'])->name('users.json');
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
    Route::get('/files/{id}/preview', [FileController::class, 'preview'])->name('file-preview')->whereNumber('id');
    Route::delete('/files/{id}', [FileController::class, 'destroy'])->whereNumber('id');
    Route::patch('/files/{id}/restore', [FileController::class, 'restore'])->whereNumber('id');
    Route::delete('/files/{id}/force-delete', [FileController::class, 'forceDelete'])->whereNumber('id');
    Route::patch('/files/{id}/move', [FileController::class, 'move'])->whereNumber('id');
    Route::get('/files/storage-usage', [FileController::class, 'getStorageUsage']);


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
        Route::get('/files/{file}/history', [BlockchainController::class, 'getFileHistory'])->name('blockchain.file.history');
        Route::post('/upload', [BlockchainController::class, 'upload'])->name('blockchain.upload');
        Route::post('/unpin/{file}', [BlockchainController::class, 'unpinFile'])->name('blockchain.unpin');
        Route::post('/unpin-by-hash', [BlockchainController::class, 'unpinByHash'])->name('unpin.hash');
    });
    Route::post('/upload-existing', [BlockchainController::class, 'uploadExistingFile'])->name('upload.existing');
    Route::post('/preflight-validation', [BlockchainController::class, 'preflightValidation'])->name('preflight');
    Route::delete('/unpin/{file}', [BlockchainController::class, 'unpinFile'])->name('unpin.file');
    Route::post('/unpin-by-hash', [BlockchainController::class, 'unpinByHash'])->name('unpin.hash');

    // File vector and blockchain management routes
    Route::delete('/files/{file}/remove-from-vector', [FileController::class, 'removeFromVector'])->name('files.remove-from-vector');
    Route::post('/files/{file}/add-to-vector', [FileController::class, 'addToVector'])->name('files.add-to-vector');
    Route::patch('/files/{file}/restore-vectors', [FileController::class, 'restoreVectors'])->name('files.restore-vectors');
    Route::delete('/files/{file}/remove-from-blockchain', [FileController::class, 'removeFromBlockchain'])->name('files.blockchain.remove');
    Route::get('/files/{file}/processing-status', [FileController::class, 'getProcessingStatus'])->name('files.processing.status');
    Route::post('/files/{file}/download-from-blockchain', [FileController::class, 'downloadFromBlockchain'])->name('files.download-from-blockchain');
    Route::post('/files/{file}/enable-permanent-storage', [FileController::class, 'enablePermanentStorage'])->name('files.enable-permanent-storage');
    Route::get('/files/processing-options', [FileController::class, 'getProcessingOptions'])->name('files.processing-options');
    Route::get('/files/storage-usage', [FileController::class, 'getStorageUsage'])->name('files.storage-usage');

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
    
    // Security features removed: security events and all /security endpoints

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
    
    // User session management routes (must come before /user/{id})
    Route::prefix('user/sessions')->group(function () {
        Route::get('/', [App\Http\Controllers\UserSessionController::class, 'index'])->name('user.sessions.index');
        Route::delete('/{sessionId}', [App\Http\Controllers\UserSessionController::class, 'terminate'])->name('user.sessions.terminate');
        Route::post('/terminate-all', [App\Http\Controllers\UserSessionController::class, 'terminateAll'])->name('user.sessions.terminate-all');
        Route::post('/{sessionId}/trust', [App\Http\Controllers\UserSessionController::class, 'trustDevice'])->name('user.sessions.trust');
    });
    
    // Simple test route for sessions
    Route::get('/user/sessions/test', function () {
        try {
            $user = Auth::user();
            $sessions = \App\Models\UserSession::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            // If no sessions exist, create a current session for testing
            if ($sessions->isEmpty()) {
                $currentSession = \App\Models\UserSession::create([
                    'user_id' => $user->id,
                    'session_id' => session()->getId(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'device_type' => 'desktop',
                    'browser' => 'Chrome',
                    'platform' => 'Windows',
                    'is_mobile' => false,
                    'is_tablet' => false,
                    'is_desktop' => true,
                    'location_country' => 'Local',
                    'location_city' => 'Development',
                    'is_active' => true,
                    'trusted_device' => true,
                    'last_activity_at' => now(),
                    'expires_at' => now()->addDays(30),
                ]);
                
                $sessions = collect([$currentSession]);
            }
                
            return response()->json([
                'success' => true,
                'count' => $sessions->count(),
                'sessions' => $sessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'session_id' => $session->session_id,
                        'device_type' => $session->device_type ?? 'unknown',
                        'browser' => $session->browser ?? 'Unknown',
                        'platform' => $session->platform ?? 'Unknown',
                        'location' => ($session->location_city && $session->location_country) 
                            ? "{$session->location_city}, {$session->location_country}" 
                            : 'Unknown location',
                        'ip_address' => $session->ip_address,
                        'is_current' => $session->session_id === session()->getId(),
                        'is_active' => $session->is_active ?? false,
                        'is_suspicious' => $session->is_suspicious ?? false,
                        'trusted_device' => $session->trusted_device ?? false,
                        'last_activity' => $session->last_activity_at?->diffForHumans() ?? 'Unknown',
                        'created_at' => $session->created_at?->diffForHumans() ?? 'Unknown',
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    });
    
    // Activity tracking routes (must come before /user/{id})
    Route::get('/user/activity', [App\Http\Controllers\UserSessionController::class, 'getRecentActivity'])->name('user.activity');
    
    // Test route to verify activity endpoint
    Route::get('/user/activity/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Activity route is working',
            'user_id' => Auth::id(),
            'activities' => []
        ]);
    });
    
    // Notification preferences routes
    Route::prefix('user/notifications')->group(function () {
        Route::get('/preferences', [App\Http\Controllers\UserSessionController::class, 'getNotificationPreferences'])->name('user.notifications.preferences');
        Route::put('/preferences', [App\Http\Controllers\UserSessionController::class, 'updateNotificationPreferences'])->name('user.notifications.update');
    });
    
    // User public info for chat widget (must be LAST among /user/ routes)
    Route::get('/user/{id}', [UserController::class, 'showPublic'])->whereNumber('id')->name('user.show_public');
    
    // Profile sessions page
    Route::get('/profile/sessions', function () {
        return view('profile.sessions');
    })->name('profile.sessions');
    
    // Debug route for testing session system
    Route::get('/debug/session-test', function () {
        try {
            $user = Auth::user();
            $sessionId = session()->getId();
            
            // Test DeviceDetectionService
            $deviceService = app(\App\Services\DeviceDetectionService::class);
            $sessions = $deviceService->getUserSessions($user, 5);
            
            return response()->json([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'session_id_length' => strlen($sessionId),
                'session_id_type' => gettype($sessionId),
                'session_name' => session()->getName(),
                'sessions_count' => $sessions->count(),
                'sessions' => $sessions->toArray(),
                'notification_prefs' => [
                    'email_notifications_enabled' => $user->email_notifications_enabled,
                    'login_notifications_enabled' => $user->login_notifications_enabled,
                    'security_notifications_enabled' => $user->security_notifications_enabled,
                    'activity_notifications_enabled' => $user->activity_notifications_enabled,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    })->name('debug.session');

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