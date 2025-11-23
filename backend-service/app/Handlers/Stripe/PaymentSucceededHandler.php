<?php

namespace App\Handlers\Stripe;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentSucceededHandler extends BaseStripeHandler
{
    /**
     * Handle successful payment
     */
    public static function handle($invoice): void
    {
        $user = User::where('stripe_customer_id', $invoice->customer)->first();

        if (! $user) {
            Log::error('User not found for payment', ['customer_id' => $invoice->customer]);

            return;
        }

        // Ensure subscription is active
        if ($user->payment_status !== PaymentStatus::PAID->value ||
            $user->subscription_status !== SubscriptionStatus::ACTIVE->value
        ) {
            $user->update([
                'payment_status' => PaymentStatus::PAID->value,
                'subscription_status' => SubscriptionStatus::ACTIVE->value,
            ]);
        }

        Log::info('Payment succeeded', [
            'user_id' => $user->id,
            'amount' => $invoice->amount_paid / 100,
            'invoice_id' => $invoice->id,
        ]);
    }
}
