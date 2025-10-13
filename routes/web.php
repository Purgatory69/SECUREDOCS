<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Dashboard;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\FileVersionController;
use App\Http\Controllers\BlockchainTestController;
use App\Http\Controllers\BlockchainController;
use App\Http\Controllers\WebAuthnController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PermanentStorageController;
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
Route::get('/webauthn/login', function () {
    return view('webauthn.login');
})->name('webauthn.login');
Route::post('/webauthn/login/options', [WebAuthnController::class, 'loginOptions'])->name('webauthn.login.options');
Route::post('/webauthn/login/verify', [WebAuthnController::class, 'loginVerify'])->name('webauthn.login.verify');

// Blockchain Storage Test Routes (Development Only - No Auth Required)
Route::prefix('blockchain-test')->group(function () {
    Route::get('/arweave', [App\Http\Controllers\BlockchainTestController::class, 'testArweave'])->name('blockchain.test.arweave');
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
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/search', [AdminController::class, 'searchUsers'])->name('users.search');
        
        // Premium management routes
        Route::post('/users/{user}/toggle-premium', [AdminController::class, 'togglePremium'])->name('users.toggle-premium');
        Route::post('/users/{user}/reset-premium', [AdminController::class, 'resetPremium'])->name('users.reset-premium');
        Route::get('/users/{user}/premium-details', [AdminController::class, 'getUserPremiumDetails'])->name('users.premium-details');
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
    
    // Separated upload endpoints
    Route::post('/files/upload/standard', [FileController::class, 'uploadStandard']);
    Route::post('/files/upload/blockchain', [FileController::class, 'uploadBlockchain']);
    Route::post('/files/upload/ai-vectorize', [FileController::class, 'uploadAiVectorize']);
    
    Route::get('/files/trash', [FileController::class, 'getTrashItems']);
    Route::post('/files/create-folder', [FileController::class, 'createFolder']);
    Route::get('/files/{id}', [FileController::class, 'show'])->whereNumber('id');
    Route::get('/files/{id}/preview', [FileController::class, 'preview'])->name('file-preview')->whereNumber('id');
    Route::delete('/files/{id}', [FileController::class, 'destroy'])->whereNumber('id');
    Route::patch('/files/{id}/restore', [FileController::class, 'restore'])->whereNumber('id');
    Route::delete('/files/{id}/force-delete', [FileController::class, 'forceDelete'])->whereNumber('id');
    Route::patch('/files/{id}/move', [FileController::class, 'move'])->whereNumber('id');
    Route::patch('/files/{id}/rename', [FileController::class, 'rename'])->whereNumber('id');
    Route::get('/files/storage-usage', [FileController::class, 'getStorageUsage']);


    // Search routes
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/search/filters', [SearchController::class, 'getSearchFilters'])->name('search.filters');

    // Blockchain routes (authenticated)
    Route::prefix('blockchain')->group(function () {
        Route::get('/storage-info', [BlockchainController::class, 'getStorageInfo'])->name('blockchain.storage-info');
        Route::get('/providers', [BlockchainController::class, 'getProviders'])->name('blockchain.providers');
        Route::get('/stats', [BlockchainController::class, 'getStats'])->name('blockchain.stats');
        Route::get('/files', [BlockchainController::class, 'getFiles'])->name('blockchain.files');
        Route::get('/files/{file}/history', [BlockchainController::class, 'getFileHistory'])->name('blockchain.file.history');
        Route::post('/upload', [BlockchainController::class, 'upload'])->name('blockchain.upload');
        Route::post('/unpin/{file}', [BlockchainController::class, 'unpinFile'])->name('blockchain.unpin');
        Route::post('/unpin-by-hash', [BlockchainController::class, 'unpinByHash'])->name('unpin.hash');
    });

    // Arweave payment routes (authenticated)
    Route::prefix('arweave')->group(function () {
        Route::post('/pricing', [App\Http\Controllers\ArweavePaymentController::class, 'getUploadPricing'])->name('arweave.pricing');
        Route::post('/payment', [App\Http\Controllers\ArweavePaymentController::class, 'processPayment'])->name('arweave.payment');
        Route::get('/payments', [App\Http\Controllers\ArweavePaymentController::class, 'getPaymentHistory'])->name('arweave.payments');
        Route::get('/payment/{paymentId}', [App\Http\Controllers\ArweavePaymentController::class, 'getPaymentDetails'])->name('arweave.payment.details');
        Route::post('/verify-transaction', [App\Http\Controllers\ArweavePaymentController::class, 'verifyTransaction'])->name('arweave.verify');
    });
    Route::post('/upload-existing', [BlockchainController::class, 'uploadExistingFile'])->name('upload.existing');
    Route::post('/preflight-validation', [BlockchainController::class, 'preflightValidation'])->name('preflight');
    Route::delete('/unpin/{file}', [BlockchainController::class, 'unpinFile'])->name('unpin.file');
    Route::post('/unpin-by-hash', [BlockchainController::class, 'unpinByHash'])->name('unpin.hash');

    // Include Arweave routes
    require __DIR__.'/arweave_routes.php';

    // OLD: Permanent Storage Routes (DISABLED - use client-side instead)
    Route::prefix('permanent-storage')->group(function () {
        Route::any('/{any}', function() {
            return response()->json([
                'error' => 'Server-side permanent storage is disabled. Please use client-side Arweave uploads.',
                'redirect' => '/dashboard'
            ], 410); // Gone
        })->where('any', '.*');
    });

    // File OTP Security routes
    Route::prefix('file-otp')->group(function () {
        Route::get('/check-access', [App\Http\Controllers\FileOtpController::class, 'checkOtpAccess'])->name('file-otp.check-access');
        Route::post('/enable', [App\Http\Controllers\FileOtpController::class, 'enableOtp'])->name('file-otp.enable');
        Route::post('/disable', [App\Http\Controllers\FileOtpController::class, 'disableOtp'])->name('file-otp.disable');
        Route::post('/send', [App\Http\Controllers\FileOtpController::class, 'sendOtp'])->name('file-otp.send');
        Route::post('/verify', [App\Http\Controllers\FileOtpController::class, 'verifyOtp'])->name('file-otp.verify');
        Route::get('/status', [App\Http\Controllers\FileOtpController::class, 'getOtpStatus'])->name('file-otp.status');
    });

    // Test email routes (remove in production)
    Route::get('/test-email-verification', function () {
        $user = Auth::user();
        $user->sendEmailVerificationNotification();
        return 'Email verification sent to ' . $user->email;
    })->name('test.email-verification');

    Route::get('/test-otp-email', function () {
        $user = Auth::user();
        // Create a test OTP email
        Mail::send('emails.otp-verification', [
            'user' => $user,
            'fileName' => 'test-document.pdf',
            'otp' => '123456',
            'expiryMinutes' => 10
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('🔐 SecureDocs - Test OTP Email');
        });
        return 'Test OTP email sent to ' . $user->email;
    })->name('test.otp-email');

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
    
    // Debug route for WebAuthn configuration (remove in production)
    Route::get('/debug/webauthn-config', function () {
        return response()->json([
            'request_info' => [
                'host' => request()->getHost(),
                'url' => request()->url(),
                'scheme' => request()->getScheme(),
                'full_url' => request()->fullUrl(),
            ],
            'webauthn_config' => [
                'rp_id' => config('webauthn.relying_party.id'),
                'rp_name' => config('webauthn.relying_party.name'),
                'origins' => config('webauthn.origins'),
            ],
            'app_config' => [
                'app_url' => config('app.url'),
                'app_env' => config('app.env'),
            ],
            'env_vars' => [
                'WEBAUTHN_RP_ID' => env('WEBAUTHN_RP_ID'),
                'APP_URL' => env('APP_URL'),
            ]
        ]);
    });

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
    
    // User activity route
    Route::get('/user/activity/test', function () {
        try {
            $user = Auth::user();
            
            Log::info('Activity route called', [
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            
            $activities = \App\Models\SystemActivity::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    $icons = [
                        'auth' => '🔐',
                        'file' => '📄', 
                        'security' => '🛡️',
                        'system' => '⚙️'
                    ];
                    
                    // Special icons for blockchain activities
                    if ($activity->activity_type === 'file' && 
                        isset($activity->metadata['blockchain_provider'])) {
                        $icons['file'] = '⛓️'; // Blockchain icon for blockchain files
                    }
                    
                    // Handle metadata parsing safely first
                    $metadata = is_string($activity->metadata) ? json_decode($activity->metadata, true) : $activity->metadata;
                    $metadata = $metadata ?? [];
                    
                    $riskLevels = [
                        'low' => '✅',
                        'medium' => '⚠️', 
                        'high' => '🚨'
                    ];
                    
                    // Special handling for suspicious activities
                    if ($activity->risk_level === 'high' && 
                        isset($metadata['is_suspicious']) && 
                        $metadata['is_suspicious'] === true) {
                        $icons[$activity->activity_type] = '🚨'; // Override with warning icon
                    }
                    
                    return [
                        'description' => $activity->description,
                        'activity_type_icon' => $icons[$activity->activity_type] ?? '📋',
                        'time_ago' => $activity->created_at->diffForHumans(),
                        'location' => $metadata['location_info']['city'] ?? ($metadata['city'] ?? null),
                        'device_type' => $metadata['device_info']['device_type'] ?? ($metadata['device_type'] ?? null),
                        'risk_level' => $activity->risk_level,
                        'risk_level_icon' => $riskLevels[$activity->risk_level] ?? '❓'
                    ];
                });
                
            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'activities' => []
            ], 500);
        }
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
    
    // Profile FAQ page
    Route::get('/profile/faq', function () {
        return view('profile.faq');
    })->name('profile.faq');
    
    // Premium/Payment routes
    Route::prefix('premium')->group(function () {
        Route::get('/upgrade', [App\Http\Controllers\PaymentController::class, 'showUpgrade'])->name('premium.upgrade');
        Route::post('/create-payment-intent', [App\Http\Controllers\PaymentController::class, 'createPaymentIntent'])->name('premium.create-payment-intent');
        Route::get('/success', [App\Http\Controllers\PaymentController::class, 'success'])->name('premium.success');
        Route::get('/cancel', [App\Http\Controllers\PaymentController::class, 'cancel'])->name('premium.cancel');
        Route::get('/payment-history', [App\Http\Controllers\PaymentController::class, 'paymentHistory'])->name('premium.payment-history');
        
    });
    
    // PayMongo webhook (no auth/CSRF required - external service)
    Route::post('/webhook/paymongo', [App\Http\Controllers\PaymentController::class, 'webhook'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('webhook.paymongo');
    
    // Manual payment verification tool (for testing)
    Route::get('/debug/verify-payment/{paymentId}', function ($paymentId) {
        try {
            $payment = \App\Models\Payment::findOrFail($paymentId);
            
            // Check if user owns this payment or is admin
            if ($payment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $controller = new \App\Http\Controllers\PaymentController();
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('verifyAndCompletePayment');
            $method->setAccessible(true);
            
            $verified = $method->invoke($controller, $payment);
            
            $payment->refresh();
            
            return response()->json([
                'success' => $verified,
                'payment' => [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'paid_at' => $payment->paid_at,
                    'user_is_premium' => $payment->user->is_premium
                ],
                'message' => $verified ? 'Payment verified and user upgraded!' : 'Payment not yet completed on PayMongo'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    })->middleware('auth')->name('debug.verify-payment');
    
    // Debug route to test PayMongo connection
    Route::get('/debug/paymongo-test', function () {
        try {
            $secretKey = env('PAYMONGO_SECRET_KEY');
            $publicKey = env('PAYMONGO_PUBLIC_KEY');
            
            if (empty($secretKey) || empty($publicKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PayMongo keys not configured',
                    'secret_key_set' => !empty($secretKey),
                    'public_key_set' => !empty($publicKey)
                ]);
            }
            
            // Test API connection with checkout sessions endpoint
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.paymongo.com/v1/checkout_sessions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($secretKey . ':'),
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                // SSL configuration for development
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            return response()->json([
                'success' => $httpCode === 200,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'response' => json_decode($response, true),
                'secret_key_prefix' => substr($secretKey, 0, 10) . '...',
                'public_key_prefix' => substr($publicKey, 0, 10) . '...'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->middleware('auth');
    
    // Test checkout session creation
    Route::get('/debug/test-checkout', function () {
        try {
            $secretKey = env('PAYMONGO_SECRET_KEY');
            
            if (empty($secretKey)) {
                return response()->json(['error' => 'PayMongo secret key not configured']);
            }
            
            $data = [
                'data' => [
                    'attributes' => [
                        'send_email_receipt' => false,
                        'show_description' => true,
                        'show_line_items' => true,
                        'description' => 'Test SecureDocs Premium Subscription',
                        'cancel_url' => url('/premium/cancel'),
                        'success_url' => url('/premium/success'),
                        'payment_method_types' => ['gcash'],
                        'line_items' => [
                            [
                                'currency' => 'PHP',
                                'amount' => 29900, // ₱299.00
                                'description' => 'Test SecureDocs Premium',
                                'name' => 'Premium Test',
                                'quantity' => 1
                            ]
                        ]
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.paymongo.com/v1/checkout_sessions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($secretKey . ':'),
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $decodedResponse = json_decode($response, true);
            
            return response()->json([
                'success' => $httpCode === 200 || $httpCode === 201,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'response' => $decodedResponse,
                'checkout_url' => $decodedResponse['data']['attributes']['checkout_url'] ?? 'NOT FOUND',
                'sent_data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->middleware('auth');
    
    // Manual payment completion for testing (when webhooks don't work)
    Route::post('/debug/complete-payment/{paymentId}', function ($paymentId) {
        try {
            $payment = \App\Models\Payment::find($paymentId);
            
            if (!$payment) {
                return response()->json(['error' => 'Payment not found']);
            }
            
            if ($payment->status !== 'pending') {
                return response()->json(['error' => 'Payment is not pending']);
            }
            
            // Simulate successful payment
            $payment->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);
            
            // Grant premium access
            $user = $payment->user;
            $user->update(['is_premium' => true]);
            
            // Create subscription
            \App\Models\Subscription::create([
                'user_id' => $user->id,
                'plan_name' => 'premium',
                'status' => 'active',
                'amount' => $payment->amount,
                'currency' => 'PHP',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'auto_renew' => true
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment completed and premium access granted',
                'user_premium' => $user->fresh()->is_premium
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->middleware('auth');
    
    // Test toggle premium functionality
    Route::get('/debug/test-premium-toggle/{userId}', function ($userId) {
        try {
            $user = \App\Models\User::findOrFail($userId);
            
            // Test creating a subscription
            $subscription = \App\Models\Subscription::create([
                'user_id' => $user->id,
                'plan_name' => 'premium',
                'status' => 'active',
                'amount' => 0.00,
                'currency' => 'PHP',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'auto_renew' => false
            ]);
            
            return response()->json([
                'success' => true,
                'user' => $user->only(['id', 'name', 'email', 'is_premium']),
                'subscription' => $subscription,
                'message' => 'Test subscription created successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth');
    
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

    // Database Schema Documentation (admin only) - Excludes system tables like migrations, cache, jobs, and n8n tables
    Route::get('/db-schema', function () {
        return view('db-schema');
    })->middleware(['auth:sanctum', 'verified', \App\Http\Middleware\RoleMiddleware::class.':admin'])
      ->name('db-schema');

    // Simple Database Schema View (admin only)
    Route::get('/simple-db-schema', function () {
        return view('simple-db-schema');
    })->middleware(['auth:sanctum', 'verified', \App\Http\Middleware\RoleMiddleware::class.':admin'])->name('simple-db-schema');

    

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
