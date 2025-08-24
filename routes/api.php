<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\N8nNotificationController;
use App\Http\Controllers\WebsiteRefreshController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
