<?php

namespace Modules\Crowdfunding\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignContribution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CrowdfundingController extends Controller
{
    /**
     * Display a listing of campaigns
     */
    public function index(Request $request)
    {
        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            $query = Campaign::with(['creator', 'contributions'])
                ->withCount('contributions');

            // For regular users (non-admins) and public access, only show active, successful, or closed campaigns by default
            $user = auth()->user();
            $isAdmin = $user && $user->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor']);

            // Filter by status - if not specified and user is not admin, show only active/successful/closed
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } elseif (!$isAdmin) {
                // Regular users and public visitors only see active, successful, or closed campaigns
                $query->whereIn('status', ['active', 'successful', 'closed']);
            }

            // Admins can see all campaigns including drafts

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Search by title or description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by user's campaigns
            if ($request->has('my_campaigns') && $request->my_campaigns) {
                $query->where('created_by', Auth::id());
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            if (in_array($sortBy, ['created_at', 'goal_amount', 'raised_amount', 'deadline'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $campaigns = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $campaigns,
                'message' => 'Campaigns retrieved successfully'
            ]);
        }

        // For web requests, return view
        $user = auth()->user();
        $isAdmin = $user && $user->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor']);

        $query = Campaign::with(['creator', 'contributions'])
            ->withCount('contributions');

        // Regular users only see active/successful/closed campaigns
        if (!$isAdmin) {
            $query->whereIn('status', ['active', 'successful', 'closed']);
        }

        $campaigns = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('crowdfunding::index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new campaign
     * Only admins can access this
     */
    public function create()
    {
        // Only allow admins to create campaigns
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor'])) {
            abort(403, 'Unauthorized. Only administrators can create campaigns.');
        }

        return view('crowdfunding::create');
    }

    /**
     * Store a newly created campaign
     * Only admins can create campaigns - regular users can only contribute
     */
    public function store(Request $request)
    {
        // Only allow admins to create campaigns
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor'])) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only administrators can create campaigns. Regular users can contribute to existing campaigns.',
                ], 403);
            }
            abort(403, 'Unauthorized. Only administrators can create campaigns.');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:emergency,project,community,education,health,environment',
            'description' => 'required|string|min:50',
            'goal_amount' => 'required|numeric|min:100',
            'currency' => 'required|string|size:3',
            'deadline' => 'nullable|date|after:today',
            'starts_at' => 'nullable|date|after_or_equal:today',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $campaign = Campaign::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title) . '-' . time(),
                'category' => $request->category,
                'description' => $request->description,
                'created_by' => Auth::id(),
                'goal_amount' => $request->goal_amount * 100, // Convert to cents
                'currency' => strtoupper($request->currency),
                'deadline' => $request->deadline,
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'status' => 'draft',
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $campaign->load('creator'),
                    'message' => 'Campaign created successfully'
                ], 201);
            }

            return redirect()->route('crowdfunding.index')
                ->with('success', 'Campaign created successfully! You can activate it to make it visible to users.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create campaign',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create campaign: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified campaign
     */
    public function show(Request $request, $id)
    {
        $campaign = Campaign::with(['creator', 'contributions.user', 'rewardTiers'])
            ->withCount('contributions')
            ->findOrFail($id);

        // Regular users can only view active, successful, or closed campaigns
        // Admins can view all campaigns including drafts
        $user = auth()->user();
        $isAdmin = $user && $user->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor']);

        if (!$isAdmin && !in_array($campaign->status, ['active', 'successful', 'closed'])) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This campaign is not yet available for viewing.'
                ], 404);
            }
            abort(404, 'This campaign is not yet available for viewing.');
        }

        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $campaign,
                'message' => 'Campaign retrieved successfully'
            ]);
        }

        // For web requests, return view
        return view('crowdfunding::show', compact('campaign'));
    }

    /**
     * Update the specified campaign
     * Only admins can update campaigns - regular users can only contribute
     */
    public function update(Request $request, $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        // Only allow admins to update campaigns
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Moderator', 'Treasurer', 'Secretary', 'Auditor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only administrators can update campaigns. Regular users can contribute to existing campaigns.',
            ], 403);
        }

        // Check if campaign can be updated
        if ($campaign->status === 'active' && $campaign->raised_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update active campaign with contributions'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|in:emergency,project,community,education,health,environment',
            'description' => 'sometimes|string|min:50',
            'goal_amount' => 'sometimes|numeric|min:100',
            'currency' => 'sometimes|string|size:3',
            'deadline' => 'nullable|date|after:today',
            'starts_at' => 'nullable|date|after_or_equal:today',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only([
                'title', 'category', 'description', 'currency', 'deadline', 'starts_at', 'ends_at'
            ]);

            if ($request->has('goal_amount')) {
                $updateData['goal_amount'] = $request->goal_amount * 100;
            }

            if ($request->has('title')) {
                $updateData['slug'] = Str::slug($request->title) . '-' . $campaign->id;
            }

            $campaign->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $campaign->load('creator'),
                'message' => 'Campaign updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified campaign
     * Only admins can delete campaigns - regular users can only contribute
     */
    public function destroy($id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        // Only allow admins to delete campaigns
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Moderator', 'Treasurer', 'Secretary', 'Auditor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only administrators can delete campaigns. Regular users can contribute to existing campaigns.',
            ], 403);
        }

        // Check if campaign can be deleted
        if ($campaign->status === 'active' && $campaign->raised_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active campaign with contributions'
            ], 400);
        }

        try {
            $campaign->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make a contribution to a campaign
     */
    public function contribute(Request $request, $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        // Check if campaign is active
        if (!$campaign->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is not active'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|string',
            'payment_processor' => 'required|string|in:stripe,paypal,mpesa,flutterwave',
            'transaction_id' => 'nullable|string',
            'reward_tier_id' => 'nullable|exists:reward_tiers,id',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_country' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_phone' => 'nullable|string|max:20',
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

            $contribution = CampaignContribution::create([
                'campaign_id' => $campaign->id,
                'user_id' => Auth::id(),
                'amount' => $request->amount * 100, // Convert to cents
                'currency' => strtoupper($request->currency),
                'payment_processor' => $request->payment_processor,
                'transaction_id' => $request->transaction_id,
                'reward_tier_id' => $request->reward_tier_id,
                'status' => $request->status ?? 'succeeded', // Default to succeeded if payment was processed
            ]);

            // Create contribution detail if reward tier requires shipping or has details
            if ($request->reward_tier_id || $request->has('shipping_address')) {
                $detail = \App\Models\ContributionDetail::create([
                    'contribution_id' => $contribution->id,
                    'reward_tier_id' => $request->reward_tier_id,
                    'shipping_name' => $request->shipping_name,
                    'shipping_address' => $request->shipping_address,
                    'shipping_city' => $request->shipping_city,
                    'shipping_state' => $request->shipping_state,
                    'shipping_country' => $request->shipping_country,
                    'shipping_postal_code' => $request->shipping_postal_code,
                    'shipping_phone' => $request->shipping_phone,
                ]);
            }

            // Update campaign raised amount only if status is succeeded
            if ($contribution->status === 'succeeded') {
                $campaign->increment('raised_amount', $contribution->amount);

                // Check if campaign goal is reached
                if ($campaign->raised_amount >= $campaign->goal_amount) {
                    $campaign->update(['status' => 'successful']);
                }

                // Increment reward tier quantity claimed if applicable
                if ($request->reward_tier_id) {
                    \App\Models\RewardTier::where('id', $request->reward_tier_id)
                        ->increment('quantity_claimed');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $contribution->load(['user', 'rewardTier', 'detail']),
                'message' => 'Contribution made successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to make contribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign contributions
     */
    public function contributions($id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $contributions = $campaign->contributions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $contributions,
            'message' => 'Contributions retrieved successfully'
        ]);
    }

    /**
     * Get campaign analytics
     */
    public function analytics($id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $analytics = [
            'total_contributions' => $campaign->contributions()->count(),
            'total_raised' => $campaign->raised_amount,
            'goal_amount' => $campaign->goal_amount,
            'progress_percentage' => $campaign->progress_percentage,
            'average_contribution' => $campaign->contributions()->avg('amount') ?? 0,
            'contributions_by_status' => $campaign->contributions()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'contributions_timeline' => $campaign->contributions()
                ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Analytics retrieved successfully'
        ]);
    }

    /**
     * Activate/Publish a campaign (change status from draft to active)
     * Only admins can activate campaigns
     */
    public function activate(Request $request, $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        // Only allow admins to activate campaigns
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only administrators can activate campaigns.',
            ], 403);
        }

        // Only draft campaigns can be activated
        if ($campaign->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => "Campaign status is '{$campaign->status}'. Only draft campaigns can be activated.",
            ], 400);
        }

        try {
            $campaign->update([
                'status' => 'active',
                'starts_at' => $campaign->starts_at ?? now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $campaign->load('creator'),
                'message' => 'Campaign activated successfully and is now visible to all users'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search campaigns
     */
    public function search(Request $request): JsonResponse
    {
        $query = Campaign::with(['creator'])
            ->withCount('contributions');

        // For regular users, only show active/successful/closed campaigns
        $user = auth()->user();
        $isAdmin = $user && $user->hasAnyRole(['Super Admin', 'Moderator', 'Financial Admin', 'Treasurer', 'Secretary', 'Auditor']);

        if (!$isAdmin) {
            $query->whereIn('status', ['active', 'successful', 'closed']);
        }

        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('min_amount')) {
            $query->where('goal_amount', '>=', $request->min_amount * 100);
        }

        if ($request->has('max_amount')) {
            $query->where('goal_amount', '<=', $request->max_amount * 100);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $campaigns,
            'message' => 'Search results retrieved successfully'
        ]);
    }
}
