<?php

namespace App\Handlers\Stripe;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionScheduleUpdatedHandler extends BaseStripeHandler
{
    /**
     * Handle subscription schedule created or updated
     * This is triggered when a user downgrades/upgrades to take effect at period end
     */
    public static function handle($schedule): void
    {
        // Get the customer ID from the schedule
        $customerId = $schedule->customer;

        $user = User::where('stripe_customer_id', $customerId)->first();

        if (! $user) {
            Log::error('User not found for subscription schedule', [
                'schedule_id' => $schedule->id,
                'customer_id' => $customerId,
            ]);

            return;
        }

        // Get the phases to determine the pending plan
        $phases = $schedule->phases ?? [];

        if (empty($phases)) {
            Log::warning('No phases in subscription schedule', ['schedule_id' => $schedule->id]);

            return;
        }

        // The last phase is what the subscription will become
        $lastPhase = end($phases);

        // Get the price ID from the last phase
        $priceId = $lastPhase->items[0]->price ?? null;

        if (! $priceId) {
            Log::warning('No price in last phase', ['schedule_id' => $schedule->id]);

            return;
        }

        // Determine the pending plan
        $pendingPlan = self::mapStripePriceIdToPlan($priceId);

        // Get when the change will take effect
        $changeDate = isset($lastPhase->start_date) ? Carbon::createFromTimestamp($lastPhase->start_date) : null;

        if ($schedule->status === 'canceled' || $schedule->status === 'released') {
            $user->update([
                'pending_plan' => null,
                'stripe_schedule_id' => null,
            ]);
        } else {
            $user->update([
                'pending_plan' => $pendingPlan,
                'stripe_schedule_id' => $schedule->id,
            ]);
        }

        Log::info('Subscription schedule updated', [
            'user_id' => $user->id,
            'current_plan' => $user->subscription_plan,
            'pending_plan' => $pendingPlan,
            'effective_date' => $changeDate,
            'schedule_id' => $schedule->id,
        ]);
    }
}
