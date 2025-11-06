<?php

use App\Http\Controllers\ArweaveController;
use App\Http\Controllers\ArweaveUploadController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Arweave Routes - L2 Bundling Service
|--------------------------------------------------------------------------
|
| Routes for Arweave permanent storage with fiat payment integration
|
*/



// Arweave Upload Routes (Client-side Bundlr integration)
// Middleware is applied by the parent group in web.php, but we apply it explicitly here for clarity
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->prefix('arweave-upload')->group(function () {
    
    // Preflight validation before upload
    Route::post('/preflight-validation', [ArweaveUploadController::class, 'preflightValidation'])->name('arweave.upload.preflight');
    
    // Upload existing file to Arweave
    Route::post('/upload-existing', [ArweaveUploadController::class, 'uploadExistingFile'])->name('arweave.upload.existing');
    
    // Get user's Arweave uploads
    Route::get('/uploads', [ArweaveUploadController::class, 'getUserUploads'])->name('arweave.uploads');
    
    // Get upload statistics
    Route::get('/stats', [ArweaveUploadController::class, 'getUploadStats'])->name('arweave.upload.stats');
    
});

// Legacy Arweave routes (for advanced users who want direct wallet management)
// Note: Middleware is already applied by the parent group in web.php
Route::prefix('arweave')->group(function () {
    
    // Get Arweave URLs (for blockchain tab display)
    Route::get('/urls', function () {
        $user = Auth::user();
        $urls = DB::table('arweave_urls')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'urls' => $urls
        ]);
    })->name('arweave.urls');
    
    // Wallet Management (Advanced)
    Route::post('/wallet/create', [ArweaveController::class, 'createWallet'])->name('arweave.wallet.create');
    Route::get('/wallet/info', [ArweaveController::class, 'getWalletInfo'])->name('arweave.wallet.info');
    
    // Direct Upload (Advanced)
    Route::post('/files/{file}/upload', [ArweaveController::class, 'uploadToPermanentStorage'])->name('arweave.upload');
    Route::get('/files/{file}/cost-estimate', [ArweaveController::class, 'getCostEstimate'])->name('arweave.cost-estimate');
    
    // Transaction Management
    Route::get('/transactions', [ArweaveController::class, 'getTransactions'])->name('arweave.transactions');
    Route::get('/transactions/{txId}/status', [ArweaveController::class, 'getTransactionStatus'])->name('arweave.transaction.status');
    
});
