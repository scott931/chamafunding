<?php

namespace Modules\Crowdfunding\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignContribution;
use App\Models\CampaignUpdate;
use App\Models\ContributionDetail;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;
use App\Models\SavedCampaign;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BackerDashboardController extends Controller
{
    /**
     * Get all backed projects/pledges for the authenticated user
     */
    public function myPledges(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = CampaignContribution::with([
                'campaign' => function ($q) {
                    $q->with(['creator', 'rewardTiers']);
                },
                'rewardTier',
                'detail',
            ])
            ->where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->orderBy('created_at', 'desc');

        // Filter by campaign status
        if ($request->has('campaign_status')) {
            $status = $request->campaign_status;
            $query->whereHas('campaign', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $contributions = $query->paginate($request->get('per_page', 15));

        // Transform the data for the frontend
        $pledges = $contributions->getCollection()->map(function ($contribution) {
            $campaign = $contribution->campaign;
            $detail = $contribution->detail;

            return [
                'id' => $contribution->id,
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'featured_image' => $campaign->featured_image,
                    'status' => $campaign->status,
                    'progress_percentage' => $campaign->progress_percentage,
                    'goal_amount' => $campaign->formatted_goal_amount,
                    'raised_amount' => $campaign->formatted_raised_amount,
                    'currency' => $campaign->currency,
                    'deadline' => $campaign->deadline?->format('Y-m-d'),
                ],
                'creator' => [
                    'id' => $campaign->creator->id,
                    'name' => $campaign->creator->name,
                ],
                'pledge' => [
                    'amount' => $contribution->formatted_amount,
                    'currency' => $contribution->currency,
                    'date' => $contribution->created_at->format('Y-m-d H:i:s'),
                ],
                'reward_tier' => $contribution->rewardTier ? [
                    'id' => $contribution->rewardTier->id,
                    'name' => $contribution->rewardTier->name,
                    'description' => $contribution->rewardTier->description,
                    'estimated_delivery' => $contribution->rewardTier->estimated_delivery_date,
                ] : null,
                'fulfillment' => [
                    'delivery_status' => $detail?->delivery_status ?? 'pending',
                    'survey_completed' => $detail?->survey_completed ?? false,
                    'has_shipping_address' => $detail?->hasShippingAddress() ?? false,
                    'tracking_number' => $detail?->tracking_number,
                    'tracking_carrier' => $detail?->tracking_carrier,
                    'shipped_at' => $detail?->shipped_at?->format('Y-m-d H:i:s'),
                    'delivered_at' => $detail?->delivered_at?->format('Y-m-d H:i:s'),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'pledges' => $pledges,
                'pagination' => [
                    'current_page' => $contributions->currentPage(),
                    'last_page' => $contributions->lastPage(),
                    'per_page' => $contributions->perPage(),
                    'total' => $contributions->total(),
                ],
            ],
            'message' => 'Pledges retrieved successfully',
        ]);
    }

    /**
     * Get detailed pledge information
     */
    public function pledgeDetails($contributionId): JsonResponse
    {
        $user = Auth::user();

        $contribution = CampaignContribution::with([
                'campaign' => function ($q) {
                    $q->with(['creator', 'rewardTiers']);
                },
                'rewardTier',
                'detail',
            ])
            ->where('user_id', $user->id)
            ->where('id', $contributionId)
            ->firstOrFail();

        $campaign = $contribution->campaign;
        $detail = $contribution->detail;

        $data = [
            'contribution' => [
                'id' => $contribution->id,
                'amount' => $contribution->formatted_amount,
                'currency' => $contribution->currency,
                'status' => $contribution->status,
                'payment_processor' => $contribution->payment_processor,
                'transaction_id' => $contribution->transaction_id,
                'created_at' => $contribution->created_at->format('Y-m-d H:i:s'),
            ],
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'featured_image' => $campaign->featured_image,
                'images' => $campaign->images ?? [],
                'status' => $campaign->status,
                'progress_percentage' => $campaign->progress_percentage,
                'goal_amount' => $campaign->formatted_goal_amount,
                'raised_amount' => $campaign->formatted_raised_amount,
                'currency' => $campaign->currency,
                'deadline' => $campaign->deadline?->format('Y-m-d'),
            ],
            'creator' => [
                'id' => $campaign->creator->id,
                'name' => $campaign->creator->name,
                'email' => $campaign->creator->email,
            ],
            'reward_tier' => $contribution->rewardTier ? [
                'id' => $contribution->rewardTier->id,
                'name' => $contribution->rewardTier->name,
                'description' => $contribution->rewardTier->description,
                'reward_type' => $contribution->rewardTier->reward_type,
                'requires_shipping' => $contribution->rewardTier->requires_shipping,
                'estimated_delivery' => $contribution->rewardTier->estimated_delivery_date,
            ] : null,
            'shipping' => $detail ? [
                'name' => $detail->shipping_name,
                'address' => $detail->shipping_address,
                'city' => $detail->shipping_city,
                'state' => $detail->shipping_state,
                'country' => $detail->shipping_country,
                'postal_code' => $detail->shipping_postal_code,
                'phone' => $detail->shipping_phone,
                'full_address' => $detail->full_shipping_address,
            ] : null,
            'survey' => [
                'completed' => $detail?->survey_completed ?? false,
                'completed_at' => $detail?->survey_completed_at?->format('Y-m-d H:i:s'),
                'responses' => $detail?->survey_responses ?? null,
            ],
            'delivery' => [
                'status' => $detail?->delivery_status ?? 'pending',
                'tracking_number' => $detail?->tracking_number,
                'tracking_carrier' => $detail?->tracking_carrier,
                'shipped_at' => $detail?->shipped_at?->format('Y-m-d H:i:s'),
                'delivered_at' => $detail?->delivered_at?->format('Y-m-d H:i:s'),
            ],
            'digital_rewards' => $detail?->digital_rewards ?? null,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Pledge details retrieved successfully',
        ]);
    }

    /**
     * Update shipping address for a contribution
     */
    public function updateShippingAddress(Request $request, $contributionId): JsonResponse
    {
        $user = Auth::user();

        $contribution = CampaignContribution::where('user_id', $user->id)
            ->where('id', $contributionId)
            ->with(['rewardTier', 'detail'])
            ->firstOrFail();

        // Check if shipping is required
        if (!$contribution->rewardTier || !$contribution->rewardTier->requires_shipping) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping address is not required for this reward tier',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_country' => 'required|string|max:100',
            'shipping_postal_code' => 'required|string|max:20',
            'shipping_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $detail = $contribution->detail;

        if (!$detail) {
            $detail = ContributionDetail::create([
                'contribution_id' => $contribution->id,
                'reward_tier_id' => $contribution->reward_tier_id,
            ]);
        }

        $detail->update([
            'shipping_name' => $request->shipping_name,
            'shipping_address' => $request->shipping_address,
            'shipping_city' => $request->shipping_city,
            'shipping_state' => $request->shipping_state,
            'shipping_country' => $request->shipping_country,
            'shipping_postal_code' => $request->shipping_postal_code,
            'shipping_phone' => $request->shipping_phone,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'shipping' => [
                    'name' => $detail->shipping_name,
                    'address' => $detail->shipping_address,
                    'city' => $detail->shipping_city,
                    'state' => $detail->shipping_state,
                    'country' => $detail->shipping_country,
                    'postal_code' => $detail->shipping_postal_code,
                    'phone' => $detail->shipping_phone,
                    'full_address' => $detail->full_shipping_address,
                ],
            ],
            'message' => 'Shipping address updated successfully',
        ]);
    }

    /**
     * Get unified updates feed from all backed campaigns
     */
    public function updatesFeed(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Get all campaign IDs the user has contributed to
        $campaignIds = CampaignContribution::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->distinct()
            ->pluck('campaign_id');

        $query = CampaignUpdate::with(['campaign', 'author'])
            ->whereIn('campaign_id', $campaignIds)
            ->published()
            ->orderBy('published_at', 'desc');

        // Filter by campaign if specified
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $updates = $query->paginate($request->get('per_page', 20));

        $feed = $updates->getCollection()->map(function ($update) {
            return [
                'id' => $update->id,
                'campaign' => [
                    'id' => $update->campaign->id,
                    'title' => $update->campaign->title,
                    'slug' => $update->campaign->slug,
                    'featured_image' => $update->campaign->featured_image,
                ],
                'title' => $update->title,
                'content' => $update->content,
                'type' => $update->type,
                'author' => [
                    'id' => $update->author->id,
                    'name' => $update->author->name,
                ],
                'published_at' => $update->published_at->format('Y-m-d H:i:s'),
                'published_at_human' => $update->published_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'updates' => $feed,
                'pagination' => [
                    'current_page' => $updates->currentPage(),
                    'last_page' => $updates->lastPage(),
                    'per_page' => $updates->perPage(),
                    'total' => $updates->total(),
                ],
            ],
            'message' => 'Updates feed retrieved successfully',
        ]);
    }

    /**
     * Get transaction history for the user
     */
    public function transactionHistory(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = CampaignContribution::with(['campaign', 'rewardTier'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

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

        $transactions = $query->paginate($request->get('per_page', 20));

        $history = $transactions->getCollection()->map(function ($contribution) {
            return [
                'id' => $contribution->id,
                'transaction_id' => $contribution->transaction_id,
                'campaign' => [
                    'id' => $contribution->campaign->id,
                    'title' => $contribution->campaign->title,
                    'slug' => $contribution->campaign->slug,
                ],
                'amount' => $contribution->formatted_amount,
                'currency' => $contribution->currency,
                'payment_method' => $this->getPaymentMethodDisplay($contribution->payment_processor),
                'status' => $contribution->status,
                'date' => $contribution->created_at->format('Y-m-d H:i:s'),
                'date_human' => $contribution->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $history,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ],
            'message' => 'Transaction history retrieved successfully',
        ]);
    }

    /**
     * Generate and return receipt for a transaction
     */
    public function downloadReceipt($contributionId): JsonResponse
    {
        $user = Auth::user();

        $contribution = CampaignContribution::with([
                'campaign' => function ($q) {
                    $q->with('creator');
                },
                'rewardTier',
            ])
            ->where('user_id', $user->id)
            ->where('id', $contributionId)
            ->firstOrFail();

        if ($contribution->status !== 'succeeded') {
            return response()->json([
                'success' => false,
                'message' => 'Receipt is only available for successful transactions',
            ], 400);
        }

        $receipt = [
            'receipt_number' => 'RCP-' . str_pad($contribution->id, 8, '0', STR_PAD_LEFT),
            'date' => $contribution->created_at->format('F d, Y'),
            'time' => $contribution->created_at->format('H:i:s'),
            'transaction_id' => $contribution->transaction_id,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'campaign' => [
                'title' => $contribution->campaign->title,
                'creator' => $contribution->campaign->creator->name,
            ],
            'contribution' => [
                'amount' => $contribution->formatted_amount,
                'currency' => $contribution->currency,
                'payment_processor' => $contribution->payment_processor,
                'reward_tier' => $contribution->rewardTier?->name,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $receipt,
            'message' => 'Receipt generated successfully',
        ]);
    }

    /**
     * Save a campaign to watchlist
     */
    public function saveCampaign(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        $saved = SavedCampaign::firstOrCreate([
            'user_id' => $user->id,
            'campaign_id' => $request->campaign_id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'campaign_id' => $saved->campaign_id,
                'saved_at' => $saved->created_at->format('Y-m-d H:i:s'),
            ],
            'message' => 'Campaign saved successfully',
        ]);
    }

    /**
     * Remove a campaign from watchlist
     */
    public function unsaveCampaign($campaignId): JsonResponse
    {
        $user = Auth::user();

        $saved = SavedCampaign::where('user_id', $user->id)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        $saved->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign removed from watchlist',
        ]);
    }

    /**
     * Get saved campaigns (watchlist)
     */
    public function savedCampaigns(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = SavedCampaign::with(['campaign' => function ($q) {
                $q->with(['creator', 'rewardTiers']);
            }])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        $saved = $query->paginate($request->get('per_page', 15));

        $campaigns = $saved->getCollection()->map(function ($savedCampaign) {
            $campaign = $savedCampaign->campaign;
            return [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'featured_image' => $campaign->featured_image,
                'status' => $campaign->status,
                'progress_percentage' => $campaign->progress_percentage,
                'goal_amount' => $campaign->formatted_goal_amount,
                'raised_amount' => $campaign->formatted_raised_amount,
                'currency' => $campaign->currency,
                'deadline' => $campaign->deadline?->format('Y-m-d'),
                'creator' => [
                    'id' => $campaign->creator->id,
                    'name' => $campaign->creator->name,
                ],
                'saved_at' => $savedCampaign->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'campaigns' => $campaigns,
                'pagination' => [
                    'current_page' => $saved->currentPage(),
                    'last_page' => $saved->lastPage(),
                    'per_page' => $saved->perPage(),
                    'total' => $saved->total(),
                ],
            ],
            'message' => 'Saved campaigns retrieved successfully',
        ]);
    }

    /**
     * Get comprehensive dashboard data including summary, action items, and active backing
     */
    public function dashboard(): JsonResponse
    {
        $user = Auth::user();

        // Get all basic stats
        $totalPledged = CampaignContribution::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->sum('amount');

        $totalCampaignsBacked = CampaignContribution::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->distinct('campaign_id')
            ->count('campaign_id');

        // Get active backing projects with full details
        $contributions = CampaignContribution::with([
                'campaign' => function ($q) {
                    $q->with(['creator']);
                },
                'rewardTier',
                'detail',
            ])
            ->where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeBacking = $contributions->map(function ($contribution) {
            $campaign = $contribution->campaign;
            $detail = $contribution->detail;

            // Calculate days remaining
            $daysRemaining = null;
            if ($campaign->deadline && $campaign->status === 'active') {
                $now = now();
                $deadline = \Carbon\Carbon::parse($campaign->deadline);
                $daysRemaining = max(0, $now->diffInDays($deadline, false));
            }

            // Determine funding status
            $fundingStatus = $campaign->status;
            if ($campaign->status === 'active') {
                $fundingStatus = $campaign->raised_amount >= $campaign->goal_amount ? 'successful' : 'live';
            }

            // Determine project status (post-funding)
            $projectStatus = null;
            if ($campaign->status === 'successful' || ($campaign->status === 'active' && $campaign->raised_amount >= $campaign->goal_amount)) {
                if ($detail && $detail->delivery_status === 'shipped') {
                    $projectStatus = 'shipping';
                } elseif ($detail && $detail->delivery_status === 'delivered') {
                    $projectStatus = 'delivered';
                } elseif ($detail && $detail->delivery_status === 'processing') {
                    $projectStatus = 'in_production';
                } else {
                    $projectStatus = 'in_production'; // Default for successful campaigns
                }
            } elseif ($campaign->status === 'failed') {
                $projectStatus = 'unsuccessful';
            } else {
                // For live campaigns that haven't reached goal yet
                $projectStatus = 'pending';
            }

            // Generate full URL for featured image
            $featuredImageUrl = null;
            if ($campaign->featured_image) {
                if (filter_var($campaign->featured_image, FILTER_VALIDATE_URL)) {
                    // Already a full URL
                    $featuredImageUrl = $campaign->featured_image;
                } elseif (Storage::disk('public')->exists($campaign->featured_image)) {
                    // Storage path - convert to URL
                    $featuredImageUrl = Storage::disk('public')->url($campaign->featured_image);
                } else {
                    // Try as asset path
                    $featuredImageUrl = asset($campaign->featured_image);
                }
            }

            return [
                'id' => $contribution->id,
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'featured_image' => $featuredImageUrl,
                    'status' => $campaign->status,
                    'funding_status' => $fundingStatus, // live, successful, unsuccessful
                    'project_status' => $projectStatus, // in_production, shipping, delivered, unsuccessful
                    'progress_percentage' => round($campaign->progress_percentage, 1),
                    'goal_amount' => $campaign->goal_amount, // Return raw amount in cents
                    'raised_amount' => $campaign->raised_amount, // Return raw amount in cents
                    'currency' => $campaign->currency ?? 'USD',
                    'deadline' => $campaign->deadline?->format('Y-m-d'),
                    'days_remaining' => $daysRemaining,
                ],
                'creator' => $campaign->creator ? [
                    'id' => $campaign->creator->id,
                    'name' => $campaign->creator->name,
                ] : null,
                'pledge' => [
                    'amount' => $contribution->amount, // Return raw amount in cents for frontend formatting
                    'currency' => $contribution->currency ?? 'USD',
                    'date' => $contribution->created_at->format('Y-m-d H:i:s'),
                ],
                'reward_tier' => $contribution->rewardTier ? [
                    'id' => $contribution->rewardTier->id,
                    'name' => $contribution->rewardTier->name,
                    'description' => $contribution->rewardTier->description,
                    'estimated_delivery' => $contribution->rewardTier->estimated_delivery_date,
                ] : null,
                'fulfillment' => [
                    'delivery_status' => $detail?->delivery_status ?? 'pending',
                    'survey_completed' => $detail?->survey_completed ?? false,
                    'has_shipping_address' => $detail?->hasShippingAddress() ?? false,
                    'tracking_number' => $detail?->tracking_number,
                    'tracking_carrier' => $detail?->tracking_carrier,
                    'shipped_at' => $detail?->shipped_at?->format('Y-m-d H:i:s'),
                    'delivered_at' => $detail?->delivered_at?->format('Y-m-d H:i:s'),
                ],
            ];
        });

        // Get action items
        $actionItems = collect();

        // Pending surveys
        $pendingSurveyContributions = CampaignContribution::with(['campaign', 'detail'])
            ->where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->whereHas('detail', function ($q) {
                $q->where('survey_completed', false);
            })
            ->get();

        foreach ($pendingSurveyContributions as $contribution) {
            $actionItems->push([
                'type' => 'survey',
                'priority' => 'high',
                'title' => 'Complete your survey',
                'message' => 'Please complete your survey for ' . $contribution->campaign->title,
                'action_url' => '/backer/pledges/' . $contribution->id,
                'campaign_id' => $contribution->campaign_id,
                'campaign_title' => $contribution->campaign->title,
                'contribution_id' => $contribution->id,
            ]);
        }

        // Missing shipping addresses for rewards that require shipping
        $missingShipping = CampaignContribution::with(['campaign', 'rewardTier', 'detail'])
            ->where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->whereHas('rewardTier', function ($q) {
                $q->where('requires_shipping', true);
            })
            ->where(function ($q) {
                $q->whereDoesntHave('detail', function ($detailQ) {
                    // No additional conditions
                })
                  ->orWhereHas('detail', function ($subQ) {
                      $subQ->whereNull('shipping_address');
                  });
            })
            ->get();

        foreach ($missingShipping as $contribution) {
            $actionItems->push([
                'type' => 'shipping_address',
                'priority' => 'medium',
                'title' => 'Add shipping address',
                'message' => 'Please provide your shipping address for ' . $contribution->campaign->title,
                'action_url' => '/backer/pledges/' . $contribution->id,
                'campaign_id' => $contribution->campaign_id,
                'campaign_title' => $contribution->campaign->title,
                'contribution_id' => $contribution->id,
            ]);
        }

        // Failed payments
        $failedContributions = CampaignContribution::with('campaign')
            ->where('user_id', $user->id)
            ->where('status', 'failed')
            ->get();

        foreach ($failedContributions as $contribution) {
            $actionItems->push([
                'type' => 'failed_payment',
                'priority' => 'high',
                'title' => 'Payment failed',
                'message' => 'We couldn\'t process your payment for ' . $contribution->campaign->title . '. Please update your payment method.',
                'action_url' => '/backer/pledges/' . $contribution->id,
                'campaign_id' => $contribution->campaign_id,
                'campaign_title' => $contribution->campaign->title,
                'contribution_id' => $contribution->id,
            ]);
        }

        // Get latest updates
        $campaignIds = $contributions->pluck('campaign_id')->unique();
        $latestUpdates = CampaignUpdate::with(['campaign', 'author'])
            ->whereIn('campaign_id', $campaignIds)
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($update) {
                return [
                    'id' => $update->id,
                    'campaign' => [
                        'id' => $update->campaign->id,
                        'title' => $update->campaign->title,
                        'slug' => $update->campaign->slug,
                        'featured_image' => $update->campaign->featured_image,
                    ],
                    'title' => $update->title,
                    'content' => Str::limit($update->content, 200),
                    'full_content' => $update->content,
                    'type' => $update->type,
                    'author' => [
                        'id' => $update->author->id,
                        'name' => $update->author->name,
                    ],
                    'published_at' => $update->published_at->format('Y-m-d H:i:s'),
                    'published_at_human' => $update->published_at->diffForHumans(),
                ];
            });

        // Get payment methods
        $paymentMethods = PaymentMethod::where('user_id', $user->id)
            ->where('is_verified', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'display_name' => $method->display_name,
                    'last_four' => $method->last_four,
                    'brand' => $method->brand,
                    'is_default' => $method->is_default,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'summary' => [
                    'total_projects_backed' => $totalCampaignsBacked,
                    'total_amount_pledged' => $totalPledged, // Return raw amount in cents for frontend formatting
                    'currency' => 'USD',
                ],
                'active_backing' => $activeBacking,
                'action_items' => $actionItems->sortByDesc('priority')->values(),
                'latest_updates' => $latestUpdates,
                'payment_methods' => $paymentMethods,
                'stats' => [
                    'total_pledged' => number_format($totalPledged / 100, 2),
                    'total_campaigns_backed' => $totalCampaignsBacked,
                    'active_campaigns' => $contributions->where('campaign.status', 'active')->count(),
                    'pending_actions' => $actionItems->count(),
                ],
            ],
            'message' => 'Dashboard data retrieved successfully',
        ]);
    }

    /**
     * Get dashboard summary/stats
     */
    public function dashboardSummary(): JsonResponse
    {
        $user = Auth::user();

        $totalPledged = CampaignContribution::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->sum('amount');

        $totalCampaignsBacked = CampaignContribution::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->distinct('campaign_id')
            ->count('campaign_id');

        $activeCampaigns = CampaignContribution::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->whereHas('campaign', function ($q) {
                $q->where('status', 'active');
            })
            ->distinct('campaign_id')
            ->count('campaign_id');

        $pendingSurveys = ContributionDetail::whereHas('contribution', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', 'succeeded');
            })
            ->where('survey_completed', false)
            ->count();

        $unreadUpdates = CampaignUpdate::whereIn('campaign_id', function ($q) use ($user) {
                $q->select('campaign_id')
                  ->from('campaign_contributions')
                  ->where('user_id', $user->id)
                  ->where('status', 'succeeded')
                  ->distinct();
            })
            ->published()
            ->count();

        // Get financial statistics
        $totalIncome = FinancialTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->sum('net_amount');

        $totalExpenses = FinancialTransaction::where('user_id', $user->id)
            ->whereIn('transaction_type', ['withdrawal', 'fee'])
            ->where('status', 'completed')
            ->sum('amount');

        $netBalance = $totalIncome - $totalExpenses;

        // Get total payments count
        $totalPayments = FinancialTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'payment')
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_pledged' => number_format($totalPledged / 100, 2),
                'total_campaigns_backed' => $totalCampaignsBacked,
                'active_campaigns' => $activeCampaigns,
                'pending_surveys' => $pendingSurveys,
                'unread_updates' => $unreadUpdates,
                'total_income' => number_format($totalIncome / 100, 2),
                'total_expenses' => number_format($totalExpenses / 100, 2),
                'net_balance' => number_format($netBalance / 100, 2),
                'total_payments' => $totalPayments,
                'contributions' => $totalCampaignsBacked,
            ],
            'message' => 'Dashboard summary retrieved successfully',
        ]);
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
        $contributions = CampaignContribution::with(['campaign'])
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

        // Merge and transform contributions to match FinancialTransaction structure
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
        // (for backward compatibility with existing contributions)
        foreach ($allContributions as $contribution) {
            // Check if this contribution already has a FinancialTransaction
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
     * Get recommended projects based on user's backing history
     */
    public function recommendations(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Get categories of campaigns the user has backed
        $backedCategories = CampaignContribution::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->join('campaigns', 'campaign_contributions.campaign_id', '=', 'campaigns.id')
            ->distinct()
            ->pluck('campaigns.category');

        // Get campaigns in similar categories that user hasn't backed
        $query = Campaign::with(['creator'])
            ->withCount('contributions')
            ->where('status', 'active')
            ->whereNotIn('id', function ($q) use ($user) {
                $q->select('campaign_id')
                    ->from('campaign_contributions')
                    ->where('user_id', $user->id);
            });

        // If user has backed campaigns, recommend similar categories
        if ($backedCategories->isNotEmpty()) {
            $query->where(function ($q) use ($backedCategories) {
                $q->whereIn('category', $backedCategories)
                    ->orWhere(function ($subQ) {
                        // Also include trending campaigns from last 7 days
                        $subQ->whereDate('created_at', '>=', now()->subDays(7))
                            ->orderBy('raised_amount', 'desc');
                    });
            });
        } else {
            // If user hasn't backed any campaigns, show trending campaigns
            $query->whereDate('created_at', '>=', now()->subDays(7));
        }

        $campaigns = $query->orderBy('raised_amount', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 10))
            ->get();

        $recommendations = $campaigns->map(function ($campaign) {
            return [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'featured_image' => $campaign->featured_image,
                'category' => $campaign->category,
                'status' => $campaign->status,
                'progress_percentage' => $campaign->progress_percentage,
                'goal_amount' => $campaign->formatted_goal_amount,
                'raised_amount' => $campaign->formatted_raised_amount,
                'currency' => $campaign->currency,
                'deadline' => $campaign->deadline?->format('Y-m-d'),
                'creator' => [
                    'id' => $campaign->creator->id,
                    'name' => $campaign->creator->name,
                ],
                'contributions_count' => $campaign->contributions_count,
                'reason' => 'Based on your interests',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $recommendations,
            ],
            'message' => 'Recommendations retrieved successfully',
        ]);
    }

    /**
     * Get trending campaigns
     */
    public function trending(Request $request): JsonResponse
    {
        $query = Campaign::with(['creator'])
            ->withCount('contributions')
            ->where('status', 'active')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->orderBy('raised_amount', 'desc')
            ->orderBy('contributions_count', 'desc');

        // Filter by category if specified
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $campaigns = $query->limit($request->get('limit', 20))->get();

        $trending = $campaigns->map(function ($campaign) {
            return [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'featured_image' => $campaign->featured_image,
                'category' => $campaign->category,
                'status' => $campaign->status,
                'progress_percentage' => $campaign->progress_percentage,
                'goal_amount' => $campaign->formatted_goal_amount,
                'raised_amount' => $campaign->formatted_raised_amount,
                'currency' => $campaign->currency,
                'deadline' => $campaign->deadline?->format('Y-m-d'),
                'creator' => [
                    'id' => $campaign->creator->id,
                    'name' => $campaign->creator->name,
                ],
                'contributions_count' => $campaign->contributions_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'trending' => $trending,
            ],
            'message' => 'Trending campaigns retrieved successfully',
        ]);
    }

    /**
     * Complete survey for a contribution
     */
    public function completeSurvey(Request $request, $contributionId): JsonResponse
    {
        $user = Auth::user();

        $contribution = CampaignContribution::where('user_id', $user->id)
            ->where('id', $contributionId)
            ->where('status', 'succeeded')
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'responses' => 'required|array',
            'responses.*.question' => 'required|string',
            'responses.*.answer' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $detail = $contribution->detail;

        if (!$detail) {
            $detail = ContributionDetail::create([
                'contribution_id' => $contribution->id,
                'reward_tier_id' => $contribution->reward_tier_id,
            ]);
        }

        $detail->update([
            'survey_responses' => $request->responses,
            'survey_completed' => true,
            'survey_completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'survey' => [
                    'completed' => $detail->survey_completed,
                    'completed_at' => $detail->survey_completed_at->format('Y-m-d H:i:s'),
                    'responses' => $detail->survey_responses,
                ],
            ],
            'message' => 'Survey completed successfully',
        ]);
    }

    /**
     * Get user profile information
     */
    public function getProfile(): JsonResponse
    {
        $user = Auth::user();

        // Get privacy preferences
        $showBackedProjects = UserPreference::get($user->id, 'privacy', 'show_backed_projects', false);
        $showPublicProfile = UserPreference::get($user->id, 'privacy', 'show_public_profile', false);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'city' => $user->city,
                'country' => $user->country,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'privacy' => [
                    'show_public_profile' => (bool) $showPublicProfile,
                    'show_backed_projects' => (bool) $showBackedProjects,
                ],
            ],
            'message' => 'Profile retrieved successfully',
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'city' => 'sometimes|nullable|string|max:100',
            'country' => 'sometimes|nullable|string|max:100',
            'bio' => 'sometimes|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only(['name', 'phone', 'city', 'country']));

        // Store bio in preferences
        if ($request->has('bio')) {
            UserPreference::set($user->id, 'profile', 'bio', $request->bio);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'country' => $user->country,
                    'bio' => UserPreference::get($user->id, 'profile', 'bio'),
                ],
            ],
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Get privacy settings
     */
    public function getPrivacySettings(): JsonResponse
    {
        $user = Auth::user();

        $settings = [
            'show_public_profile' => UserPreference::get($user->id, 'privacy', 'show_public_profile', false),
            'show_backed_projects' => UserPreference::get($user->id, 'privacy', 'show_backed_projects', false),
            'show_email' => UserPreference::get($user->id, 'privacy', 'show_email', false),
            'show_phone' => UserPreference::get($user->id, 'privacy', 'show_phone', false),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
            'message' => 'Privacy settings retrieved successfully',
        ]);
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacySettings(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'show_public_profile' => 'sometimes|boolean',
            'show_backed_projects' => 'sometimes|boolean',
            'show_email' => 'sometimes|boolean',
            'show_phone' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->all() as $key => $value) {
            UserPreference::set($user->id, 'privacy', $key, $value ? '1' : '0');
        }

        return response()->json([
            'success' => true,
            'message' => 'Privacy settings updated successfully',
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Increase existing pledge amount
     */
    public function increasePledge(Request $request, $contributionId): JsonResponse
    {
        $user = Auth::user();

        $contribution = CampaignContribution::where('user_id', $user->id)
            ->where('id', $contributionId)
            ->where('status', 'succeeded')
            ->with('campaign')
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'additional_amount' => 'required|integer|min:100', // Minimum $1.00 (in cents)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if campaign is still active
        if ($contribution->campaign->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot increase pledge for inactive campaigns',
            ], 400);
        }

        // This would typically require payment processing
        // For now, we'll just return a response indicating the action
        return response()->json([
            'success' => true,
            'data' => [
                'current_amount' => $contribution->formatted_amount,
                'additional_amount' => number_format($request->additional_amount / 100, 2),
                'new_total' => number_format(($contribution->amount + $request->additional_amount) / 100, 2),
                'message' => 'Payment processing required to complete pledge increase',
            ],
            'message' => 'Pledge increase initiated. Payment processing required.',
        ]);
    }

    /**
     * Change reward tier for an existing pledge
     */
    public function changeRewardTier(Request $request, $contributionId): JsonResponse
    {
        $user = Auth::user();

        $contribution = CampaignContribution::where('user_id', $user->id)
            ->where('id', $contributionId)
            ->where('status', 'succeeded')
            ->with(['campaign', 'rewardTier', 'campaign.rewardTiers'])
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'reward_tier_id' => 'required|exists:reward_tiers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if campaign is still active
        if ($contribution->campaign->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change reward tier for inactive campaigns',
            ], 400);
        }

        // Verify the reward tier belongs to this campaign
        $newTier = $contribution->campaign->rewardTiers()
            ->where('id', $request->reward_tier_id)
            ->firstOrFail();

        // Update the contribution detail
        $detail = $contribution->detail;
        if ($detail) {
            $detail->update([
                'reward_tier_id' => $request->reward_tier_id,
            ]);
        } else {
            ContributionDetail::create([
                'contribution_id' => $contribution->id,
                'reward_tier_id' => $request->reward_tier_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'old_tier' => $contribution->rewardTier ? [
                    'id' => $contribution->rewardTier->id,
                    'name' => $contribution->rewardTier->name,
                ] : null,
                'new_tier' => [
                    'id' => $newTier->id,
                    'name' => $newTier->name,
                    'description' => $newTier->description,
                ],
            ],
            'message' => 'Reward tier changed successfully',
        ]);
    }

    /**
     * Get saved payment methods
     */
    public function getPaymentMethods(): JsonResponse
    {
        $user = Auth::user();

        $methods = PaymentMethod::where('user_id', $user->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'last_four' => $method->last_four ?? null,
                    'brand' => $method->brand ?? null,
                    'is_default' => $method->is_default ?? false,
                    'created_at' => $method->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'payment_methods' => $methods,
            ],
            'message' => 'Payment methods retrieved successfully',
        ]);
    }

    /**
     * Export transaction history
     */
    public function exportTransactions(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = CampaignContribution::with(['campaign', 'rewardTier'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Apply same filters as transactionHistory
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->get();

        $export = $transactions->map(function ($contribution) {
            return [
                'date' => $contribution->created_at->format('Y-m-d'),
                'transaction_id' => $contribution->transaction_id,
                'campaign' => $contribution->campaign->title,
                'amount' => number_format($contribution->amount / 100, 2),
                'currency' => $contribution->currency,
                'payment_method' => $this->getPaymentMethodDisplay($contribution->payment_processor),
                'status' => $contribution->status,
                'reward_tier' => $contribution->rewardTier?->name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $export,
                'export_date' => now()->format('Y-m-d H:i:s'),
                'total_records' => $transactions->count(),
            ],
            'message' => 'Transactions exported successfully',
        ]);
    }

    /**
     * Helper method to format payment method display
     */
    private function getPaymentMethodDisplay(?string $processor): string
    {
        return match ($processor) {
            'stripe' => 'Card',
            'paypal' => 'PayPal',
            'mpesa' => 'M-Pesa',
            default => ucfirst($processor ?? 'Unknown'),
        };
    }
}

