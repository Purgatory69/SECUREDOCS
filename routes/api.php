<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\N8nNotificationController;
use App\Http\Controllers\WebsiteRefreshController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SchemaController;
use App\Http\Controllers\PermanentStorageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/ai/categorization-status', [FileController::class, 'getCategorizationStatus']);
});

// n8n Notification API Routes
Route::prefix('n8n')->group(function () {
    // Main endpoint for receiving notifications from n8n
    Route::post('/notification', [N8nNotificationController::class, 'handleNotification']);
    
    // Status endpoint to check if the API is working
    Route::get('/status', [N8nNotificationController::class, 'getStatus']);
    
    // Test endpoint for debugging
    Route::get('/test', [N8nNotificationController::class, 'test']);
});

// Website Refresh API Routes
Route::prefix('refresh')->group(function () {
    // Get last refresh event
    Route::get('/event', [WebsiteRefreshController::class, 'getRefreshEvent']);
    
    // Manual refresh trigger
    Route::post('/trigger', [WebsiteRefreshController::class, 'triggerRefresh']);
    
    // Check for file changes
    Route::post('/check-files', [WebsiteRefreshController::class, 'checkFileChanges']);
    
    // Server-Sent Events for real-time refresh
    Route::get('/sse', [WebsiteRefreshController::class, 'sseRefreshEvents']);
});

// Vectorization completion webhook
Route::post('/vectorization-complete', [FileController::class, 'handleVectorizationComplete']);

// AI Categorization endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ai/categorize-start', [FileController::class, 'startAICategorization']);
    Route::get('/ai/categorization-status', [FileController::class, 'getCategorizationStatus']);
});

// Public categorization status endpoint (no auth required for initial check)
Route::get('/ai/categorization-status-public', [FileController::class, 'getCategorizationStatusPublic']);

// AI status update endpoint (for AI to call)
Route::post('/ai/categorization-update', [FileController::class, 'updateCategorizationStatus']);

// Database schema (live) endpoint (admin-only via Sanctum)
Route::get('/db-schema', [SchemaController::class, 'get'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('api.db-schema');

// Permanent Storage API Routes (Premium users only)
Route::middleware(['auth'])->prefix('permanent-storage')->group(function () {
    Route::post('/calculate-cost', [PermanentStorageController::class, 'calculateCost']);
    Route::post('/create-payment', [PermanentStorageController::class, 'createPayment']);
    Route::get('/payment-status/{paymentId}', [PermanentStorageController::class, 'checkPaymentStatus']);
    Route::post('/upload', [PermanentStorageController::class, 'uploadToArweave']);
    Route::get('/history', [PermanentStorageController::class, 'getHistory']);
    Route::get('/supported-options', [PermanentStorageController::class, 'getSupportedOptions']);
});
