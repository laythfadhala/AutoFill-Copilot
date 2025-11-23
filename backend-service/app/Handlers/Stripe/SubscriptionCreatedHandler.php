<?php

namespace App\Handlers\Stripe;

use App\Enums\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionCreatedHandler extends BaseStripeHandler
{
    /**
     * Handle subscription created
     */
    public static function handle($subscription): void
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if (! $user) {
            // Try to find by customer ID
            $user = User::where('stripe_customer_id', $subscription->customer)->first();
        }

        if (! $user) {
            Log::error('User not found for subscription creation', ['subscription_id' => $subscription->id]);

            return;
        }

        // Determine plan from price ID (most reliable) or metadata or amount
        $plan = SubscriptionPlan::FREE->value;
        if (isset($subscription->metadata->plan)) {
            $plan = $subscription->metadata->plan;
        } elseif (isset($subscription->items->data[0]->price->id)) {
            $plan = self::mapStripePriceIdToPlan($subscription->items->data[0]->price->id);
        } elseif (isset($subscription->items->data[0]->price->unit_amount)) {
            $plan = self::determinePlanFromAmount($subscription->items->data[0]->price->unit_amount);
        }

        $updateData = [
            'subscription_status' => $subscription->status,
            'stripe_subscription_id' => $subscription->id,
            'subscription_plan' => $plan,
            'subscription_ends_at' => null,
        ];

        if ($subscription->cancel_at) {
            $updateData['subscription_ends_at'] = Carbon::createFromTimestamp($subscription->cancel_at ?? $subscription->current_period_end);
        }

        $user->update($updateData);

        Log::info('User subscription created', [
            'user_id' => $user->id,
            'plan' => $plan,
            'status' => $subscription->status,
        ]);
    }
}
