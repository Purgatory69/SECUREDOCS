<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});



Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    });
    // Bucket test route
    Route::get('/bucket-test', function () {
        return view('bucket-test');
    })->name('bucket.test');
    
    // File routes
    Route::post('/files', [App\Http\Controllers\FileController::class, 'store'])->name('files.store');
    Route::get('/files', [App\Http\Controllers\FileController::class, 'index'])->name('files.index');
    Route::get('/files/{id}', [App\Http\Controllers\FileController::class, 'show'])->name('files.show');
    Route::delete('/files/{id}', [App\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');
});
