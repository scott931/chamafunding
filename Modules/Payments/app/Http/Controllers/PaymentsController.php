<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentsController extends Controller
{
    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            $user = Auth::user();
            $isAdmin = $user->hasAnyRole([
                'Super Admin', 'Financial Admin', 'Moderator', 'Support Agent',
                'Treasurer', 'Secretary', 'Auditor'
            ]);
            return $this->getPaymentHistoryJson($request, $isAdmin);
        }

        // For web requests, return view (Alpine.js will fetch data via API)
        return view('payments::index');
    }

    /**
     * Get payment history as JSON (for API requests)
     */
    private function getPaymentHistoryJson(Request $request, bool $isAdmin): JsonResponse
    {
        $userId = $isAdmin ? null : Auth::id();
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $financialTransactionsQuery = FinancialTransaction::with(['user', 'campaign', 'savingsAccount'])
            ->where('transaction_type', 'payment');

        if ($userId) {
            $financialTransactionsQuery->where('user_id', $userId);
        }

        $contributionsQuery = \App\Models\CampaignContribution::with(['campaign', 'user'])
            ->where('status', 'succeeded');

        if ($userId) {
            $contributionsQuery->where('user_id', $userId);
        }

        // Apply filters
        if ($request->has('from_date')) {
            $financialTransactionsQuery->whereDate('created_at', '>=', $request->from_date);
            $contributionsQuery->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $financialTransactionsQuery->whereDate('created_at', '<=', $request->to_date);
            $contributionsQuery->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('status')) {
            $financialTransactionsQuery->where('status', $request->status);
        }

        $allFinancialTransactions = $financialTransactionsQuery->get();
        $allContributions = $contributionsQuery->get();

        $mergedPayments = collect();

        foreach ($allFinancialTransactions as $transaction) {
            $mergedPayments->push([
                'id' => 'ft_' . $transaction->id,
                'source' => 'financial_transaction',
                'reference' => $transaction->reference,
                'user' => $transaction->user ? [
                    'id' => $transaction->user->id,
                    'name' => $transaction->user->name,
                    'email' => $transaction->user->email,
                ] : null,
                'campaign' => $transaction->campaign ? [
                    'id' => $transaction->campaign->id,
                    'title' => $transaction->campaign->title,
                ] : null,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'payment_provider' => $transaction->payment_provider,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at->toIso8601String(),
            ]);
        }

        foreach ($allContributions as $contribution) {
            $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                ->exists();

            if (!$hasFinancialTransaction) {
                $mergedPayments->push([
                    'id' => 'cc_' . $contribution->id,
                    'source' => 'campaign_contribution',
                    'reference' => 'CONT-' . $contribution->id,
                    'user' => $contribution->user ? [
                        'id' => $contribution->user->id,
                        'name' => $contribution->user->name,
                        'email' => $contribution->user->email,
                    ] : null,
                    'campaign' => $contribution->campaign ? [
                        'id' => $contribution->campaign->id,
                        'title' => $contribution->campaign->title,
                    ] : null,
                    'amount' => $contribution->amount,
                    'currency' => $contribution->currency,
                    'payment_method' => $this->getPaymentMethodFromProcessor($contribution->payment_processor),
                    'payment_provider' => $contribution->payment_processor,
                    'status' => 'completed',
                    'created_at' => $contribution->created_at->toIso8601String(),
                ]);
            }
        }

        $mergedPayments = $mergedPayments->sortByDesc(function($payment) {
            return is_string($payment['created_at'])
                ? strtotime($payment['created_at'])
                : $payment['created_at']->timestamp;
        })->values();

        $total = $mergedPayments->count();
        $offset = ($page - 1) * $perPage;
        $paginated = $mergedPayments->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $paginated->toArray(),
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
            'message' => 'Payments retrieved successfully'
        ]);
    }

    /**
     * Convert payment processor to payment method name
     */
    private function getPaymentMethodFromProcessor(?string $processor): string
    {
        if (!$processor) {
            return 'Unknown';
        }

        $processor = strtolower($processor);

        return match($processor) {
            'paypal' => 'PayPal',
            'venmo' => 'Venmo',
            'stripe' => 'Card',
            'mpesa' => 'M-Pesa',
            'flutterwave' => 'Flutterwave',
            default => ucfirst($processor),
        };
    }

    /**
     * Process a payment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|string',
            'payment_provider' => 'required|string|in:stripe,paypal,mpesa,flutterwave',
            'description' => 'nullable|string|max:255',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'savings_account_id' => 'nullable|exists:savings_accounts,id',
            'external_transaction_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $amount = $request->amount * 100; // Convert to cents
            $feeAmount = $this->calculateFee($amount, $request->payment_method);
            $netAmount = $amount - $feeAmount;

            $transaction = FinancialTransaction::create([
                'transaction_type' => 'payment',
                'reference' => 'TXN_' . time() . '_' . Str::random(8),
                'user_id' => Auth::id(),
                'campaign_id' => $request->campaign_id,
                'savings_account_id' => $request->savings_account_id,
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'currency' => strtoupper($request->currency),
                'payment_method' => $request->payment_method,
                'payment_provider' => $request->payment_provider,
                'external_transaction_id' => $request->external_transaction_id,
                'status' => 'pending',
                'description' => $request->description,
            ]);

            // Here you would integrate with the actual payment provider
            // For now, we'll simulate a successful payment
            $transaction->markAsCompleted();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['user', 'campaign', 'savingsAccount']),
                'message' => 'Payment processed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payment
     */
    public function show($id): JsonResponse
    {
        $payment = FinancialTransaction::with(['user', 'campaign', 'savingsAccount'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Payment retrieved successfully'
        ]);
    }

    /**
     * Check payment status
     */
    public function status($id): JsonResponse
    {
        $payment = FinancialTransaction::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'reference' => $payment->reference,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at,
                'processed_at' => $payment->processed_at,
            ],
            'message' => 'Payment status retrieved successfully'
        ]);
    }

    /**
     * Refund a payment
     */
    public function refund(Request $request, $id): JsonResponse
    {
        $payment = FinancialTransaction::where('user_id', Auth::id())
            ->findOrFail($id);

        if (!$payment->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payments can be refunded'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0.01|max:' . ($payment->amount / 100),
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refundAmount = $request->amount ? $request->amount * 100 : $payment->amount;
            $refundFee = $this->calculateFee($refundAmount, $payment->payment_method);
            $netRefundAmount = $refundAmount - $refundFee;

            $refundTransaction = FinancialTransaction::create([
                'transaction_type' => 'refund',
                'reference' => 'REF_' . time() . '_' . Str::random(8),
                'user_id' => Auth::id(),
                'campaign_id' => $payment->campaign_id,
                'savings_account_id' => $payment->savings_account_id,
                'amount' => $refundAmount,
                'fee_amount' => $refundFee,
                'net_amount' => $netRefundAmount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'payment_provider' => $payment->payment_provider,
                'status' => 'pending',
                'description' => $request->reason ?? 'Payment refund',
                'metadata' => ['original_transaction_id' => $payment->id],
            ]);

            // Here you would integrate with the actual payment provider for refund
            $refundTransaction->markAsCompleted();

            // Update original transaction status
            $payment->update(['status' => 'refunded']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $refundTransaction,
                'message' => 'Refund processed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history - returns payments made by the authenticated user (or all for admin)
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole([
            'Super Admin', 'Financial Admin', 'Moderator', 'Support Agent',
            'Treasurer', 'Secretary', 'Auditor'
        ]);

        $userId = $isAdmin ? null : $user->id;
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $financialTransactions = FinancialTransaction::with(['campaign', 'savingsAccount', 'user'])
            ->where('transaction_type', 'payment');

        if ($userId) {
            $financialTransactions->where('user_id', $userId);
        }

        $contributions = \App\Models\CampaignContribution::with(['campaign', 'user'])
            ->where('status', 'succeeded');

        if ($userId) {
            $contributions->where('user_id', $userId);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $financialTransactions->whereDate('created_at', '>=', $request->from_date);
            $contributions->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $financialTransactions->whereDate('created_at', '<=', $request->to_date);
            $contributions->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by status (optional)
        if ($request->has('status')) {
            $financialTransactions->where('status', $request->status);
        }

        $allFinancialTransactions = $financialTransactions->get();
        $allContributions = $contributions->get();

        $mergedPayments = collect();

        foreach ($allFinancialTransactions as $transaction) {
            $mergedPayments->push([
                'id' => 'ft_' . $transaction->id,
                'source' => 'financial_transaction',
                'reference' => $transaction->reference,
                'user' => $transaction->user ? [
                    'id' => $transaction->user->id,
                    'name' => $transaction->user->name,
                    'email' => $transaction->user->email,
                ] : null,
                'campaign' => $transaction->campaign ? [
                    'id' => $transaction->campaign->id,
                    'title' => $transaction->campaign->title,
                ] : null,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'payment_provider' => $transaction->payment_provider,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at->toIso8601String(),
            ]);
        }

        foreach ($allContributions as $contribution) {
            $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                ->exists();

            if (!$hasFinancialTransaction) {
                $mergedPayments->push([
                    'id' => 'cc_' . $contribution->id,
                    'source' => 'campaign_contribution',
                    'reference' => 'CONT-' . $contribution->id,
                    'user' => $contribution->user ? [
                        'id' => $contribution->user->id,
                        'name' => $contribution->user->name,
                        'email' => $contribution->user->email,
                    ] : null,
                    'campaign' => $contribution->campaign ? [
                        'id' => $contribution->campaign->id,
                        'title' => $contribution->campaign->title,
                    ] : null,
                    'amount' => $contribution->amount,
                    'currency' => $contribution->currency,
                    'payment_method' => $this->getPaymentMethodFromProcessor($contribution->payment_processor),
                    'payment_provider' => $contribution->payment_processor,
                    'status' => 'completed',
                    'created_at' => $contribution->created_at->toIso8601String(),
                ]);
            }
        }

        $mergedPayments = $mergedPayments->sortByDesc(function($payment) {
            return strtotime($payment['created_at']);
        })->values();

        $total = $mergedPayments->count();
        $offset = ($page - 1) * $perPage;
        $paginated = $mergedPayments->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $paginated->toArray(),
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
            'message' => 'Payment history retrieved successfully'
        ]);
    }

    /**
     * Get payment methods
     */
    public function paymentMethods(): JsonResponse
    {
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())
            ->where('is_verified', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentMethods,
            'message' => 'Payment methods retrieved successfully'
        ]);
    }

    /**
     * Add a payment method
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:card,bank_account,mobile_money,digital_wallet',
            'provider' => 'required|string|in:stripe,paypal,mpesa,flutterwave',
            'external_id' => 'nullable|string',
            'last_four' => 'nullable|string|size:4',
            'brand' => 'nullable|string',
            'exp_month' => 'nullable|string|size:2',
            'exp_year' => 'nullable|string|size:4',
            'country' => 'nullable|string|size:2',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // If setting as default, unset other defaults
            if ($request->is_default) {
                PaymentMethod::where('user_id', Auth::id())
                    ->update(['is_default' => false]);
            }

            $paymentMethod = PaymentMethod::create([
                'user_id' => Auth::id(),
                'type' => $request->type,
                'provider' => $request->provider,
                'external_id' => $request->external_id,
                'last_four' => $request->last_four,
                'brand' => $request->brand,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'country' => $request->country,
                'is_default' => $request->is_default ?? false,
                'is_verified' => false, // Will be verified through webhook
            ]);

            return response()->json([
                'success' => true,
                'data' => $paymentMethod,
                'message' => 'Payment method added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a payment method
     */
    public function removePaymentMethod($id): JsonResponse
    {
        $paymentMethod = PaymentMethod::where('user_id', Auth::id())
            ->findOrFail($id);

        try {
            $paymentMethod->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment method removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment summary/statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole([
            'Super Admin', 'Financial Admin', 'Moderator', 'Support Agent',
            'Treasurer', 'Secretary', 'Auditor'
        ]);

        $userId = $isAdmin ? null : $user->id;
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Get FinancialTransaction payments
        $transactionPaymentsQuery = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed');

        if ($userId) {
            $transactionPaymentsQuery->where('user_id', $userId);
        }

        if ($fromDate) {
            $transactionPaymentsQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $transactionPaymentsQuery->whereDate('created_at', '<=', $toDate);
        }

        $transactionPayments = $transactionPaymentsQuery->get();
        $totalPaymentAmount = $transactionPayments->sum('amount');
        $totalPaymentCount = $transactionPayments->count();

        // Get contributions
        $contributionsQuery = \App\Models\CampaignContribution::where('status', 'succeeded');

        if ($userId) {
            $contributionsQuery->where('user_id', $userId);
        }

        if ($fromDate) {
            $contributionsQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $contributionsQuery->whereDate('created_at', '<=', $toDate);
        }

        $allContributions = $contributionsQuery->get();
        $contributionAmount = 0;
        $contributionCount = 0;

        foreach ($allContributions as $contribution) {
            $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                ->exists();

            if (!$hasFinancialTransaction) {
                $contributionAmount += $contribution->amount;
                $contributionCount++;
            }
        }

        // Total payments (from both sources)
        $totalPayments = $totalPaymentCount + $contributionCount;
        $totalAmount = $totalPaymentAmount + $contributionAmount;

        // Get total campaigns
        $totalCampaigns = \App\Models\Campaign::count();

        // Get expenses (withdrawals and fees)
        $expensesQuery = FinancialTransaction::whereIn('transaction_type', ['withdrawal', 'fee'])
            ->where('status', 'completed');

        if ($userId) {
            $expensesQuery->where('user_id', $userId);
        }

        if ($fromDate) {
            $expensesQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $expensesQuery->whereDate('created_at', '<=', $toDate);
        }

        $totalExpenses = $expensesQuery->sum('amount');

        // Net balance
        $netBalance = $totalAmount - $totalExpenses;

        return response()->json([
            'success' => true,
            'data' => [
                'total_payments' => (int) $totalPayments,
                'total_payment_amount' => (int) $totalAmount,
                'total_expenses' => (int) $totalExpenses,
                'net_balance' => (int) $netBalance,
                'total_campaigns' => (int) $totalCampaigns,
                'contributions_count' => (int) $contributionCount,
                'contributions_amount' => (int) $contributionAmount,
            ],
            'message' => 'Payment summary retrieved successfully'
        ]);
    }

    /**
     * Get total number of campaigns
     */
    public function campaignCount(): JsonResponse
    {
        $count = \App\Models\Campaign::count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_campaigns' => $count
            ],
            'message' => 'Campaign count retrieved successfully'
        ]);
    }

    /**
     * Get total payment made to a specific campaign by the authenticated user
     */
    public function campaignTotalPayment($campaignId): JsonResponse
    {
        // Verify campaign exists
        $campaign = \App\Models\Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        // Calculate total payments made by the user to this campaign
        // Only count completed payments (excluding refunds)
        $totalPayment = FinancialTransaction::where('user_id', Auth::id())
            ->where('campaign_id', $campaignId)
            ->where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->sum('amount');

        // Also get count of payments
        $paymentCount = FinancialTransaction::where('user_id', Auth::id())
            ->where('campaign_id', $campaignId)
            ->where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'campaign_id' => $campaignId,
                'campaign_title' => $campaign->title,
                'total_amount' => $totalPayment / 100, // Convert from cents to dollars
                'total_amount_raw' => $totalPayment, // Amount in cents
                'currency' => $campaign->currency ?? 'USD',
                'payment_count' => $paymentCount,
            ],
            'message' => 'Total payment to campaign retrieved successfully'
        ]);
    }

    /**
     * Calculate payment fee
     */
    private function calculateFee(int $amount, string $paymentMethod): int
    {
        $feeRate = 0.029; // 2.9% default fee rate

        switch ($paymentMethod) {
            case 'card':
                $feeRate = 0.029;
                break;
            case 'bank_transfer':
                $feeRate = 0.008;
                break;
            case 'mobile_money':
                $feeRate = 0.015;
                break;
            default:
                $feeRate = 0.029;
        }

        return (int) round($amount * $feeRate);
    }
}
