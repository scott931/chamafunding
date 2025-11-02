<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users for management
     */
    public function index(Request $request)
    {
        // Only admins can access
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = User::with(['roles', 'approver', 'assignedCampaigns'])
            ->withCount(['campaigns', 'contributions', 'assignedCampaigns']);

        // Filter by approval status
        if ($request->has('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        // Get all roles for filter
        $roles = \Spatie\Permission\Models\Role::pluck('name')->toArray();

        // For web requests, return view
        if (!$request->wantsJson()) {
            return view('usermanagement::index', compact('users', 'roles'));
        }

        // For API requests, return JSON
        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Approve a user
     */
    public function approve(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        $user = User::findOrFail($id);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $user->update([
                'approval_status' => 'approved',
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'approval_notes' => $request->notes,
            ]);

            // Create notification for user
            Notification::create([
                'user_id' => $user->id,
                'type' => 'in_app',
                'channel' => 'in_app',
                'title' => 'Account Approved',
                'message' => 'Your account has been approved. You can now access all platform features.',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User approved successfully',
                    'data' => $user->fresh(['approver']),
                ]);
            }

            return redirect()->route('admin.users.index')
                ->with('status', 'User approved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve user: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withErrors(['error' => 'Failed to approve user: ' . $e->getMessage()]);
        }
    }

    /**
     * Decline a user
     */
    public function decline(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        $user = User::findOrFail($id);

        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $user->update([
                'approval_status' => 'declined',
                'is_approved' => false,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'approval_notes' => $request->notes,
            ]);

            // Create notification for user
            Notification::create([
                'user_id' => $user->id,
                'type' => 'in_app',
                'channel' => 'in_app',
                'title' => 'Account Declined',
                'message' => 'Your account approval has been declined. Reason: ' . $request->notes,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User declined successfully',
                    'data' => $user->fresh(['approver']),
                ]);
            }

            return redirect()->route('admin.users.index')
                ->with('status', 'User declined successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decline user: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withErrors(['error' => 'Failed to decline user: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign user to a campaign
     */
    public function assignToCampaign(Request $request, $userId)
    {
        if (!auth()->user()->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($userId);
        $campaign = Campaign::findOrFail($request->campaign_id);

        // Check if user is already assigned
        if ($user->assignedCampaigns()->where('campaign_id', $campaign->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already assigned to this campaign',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Assign user to campaign
            $user->assignedCampaigns()->attach($campaign->id, [
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'notes' => $request->notes,
            ]);

            // Create notification for user
            Notification::create([
                'user_id' => $user->id,
                'type' => 'in_app',
                'channel' => 'in_app',
                'title' => 'Assigned to Campaign',
                'message' => "You have been assigned to the campaign: {$campaign->title}",
                'data' => [
                    'campaign_id' => $campaign->id,
                    'campaign_title' => $campaign->title,
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User assigned to campaign successfully',
                    'data' => [
                        'user' => $user->fresh(['assignedCampaigns']),
                        'campaign' => $campaign,
                    ],
                ]);
            }

            return redirect()->route('admin.users.show', $userId)
                ->with('status', 'User assigned to campaign successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign user to campaign: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withErrors(['error' => 'Failed to assign user to campaign: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove user from a campaign
     */
    public function removeFromCampaign(Request $request, $userId)
    {
        if (!auth()->user()->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $user = User::findOrFail($userId);
        $campaign = Campaign::findOrFail($request->campaign_id);

        // Check if user is assigned
        if (!$user->assignedCampaigns()->where('campaign_id', $campaign->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not assigned to this campaign',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Remove user from campaign
            $user->assignedCampaigns()->detach($campaign->id);

            // Create notification for user
            Notification::create([
                'user_id' => $user->id,
                'type' => 'in_app',
                'channel' => 'in_app',
                'title' => 'Removed from Campaign',
                'message' => "You have been removed from the campaign: {$campaign->title}",
                'data' => [
                    'campaign_id' => $campaign->id,
                    'campaign_title' => $campaign->title,
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User removed from campaign successfully',
                ]);
            }

            return redirect()->route('admin.users.show', $userId)
                ->with('status', 'User removed from campaign successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove user from campaign: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withErrors(['error' => 'Failed to remove user from campaign: ' . $e->getMessage()]);
        }
    }

    /**
     * Get user details with assigned campaigns
     */
    public function show($id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $user = User::with(['roles', 'approver', 'assignedCampaigns.creator'])
            ->withCount(['campaigns', 'contributions'])
            ->findOrFail($id);

        $campaigns = Campaign::where('status', '!=', 'draft')
            ->orderBy('title')
            ->get();

        // For API requests
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        }

        // For web requests
        return view('usermanagement::show', compact('user', 'campaigns'));
    }

    /**
     * Get campaigns for dropdown
     */
    public function getCampaigns(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $campaigns = Campaign::where('status', '!=', 'draft')
            ->select('id', 'title', 'status')
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }
}
