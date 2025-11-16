<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripeCheckoutController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:' . SubscriptionPlan::PLUS->value . ',' . SubscriptionPlan::PRO->value,
        ]);

        $user = Auth::user();
        $plan = $request->plan;

        $priceIds = [
            SubscriptionPlan::PLUS->value => env('STRIPE_PLUS_PRICE_ID'),
            SubscriptionPlan::PRO->value => env('STRIPE_PRO_PRICE_ID'),
        ];

        if (!$priceIds[$plan]) {
            return redirect()->route('billing.subscriptions')
                ->with('error', 'Plan not configured.');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::create([
                'customer_email' => $user->email, // Locks the email field
                'line_items' => [[
                    'price' => $priceIds[$plan],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => route('billing.subscriptions', ['success' => 1], true),
                'cancel_url' => route('billing.subscriptions', [], true),
                'billing_address_collection' => 'auto',
                'automatic_tax' => ['enabled' => true],
                'metadata' => [
                    'user_id' => $user->id,
                    'plan' => $plan,
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            \Log::error('Stripe checkout error', ['error' => $e->getMessage()]);
            return redirect()->route('billing.subscriptions')
                ->with('error', 'Failed to create checkout. Please try again.');
        }
    }

    public function createPortalSession(Request $request)
    {
        $user = Auth::user();

        if (!$user->stripe_customer_id) {
            return redirect()->route('billing.subscriptions')
                ->with('error', 'No subscription found.');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => route('billing.subscriptions', [], true),
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            \Log::error('Stripe portal error', ['error' => $e->getMessage()]);
            return redirect()->route('billing.subscriptions')
                ->with('error', 'Failed to access billing portal. Please try again.');
        }
    }
}
