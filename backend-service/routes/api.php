<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\FormController;
// Form mapping feature removed

/*
|--------------------------------------------------------------------------
| Backend Service Routes - Consolidated API
|--------------------------------------------------------------------------
|
| This backend service consolidates all functionality including
| authentication, profiles, and autofill in a single service.
|
*/

// CORS preflight to make browser extension happy
Route::options('/{any}', function () {
    return response('', 200);
})->where('any', '.*');

// Public authentication routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    // Protected authentication routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});

// Protected form routes
Route::middleware('auth:sanctum')->prefix('forms')->group(function () {
    Route::post('/fill', [FormController::class, 'fill']);
});

