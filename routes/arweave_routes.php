<?php

use App\Http\Controllers\ArweaveController;
use App\Http\Controllers\PermanentStorageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Arweave Routes - L2 Bundling Service
|--------------------------------------------------------------------------
|
| Routes for Arweave permanent storage with fiat payment integration
|
*/

Route::middleware(['auth', 'verified'])->prefix('permanent-storage')->group(function () {
    
    // L2 Bundling Service Routes (Primary)
    Route::get('/files/{file}/quote', [PermanentStorageController::class, 'getPricingQuote'])->name('permanent.quote');
    Route::post('/files/{file}/purchase', [PermanentStorageController::class, 'purchasePermanentStorage'])->name('permanent.purchase');
    Route::get('/pricing-tiers', [PermanentStorageController::class, 'getPricingTiers'])->name('permanent.pricing');
    Route::get('/history', [PermanentStorageController::class, 'getPermanentStorageHistory'])->name('permanent.history');
    
});

// Legacy Arweave routes (for advanced users who want direct wallet management)
Route::middleware(['auth', 'verified'])->prefix('arweave')->group(function () {
    
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
