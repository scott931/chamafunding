<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignContribution;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminReportsController extends Controller
{
    /**
     * Get available reports list
     */
    public function available(): JsonResponse
    {
        $reports = [
            'platform_overview' => [
                'name' => 'Platform Overview',
                'description' => 'High-level platform metrics and statistics',
                'endpoint' => '/api/v1/admin/reports/platform-overview',
                'icon' => 'dashboard',
            ],
            'all_projects' => [
                'name' => 'All Projects',
                'description' => 'Comprehensive list of all campaigns with filters',
                'endpoint' => '/api/v1/admin/reports/all-projects',
                'icon' => 'projects',
            ],
            'financial_summary' => [
                'name' => 'Financial Summary',
                'description' => 'Revenue, fees, and payout analysis',
                'endpoint' => '/api/v1/admin/reports/financial-summary',
                'icon' => 'money',
            ],
            'backer_report' => [
                'name' => 'Backer Report',
                'description' => 'Detailed contribution and backer analytics',
                'endpoint' => '/api/v1/admin/reports/backer-report',
                'icon' => 'users',
            ],
            'user_management' => [
                'name' => 'User Management',
                'description' => 'User activity and engagement metrics',
                'endpoint' => '/api/v1/admin/reports/user-management',
                'icon' => 'people',
            ],
            'support_moderation' => [
                'name' => 'Support & Moderation',
                'description' => 'Pending reviews and flagged content',
                'endpoint' => '/api/v1/admin/reports/support-moderation',
                'icon' => 'shield',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Platform Overview Report
     */
    public function platformOverview(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Total Money Pledged (All Time)
        $totalPledgedAllTime = Campaign::sum('raised_amount') / 100;

        // Total Money Pledged (This Month)
        $totalPledgedThisMonth = Campaign::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('raised_amount') / 100;

        // Active Projects
        $activeProjects = Campaign::where('status', 'active')->count();

        // Successful Projects
        $successfulProjects = Campaign::where('status', 'successful')->count();

        // Platform Fees (All Time)
        $platformFeesAllTime = FinancialTransaction::where('transaction_type', 'fee')
            ->where('status', 'completed')
            ->sum('amount') / 100;

        // Platform Fees (This Month)
        $platformFeesThisMonth = FinancialTransaction::where('transaction_type', 'fee')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount') / 100;

        // New User Registrations (This Week)
        $newUsersThisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();

        // New User Registrations (This Month)
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Total Users
        $totalUsers = User::count();

        // Total Backers
        $totalBackers = CampaignContribution::distinct('user_id')->count();

        // Pledges over time (last 30 days)
        $pledgesOverTime = Campaign::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(raised_amount) / 100 as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'amount' => (float) $item->total,
                ];
            });

        // Campaigns by status
        $campaignsByStatus = Campaign::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                ];
            });

        // Top campaigns by raised amount
        $topCampaigns = Campaign::with('creator:id,name,email')
            ->orderByDesc('raised_amount')
            ->limit(10)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'creator' => $campaign->creator->name ?? 'Unknown',
                    'raised' => $campaign->raised_amount / 100,
                    'goal' => $campaign->goal_amount / 100,
                    'progress' => $campaign->goal_amount > 0 
                        ? min(100, ($campaign->raised_amount / $campaign->goal_amount) * 100)
                        : 0,
                    'status' => $campaign->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_pledged_all_time' => $totalPledgedAllTime,
                    'total_pledged_this_month' => $totalPledgedThisMonth,
                    'active_projects' => $activeProjects,
                    'successful_projects' => $successfulProjects,
                    'platform_fees_all_time' => $platformFeesAllTime,
                    'platform_fees_this_month' => $platformFeesThisMonth,
                    'new_users_this_week' => $newUsersThisWeek,
                    'new_users_this_month' => $newUsersThisMonth,
                    'total_users' => $totalUsers,
                    'total_backers' => $totalBackers,
                ],
                'charts' => [
                    'pledges_over_time' => $pledgesOverTime,
                    'campaigns_by_status' => $campaignsByStatus,
                ],
                'top_campaigns' => $topCampaigns,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * All Projects Report
     */
    public function allProjects(Request $request): JsonResponse
    {
        $query = Campaign::with(['creator:id,name,email']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('success_filter')) {
            if ($request->success_filter === 'successful') {
                $query->where('status', 'successful');
            } elseif ($request->success_filter === 'failed') {
                $query->where('status', 'failed');
            }
        }

        $perPage = min(max((int) $request->get('per_page', 20), 5), 100);
        $projects = $query->orderByDesc('created_at')->paginate($perPage);

        $statuses = Campaign::distinct()->pluck('status');

        return response()->json([
            'success' => true,
            'data' => [
                'projects' => $projects,
                'statuses' => $statuses,
                'filters' => $request->only(['status', 'start_date', 'end_date', 'success_filter']),
            ],
        ]);
    }

    /**
     * Financial Summary Report
     */
    public function financialSummary(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $statusFilter = $request->get('status', null);

        $query = Campaign::with(['creator:id,name,email']);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $campaigns = $query->whereBetween('created_at', [$startDate, $endDate])
            ->orWhere(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('deadline', [$startDate, $endDate]);
            })
            ->orderByDesc('created_at')
            ->get();

        $platformFeePercentage = PlatformSetting::getFloat('fee_structure.platform_fee_percentage', 5.0);
        $platformFeeFixed = PlatformSetting::getFloat('fee_structure.platform_fee_fixed', 0.0);

        $financialData = [];
        $totalGross = 0;
        $totalFees = 0;
        $totalPayouts = 0;

        foreach ($campaigns as $campaign) {
            $grossPledges = $campaign->raised_amount / 100;

            $actualFees = FinancialTransaction::where('campaign_id', $campaign->id)
                ->where('transaction_type', 'fee')
                ->where('status', 'completed')
                ->sum('amount');

            if ($actualFees > 0) {
                $fees = $actualFees / 100;
            } else {
                $fees = ($grossPledges * ($platformFeePercentage / 100)) + ($platformFeeFixed * $campaign->contributions()->count());
            }

            $payoutAmount = $grossPledges - $fees;

            $financialData[] = [
                'campaign_id' => $campaign->id,
                'campaign_title' => $campaign->title,
                'creator_name' => $campaign->creator->name ?? 'Unknown',
                'status' => $campaign->status,
                'gross_pledges' => $grossPledges,
                'platform_fees' => $fees,
                'payout_amount' => $payoutAmount,
            ];

            $totalGross += $grossPledges;
            $totalFees += $fees;
            $totalPayouts += $payoutAmount;
        }

        // Revenue over time chart data
        $revenueOverTime = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(amount) / 100 as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->total,
                ];
            });

        // Fees over time
        $feesOverTime = FinancialTransaction::where('transaction_type', 'fee')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(amount) / 100 as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'fees' => (float) $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'financial_data' => $financialData,
                'totals' => [
                    'total_gross' => $totalGross,
                    'total_fees' => $totalFees,
                    'total_payouts' => $totalPayouts,
                ],
                'charts' => [
                    'revenue_over_time' => $revenueOverTime,
                    'fees_over_time' => $feesOverTime,
                ],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status_filter' => $statusFilter,
                'platform_fee_percentage' => $platformFeePercentage,
                'platform_fee_fixed' => $platformFeeFixed,
            ],
        ]);
    }

    /**
     * Backer Report
     */
    public function backerReport(Request $request): JsonResponse
    {
        $query = CampaignContribution::with(['user:id,name,email', 'campaign:id,title', 'rewardTier:id,name']);

        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $perPage = min(max((int) $request->get('per_page', 50), 5), 200);
        $contributions = $query->orderByDesc('created_at')->paginate($perPage);

        $backerData = [];
        foreach ($contributions as $contribution) {
            $paymentStatus = 'Paid';
            if ($contribution->status === 'refunded') {
                $paymentStatus = 'Refunded';
            } elseif ($contribution->status === 'failed') {
                $paymentStatus = 'Failed';
            } elseif ($contribution->status === 'pending') {
                $paymentStatus = 'Pending';
            }

            $backerData[] = [
                'backer_name' => $contribution->user->name ?? 'N/A',
                'backer_email' => $contribution->user->email ?? 'N/A',
                'project_name' => $contribution->campaign->title ?? 'N/A',
                'project_id' => $contribution->campaign_id,
                'pledge_amount' => $contribution->amount / 100,
                'reward_tier' => $contribution->rewardTier->name ?? 'No Reward Selected',
                'pledge_date' => $contribution->created_at->toIso8601String(),
                'payment_status' => $paymentStatus,
            ];
        }

        // Backer statistics
        $totalBackers = CampaignContribution::distinct('user_id')->count();
        $totalPledged = CampaignContribution::where('status', 'succeeded')->sum('amount') / 100;
        $averagePledge = CampaignContribution::where('status', 'succeeded')->avg('amount') / 100;

        // Top backers
        $topBackers = CampaignContribution::where('status', 'succeeded')
            ->select('user_id', DB::raw('SUM(amount) / 100 as total_pledged'), DB::raw('COUNT(*) as contribution_count'))
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderByDesc('total_pledged')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'name' => $item->user->name ?? 'Unknown',
                    'email' => $item->user->email ?? 'N/A',
                    'total_pledged' => (float) $item->total_pledged,
                    'contribution_count' => (int) $item->contribution_count,
                ];
            });

        // Pledges by status
        $pledgesByStatus = CampaignContribution::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) / 100 as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                    'total' => (float) $item->total,
                ];
            });

        $campaigns = Campaign::orderBy('title')->get(['id', 'title']);

        return response()->json([
            'success' => true,
            'data' => [
                'backer_data' => $backerData,
                'pagination' => [
                    'current_page' => $contributions->currentPage(),
                    'last_page' => $contributions->lastPage(),
                    'per_page' => $contributions->perPage(),
                    'total' => $contributions->total(),
                ],
                'statistics' => [
                    'total_backers' => $totalBackers,
                    'total_pledged' => $totalPledged,
                    'average_pledge' => $averagePledge,
                ],
                'top_backers' => $topBackers,
                'pledges_by_status' => $pledgesByStatus,
                'campaigns' => $campaigns,
                'filters' => $request->only(['campaign_id', 'start_date', 'end_date']),
            ],
        ]);
    }

    /**
     * User Management Report
     */
    public function userManagement(Request $request): JsonResponse
    {
        $query = User::withCount(['campaigns', 'contributions']);

        if ($request->has('user_type')) {
            if ($request->user_type === 'creator') {
                $query->has('campaigns');
            } elseif ($request->user_type === 'backer') {
                $query->has('contributions');
            } elseif ($request->user_type === 'both') {
                $query->has('campaigns')->has('contributions');
            }
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $perPage = min(max((int) $request->get('per_page', 50), 5), 200);
        $users = $query->orderByDesc('created_at')->paginate($perPage);

        $userData = [];
        foreach ($users as $user) {
            $totalPledged = $user->contributions()->sum('amount') / 100;
            $totalRaised = $user->campaigns()->sum('raised_amount') / 100;

            $userType = 'Backer';
            if ($user->campaigns_count > 0 && $user->contributions_count > 0) {
                $userType = 'Both';
            } elseif ($user->campaigns_count > 0) {
                $userType = 'Creator';
            }

            $userData[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'date_registered' => $user->created_at->toIso8601String(),
                'user_type' => $userType,
                'projects_created' => $user->campaigns_count,
                'total_pledged' => $totalPledged,
                'total_raised' => $totalRaised,
                'account_status' => $user->is_approved ? 'Active' : 'Pending',
            ];
        }

        // User registration over time
        $registrationsOverTime = User::whereBetween('created_at', [
            $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            $request->get('end_date', now()->format('Y-m-d'))
        ])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => (int) $item->count,
                ];
            });

        // Users by type
        $usersByType = [
            'Creators' => User::has('campaigns')->doesntHave('contributions')->count(),
            'Backers' => User::has('contributions')->doesntHave('campaigns')->count(),
            'Both' => User::has('campaigns')->has('contributions')->count(),
            'Inactive' => User::doesntHave('campaigns')->doesntHave('contributions')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user_data' => $userData,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
                'charts' => [
                    'registrations_over_time' => $registrationsOverTime,
                    'users_by_type' => $usersByType,
                ],
                'filters' => $request->only(['user_type', 'start_date', 'end_date']),
            ],
        ]);
    }

    /**
     * Support & Moderation Report
     */
    public function supportModeration(Request $request): JsonResponse
    {
        $pendingProjects = Campaign::whereIn('status', ['draft'])
            ->with('creator:id,name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'creator_name' => $project->creator->name ?? 'Unknown',
                    'creator_email' => $project->creator->email ?? 'N/A',
                    'created_at' => $project->created_at->toIso8601String(),
                    'reason' => 'Project pending approval',
                ];
            });

        $flaggedProjects = Campaign::where('status', 'active')
            ->whereHas('contributions', function ($q) {
                $q->select('campaign_id', 'user_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('campaign_id', 'user_id')
                    ->havingRaw('COUNT(*) > 10');
            })
            ->with('creator:id,name,email')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'creator_name' => $project->creator->name ?? 'Unknown',
                    'creator_email' => $project->creator->email ?? 'N/A',
                    'created_at' => $project->created_at->toIso8601String(),
                    'reason' => 'Multiple contributions from same user',
                ];
            });

        $suspiciousCampaigns = Campaign::where('status', 'active')
            ->whereHas('contributions', function ($q) {
                $q->where('amount', '<', 1000)
                    ->select('campaign_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('campaign_id')
                    ->havingRaw('COUNT(*) > 50');
            })
            ->with('creator:id,name,email')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'creator_name' => $project->creator->name ?? 'Unknown',
                    'creator_email' => $project->creator->email ?? 'N/A',
                    'created_at' => $project->created_at->toIso8601String(),
                    'reason' => 'Many small contributions detected',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'pending_projects' => $pendingProjects,
                'flagged_projects' => $flaggedProjects,
                'suspicious_campaigns' => $suspiciousCampaigns,
                'summary' => [
                    'pending_count' => $pendingProjects->count(),
                    'flagged_count' => $flaggedProjects->count(),
                    'suspicious_count' => $suspiciousCampaigns->count(),
                ],
            ],
        ]);
    }
}

