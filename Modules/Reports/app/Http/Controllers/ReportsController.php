<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ReportsCache;
use App\Models\Campaign;
use App\Models\FinancialTransaction;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Display a listing of reports
     */
    public function index(Request $request)
    {
        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return $this->campaignReports($request);
        }

        // For web requests, return view
        return view('reports::index');
    }

    /**
     * Get campaign reports
     */
    public function campaignReports(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $cacheKey = "campaign_reports_{$fromDate}_{$toDate}";

        // Check cache first
        $cachedData = ReportsCache::get('campaign_reports', $cacheKey);
        if ($cachedData) {
            return response()->json([
                'success' => true,
                'data' => $cachedData,
                'message' => 'Campaign reports retrieved successfully (cached)'
            ]);
        }

        $reports = [
            'overview' => $this->getCampaignOverview($fromDate, $toDate),
            'by_category' => $this->getCampaignsByCategory($fromDate, $toDate),
            'by_status' => $this->getCampaignsByStatus($fromDate, $toDate),
            'top_campaigns' => $this->getTopCampaigns($fromDate, $toDate),
            'contributions_trend' => $this->getContributionsTrend($fromDate, $toDate),
        ];

        // Cache the results for 1 hour
        ReportsCache::put('campaign_reports', $cacheKey, $reports, 60);

        return response()->json([
            'success' => true,
            'data' => $reports,
            'message' => 'Campaign reports retrieved successfully'
        ]);
    }

    /**
     * Get financial reports
     */
    public function financialReports(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $cacheKey = "financial_reports_{$fromDate}_{$toDate}";

        // Check cache first
        $cachedData = ReportsCache::get('financial_reports', $cacheKey);
        if ($cachedData) {
            return response()->json([
                'success' => true,
                'data' => $cachedData,
                'message' => 'Financial reports retrieved successfully (cached)'
            ]);
        }

        $reports = [
            'overview' => $this->getFinancialOverview($fromDate, $toDate),
            'transactions' => $this->getTransactionReports($fromDate, $toDate),
            'revenue' => $this->getRevenueReports($fromDate, $toDate),
            'fees' => $this->getFeesReports($fromDate, $toDate),
            'trends' => $this->getFinancialTrends($fromDate, $toDate),
        ];

        // Cache the results for 1 hour
        ReportsCache::put('financial_reports', $cacheKey, $reports, 60);

        return response()->json([
            'success' => true,
            'data' => $reports,
            'message' => 'Financial reports retrieved successfully'
        ]);
    }

    /**
     * Get user reports
     */
    public function userReports(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $cacheKey = "user_reports_{$fromDate}_{$toDate}";

        // Check cache first
        $cachedData = ReportsCache::get('user_reports', $cacheKey);
        if ($cachedData) {
            return response()->json([
                'success' => true,
                'data' => $cachedData,
                'message' => 'User reports retrieved successfully (cached)'
            ]);
        }

        $reports = [
            'overview' => $this->getUserOverview($fromDate, $toDate),
            'activity' => $this->getUserActivity($fromDate, $toDate),
            'engagement' => $this->getUserEngagement($fromDate, $toDate),
            'growth' => $this->getUserGrowth($fromDate, $toDate),
        ];

        // Cache the results for 1 hour
        ReportsCache::put('user_reports', $cacheKey, $reports, 60);

        return response()->json([
            'success' => true,
            'data' => $reports,
            'message' => 'User reports retrieved successfully'
        ]);
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $cacheKey = "analytics_{$fromDate}_{$toDate}";

        // Check cache first
        $cachedData = ReportsCache::get('analytics', $cacheKey);
        if ($cachedData) {
            return response()->json([
                'success' => true,
                'data' => $cachedData,
                'message' => 'Analytics data retrieved successfully (cached)'
            ]);
        }

        $analytics = [
            'platform_metrics' => $this->getPlatformMetrics($fromDate, $toDate),
            'performance' => $this->getPerformanceMetrics($fromDate, $toDate),
            'conversion' => $this->getConversionMetrics($fromDate, $toDate),
            'retention' => $this->getRetentionMetrics($fromDate, $toDate),
        ];

        // Cache the results for 30 minutes
        ReportsCache::put('analytics', $cacheKey, $analytics, 30);

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Analytics data retrieved successfully'
        ]);
    }

    /**
     * Export data
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string|in:campaigns,financial,users,analytics',
            'format' => 'required|string|in:csv,excel,pdf',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after:from_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        try {
            $reportType = $request->input('report_type');
            $format = $request->input('format');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Generate export data based on report type
            $data = $this->generateExportData($reportType, $fromDate, $toDate);

            // Here you would implement actual export functionality
            // For now, we'll return the data structure
            return response()->json([
                'success' => true,
                'data' => [
                    'report_type' => $reportType,
                    'format' => $format,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'data' => $data,
                    'export_url' => null, // Would be generated by actual export service
                ],
                'message' => 'Export data prepared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign overview
     */
    private function getCampaignOverview($fromDate, $toDate): array
    {
        $campaigns = Campaign::whereBetween('created_at', [$fromDate, $toDate]);

        return [
            'total_campaigns' => $campaigns->count(),
            'successful_campaigns' => $campaigns->where('status', 'successful')->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'total_goal_amount' => $campaigns->sum('goal_amount'),
            'total_raised' => $campaigns->sum('raised_amount'),
            'average_goal' => $campaigns->avg('goal_amount'),
            'average_raised' => $campaigns->avg('raised_amount'),
        ];
    }

    /**
     * Get campaigns by category
     */
    private function getCampaignsByCategory($fromDate, $toDate): array
    {
        return Campaign::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('category, COUNT(*) as count, SUM(goal_amount) as total_goal, SUM(raised_amount) as total_raised')
            ->groupBy('category')
            ->get();
    }

    /**
     * Get campaigns by status
     */
    private function getCampaignsByStatus($fromDate, $toDate): array
    {
        return Campaign::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('status, COUNT(*) as count, SUM(goal_amount) as total_goal, SUM(raised_amount) as total_raised')
            ->groupBy('status')
            ->get();
    }

    /**
     * Get top campaigns
     */
    private function getTopCampaigns($fromDate, $toDate): array
    {
        return Campaign::whereBetween('created_at', [$fromDate, $toDate])
            ->with('creator')
            ->orderBy('raised_amount', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get contributions trend
     */
    private function getContributionsTrend($fromDate, $toDate): array
    {
        return DB::table('campaign_contributions')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get financial overview
     */
    private function getFinancialOverview($fromDate, $toDate): array
    {
        $transactions = FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate]);

        return [
            'total_transactions' => $transactions->count(),
            'total_volume' => $transactions->sum('amount'),
            'total_fees' => $transactions->where('transaction_type', 'fee')->sum('amount'),
            'successful_transactions' => $transactions->where('status', 'completed')->count(),
            'failed_transactions' => $transactions->where('status', 'failed')->count(),
        ];
    }

    /**
     * Get transaction reports
     */
    private function getTransactionReports($fromDate, $toDate): array
    {
        return [
            'by_type' => FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('transaction_type')
                ->get(),
            'by_status' => FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('status')
                ->get(),
        ];
    }

    /**
     * Get revenue reports
     */
    private function getRevenueReports($fromDate, $toDate): array
    {
        return [
            'total_revenue' => FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
                ->where('transaction_type', 'payment')
                ->where('status', 'completed')
                ->sum('net_amount'),
            'revenue_by_payment_method' => FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
                ->where('transaction_type', 'payment')
                ->where('status', 'completed')
                ->selectRaw('payment_method, SUM(net_amount) as total')
                ->groupBy('payment_method')
                ->get(),
        ];
    }

    /**
     * Get fees reports
     */
    private function getFeesReports($fromDate, $toDate): array
    {
        return [
            'total_fees' => FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
                ->where('transaction_type', 'fee')
                ->sum('amount'),
            'fees_by_payment_method' => FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
                ->where('transaction_type', 'fee')
                ->selectRaw('payment_method, SUM(amount) as total')
                ->groupBy('payment_method')
                ->get(),
        ];
    }

    /**
     * Get financial trends
     */
    private function getFinancialTrends($fromDate, $toDate): array
    {
        return FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as transactions, SUM(amount) as volume')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get user overview
     */
    private function getUserOverview($fromDate, $toDate): array
    {
        $users = User::whereBetween('created_at', [$fromDate, $toDate]);

        return [
            'total_users' => $users->count(),
            'verified_users' => $users->where('is_verified', true)->count(),
            'active_users' => User::whereHas('financialTransactions', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })->count(),
        ];
    }

    /**
     * Get user activity
     */
    private function getUserActivity($fromDate, $toDate): array
    {
        return [
            'campaign_creators' => User::whereHas('campaigns', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })->count(),
            'contributors' => User::whereHas('contributions', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })->count(),
            'savings_users' => User::whereHas('savingsAccounts', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })->count(),
        ];
    }

    /**
     * Get user engagement
     */
    private function getUserEngagement($fromDate, $toDate): array
    {
        return [
            'average_contributions_per_user' => User::whereHas('contributions', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })->withCount('contributions')->avg('contributions_count'),
            'average_campaigns_per_user' => User::whereHas('campaigns', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })->withCount('campaigns')->avg('campaigns_count'),
        ];
    }

    /**
     * Get user growth
     */
    private function getUserGrowth($fromDate, $toDate): array
    {
        return User::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get platform metrics
     */
    private function getPlatformMetrics($fromDate, $toDate): array
    {
        return [
            'total_campaigns' => Campaign::count(),
            'total_users' => User::count(),
            'total_volume' => FinancialTransaction::where('status', 'completed')->sum('amount'),
            'success_rate' => $this->calculateSuccessRate($fromDate, $toDate),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($fromDate, $toDate): array
    {
        return [
            'average_campaign_duration' => $this->getAverageCampaignDuration($fromDate, $toDate),
            'average_contribution_amount' => $this->getAverageContributionAmount($fromDate, $toDate),
            'conversion_rate' => $this->getConversionRate($fromDate, $toDate),
        ];
    }

    /**
     * Get conversion metrics
     */
    private function getConversionMetrics($fromDate, $toDate): array
    {
        return [
            'visitor_to_user' => $this->getVisitorToUserConversion($fromDate, $toDate),
            'user_to_contributor' => $this->getUserToContributorConversion($fromDate, $toDate),
            'contributor_to_creator' => $this->getContributorToCreatorConversion($fromDate, $toDate),
        ];
    }

    /**
     * Get retention metrics
     */
    private function getRetentionMetrics($fromDate, $toDate): array
    {
        return [
            'user_retention_7d' => $this->getUserRetention(7, $fromDate, $toDate),
            'user_retention_30d' => $this->getUserRetention(30, $fromDate, $toDate),
            'contributor_retention' => $this->getContributorRetention($fromDate, $toDate),
        ];
    }

    /**
     * Generate export data
     */
    private function generateExportData(string $reportType, string $fromDate, string $toDate): array
    {
        switch ($reportType) {
            case 'campaigns':
                return Campaign::whereBetween('created_at', [$fromDate, $toDate])
                    ->with('creator')
                    ->get()
                    ->toArray();
            case 'financial':
                return FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
                    ->with(['user', 'campaign', 'savingsAccount'])
                    ->get()
                    ->toArray();
            case 'users':
                return User::whereBetween('created_at', [$fromDate, $toDate])
                    ->get()
                    ->toArray();
            case 'analytics':
                return [
                    'campaigns' => $this->getCampaignOverview($fromDate, $toDate),
                    'financial' => $this->getFinancialOverview($fromDate, $toDate),
                    'users' => $this->getUserOverview($fromDate, $toDate),
                ];
            default:
                return [];
        }
    }

    /**
     * Calculate success rate
     */
    private function calculateSuccessRate($fromDate, $toDate): float
    {
        $totalCampaigns = Campaign::whereBetween('created_at', [$fromDate, $toDate])->count();
        $successfulCampaigns = Campaign::whereBetween('created_at', [$fromDate, $toDate])
            ->where('status', 'successful')
            ->count();

        return $totalCampaigns > 0 ? ($successfulCampaigns / $totalCampaigns) * 100 : 0;
    }

    /**
     * Get average campaign duration
     */
    private function getAverageCampaignDuration($fromDate, $toDate): float
    {
        $campaigns = Campaign::whereBetween('created_at', [$fromDate, $toDate])
            ->whereNotNull('ends_at')
            ->get();

        if ($campaigns->isEmpty()) {
            return 0;
        }

        $totalDays = $campaigns->sum(function ($campaign) {
            return $campaign->created_at->diffInDays($campaign->ends_at);
        });

        return $totalDays / $campaigns->count();
    }

    /**
     * Get average contribution amount
     */
    private function getAverageContributionAmount($fromDate, $toDate): float
    {
        return FinancialTransaction::whereBetween('created_at', [$fromDate, $toDate])
            ->where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->avg('amount') ?? 0;
    }

    /**
     * Get conversion rate
     */
    private function getConversionRate($fromDate, $toDate): float
    {
        $totalUsers = User::whereBetween('created_at', [$fromDate, $toDate])->count();
        $contributors = User::whereHas('contributions', function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        })->count();

        return $totalUsers > 0 ? ($contributors / $totalUsers) * 100 : 0;
    }

    /**
     * Get visitor to user conversion
     */
    private function getVisitorToUserConversion($fromDate, $toDate): float
    {
        // This would require tracking visitors, which isn't implemented yet
        return 0;
    }

    /**
     * Get user to contributor conversion
     */
    private function getUserToContributorConversion($fromDate, $toDate): float
    {
        $totalUsers = User::where('created_at', '<=', $toDate)->count();
        $contributors = User::whereHas('contributions', function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        })->count();

        return $totalUsers > 0 ? ($contributors / $totalUsers) * 100 : 0;
    }

    /**
     * Get contributor to creator conversion
     */
    private function getContributorToCreatorConversion($fromDate, $toDate): float
    {
        $contributors = User::whereHas('contributions')->count();
        $creators = User::whereHas('campaigns', function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        })->count();

        return $contributors > 0 ? ($creators / $contributors) * 100 : 0;
    }

    /**
     * Get user retention
     */
    private function getUserRetention(int $days, $fromDate, $toDate): float
    {
        // This would require more complex tracking
        return 0;
    }

    /**
     * Get contributor retention
     */
    private function getContributorRetention($fromDate, $toDate): float
    {
        // This would require more complex tracking
        return 0;
    }

    /**
     * Get payment history - returns payments made by the authenticated user
     * Includes both FinancialTransaction and CampaignContribution (PayPal/Venmo)
     */
    public function paymentHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole([
            'Super Admin', 'Financial Admin', 'Moderator', 'Support Agent',
            'Treasurer', 'Secretary', 'Auditor'
        ]);

        $userId = $isAdmin ? null : $user->id;
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        // Get payments from FinancialTransaction table
        $financialTransactions = FinancialTransaction::with(['campaign', 'savingsAccount', 'user'])
            ->where('transaction_type', 'payment');

        if ($userId) {
            $financialTransactions->where('user_id', $userId);
        }

        // Get contributions that may not have FinancialTransaction records
        $contributions = \App\Models\CampaignContribution::with(['campaign', 'user'])
            ->where('status', 'succeeded');

        if ($userId) {
            $contributions->where('user_id', $userId);
        }

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

        // Add Contributions that don't have corresponding FinancialTransaction
        foreach ($allContributions as $contribution) {
            $hasFinancialTransaction = false;
            if ($contribution->transaction_id) {
                $query = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                    ->where('transaction_type', 'payment');
                if ($userId) {
                    $query->where('user_id', $userId);
                }
                $hasFinancialTransaction = $query->exists();
            }

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
}
