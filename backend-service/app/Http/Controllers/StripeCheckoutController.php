<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;
use Stripe\Stripe;

class StripeCheckoutController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:'.SubscriptionPlan::PLUS->value.','.SubscriptionPlan::PRO->value,
        ]);

        $user = Auth::user();
        $plan = $request->plan;

        $priceIds = [
            SubscriptionPlan::PLUS->value => config('services.stripe.plus_price_id'),
            SubscriptionPlan::PRO->value => config('services.stripe.pro_price_id'), ];

        if (! $priceIds[$plan]) {
            return redirect()->route('billing.subscriptions')
                ->with('error', 'Plan not configured.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        try {
            // Use Billing Portal Session for existing subscriptions
            if ($user->stripe_subscription_id) {
                $session = BillingPortalSession::create([
                    'customer' => $user->stripe_customer_id,
                    'return_url' => route('billing.subscriptions', [], true),
                    'flow_data' => [
                        'type' => 'subscription_update',
                        'subscription_update' => [
                            'subscription' => $user->stripe_subscription_id,
                        ],
                    ],
                ]);
            } else {

                // Create Stripe Customer if not exists
                if (! $user->stripe_customer_id) {
                    $stripeCustomer = Customer::create([
                        'email' => $user->email,
                    ]);
                    $user->stripe_customer_id = $stripeCustomer->id;
                    $user->save();
                }

                // Create Checkout Session for new subscriptions
                $session = CheckoutSession::create([
                    'customer' => $user->stripe_customer_id,
                    'customer_update' => [
                        'name' => 'auto',
                        'address' => 'auto',
                    ],
                    'client_reference_id' => (string) $user->id, // User ID for webhook lookup
                    'line_items' => [[
                        'price' => $priceIds[$plan],
                        'quantity' => 1,
                    ]],
                    'mode' => 'subscription',
                    'success_url' => route('billing.subscriptions', ['success' => 1], true),
                    'cancel_url' => route('billing.subscriptions', [], true),
                    'billing_address_collection' => 'required',
                    'automatic_tax' => ['enabled' => true],
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan' => $plan,
                        'email' => $user->email,
                    ],
                    'tax_id_collection' => [
                        'enabled' => true,
                    ],
                ]);
            }

            return redirect($session->url);
        } catch (Exception $e) {
            Log::error('Stripe checkout error', ['error' => $e->getMessage()]);

            return redirect()->route('billing.subscriptions')
                ->with('error', 'Failed to create checkout. Please try again.');
        }
    }

    public function createPortalSession(Request $request)
    {
        $user = Auth::user();

        if (! $user->stripe_customer_id) {
            return redirect()->route('billing.subscriptions')
                ->with('error', 'No subscription found.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = BillingPortalSession::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => route('billing.subscriptions', [], true),
            ]);

            return redirect($session->url);
        } catch (Exception $e) {
            Log::error('Stripe portal error', ['error' => $e->getMessage()]);

            return redirect()->route('billing.subscriptions')
                ->with('error', 'Failed to access billing portal. Please try again.');
        }
    }
}
