<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'api-gateway',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// CORS preflight for browser extension
Route::options('/{any}', function () {
    return response('', 200);
})->where('any', '.*');

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', function (Request $request) {
        // TODO: Implement actual authentication
        return response()->json([
            'message' => 'Login endpoint - coming soon',
            'token' => 'mock-jwt-token',
            'user' => ['id' => 1, 'name' => 'Test User']
        ]);
    });

    Route::post('/register', function (Request $request) {
        return response()->json([
            'message' => 'Registration endpoint - coming soon'
        ]);
    });

    Route::post('/logout', function (Request $request) {
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    });
});

// AutoFill main functionality
Route::middleware(['cors'])->group(function () {

    // Main autofill endpoint for browser extension
    Route::post('/autofill', function (Request $request) {
        return response()->json([
            'message' => 'AutoFill endpoint',
            'form_fields' => $request->input('form_fields', []),
            'suggested_data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890'
            ],
            'confidence' => 0.95
        ]);
    });

    // Profile management endpoints
    Route::prefix('profiles')->group(function () {
        Route::get('/', function () {
            return response()->json([
                'profiles' => [
                    ['id' => 1, 'name' => 'Personal', 'type' => 'personal'],
                    ['id' => 2, 'name' => 'Work', 'type' => 'business']
                ]
            ]);
        });

        Route::get('/{id}', function ($id) {
            return response()->json([
                'id' => $id,
                'name' => 'Personal Profile',
                'data' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com'
                ]
            ]);
        });

        Route::post('/', function (Request $request) {
            return response()->json([
                'message' => 'Profile created',
                'id' => rand(1000, 9999)
            ], 201);
        });
    });

    // Document parsing endpoints
    Route::prefix('documents')->group(function () {
        Route::post('/parse', function (Request $request) {
            return response()->json([
                'message' => 'Document parsing - forwarding to doc-parser service',
                'status' => 'processing',
                'job_id' => 'doc_' . uniqid()
            ]);
        });

        Route::get('/status/{jobId}', function ($jobId) {
            return response()->json([
                'job_id' => $jobId,
                'status' => 'completed',
                'extracted_data' => [
                    'name' => 'John Doe',
                    'date_of_birth' => '1990-01-01'
                ]
            ]);
        });
    });

    // AI analysis endpoints
    Route::prefix('ai')->group(function () {
        Route::post('/analyze', function (Request $request) {
            return response()->json([
                'message' => 'AI analysis - forwarding to ai-service',
                'form_type' => 'contact_form',
                'confidence' => 0.89,
                'suggestions' => [
                    'field_mapping' => [
                        'name_field' => 'full_name',
                        'email_field' => 'email_address'
                    ]
                ]
            ]);
        });
    });
});

// Service communication endpoints (internal)
Route::prefix('internal')->middleware(['internal'])->group(function () {
    Route::get('/services/status', function () {
        return response()->json([
            'api_gateway' => 'online',
            'profile_service' => 'online', // TODO: Check actual service
            'ai_service' => 'online',      // TODO: Check actual service
            'doc_parser' => 'online',      // TODO: Check actual service
            'auth_service' => 'online'     // TODO: Check actual service
        ]);
    });
});
