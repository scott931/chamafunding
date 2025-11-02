<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Campaign;
use App\Models\SavingsAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinanceController extends Controller
{
    /**
     * Display a listing of finances
     */
    public function index(Request $request)
    {
        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return $this->reports($request);
        }

        // For web requests, return view
        return view('finance::index');
    }

    /**
     * Get financial reports
     */
    public function reports(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        $reports = [
            'summary' => $this->getFinancialSummary($userId, $fromDate, $toDate),
            'transactions' => $this->getTransactionSummary($userId, $fromDate, $toDate),
            'campaigns' => $this->getCampaignFinancials($userId, $fromDate, $toDate),
            'savings' => $this->getSavingsSummary($userId, $fromDate, $toDate),
            'fees' => $this->getFeesSummary($userId, $fromDate, $toDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $reports,
            'message' => 'Financial reports retrieved successfully'
        ]);
    }

    /**
     * Get transaction history
     */
    public function transactionHistory(Request $request): JsonResponse
    {
        $query = FinancialTransaction::with(['user', 'campaign', 'savingsAccount'])
            ->where('user_id', Auth::id());

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'message' => 'Transaction history retrieved successfully'
        ]);
    }

    /**
     * Calculate balance
     * Includes both FinancialTransaction and CampaignContribution data
     */
    public function balance(): JsonResponse
    {
        $userId = Auth::id();

        // Get income from FinancialTransaction (completed payments)
        $transactionIncome = FinancialTransaction::where('user_id', $userId)
            ->where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->sum('net_amount');

        // Get contributions that may not have FinancialTransaction records
        // Only count contributions that don't have corresponding FinancialTransaction
        $allContributions = \App\Models\CampaignContribution::where('user_id', $userId)
            ->where('status', 'succeeded')
            ->get();

        $contributionIncome = 0;
        foreach ($allContributions as $contribution) {
            // Check if this contribution already has a FinancialTransaction
            $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                ->where('user_id', $userId)
                ->exists();

            if (!$hasFinancialTransaction) {
                $contributionIncome += $contribution->amount;
            }
        }

        // Calculate net contribution amount (subtract typical 2.9% fee)
        $feeRate = 0.029;
        $contributionIncomeNet = (int) round($contributionIncome * (1 - $feeRate));

        // Total income from both sources
        $totalIncome = $transactionIncome + $contributionIncomeNet;

        // Campaign contributions (total amount contributed, not net)
        $transactionContributions = FinancialTransaction::where('user_id', $userId)
            ->where('transaction_type', 'payment')
            ->whereNotNull('campaign_id')
            ->where('status', 'completed')
            ->sum('amount');

        $contributionContributions = \App\Models\CampaignContribution::where('user_id', $userId)
            ->where('status', 'succeeded')
            ->sum('amount');

        // Total contributions (use transaction contributions if they exist, otherwise use direct contributions)
        $campaignContributions = $transactionContributions > 0
            ? $transactionContributions
            : $contributionContributions;

        $balance = [
            'total_income' => $totalIncome,
            'total_expenses' => FinancialTransaction::where('user_id', $userId)
                ->whereIn('transaction_type', ['withdrawal', 'fee'])
                ->where('status', 'completed')
                ->sum('amount'),
            'total_fees' => FinancialTransaction::where('user_id', $userId)
                ->where('transaction_type', 'fee')
                ->where('status', 'completed')
                ->sum('amount'),
            'savings_balance' => SavingsAccount::where('user_id', $userId)
                ->where('status', 'active')
                ->sum('balance'),
            'campaign_contributions' => $campaignContributions,
            'total_payments_count' => FinancialTransaction::where('user_id', $userId)
                ->where('transaction_type', 'payment')
                ->where('status', 'completed')
                ->count() + collect($allContributions)->filter(function($contribution) use ($userId) {
                    return !FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                        ->where('user_id', $userId)
                        ->exists();
                })->count(),
        ];

        $balance['net_balance'] = $balance['total_income'] - $balance['total_expenses'];

        return response()->json([
            'success' => true,
            'data' => $balance,
            'message' => 'Balance calculated successfully'
        ]);
    }

    /**
     * Calculate fees
     */
    public function calculateFees(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'currency' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount * 100; // Convert to cents
        $feeRate = $this->getFeeRate($request->payment_method);
        $feeAmount = $amount * $feeRate;
        $netAmount = $amount - $feeAmount;

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $request->amount,
                'fee_rate' => $feeRate * 100,
                'fee_amount' => $feeAmount / 100,
                'net_amount' => $netAmount / 100,
                'currency' => strtoupper($request->currency),
            ],
            'message' => 'Fees calculated successfully'
        ]);
    }

    /**
     * Calculate interest
     */
    public function calculateInterest(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'principal' => 'required|numeric|min:0.01',
            'rate' => 'required|numeric|min:0|max:100',
            'time_period' => 'required|integer|min:1',
            'time_unit' => 'required|string|in:days,months,years',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $principal = $request->principal;
        $rate = $request->rate / 100; // Convert percentage to decimal
        $timePeriod = $request->time_period;
        $timeUnit = $request->time_unit;

        // Calculate interest based on time unit
        switch ($timeUnit) {
            case 'days':
                $interest = $principal * $rate * ($timePeriod / 365);
                break;
            case 'months':
                $interest = $principal * $rate * ($timePeriod / 12);
                break;
            case 'years':
                $interest = $principal * $rate * $timePeriod;
                break;
        }

        $totalAmount = $principal + $interest;

        return response()->json([
            'success' => true,
            'data' => [
                'principal' => $principal,
                'rate' => $request->rate,
                'time_period' => $timePeriod,
                'time_unit' => $timeUnit,
                'interest' => round($interest, 2),
                'total_amount' => round($totalAmount, 2),
            ],
            'message' => 'Interest calculated successfully'
        ]);
    }

    /**
     * Get financial summary
     */
    private function getFinancialSummary($userId, $fromDate, $toDate): array
    {
        $query = FinancialTransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$fromDate, $toDate]);

        return [
            'total_transactions' => $query->count(),
            'total_volume' => $query->sum('amount'),
            'total_fees' => $query->where('transaction_type', 'fee')->sum('amount'),
            'successful_transactions' => $query->where('status', 'completed')->count(),
            'failed_transactions' => $query->where('status', 'failed')->count(),
        ];
    }

    /**
     * Get transaction summary
     */
    private function getTransactionSummary($userId, $fromDate, $toDate): array
    {
        $query = FinancialTransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$fromDate, $toDate]);

        return [
            'by_type' => $query->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('transaction_type')
                ->get(),
            'by_status' => $query->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('status')
                ->get(),
            'by_payment_method' => $query->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('payment_method')
                ->get(),
        ];
    }

    /**
     * Get campaign financials
     */
    private function getCampaignFinancials($userId, $fromDate, $toDate): array
    {
        $campaigns = Campaign::where('created_by', $userId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        return [
            'total_campaigns' => $campaigns->count(),
            'total_goal_amount' => $campaigns->sum('goal_amount'),
            'total_raised' => $campaigns->sum('raised_amount'),
            'successful_campaigns' => $campaigns->where('status', 'successful')->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
        ];
    }

    /**
     * Get savings summary
     */
    private function getSavingsSummary($userId, $fromDate, $toDate): array
    {
        $savingsAccounts = SavingsAccount::where('user_id', $userId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        return [
            'total_accounts' => $savingsAccounts->count(),
            'total_balance' => $savingsAccounts->sum('balance'),
            'active_accounts' => $savingsAccounts->where('status', 'active')->count(),
            'average_balance' => $savingsAccounts->avg('balance'),
        ];
    }

    /**
     * Get fees summary
     */
    private function getFeesSummary($userId, $fromDate, $toDate): array
    {
        $fees = FinancialTransaction::where('user_id', $userId)
            ->where('transaction_type', 'fee')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        return [
            'total_fees' => $fees->sum('amount'),
            'fees_by_payment_method' => $fees->groupBy('payment_method')
                ->map->sum('amount'),
            'average_fee' => $fees->avg('amount'),
        ];
    }

    /**
     * Get payment history - returns payments made by the authenticated user
     * Includes both FinancialTransaction and CampaignContribution (PayPal/Venmo)
     */
    public function paymentHistory(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        // Get payments from FinancialTransaction table
        $financialTransactions = FinancialTransaction::with(['campaign', 'savingsAccount'])
            ->where('user_id', $userId)
            ->where('transaction_type', 'payment');

        // Get contributions that may not have FinancialTransaction records
        $contributions = \App\Models\CampaignContribution::with(['campaign'])
            ->where('user_id', $userId)
            ->where('status', 'succeeded');

        // Apply date filters if provided
        if ($request->has('from_date')) {
            $financialTransactions->whereDate('created_at', '>=', $request->from_date);
            $contributions->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $financialTransactions->whereDate('created_at', '<=', $request->to_date);
            $contributions->whereDate('created_at', '<=', $request->to_date);
        }

        // Apply status filter
        if ($request->has('status')) {
            $financialTransactions->where('status', $request->status);
        }

        // Get all records
        $allFinancialTransactions = $financialTransactions->get();
        $allContributions = $contributions->get();

        // Merge and transform
        $mergedPayments = collect();

        // Add FinancialTransactions
        foreach ($allFinancialTransactions as $transaction) {
            $mergedPayments->push([
                'id' => 'ft_' . $transaction->id,
                'source' => 'financial_transaction',
                'reference' => $transaction->reference,
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

        // Add Contributions that don't have corresponding FinancialTransaction
        foreach ($allContributions as $contribution) {
            $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                ->where('user_id', $userId)
                ->exists();

            if (!$hasFinancialTransaction) {
                $mergedPayments->push([
                    'id' => 'cc_' . $contribution->id,
                    'source' => 'campaign_contribution',
                    'reference' => 'CONT-' . $contribution->id,
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

        // Sort by created_at descending (handle ISO8601 string dates)
        $mergedPayments = $mergedPayments->sortByDesc(function($payment) {
            return is_string($payment['created_at'])
                ? strtotime($payment['created_at'])
                : $payment['created_at']->timestamp ?? 0;
        })->values();

        // Manual pagination
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
     * Helper method to get payment method from processor
     */
    private function getPaymentMethodFromProcessor($processor): string
    {
        $mapping = [
            'paypal' => 'paypal',
            'stripe' => 'card',
            'mpesa' => 'mobile_money',
            'flutterwave' => 'card',
        ];

        return $mapping[strtolower($processor ?? '')] ?? 'unknown';
    }

    /**
     * Get total number of campaigns
     */
    public function campaignCount(): JsonResponse
    {
        $count = Campaign::count();

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
        $campaign = Campaign::find($campaignId);

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
     * Get fee rate for payment method
     */
    private function getFeeRate(string $paymentMethod): float
    {
        switch ($paymentMethod) {
            case 'card':
                return 0.029; // 2.9%
            case 'bank_transfer':
                return 0.008; // 0.8%
            case 'mobile_money':
                return 0.015; // 1.5%
            case 'digital_wallet':
                return 0.025; // 2.5%
            default:
                return 0.029; // 2.9%
        }
    }
}
