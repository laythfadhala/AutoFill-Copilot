<?php

namespace App\Http\Controllers;

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

        // Get dynamic free token limit for display
        $freeTokenLimit = User::first()?->getDynamicFreeTokenLimit() ?? 100000;
        $freeTokensFormatted = $freeTokenLimit >= 1000
            ? ($freeTokenLimit / 1000) . 'K'
            : $freeTokenLimit;

        return [
            'free' => [
                'name' => 'Free',
                'price' => 0,
                'tokens' => $freeTokensFormatted . '/month',
                'profiles' => 1,
                'documents' => 10,
                'features' => ['Basic AI model', 'Limited token usage'],
                'popular' => $mostPopularPlan === 'free',
            ],
            'plus' => [
                'name' => 'Plus',
                'price' => 999, // $9.99
                'tokens' => '5M/month',
                'profiles' => 10,
                'documents' => 50,
                'features' => ['Advanced AI Model', 'Faster processing'],
                'popular' => $mostPopularPlan === 'plus',
            ],
            'pro' => [
                'name' => 'Professional',
                'price' => 4999, // $49.99
                'tokens' => '25M/month',
                'profiles' => 'Unlimited',
                'documents' => 'Unlimited',
                'features' => ['Everything in Plus', 'Flagship AI Model', 'Priority support'],
                'popular' => $mostPopularPlan === 'pro',
            ],
        ];
    }

    /**
     * Get the most popular plan based on number of active subscriptions
     */
    private function getMostPopularPlan(): string
    {
        $popularPlan = User::where('subscription_status', 'active')
            ->whereIn('subscription_plan', ['plus', 'pro']) // Only count paid plans
            ->select('subscription_plan', DB::raw('COUNT(*) as count'))
            ->groupBy('subscription_plan')
            ->orderBy('count', 'desc')
            ->first();

        // Default to 'plus' if no paid subscription data exists yet
        if (!$popularPlan) {
            return 'plus';
        }

        return $popularPlan->subscription_plan;
    }
}
