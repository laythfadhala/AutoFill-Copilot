<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProfileController;
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

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Public authentication routes
Route::prefix('auth')->group(function () {
    // Protected authentication routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
    });
});

// Protected routes - require authentication
Route::middleware('auth:sanctum')->group(function () {

    // User Management
    Route::apiResource('users', UserController::class);

    // User Profile Management
    Route::apiResource('user-profiles', UserProfileController::class);
    Route::get('users/{user}/default-profile', [UserProfileController::class, 'getDefault']);

    // Form Mapping Management removed

    // Legacy Profile management endpoints redirected to user-profiles resource
    Route::prefix('profiles')->group(function () {
        Route::get('/', [UserProfileController::class, 'index']);
        Route::post('/', [UserProfileController::class, 'store']);
    });

    // AutoFill endpoints removed — feature deprecated and controller deleted
});
