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
     */
    public function balance(): JsonResponse
    {
        $userId = Auth::id();

        $balance = [
            'total_income' => FinancialTransaction::where('user_id', $userId)
                ->where('transaction_type', 'payment')
                ->where('status', 'completed')
                ->sum('net_amount'),
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
            'campaign_contributions' => FinancialTransaction::where('user_id', $userId)
                ->where('transaction_type', 'payment')
                ->whereNotNull('campaign_id')
                ->where('status', 'completed')
                ->sum('amount'),
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
