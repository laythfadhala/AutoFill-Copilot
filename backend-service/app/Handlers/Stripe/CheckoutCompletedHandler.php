<?php

namespace App\Handlers\Stripe;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CheckoutCompletedHandler extends BaseStripeHandler
{
    /**
     * Handle checkout session completed
     */
    public static function handle($session): void
    {
        Log::info('Checkout completed', ['session_id' => $session->id, 'mode' => $session->mode]);

        // Method 1: User ID from client_reference_id (most reliable - dedicated stripe field)
        if (isset($session->client_reference_id)) {
            $user = User::find($session->client_reference_id);
            Log::info('Found user by client_reference_id', ['user_id' => $user->id ?? null]);
        }

        // Method 2: User ID from metadata (fallback)
        if (! $user && isset($session->metadata->user_id)) {
            $user = User::find($session->metadata->user_id);
            Log::info('Found user by metadata user_id', ['user_id' => $user->id ?? null]);
        }

        // Method 3: Email address (final fallback)
        if (! $user) {
            $email = $session->customer_email ?? $session->customer_details->email ?? null;
            if ($email) {
                $user = User::where('email', $email)->first();
                Log::info('Found user by email', ['email' => $email, 'user_id' => $user->id ?? null]);
                // TODO: This should be reported if it happens
            }
        }

        if (! $user) {
            Log::error('User not found for checkout', [
                'session_id' => $session->id,
                'customer_email' => $session->customer_email ?? $session->customer_details->email ?? null,
            ]);

            return;
        }

        // Security: Verify email matches (in case of ID spoofing, though unlikely with server-side creation)
        $sessionEmail = $session->customer_email ?? $session->customer_details->email ?? null;
        if ($sessionEmail && strtolower($user->email) !== strtolower($sessionEmail)) {
            Log::error('Email mismatch in checkout', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'session_email' => $sessionEmail,
                'session_id' => $session->id,
            ]);

            return;
        }

        // Determine plan from metadata or amount
        $plan = $session->metadata->plan ?? self::determinePlanFromAmount($session->amount_total);

        // Update user subscription
        $user->update([
            'subscription_plan' => $plan,
            'payment_status' => $session->payment_status,
            'stripe_subscription_id' => $session->subscription,
        ]);

        Log::info('User subscription activated via checkout', [
            'user_id' => $user->id,
            'email' => $user->email,
            'plan' => $plan,
            'payment_status' => $session->payment_status,
        ]);
    }
}
