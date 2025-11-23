<?php

namespace App\Handlers\Stripe;

use App\Enums\SubscriptionPlan;

abstract class BaseStripeHandler
{
    /**
     * Handle the Stripe event
     */
    abstract public static function handle($eventData): void;

    /**
     * Determine plan from amount (in cents)
     */
    protected static function determinePlanFromAmount($amount): string
    {
        if ($amount >= SubscriptionPlan::PRO->priceInCents()) {
            return SubscriptionPlan::PRO->value;
        } elseif ($amount >= SubscriptionPlan::PLUS->priceInCents()) {
            return SubscriptionPlan::PLUS->value;
        }

        return SubscriptionPlan::FREE->value;
    }

    /**
     * Map Stripe price ID to plan
     */
    protected static function mapStripePriceIdToPlan(string $priceId): string
    {
        if ($priceId === config('services.stripe.pro_price_id')) {
            return SubscriptionPlan::PRO->value;
        }
        if ($priceId === config('services.stripe.plus_price_id')) {
            return SubscriptionPlan::PLUS->value;
        }

        return SubscriptionPlan::FREE->value;
    }

    /**
     * Check if the plan change is a downgrade
     */
    protected static function isDowngrade(?string $currentPlan, string $newPlan): bool
    {
        $planHierarchy = [
            SubscriptionPlan::FREE->value => 0,
            SubscriptionPlan::PLUS->value => 1,
            SubscriptionPlan::PRO->value => 2,
        ];

        $currentLevel = $planHierarchy[$currentPlan] ?? 0;
        $newLevel = $planHierarchy[$newPlan] ?? 0;

        return $newLevel < $currentLevel;
    }
}
