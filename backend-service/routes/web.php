<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/signin', [AuthController::class, 'showSigninForm'])->name('login');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/auth/microsoft', [AuthController::class, 'redirectToMicrosoft'])->name('microsoft.login');
Route::get('/auth/microsoft/callback', [AuthController::class, 'handleMicrosoftCallback']);
