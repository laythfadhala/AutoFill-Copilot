<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook: Invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            default:
                Log::info('Stripe webhook: Unhandled event type', ['type' => $event->type]);
        }

        \Log::debug(var_export($request->all(), true));

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle checkout session completed
     */
    protected function handleCheckoutCompleted($session)
    {
        Log::info('Checkout completed', ['session' => $session]);

        // Try to find user by multiple methods (most reliable first)
        $user = null;

        // Method 1: User ID from client_reference_id (passed in URL)
        if (isset($session->client_reference_id)) {
            $user = User::find($session->client_reference_id);
            Log::info('Found user by client_reference_id', ['user_id' => $user->id ?? null]);
        }

        // Method 2: Email address (fallback)
        if (!$user) {
            $email = $session->customer_email ?? $session->customer_details->email ?? null;
            if ($email) {
                $user = User::where('email', $email)->first();
                Log::info('Found user by email', ['email' => $email, 'user_id' => $user->id ?? null]);
            }
        }

        if (!$user) {
            Log::error('User not found for checkout', ['session_id' => $session->id]);
            return;
        }

        // Determine plan from amount (since we're using direct links)
        $plan = $this->determinePlanFromAmount($session->amount_total);

        // Update user subscription
        $user->update([
            'subscription_plan' => $plan,
            'subscription_status' => 'active',
            'stripe_customer_id' => $session->customer,
            'stripe_subscription_id' => $session->subscription,
        ]);

        Log::info('User subscription activated', [
            'user_id' => $user->id,
            'email' => $email,
            'plan' => $plan,
        ]);
    }

    /**
     * Handle subscription created or updated
     */
    protected function handleSubscriptionUpdated($subscription)
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if (!$user) {
            // Try to find by customer ID
            $user = User::where('stripe_customer_id', $subscription->customer)->first();
        }

        if (!$user) {
            Log::error('User not found for subscription update', ['subscription_id' => $subscription->id]);
            return;
        }

        $status = $subscription->status; // active, past_due, canceled, etc.
        $plan = $subscription->metadata->plan ?? $this->determinePlanFromAmount($subscription->items->data[0]->price->unit_amount);

        $user->update([
            'subscription_plan' => $status === 'active' ? $plan : $user->subscription_plan,
            'subscription_status' => $status,
            'stripe_subscription_id' => $subscription->id,
        ]);

        Log::info('User subscription updated', [
            'user_id' => $user->id,
            'plan' => $plan,
            'status' => $status,
        ]);
    }

    /**
     * Handle subscription deleted/canceled
     */
    protected function handleSubscriptionDeleted($subscription)
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if (!$user) {
            Log::error('User not found for subscription deletion', ['subscription_id' => $subscription->id]);
            return;
        }

        $user->update([
            'subscription_plan' => 'free',
            'subscription_status' => 'canceled',
        ]);

        Log::info('User subscription canceled', ['user_id' => $user->id]);
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSucceeded($invoice)
    {
        $user = User::where('stripe_customer_id', $invoice->customer)->first();

        if (!$user) {
            Log::error('User not found for payment', ['customer_id' => $invoice->customer]);
            return;
        }

        Log::info('Payment succeeded', [
            'user_id' => $user->id,
            'amount' => $invoice->amount_paid / 100,
            'invoice_id' => $invoice->id,
        ]);

        // Ensure subscription is active
        if ($user->subscription_status !== 'active') {
            $user->update(['subscription_status' => 'active']);
        }
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed($invoice)
    {
        $user = User::where('stripe_customer_id', $invoice->customer)->first();

        if (!$user) {
            Log::error('User not found for failed payment', ['customer_id' => $invoice->customer]);
            return;
        }

        Log::warning('Payment failed', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        $user->update(['subscription_status' => 'past_due']);
    }

    /**
     * Determine plan from amount (in cents)
     */
    private function mapStripePriceIdToPlan(string $priceId): string
    {
        if ($priceId === env('STRIPE_PRO_PRICE_ID')) {
            return SubscriptionPlan::PRO->value;
        }
        if ($priceId === env('STRIPE_PLUS_PRICE_ID')) {
            return SubscriptionPlan::PLUS->value;
        }
        return SubscriptionPlan::FREE->value;
    }
}
