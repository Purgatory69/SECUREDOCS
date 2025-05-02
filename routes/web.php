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


Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    });
});





