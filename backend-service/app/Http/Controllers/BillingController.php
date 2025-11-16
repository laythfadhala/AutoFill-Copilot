<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class BillingController extends Controller
{
    public function subscriptions()
    {
        $user = Auth::user();
        $plans = $this->getPlans();

        return view('billing.subscriptions', compact('user', 'plans'));
    }

    private function getPlans()
    {
        // Get the most popular plan based on active subscriptions
        $mostPopularPlan = $this->getMostPopularPlan();

        return [
            SubscriptionPlan::FREE->value => [
                'name' => SubscriptionPlan::FREE->label(),
                'price' => 0,
                'tokens' => 'Limited per month',
                'features' => ['Basic AI model', 'Limited tokens based on service usage'],
                'popular' => $mostPopularPlan === SubscriptionPlan::FREE->value,
            ],
            SubscriptionPlan::PLUS->value => [
                'name' => SubscriptionPlan::PLUS->label(),
                'price' => 999, // $9.99
                'tokens' => '5M/month',
                'features' => ['Advanced AI Model', 'Faster processing'],
                'popular' => $mostPopularPlan === SubscriptionPlan::PLUS->value,
            ],
            SubscriptionPlan::PRO->value => [
                'name' => SubscriptionPlan::PRO->label(),
                'price' => 4999, // $49.99
                'tokens' => '25M/month',
                'features' => ['Everything in Plus', 'Flagship AI Model', 'Priority support'],
                'popular' => $mostPopularPlan === SubscriptionPlan::PRO->value,
            ],
        ];
    }

    /**
     * Get the most popular plan based on number of active subscriptions
     */
    private function getMostPopularPlan(): string
    {
        $popularPlan = User::where('subscription_status', 'active')
            ->whereIn('subscription_plan', [SubscriptionPlan::PLUS->value, SubscriptionPlan::PRO->value]) // Only count paid plans
            ->select('subscription_plan', DB::raw('COUNT(*) as count'))
            ->groupBy('subscription_plan')
            ->orderBy('count', 'desc')
            ->first();

        // Default to 'plus' if no paid subscription data exists yet
        if (!$popularPlan) {
            return SubscriptionPlan::PLUS->value;
        }

        return $popularPlan->subscription_plan;
    }
}
