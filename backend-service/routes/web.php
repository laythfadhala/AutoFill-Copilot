<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\StripeCheckoutController;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('user.settings');
    Route::get('/account', [AccountController::class, 'manage'])->name('account.manage');
    Route::get('/billing', [BillingController::class, 'subscriptions'])->name('billing.subscriptions');
    Route::post('/stripe/checkout', [StripeCheckoutController::class, 'createCheckoutSession'])->name('stripe.checkout');
    Route::post('/stripe/portal', [StripeCheckoutController::class, 'createPortalSession'])->name('stripe.portal');
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

// Test form route for extension testing
Route::get('/test-form', function () {
    return view('test-form');
})->name('test.form');
