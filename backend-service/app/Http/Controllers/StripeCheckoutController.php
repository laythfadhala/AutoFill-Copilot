<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripeCheckoutController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:plus,pro',
        ]);

        $user = Auth::user();
        $plan = $request->plan;

        $priceIds = [
            'plus' => env('STRIPE_PLUS_PRICE_ID'),
            'pro' => env('STRIPE_PRO_PRICE_ID'),
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
}
