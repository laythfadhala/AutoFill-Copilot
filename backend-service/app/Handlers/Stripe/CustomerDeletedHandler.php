<?php

namespace App\Handlers\Stripe;

use App\Enums\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CustomerDeletedHandler extends BaseStripeHandler
{
    /**
     * Handle customer deleted event
     */
    public static function handle($customer): void
    {
        $user = User::where('stripe_customer_id', $customer->id)->first();

        if (! $user) {
            Log::warning('User not found for deleted customer', ['customer_id' => $customer->id]);

            return;
        }

        // Reset user to free plan and clear all Stripe-related data
        $user->update([
            'subscription_plan' => SubscriptionPlan::FREE->value,
            'payment_status' => null,
            'subscription_ends_at' => null,
            'pending_plan' => null,
            'stripe_customer_id' => null,
            'stripe_subscription_id' => null,
            'stripe_schedule_id' => null,
        ]);

        Log::info('Customer deleted and user data cleaned up', [
            'user_id' => $user->id,
            'customer_id' => $customer->id,
        ]);
    }
}
