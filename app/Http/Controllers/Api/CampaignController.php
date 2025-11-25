<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignContribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    /**
     * Statuses that regular users are allowed to see.
     */
    private array $publicStatuses = ['active', 'successful', 'closed'];

    /**
     * List campaigns with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Campaign::query()->with(['creator:id,name,email']);

        if (!$user || !$user->isAdmin()) {
            $query->whereIn('status', $this->publicStatuses);
        } else {
            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            if ($category = $request->query('category')) {
                $query->where('category', $category);
            }
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 5), 100);
        $campaigns = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    /**
     * Store a newly created campaign (admins only).
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:emergency,project,community,education,health,environment'],
            'description' => ['required', 'string', 'min:50'],
            'goal_amount' => ['required', 'numeric', 'min:100'],
            'currency' => ['required', 'string', 'size:3'],
            'deadline' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'featured_image' => ['nullable', 'string', 'max:2048'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:2048'],
        ]);

        $goalAmount = (int) round($data['goal_amount'] * 100);

        $campaign = Campaign::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::random(6),
            'category' => $data['category'],
            'description' => $data['description'],
            'created_by' => $request->user()->id,
            'goal_amount' => $goalAmount,
            'raised_amount' => 0,
            'currency' => strtoupper($data['currency']),
            'deadline' => $data['deadline'] ?? null,
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'] ?? null,
            'featured_image' => $data['featured_image'] ?? null,
            'images' => $data['images'] ?? [],
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Campaign created successfully (draft status).',
            'data' => $campaign->fresh(['creator:id,name,email']),
        ], 201);
    }

    /**
     * Display a specific campaign.
     */
    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        $user = $request->user();

        if ((!$user || !$user->isAdmin()) && !in_array($campaign->status, $this->publicStatuses, true)) {
            abort(404);
        }

        $campaign->load(['creator:id,name,email', 'contributions']);

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }

    /**
     * Update a campaign (admins only).
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'in:emergency,project,community,education,health,environment'],
            'description' => ['sometimes', 'string', 'min:10'],
            'goal_amount' => ['sometimes', 'numeric', 'min:100'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'deadline' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'string', 'in:draft,active,successful,failed,closed,suspended'],
            'featured_image' => ['nullable', 'string', 'max:2048'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:2048'],
        ]);

        if (isset($data['goal_amount'])) {
            $data['goal_amount'] = (int) round($data['goal_amount'] * 100);
        }

        if (isset($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        $campaign->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Campaign updated successfully.',
            'data' => $campaign->fresh(['creator:id,name,email']),
        ]);
    }

    /**
     * Remove a campaign (admins only).
     */
    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        $this->ensureAdmin($request);

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully.',
        ]);
    }

    /**
     * Activate a campaign (admins only).
     */
    public function activate(Request $request, Campaign $campaign): JsonResponse
    {
        $this->ensureAdmin($request);

        if ($campaign->status === 'active') {
            return response()->json([
                'success' => true,
                'message' => 'Campaign is already active.',
                'data' => $campaign,
            ]);
        }

        $campaign->update([
            'status' => 'active',
            'starts_at' => $campaign->starts_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Campaign activated successfully.',
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Create a contribution to a campaign.
     */
    public function contribute(Request $request, Campaign $campaign): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'You must be authenticated to contribute.');
        }

        if ($campaign->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'You can only contribute to active campaigns.',
            ], 422);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'payment_processor' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'reward_tier_id' => ['nullable', 'integer'],
            'status' => ['sometimes', 'string', 'in:pending,succeeded,failed,refunded'],
        ]);

        // Convert amount to cents
        $amountInCents = (int) round($data['amount'] * 100);

        $contribution = CampaignContribution::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'amount' => $amountInCents,
            'currency' => strtoupper($data['currency'] ?? $campaign->currency ?? 'USD'),
            'payment_processor' => $data['payment_processor'],
            'transaction_id' => $data['transaction_id'],
            'reward_tier_id' => $data['reward_tier_id'] ?? null,
            'status' => $data['status'] ?? 'succeeded',
        ]);

        // Update campaign raised amount
        $campaign->increment('raised_amount', $amountInCents);

        // Check if campaign goal is reached
        if ($campaign->raised_amount >= $campaign->goal_amount && $campaign->status === 'active') {
            $campaign->update(['status' => 'successful']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contribution created successfully.',
            'data' => $contribution->load('user:id,name,email'),
        ], 201);
    }

    /**
     * Ensure the current user is an administrator.
     */
    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            abort(403, 'Only administrators can perform this action.');
        }
    }
}

