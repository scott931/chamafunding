<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\CampaignContribution;
use App\Models\SavingsAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display admin dashboard with platform overview.
     */
    public function index()
    {
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
            'pending_payouts' => Campaign::where('status', 'successful')
                ->sum('raised_amount') / 100,
            'open_support_tickets' => 0, // Placeholder - implement support system
            'total_users' => User::count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'new_campaigns_this_month' => Campaign::whereMonth('created_at', now()->month)->count(),
        ];

        // Funding over time (last 30 days)
        $fundingOverTime = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

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
        $activities = [];

        // Large pledges
        $largePledges = FinancialTransaction::where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->where('amount', '>=', 100000) // $1000 or more
            ->with(['campaign', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($tx) {
                return [
                    'type' => 'large_pledge',
                    'message' => "Large pledge of $" . number_format($tx->amount / 100, 2) . " processed on campaign: " . ($tx->campaign->title ?? 'N/A'),
                    'time' => $tx->created_at,
                    'url' => $tx->campaign ? route('admin.campaigns.show', $tx->campaign_id) : null,
                ];
            });

        // New high-value campaigns
        $highValueCampaigns = Campaign::where('goal_amount', '>=', 10000000) // $100k or more
            ->where('created_at', '>=', now()->subDays(7))
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'type' => 'high_value_campaign',
                    'message' => "New campaign '{$campaign->title}' launched with a $" . number_format($campaign->goal_amount / 100, 0) . "+ goal",
                    'time' => $campaign->created_at,
                    'url' => route('admin.campaigns.show', $campaign->id),
                ];
            });

        // Campaigns under review or flagged
        $flaggedCampaigns = Campaign::whereIn('status', ['draft', 'pending'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'type' => 'flagged',
                    'message' => "Campaign '{$campaign->title}' is {$campaign->status} and requires review",
                    'time' => $campaign->created_at,
                    'url' => route('admin.campaigns.show', $campaign->id),
                ];
            });

        $activities = collect([...$largePledges, ...$highValueCampaigns, ...$flaggedCampaigns])
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
        $query = FinancialTransaction::with(['user', 'campaign', 'savingsAccount']);

        if ($request->has('type') && $request->type) {
            $query->where('transaction_type', $request->type);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->orderByDesc('created_at')->paginate(50);

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
     */
    public function reports()
    {
        return view('admin::reports.index');
    }
}
