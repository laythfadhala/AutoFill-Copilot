<?php

namespace App\Http\Controllers\Api;

use App\Handlers\Stripe\CheckoutCompletedHandler;
use App\Handlers\Stripe\CustomerDeletedHandler;
use App\Handlers\Stripe\PaymentSucceededHandler;
use App\Handlers\Stripe\SubscriptionCreatedHandler;
use App\Handlers\Stripe\SubscriptionDeletedHandler;
use App\Handlers\Stripe\SubscriptionScheduleUpdatedHandler;
use App\Handlers\Stripe\SubscriptionUpdatedHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

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
            // Checkout events
            case 'checkout.session.completed':
                CheckoutCompletedHandler::handle($event->data->object);
                break;

                // Subscription lifecycle events
            case 'customer.subscription.created':
                SubscriptionCreatedHandler::handle($event->data->object);
                break;
            case 'customer.subscription.updated':
                SubscriptionUpdatedHandler::handle($event->data->object);
                break;
            case 'customer.subscription.deleted':
                SubscriptionDeletedHandler::handle($event->data->object);
                break;

                // Customer events
            case 'customer.deleted':
                CustomerDeletedHandler::handle($event->data->object);
                break;

                // Subscription schedule events (for plan changes at period end)
            case 'subscription_schedule.updated':
                SubscriptionScheduleUpdatedHandler::handle($event->data->object);
                break;

                // Invoice and payment events
            case 'invoice.payment_succeeded':
                PaymentSucceededHandler::handle($event->data->object);
                break;

            default:
        }

        return response()->json(['status' => 'success']);
    }
}
