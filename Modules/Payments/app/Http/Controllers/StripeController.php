<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook: Invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'event_type' => $event->type,
            'event_id' => $event->id
        ]);

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
            case 'payment_method.attached':
                $this->handlePaymentMethodAttached($event->data->object);
                break;
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;
            default:
                Log::info('Stripe webhook: Unhandled event type', ['event_type' => $event->type]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($paymentIntent): void
    {
        $transaction = FinancialTransaction::where('external_transaction_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            Log::info('Payment succeeded', [
                'transaction_id' => $transaction->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent): void
    {
        $transaction = FinancialTransaction::where('external_transaction_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'failed',
                'processed_at' => now(),
            ]);

            Log::info('Payment failed', [
                'transaction_id' => $transaction->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount
            ]);
        }
    }

    /**
     * Handle payment method attached
     */
    private function handlePaymentMethodAttached($paymentMethod): void
    {
        // Find user by customer ID
        $user = \App\Models\User::where('stripe_id', $paymentMethod->customer)->first();

        if ($user) {
            PaymentMethod::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'external_id' => $paymentMethod->id,
                ],
                [
                    'type' => $paymentMethod->type,
                    'provider' => 'stripe',
                    'last_four' => $paymentMethod->card->last4 ?? null,
                    'brand' => $paymentMethod->card->brand ?? null,
                    'exp_month' => $paymentMethod->card->exp_month ?? null,
                    'exp_year' => $paymentMethod->card->exp_year ?? null,
                    'country' => $paymentMethod->card->country ?? null,
                    'is_verified' => true,
                ]
            );

            Log::info('Payment method attached', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id
            ]);
        }
    }

    /**
     * Handle subscription created
     */
    private function handleSubscriptionCreated($subscription): void
    {
        Log::info('Subscription created', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer
        ]);
    }

    /**
     * Handle subscription updated
     */
    private function handleSubscriptionUpdated($subscription): void
    {
        Log::info('Subscription updated', [
            'subscription_id' => $subscription->id,
            'status' => $subscription->status
        ]);
    }

    /**
     * Handle subscription deleted
     */
    private function handleSubscriptionDeleted($subscription): void
    {
        Log::info('Subscription deleted', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer
        ]);
    }

    /**
     * Handle invoice payment succeeded
     */
    private function handleInvoicePaymentSucceeded($invoice): void
    {
        Log::info('Invoice payment succeeded', [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer,
            'amount' => $invoice->amount_paid
        ]);
    }

    /**
     * Handle invoice payment failed
     */
    private function handleInvoicePaymentFailed($invoice): void
    {
        Log::info('Invoice payment failed', [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer,
            'amount' => $invoice->amount_due
        ]);
    }
}
