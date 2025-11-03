<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\CampaignContribution;
use App\Models\SavingsAccount;
use App\Models\TransactionNotificationRead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display admin dashboard with platform overview.
     */
    public function index(Request $request)
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if ($fromDate && !$toDate) { $toDate = now()->format('Y-m-d'); }
        if ($toDate && !$fromDate) { $fromDate = now()->subDays(30)->format('Y-m-d'); }
        $hasCustomRange = $fromDate && $toDate;

        // Calculate total contributions this month
        // Get FinancialTransaction payments for this month
        $ftPaymentsThisMonth = FinancialTransaction::where('transaction_type', 'payment')
            ->whereMonth('created_at', now()->month)
            ->where('status', 'completed')
            ->sum('amount');

        // Get CampaignContribution records for this month
        $contributionsThisMonth = CampaignContribution::where('status', 'succeeded')
            ->whereMonth('created_at', now()->month)
            ->get();

        $contributionAmount = 0;
        foreach ($contributionsThisMonth as $contribution) {
            // Check if this contribution already has a FinancialTransaction
            $hasFinancialTransaction = false;
            if ($contribution->transaction_id) {
                $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                    ->where('transaction_type', 'payment')
                    ->whereMonth('created_at', now()->month)
                    ->exists();
            }

            if (!$hasFinancialTransaction) {
                $contributionAmount += $contribution->amount;
            }
        }

        // Total contributions this month (in cents)
        $totalContributionsThisMonth = $ftPaymentsThisMonth + $contributionAmount;

        // Platform KPIs
        $stats = [
            'total_raised' => Campaign::sum('raised_amount') / 100,
            'active_campaigns' => Campaign::where('status', 'active')->count(),
            'total_campaigns' => Campaign::count(),
            'total_backers_month' => CampaignContribution::whereMonth('created_at', now()->month)->distinct('user_id')->count(),
            'total_backers_alltime' => CampaignContribution::distinct('user_id')->count(),
            'platform_fees_month' => FinancialTransaction::where('transaction_type', 'fee')
                ->whereMonth('created_at', now()->month)
                ->where('status', 'completed')
                ->sum('amount') / 100,
            'contributions_this_month' => $totalContributionsThisMonth / 100,
            'pending_payouts' => Campaign::where('status', 'successful')
                ->sum('raised_amount') / 100,
            'open_support_tickets' => 0, // Placeholder - implement support system
            'total_users' => User::count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'new_campaigns_this_month' => Campaign::whereMonth('created_at', now()->month)->count(),
        ];

        // Funding over time (range) - Include both FinancialTransaction and CampaignContribution
        $ftFundingQuery = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed');
        if ($hasCustomRange) {
            $ftFundingQuery->whereDate('created_at', '>=', $fromDate)
                           ->whereDate('created_at', '<=', $toDate);
        } else {
            $ftFundingQuery->where('created_at', '>=', now()->subDays(30));
        }
        $ftFunding = $ftFundingQuery
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Get contributions and merge with FinancialTransactions
        $contributionsQuery = CampaignContribution::where('status', 'succeeded');
        if ($hasCustomRange) {
            $contributionsQuery->whereDate('created_at', '>=', $fromDate)
                               ->whereDate('created_at', '<=', $toDate);
        } else {
            $contributionsQuery->where('created_at', '>=', now()->subDays(30));
        }
        $contributions = $contributionsQuery
            ->get();

        $fundingOverTime = collect();

        // Build date range loop
        $rangeStart = $hasCustomRange ? \Carbon\Carbon::parse($fromDate) : now()->subDays(30);
        $rangeEnd = $hasCustomRange ? \Carbon\Carbon::parse($toDate) : now();
        $period = new \DatePeriod($rangeStart->startOfDay(), new \DateInterval('P1D'), $rangeEnd->copy()->addDay()->startOfDay());
        foreach ($period as $day) {
            $date = $day->format('Y-m-d');
            $total = 0;

            // Add FinancialTransaction amount
            if ($ftFunding->has($date)) {
                $total += $ftFunding[$date]->total;
            }

            // Add contributions that don't have FinancialTransaction
            foreach ($contributions as $contribution) {
                if ($contribution->created_at->format('Y-m-d') === $date) {
                    // Check if this contribution has a corresponding FinancialTransaction
                    $hasFT = false;
                    if ($contribution->transaction_id) {
                        $hasFT = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                            ->where('transaction_type', 'payment')
                            ->whereDate('created_at', $date)
                            ->exists();
                    }

                    if (!$hasFT) {
                        $total += $contribution->amount;
                    }
                }
            }

            $fundingOverTime->push((object) [
                'date' => $date,
                'total' => $total
            ]);
        }

        // New campaigns and users over time (last 30 days)
        $growthData = [
            'campaigns' => Campaign::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'users' => User::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        // Top performing categories
        $topCategories = Campaign::select('category', DB::raw('SUM(raised_amount) as total_raised'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total_raised')
            ->limit(5)
            ->get();

        // Recent critical activity
        $recentActivity = $this->getRecentActivity();

        // Campaigns by status
        $campaignsByStatus = Campaign::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Recent large transactions
        $largeTransactions = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->where('amount', '>=', 50000) // $500 or more
            ->with(['campaign', 'user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin::index', compact(
            'stats',
            'fundingOverTime',
            'growthData',
            'topCategories',
            'recentActivity',
            'campaignsByStatus',
            'largeTransactions'
        ));
    }

    /**
     * Get recent critical activity for admin dashboard.
     */
    private function getRecentActivity()
    {
        $userId = Auth::id();
        $activities = [];

        // User's contributions
        $contributions = CampaignContribution::where('user_id', $userId)
            ->where('status', 'succeeded')
            ->with(['campaign'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($contribution) {
                return [
                    'type' => 'contribution',
                    'message' => "Contributed $" . number_format($contribution->amount / 100, 2) . " to campaign: " . ($contribution->campaign->title ?? 'N/A'),
                    'time' => $contribution->created_at,
                    'url' => $contribution->campaign ? route('admin.campaigns.show', $contribution->campaign_id) : null,
                ];
            });

        // User's financial transactions
        $transactions = FinancialTransaction::where('user_id', $userId)
            ->where('status', 'completed')
            ->with(['campaign'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($tx) {
                $typeLabel = match($tx->transaction_type) {
                    'payment' => 'Payment',
                    'refund' => 'Refund',
                    'transfer' => 'Transfer',
                    'interest' => 'Interest',
                    default => ucfirst($tx->transaction_type),
                };

                return [
                    'type' => $tx->transaction_type,
                    'message' => "{$typeLabel} of $" . number_format($tx->amount / 100, 2) .
                        ($tx->campaign ? " for campaign: {$tx->campaign->title}" : ""),
                    'time' => $tx->created_at,
                    'url' => $tx->campaign ? route('admin.campaigns.show', $tx->campaign_id) : null,
                ];
            });

        // User's campaigns created
        $campaignsCreated = Campaign::where('created_by', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'type' => 'campaign_created',
                    'message' => "Created campaign '{$campaign->title}'",
                    'time' => $campaign->created_at,
                    'url' => route('admin.campaigns.show', $campaign->id),
                ];
            });

        // Settings changes made by user
        $settingsChanges = \App\Models\SettingsAuditLog::where('changed_by', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'type' => 'settings_change',
                    'message' => "Updated setting: " . str_replace('_', ' ', $log->setting_key),
                    'time' => $log->created_at,
                    'url' => null,
                ];
            });

        // Campaigns assigned to user
        $assignedCampaigns = Campaign::whereHas('assignedUsers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'type' => 'campaign_assigned',
                    'message' => "Assigned to campaign '{$campaign->title}'",
                    'time' => $campaign->updated_at,
                    'url' => route('admin.campaigns.show', $campaign->id),
                ];
            });

        $activities = collect([
            ...$contributions,
            ...$transactions,
            ...$campaignsCreated,
            ...$settingsChanges,
            ...$assignedCampaigns
        ])
            ->sortByDesc('time')
            ->take(10)
            ->values();

        return $activities;
    }

    /**
     * Campaign Management - List all campaigns with filters.
     */
    public function campaigns(Request $request)
    {
        $query = Campaign::with(['creator', 'contributions'])->withCount('contributions');

        // Filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->has('flagged') && $request->flagged) {
            $query->whereIn('status', ['draft', 'pending']);
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(20);
        $statuses = Campaign::distinct()->pluck('status');
        $categories = Campaign::distinct()->pluck('category');

        return view('admin::campaigns.index', compact('campaigns', 'statuses', 'categories'));
    }

    /**
     * Show detailed campaign view for admin.
     */
    public function showCampaign($id)
    {
        $campaign = Campaign::with(['creator', 'contributions.user'])
            ->withCount('contributions')
            ->findOrFail($id);

        $contributions = $campaign->contributions()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin::campaigns.show', compact('campaign', 'contributions'));
    }

    /**
     * Update campaign status (approve, suspend, etc.).
     */
    public function updateCampaignStatus(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update(['status' => $request->status]);

        return back()->with('success', 'Campaign status updated successfully.');
    }

    /**
     * Update campaign details (dates, goal, etc.).
     */
    public function updateCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|in:emergency,project,community,education,health,environment',
            'description' => 'sometimes|string|min:10',
            'goal_amount' => 'sometimes|numeric|min:100',
            'currency' => 'sometimes|string|size:3',
            'deadline' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'status' => 'sometimes|string|in:draft,active,successful,failed,closed,suspended',
        ]);

        // Convert goal_amount from dollars to cents if provided
        if (isset($validated['goal_amount'])) {
            $validated['goal_amount'] = (int)($validated['goal_amount'] * 100);
        }

        // Update the campaign
        $campaign->update($validated);

        return back()->with('success', 'Campaign updated successfully.');
    }

    /**
     * Show user details.
     */
    public function showUser($id)
    {
        $user = User::with(['campaigns', 'contributions.campaign'])
            ->withCount(['campaigns', 'contributions'])
            ->findOrFail($id);

        $totalContributed = $user->contributions()->sum('amount') / 100;
        $totalRaised = $user->campaigns()->sum('raised_amount') / 100;

        return view('admin::users.show', compact('user', 'totalContributed', 'totalRaised'));
    }

    /**
     * Financial Overview Dashboard.
     */
    public function financial()
    {
        $stats = [
            'total_fees_this_month' => FinancialTransaction::where('transaction_type', 'fee')
                ->whereMonth('created_at', now()->month)
                ->where('status', 'completed')
                ->sum('amount') / 100,
            'total_fees_quarter' => FinancialTransaction::where('transaction_type', 'fee')
                ->where('created_at', '>=', now()->startOfQuarter())
                ->where('status', 'completed')
                ->sum('amount') / 100,
            'total_fees_year' => FinancialTransaction::where('transaction_type', 'fee')
                ->whereYear('created_at', now()->year)
                ->where('status', 'completed')
                ->sum('amount') / 100,
            'total_volume_this_month' => FinancialTransaction::where('transaction_type', 'payment')
                ->whereMonth('created_at', now()->month)
                ->where('status', 'completed')
                ->sum('amount') / 100,
        ];

        $feeRevenueOverTime = FinancialTransaction::where('transaction_type', 'fee')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin::financial.index', compact('stats', 'feeRevenueOverTime'));
    }

    /**
     * Transaction Log.
     */
    public function transactions(Request $request)
    {
        // Get FinancialTransaction records
        $ftQuery = FinancialTransaction::with(['user', 'campaign', 'savingsAccount']);

        if ($request->has('type') && $request->type) {
            $ftQuery->where('transaction_type', $request->type);
        }
        if ($request->has('status') && $request->status) {
            $ftQuery->where('status', $request->status);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $ftQuery->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $financialTransactions = $ftQuery->get();

        // Get CampaignContribution records (only payments that don't have FinancialTransaction)
        $ccQuery = CampaignContribution::with(['campaign', 'user'])
            ->where('status', 'succeeded');

        if ($request->has('type') && $request->type === 'payment') {
            // Only include contributions when filtering for payments
        } elseif ($request->has('type') && $request->type) {
            // If filtering for non-payment types, exclude contributions
            $ccQuery->whereRaw('1 = 0'); // No results
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $ccQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        $contributions = $ccQuery->get();

        // Create a collection to hold all transactions
        $allTransactions = collect();

        // Add FinancialTransactions
        foreach ($financialTransactions as $transaction) {
            $allTransactions->push($transaction);
        }

        // Add Contributions (as FinancialTransaction-like objects)
        foreach ($contributions as $contribution) {
            // Check if this contribution already has a corresponding FinancialTransaction
            $hasFinancialTransaction = false;
            if ($contribution->transaction_id) {
                $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                    ->where('transaction_type', 'payment')
                    ->exists();
            }

            if (!$hasFinancialTransaction) {
                // Create a virtual FinancialTransaction-like object from the contribution
                $virtualTransaction = (object) [
                    'id' => 'cc_' . $contribution->id,
                    'reference' => 'CONT-' . $contribution->id,
                    'transaction_type' => 'payment',
                    'user' => $contribution->user,
                    'campaign' => $contribution->campaign,
                    'savingsAccount' => null,
                    'amount' => $contribution->amount,
                    'fee_amount' => 0, // Contributions don't track fees separately
                    'net_amount' => $contribution->amount,
                    'currency' => $contribution->currency ?? 'USD',
                    'payment_method' => $this->getPaymentMethodFromProcessor($contribution->payment_processor),
                    'payment_provider' => $contribution->payment_processor,
                    'status' => 'completed',
                    'created_at' => $contribution->created_at,
                    'updated_at' => $contribution->updated_at,
                    'is_virtual' => true, // Flag to identify virtual transactions
                ];
                $allTransactions->push($virtualTransaction);
            }
        }

        // Sort by created_at descending
        $allTransactions = $allTransactions->sortByDesc(function($transaction) {
            return $transaction->created_at instanceof \Carbon\Carbon
                ? $transaction->created_at->timestamp
                : strtotime($transaction->created_at);
        })->values();

        // Manual pagination
        $perPage = 50;
        $currentPage = $request->get('page', 1);
        $total = $allTransactions->count();
        $offset = ($currentPage - 1) * $perPage;
        $paginated = $allTransactions->slice($offset, $perPage)->values();

        // Create a LengthAwarePaginator instance
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin::transactions.index', compact('transactions'));
    }

    /**
     * Support Center (Placeholder).
     */
    public function support()
    {
        return view('admin::support.index');
    }

    /**
     * Reported Content Queue (Placeholder).
     * Note: Reports functionality has been moved to ReportsController.
     * This method is kept for backwards compatibility but should not be used.
     */
    public function reports()
    {
        // Redirect to the new reports controller
        return redirect()->route('admin.reports.index');
    }

    /**
     * Get comprehensive admin dashboard statistics (API endpoint)
     */
    public function dashboardStats(): JsonResponse
    {
        // Count FinancialTransaction payments
        $transactionPayments = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->count();

        // Count contributions that don't have FinancialTransaction records
        $allContributions = CampaignContribution::where('status', 'succeeded')->get();
        $contributionPaymentsCount = 0;
        $contributionVolume = 0;

        foreach ($allContributions as $contribution) {
            // Check if this contribution already has a FinancialTransaction
            $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                ->where('transaction_type', 'payment')
                ->exists();

            if (!$hasFinancialTransaction) {
                $contributionPaymentsCount++;
                $contributionVolume += $contribution->amount;
            }
        }

        // Total volume from FinancialTransaction
        $transactionVolume = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->sum('amount');

        $stats = [
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', 'active')->count(),
            'successful_campaigns' => Campaign::where('status', 'successful')->count(),
            'total_raised' => Campaign::sum('raised_amount') / 100,
            'total_users' => User::count(),
            'total_backers' => CampaignContribution::distinct('user_id')->count(),
            'total_payments' => $transactionPayments + $contributionPaymentsCount,
            'total_volume' => ($transactionVolume + $contributionVolume) / 100,
            'platform_fees' => FinancialTransaction::where('transaction_type', 'fee')
                ->where('status', 'completed')
                ->sum('amount') / 100,
            'monthly_stats' => [
                'new_users' => User::whereMonth('created_at', now()->month)->count(),
                'new_campaigns' => Campaign::whereMonth('created_at', now()->month)->count(),
                'payments' => FinancialTransaction::where('transaction_type', 'payment')
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->count() + collect(CampaignContribution::where('status', 'succeeded')
                    ->whereMonth('created_at', now()->month)
                    ->get())->filter(function($contribution) {
                        return !FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                            ->where('transaction_type', 'payment')
                            ->exists();
                    })->count(),
                'volume' => (FinancialTransaction::where('transaction_type', 'payment')
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount') + collect(CampaignContribution::where('status', 'succeeded')
                    ->whereMonth('created_at', now()->month)
                    ->get())->filter(function($contribution) {
                        return !FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                            ->where('transaction_type', 'payment')
                            ->exists();
                    })->sum('amount')) / 100,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Admin dashboard statistics retrieved successfully'
        ]);
    }

    /**
     * Get payment history for admin (all users' payments)
     */
    public function paymentHistory(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);

        // Get payments from FinancialTransaction table
        $financialTransactions = FinancialTransaction::with(['campaign', 'user'])
            ->where('transaction_type', 'payment');

        // Get contributions
        $contributions = CampaignContribution::with(['campaign', 'user'])
            ->where('status', 'succeeded');

        // Apply filters
        if ($request->has('user_id')) {
            $financialTransactions->where('user_id', $request->user_id);
            $contributions->where('user_id', $request->user_id);
        }

        if ($request->has('campaign_id')) {
            $financialTransactions->where('campaign_id', $request->campaign_id);
            $contributions->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('from_date')) {
            $financialTransactions->whereDate('created_at', '>=', $request->from_date);
            $contributions->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $financialTransactions->whereDate('created_at', '<=', $request->to_date);
            $contributions->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('payment_provider')) {
            $financialTransactions->where('payment_provider', $request->payment_provider);
            $contributions->where('payment_processor', $request->payment_provider);
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

        // Add Contributions
        foreach ($allContributions as $contribution) {
            // Only check for duplicate if transaction_id exists
            $hasFinancialTransaction = false;
            if ($contribution->transaction_id) {
                $hasFinancialTransaction = FinancialTransaction::where('external_transaction_id', $contribution->transaction_id)
                    ->where('transaction_type', 'payment')
                    ->exists();
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
     * Get available reports list
     */
    public function reportsAvailable(): JsonResponse
    {
        $reports = [
            'campaign_reports' => [
                'name' => 'Campaign Reports',
                'description' => 'View campaign performance metrics',
                'endpoint' => '/api/v1/reports/campaigns',
            ],
            'financial_reports' => [
                'name' => 'Financial Reports',
                'description' => 'Analyze financial trends',
                'endpoint' => '/api/v1/reports/financial',
            ],
            'user_reports' => [
                'name' => 'User Reports',
                'description' => 'User activity and engagement',
                'endpoint' => '/api/v1/reports/users',
            ],
            'analytics' => [
                'name' => 'Analytics',
                'description' => 'Platform performance metrics',
                'endpoint' => '/api/v1/reports/analytics',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $reports,
            'message' => 'Available reports retrieved successfully'
        ]);
    }

    /**
     * Get transaction notifications grouped by campaign
     */
    public function transactionNotifications(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);

        // Get transactions from FinancialTransaction
        $financialTransactions = FinancialTransaction::with(['user', 'campaign'])
            ->where('transaction_type', 'payment')
            ->whereIn('status', ['completed', 'pending', 'processing'])
            ->whereNotNull('campaign_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        // Get transactions from CampaignContribution
        $contributions = CampaignContribution::with(['user', 'campaign'])
            ->where('status', 'succeeded')
            ->whereNotNull('campaign_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        // Merge and group by campaign
        $notifications = collect();

        // Process FinancialTransactions
        foreach ($financialTransactions as $transaction) {
            $notifications->push([
                'id' => 'ft_' . $transaction->id,
                'type' => 'financial_transaction',
                'campaign_id' => $transaction->campaign_id,
                'campaign_name' => $transaction->campaign?->title ?? 'Unknown Campaign',
                'user_name' => $transaction->user?->name ?? 'Unknown User',
                'user_email' => $transaction->user?->email ?? '',
                'amount' => $transaction->amount,
                'currency' => $transaction->currency ?? 'USD',
                'status' => $transaction->status,
                'reference' => $transaction->reference,
                'created_at' => $transaction->created_at,
                'formatted_amount' => number_format($transaction->amount / 100, 2),
                'formatted_date' => $transaction->created_at->diffForHumans(),
            ]);
        }

        // Process Contributions
        foreach ($contributions as $contribution) {
            $notifications->push([
                'id' => 'cc_' . $contribution->id,
                'type' => 'campaign_contribution',
                'campaign_id' => $contribution->campaign_id,
                'campaign_name' => $contribution->campaign?->title ?? 'Unknown Campaign',
                'user_name' => $contribution->user?->name ?? 'Unknown User',
                'user_email' => $contribution->user?->email ?? '',
                'amount' => $contribution->amount,
                'currency' => $contribution->currency ?? 'USD',
                'status' => 'succeeded',
                'reference' => $contribution->transaction_id ?? $contribution->id,
                'created_at' => $contribution->created_at,
                'formatted_amount' => number_format($contribution->amount / 100, 2),
                'formatted_date' => $contribution->created_at->diffForHumans(),
            ]);
        }

        // Get read campaign IDs for the current user
        $readCampaignIds = TransactionNotificationRead::getReadCampaignIds(Auth::id());

        // Group by campaign and filter out read campaigns
        $grouped = $notifications->groupBy('campaign_id')
            ->filter(function ($items, $campaignId) use ($readCampaignIds) {
                // Only include campaigns that haven't been marked as read
                return !in_array($campaignId, $readCampaignIds);
            })
            ->map(function ($items, $campaignId) {
                $firstItem = $items->first();
                return [
                    'campaign_id' => $campaignId,
                    'campaign_name' => $firstItem['campaign_name'],
                    'total_transactions' => $items->count(),
                    'total_amount' => $items->sum('amount'),
                    'formatted_total_amount' => number_format($items->sum('amount') / 100, 2),
                    'currency' => $firstItem['currency'],
                    'transactions' => $items->take(10)->values()->all(), // Show latest 10 per campaign
                    'latest_transaction_date' => $items->max('created_at'),
                ];
            })
            ->values()
            ->take(20); // Show latest 20 campaigns

        // Calculate unread count (only unread campaigns)
        $unreadCount = $grouped->sum('total_transactions');

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $grouped,
                'unread_count' => $unreadCount,
                'total_campaigns' => $grouped->count(),
            ],
            'message' => 'Transaction notifications retrieved successfully'
        ]);
    }

    /**
     * Mark transaction notification as read (by campaign)
     */
    public function markNotificationRead(Request $request, $campaignId): JsonResponse
    {
        $userId = Auth::id();

        // Validate campaign exists
        if (!Campaign::find($campaignId)) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        // Mark as read
        TransactionNotificationRead::markAsRead($userId, $campaignId);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read'
        ]);
    }

    /**
     * Mark all transaction notifications as read
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // Get all campaigns with unread notifications
        $readCampaignIds = TransactionNotificationRead::getReadCampaignIds($userId);

        // Get all campaign IDs that have transactions
        $limit = $request->get('limit', 100);

        $financialTransactionCampaignIds = FinancialTransaction::where('transaction_type', 'payment')
            ->whereIn('status', ['completed', 'pending', 'processing'])
            ->whereNotNull('campaign_id')
            ->limit($limit)
            ->pluck('campaign_id')
            ->unique()
            ->toArray();

        $contributionCampaignIds = CampaignContribution::where('status', 'succeeded')
            ->whereNotNull('campaign_id')
            ->limit($limit)
            ->pluck('campaign_id')
            ->unique()
            ->toArray();

        $allCampaignIds = array_unique(array_merge($financialTransactionCampaignIds, $contributionCampaignIds));

        // Mark all as read
        foreach ($allCampaignIds as $campaignId) {
            if (!in_array($campaignId, $readCampaignIds)) {
                TransactionNotificationRead::markAsRead($userId, $campaignId);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
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
}
