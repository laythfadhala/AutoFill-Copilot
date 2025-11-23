<?php

namespace App\Handlers\Stripe;

use App\Enums\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SubscriptionDeletedHandler extends BaseStripeHandler
{
    /**
     * Handle subscription deleted/canceled
     */
    public static function handle($subscription): void
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if (! $user) {
            Log::error('User not found for subscription deletion', ['subscription_id' => $subscription->id]);

            return;
        }
        $newPlan = SubscriptionPlan::FREE->value;

        $user->update([
            'subscription_plan' => $newPlan,
            'subscription_ends_at' => null,
            'stripe_subscription_id' => null,
            'payment_status' => null,
            'pending_plan' => null,
            'stripe_schedule_id' => null,
        ]);

        Log::info('User subscription canceled', [
            'user_id' => $user->id,
            'new_plan' => $newPlan,
        ]);
    }
}
