<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StripeWebhookController;
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

// Stripe webhook (must be outside auth middleware and CSRF protection)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Public authentication routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
    });

    Route::prefix('forms')->middleware('subscription.limits:tokens')->group(function () {
        Route::post('/fill', [FormController::class, 'fill']);
    });

    Route::prefix('fields')->middleware('subscription.limits:tokens')->group(function () {
        Route::post('/fill', [FieldController::class, 'fill']);
    });

    Route::prefix('profiles')->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
    });
});

