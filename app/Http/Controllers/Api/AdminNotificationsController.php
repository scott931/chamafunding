<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignContribution;
use App\Models\FinancialTransaction;
use App\Models\TransactionNotificationRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminNotificationsController extends Controller
{
    /**
     * Get transaction notifications grouped by campaign
     */
    public function transactions(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $includeRead = $request->get('include_read', false);
        $userId = Auth::id();

        $financialTransactions = FinancialTransaction::with(['user', 'campaign'])
            ->where('transaction_type', 'payment')
            ->whereIn('status', ['completed', 'pending', 'processing'])
            ->whereNotNull('campaign_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $contributions = CampaignContribution::with(['user', 'campaign'])
            ->where('status', 'succeeded')
            ->whereNotNull('campaign_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        // Get read campaign IDs
        $readCampaignIds = TransactionNotificationRead::getReadCampaignIds($userId);

        $notifications = collect();

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
                'created_at' => $transaction->created_at->toIso8601String(),
                'formatted_amount' => number_format($transaction->amount / 100, 2),
                'formatted_date' => $transaction->created_at->diffForHumans(),
            ]);
        }

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
                'created_at' => $contribution->created_at->toIso8601String(),
                'formatted_amount' => number_format($contribution->amount / 100, 2),
                'formatted_date' => $contribution->created_at->diffForHumans(),
            ]);
        }

        // Group by campaign
        $grouped = $notifications->groupBy('campaign_id')
            ->map(function ($items, $campaignId) use ($readCampaignIds) {
                $firstItem = $items->first();
                $isRead = in_array($campaignId, $readCampaignIds);
                return [
                    'campaign_id' => $campaignId,
                    'campaign_name' => $firstItem['campaign_name'],
                    'total_transactions' => $items->count(),
                    'total_amount' => $items->sum('amount'),
                    'formatted_total_amount' => number_format($items->sum('amount') / 100, 2),
                    'currency' => $firstItem['currency'],
                    'transactions' => $items->take(10)->values()->all(),
                    'latest_transaction_date' => $items->max('created_at'),
                    'is_read' => $isRead,
                ];
            });

        // Filter out read campaigns if include_read is false
        if (!$includeRead) {
            $grouped = $grouped->filter(function ($item) {
                return !$item['is_read'];
            });
        }

        $grouped = $grouped->values()->take(20);

        // Calculate unread count
        $unreadCount = $grouped->filter(function ($item) {
            return !$item['is_read'];
        })->sum('total_transactions');

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $grouped,
                'total_campaigns' => $grouped->count(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark transaction notification as read (by campaign)
     */
    public function markAsRead(Request $request, $campaignId): JsonResponse
    {
        $userId = Auth::id();

        if (!Campaign::find($campaignId)) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        TransactionNotificationRead::markAsRead($userId, $campaignId);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read',
        ]);
    }

    /**
     * Mark all transaction notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 100);

        $readCampaignIds = TransactionNotificationRead::getReadCampaignIds($userId);

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

        foreach ($allCampaignIds as $campaignId) {
            if (!in_array($campaignId, $readCampaignIds)) {
                TransactionNotificationRead::markAsRead($userId, $campaignId);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get support tickets/issues (placeholder - can be extended)
     */
    public function support(Request $request): JsonResponse
    {
        // Pending campaigns for review
        $pendingCampaigns = Campaign::where('status', 'draft')
            ->with('creator:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'type' => 'pending_campaign',
                    'title' => $campaign->title,
                    'creator_name' => $campaign->creator->name ?? 'Unknown',
                    'creator_email' => $campaign->creator->email ?? '',
                    'created_at' => $campaign->created_at->toIso8601String(),
                    'formatted_date' => $campaign->created_at->diffForHumans(),
                ];
            });

        // Flagged projects (campaigns with multiple contributions from same user)
        $flaggedProjects = Campaign::where('status', 'active')
            ->whereHas('contributions', function ($q) {
                $q->select('campaign_id', 'user_id', \DB::raw('COUNT(*) as count'))
                    ->groupBy('campaign_id', 'user_id')
                    ->havingRaw('COUNT(*) > 10');
            })
            ->with('creator:id,name,email')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'type' => 'flagged',
                    'title' => $project->title,
                    'creator_name' => $project->creator->name ?? 'Unknown',
                    'creator_email' => $project->creator->email ?? '',
                    'reason' => 'Multiple contributions from same user',
                    'created_at' => $project->created_at->toIso8601String(),
                    'formatted_date' => $project->created_at->diffForHumans(),
                ];
            });

        // Suspicious campaigns (many small contributions)
        $suspiciousCampaigns = Campaign::where('status', 'active')
            ->whereHas('contributions', function ($q) {
                $q->where('amount', '<', 1000) // Less than $10
                    ->select('campaign_id', \DB::raw('COUNT(*) as count'))
                    ->groupBy('campaign_id')
                    ->havingRaw('COUNT(*) > 50');
            })
            ->with('creator:id,name,email')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'type' => 'suspicious',
                    'title' => $project->title,
                    'creator_name' => $project->creator->name ?? 'Unknown',
                    'creator_email' => $project->creator->email ?? '',
                    'reason' => 'Many small contributions detected',
                    'created_at' => $project->created_at->toIso8601String(),
                    'formatted_date' => $project->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'pending_campaigns' => $pendingCampaigns,
                'flagged_projects' => $flaggedProjects,
                'suspicious_campaigns' => $suspiciousCampaigns,
            ],
        ]);
    }

    /**
     * Get all notifications (transactions + support)
     */
    public function all(Request $request): JsonResponse
    {
        $transactions = $this->transactions($request);
        $support = $this->support($request);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->getData()->data,
                'support' => $support->getData()->data,
            ],
        ]);
    }
}

