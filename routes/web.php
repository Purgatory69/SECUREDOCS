<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UserController::class , 'index']) ->name('home');
Route::get('/login', [UserController::class, 'login1'])->name('login');
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [UserController::class, 'showForgotPasswordForm'])->name('password.request');

Route::post('/register', [UserController::class, 'store'])->name('register');
Route::get('/register', [UserController::class, 'create'])->name('register.form');

Route::get('/table', [UserController::class, 'showTable'])->name('table');

Route::post('/webhook/{uuid}/chat', function ($uuid) {
    // Validate UUID and handle webhook
    return response()->json(['status' => 'received']);
});

Route::middleware(['auth'])->group(function () {
    
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
