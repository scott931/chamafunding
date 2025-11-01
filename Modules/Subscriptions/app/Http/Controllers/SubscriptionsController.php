<?php

namespace Modules\Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Cashier\Subscription;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::where('user_id', Auth::id());

        // Filter by status
        if ($request->has('status')) {
            $query->where('stripe_status', $request->status);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'message' => 'Subscriptions retrieved successfully'
        ]);
    }

    /**
     * Create a new subscription
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'price_id' => 'required|string',
            'payment_method' => 'required|string',
            'quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            // Create subscription using Laravel Cashier
            $subscription = $user->newSubscription('default', $request->price_id)
                ->quantity($request->quantity ?? 1)
                ->create($request->payment_method);

            return response()->json([
                'success' => true,
                'data' => $subscription,
                'message' => 'Subscription created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified subscription
     */
    public function show($id): JsonResponse
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subscription,
            'message' => 'Subscription retrieved successfully'
        ]);
    }

    /**
     * Update the specified subscription
     */
    public function update(Request $request, $id): JsonResponse
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price_id' => 'sometimes|string',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update subscription using Laravel Cashier
            if ($request->has('price_id')) {
                $subscription->swap($request->price_id);
            }

            if ($request->has('quantity')) {
                $subscription->updateQuantity($request->quantity);
            }

            return response()->json([
                'success' => true,
                'data' => $subscription->fresh(),
                'message' => 'Subscription updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel the specified subscription
     */
    public function cancel($id): JsonResponse
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->findOrFail($id);

        try {
            // Cancel subscription using Laravel Cashier
            $subscription->cancel();

            return response()->json([
                'success' => true,
                'data' => $subscription->fresh(),
                'message' => 'Subscription cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resume the specified subscription
     */
    public function resume($id): JsonResponse
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->findOrFail($id);

        try {
            // Resume subscription using Laravel Cashier
            $subscription->resume();

            return response()->json([
                'success' => true,
                'data' => $subscription->fresh(),
                'message' => 'Subscription resumed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription status
     */
    public function status($id): JsonResponse
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $subscription->id,
                'status' => $subscription->stripe_status,
                'type' => $subscription->type,
                'stripe_price' => $subscription->stripe_price,
                'quantity' => $subscription->quantity,
                'trial_ends_at' => $subscription->trial_ends_at,
                'ends_at' => $subscription->ends_at,
                'created_at' => $subscription->created_at,
            ],
            'message' => 'Subscription status retrieved successfully'
        ]);
    }

    /**
     * Get billing history
     */
    public function billingHistory(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            // Get billing history from Stripe
            $invoices = $user->invoices();

            $billingHistory = $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency,
                    'status' => $invoice->status,
                    'created' => $invoice->created,
                    'period_start' => $invoice->period_start,
                    'period_end' => $invoice->period_end,
                    'download_url' => $invoice->invoice_pdf,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $billingHistory,
                'message' => 'Billing history retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve billing history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download invoice
     */
    public function downloadInvoice($invoiceId): JsonResponse
    {
        $user = Auth::user();

        try {
            $invoice = $user->findInvoice($invoiceId);

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $invoice->invoice_pdf,
                ],
                'message' => 'Invoice download URL retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod(Request $request, $id): JsonResponse
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update payment method using Laravel Cashier
            $subscription->updateDefaultPaymentMethod($request->payment_method);

            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming invoice
     */
    public function upcomingInvoice($id): JsonResponse
    {
        $subscription = Subscription::where('user_id', Auth::id())
            ->findOrFail($id);

        try {
            $upcomingInvoice = $subscription->upcomingInvoice();

            return response()->json([
                'success' => true,
                'data' => [
                    'amount_due' => $upcomingInvoice->amount_due,
                    'currency' => $upcomingInvoice->currency,
                    'period_start' => $upcomingInvoice->period_start,
                    'period_end' => $upcomingInvoice->period_end,
                    'lines' => $upcomingInvoice->lines->data,
                ],
                'message' => 'Upcoming invoice retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
