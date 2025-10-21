<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutoFillController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\FormMappingController;

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

    // Form Mapping Management
    Route::apiResource('form-mappings', FormMappingController::class);
    Route::get('form-mappings/by-domain', [FormMappingController::class, 'getByDomain']);
    Route::post('form-mappings/{formMapping}/track-usage', [FormMappingController::class, 'trackUsage']);
    Route::get('users/{user}/popular-domains', [FormMappingController::class, 'getPopularDomains']);

    // Legacy Profile management endpoints redirected to user-profiles resource
    Route::prefix('profiles')->group(function () {
        Route::get('/', [UserProfileController::class, 'index']);
        Route::post('/', [UserProfileController::class, 'store']);
    });

    // AutoFill endpoints
    Route::post('/autofill', [AutoFillController::class, 'autofill']);
    Route::post('/autofill/analyze', [AutoFillController::class, 'analyzeForm']);
    Route::post('/autofill/analyze-ai', [AutoFillController::class, 'analyzeWithAi']);
});
