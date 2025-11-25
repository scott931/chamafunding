<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * List users with filters (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $query = User::query()
            ->with(['roles:id,name'])
            ->withCount(['campaigns', 'assignedCampaigns', 'contributions']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('approval_status')) {
            $query->where('approval_status', $status);
        }

        if ($role = $request->query('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $perPage = min(max((int) $request->query('per_page', 20), 5), 100);
        $users = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'roles' => Role::query()->orderBy('name')->pluck('name'),
            ],
        ]);
    }

    /**
     * Show detailed user information (admin only).
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);

        $user->load([
            'roles:id,name',
            'approver:id,name,email',
            'campaigns' => function ($q) {
                $q->select('id', 'title', 'status', 'goal_amount', 'raised_amount', 'created_at')
                  ->orderBy('created_at', 'desc')
                  ->limit(10);
            },
            'contributions' => function ($q) {
                $q->select('id', 'campaign_id', 'amount', 'currency', 'status', 'created_at')
                  ->with('campaign:id,title')
                  ->orderBy('created_at', 'desc')
                  ->limit(10);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'address' => $user->address,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country,
                'postal_code' => $user->postal_code,
                'is_verified' => $user->is_verified,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'approval_status' => $user->approval_status,
                'is_approved' => $user->is_approved,
                'approved_at' => $user->approved_at?->format('Y-m-d H:i:s'),
                'approved_by' => $user->approved_by,
                'approver' => $user->approver ? [
                    'id' => $user->approver->id,
                    'name' => $user->approver->name,
                    'email' => $user->approver->email,
                ] : null,
                'approval_notes' => $user->approval_notes,
                'membership_type' => $user->membership_type,
                'preferred_contribution_amount' => $user->preferred_contribution_amount,
                'payment_frequency' => $user->payment_frequency,
                'referral_code' => $user->referral_code,
                'terms_accepted_at' => $user->terms_accepted_at?->format('Y-m-d H:i:s'),
                'privacy_accepted_at' => $user->privacy_accepted_at?->format('Y-m-d H:i:s'),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                'roles' => $user->roles->map(fn($r) => $r->name),
                'campaigns_count' => $user->campaigns_count ?? $user->campaigns->count(),
                'contributions_count' => $user->contributions_count ?? $user->contributions->count(),
                'assigned_campaigns_count' => $user->assigned_campaigns_count ?? 0,
                'recent_campaigns' => $user->campaigns->map(function ($campaign) {
                    return [
                        'id' => $campaign->id,
                        'title' => $campaign->title,
                        'status' => $campaign->status,
                        'goal_amount' => $campaign->goal_amount / 100,
                        'raised_amount' => $campaign->raised_amount / 100,
                        'created_at' => $campaign->created_at->format('Y-m-d'),
                    ];
                }),
                'recent_contributions' => $user->contributions->map(function ($contribution) {
                    return [
                        'id' => $contribution->id,
                        'campaign_id' => $contribution->campaign_id,
                        'campaign_title' => $contribution->campaign->title ?? 'N/A',
                        'amount' => $contribution->amount / 100,
                        'currency' => $contribution->currency,
                        'status' => $contribution->status,
                        'created_at' => $contribution->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update a user's primary role.
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->syncRoles([$data['role']]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $user->fresh('roles'),
        ]);
    }

    /**
     * Approve, decline, or reset a user's approval status.
     */
    public function updateApproval(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'status' => ['required', 'in:approved,declined,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $data['status'];
        $user->approval_status = $status;
        $user->approval_notes = $data['notes'] ?? null;

        if ($status === 'approved') {
            $user->is_approved = true;
            $user->approved_at = now();
            $user->approved_by = $request->user()->id;
        } elseif ($status === 'declined') {
            $user->is_approved = false;
            $user->approved_at = null;
            $user->approved_by = $request->user()->id;
        } else {
            $user->is_approved = false;
            $user->approved_at = null;
            $user->approved_by = null;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User approval status updated.',
            'data' => $user->fresh(['roles', 'approver']),
        ]);
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

