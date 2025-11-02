<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">User Details</h2>
                <p class="text-sm text-gray-600 mt-1">{{ $user->name }}</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                ← Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- User Info Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->phone ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Role</dt>
                                <dd class="mt-1">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                        {{ $user->getRoleNames()->implode(', ') ?: 'Member' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Status</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    @if($user->approval_status === 'approved')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">Approved</span>
                                    @elseif($user->approval_status === 'declined')
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">Declined</span>
                                    @else
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Pending</span>
                                    @endif
                                </dd>
                            </div>
                            @if($user->approved_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Approved At</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $user->approved_at->format('M d, Y H:i') }}</dd>
                                </div>
                                @if($user->approver)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Approved By</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $user->approver->name }}</dd>
                                    </div>
                                @endif
                            @endif
                            @if($user->approval_notes)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Notes</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $user->approval_notes }}</dd>
                                </div>
                            @endif
                        </dl>
                        @if($user->approval_status === 'pending')
                            <div class="mt-4 space-x-2">
                                <button onclick="approveUser({{ $user->id }})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Approve</button>
                                <button onclick="declineUser({{ $user->id }})" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">Decline</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Campaign Assignment Section -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Campaign Assignments</h3>
                    <button onclick="openAssignModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        + Assign to Campaign
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Assigned At</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Notes</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($user->assignedCampaigns as $campaign)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $campaign->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $campaign->status }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $campaign->pivot->assigned_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $campaign->pivot->notes ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="removeFromCampaign({{ $user->id }}, {{ $campaign->id }}, '{{ $campaign->title }}')"
                                                class="text-red-600 hover:text-red-900">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        <p>No campaign assignments</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-6 text-white shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-200 transform hover:scale-105">
                    <div class="text-sm font-medium text-white/90">Campaigns Created</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ $user->campaigns_count ?? 0 }}</div>
                </div>
                <div class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg shadow-teal-500/30 hover:shadow-xl hover:shadow-teal-500/40 transition-all duration-200 transform hover:scale-105">
                    <div class="text-sm font-medium text-white/90">Total Contributions</div>
                    <div class="mt-2 text-3xl font-semibold text-white">${{ number_format(($user->contributions()->sum('amount') ?? 0) / 100, 2) }}</div>
                </div>
                <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl p-6 text-white shadow-lg shadow-rose-500/30 hover:shadow-xl hover:shadow-rose-500/40 transition-all duration-200 transform hover:scale-105">
                    <div class="text-sm font-medium text-white/90">Assigned Campaigns</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ $user->assignedCampaigns->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Campaign Modal -->
    <div id="assignModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Assign to Campaign</h3>
                <form id="assignForm">
                    <input type="hidden" id="assignUserId" value="{{ $user->id }}">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campaign <span class="text-red-500">*</span></label>
                        <select id="campaignId" name="campaign_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a campaign...</option>
                            @foreach($campaigns as $campaign)
                                @if(!$user->assignedCampaigns->contains($campaign->id))
                                    <option value="{{ $campaign->id }}">{{ $campaign->title }} ({{ $campaign->status }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optional)</label>
                        <textarea id="assignNotes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAssignModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function approveUser(userId) {
            const notes = prompt('Enter approval notes (optional):');
            if (notes === null) return;

            assignOrApproveAction(`/api/v1/users/${userId}/approve`, { notes: notes || '' }, 'approve');
        }

        function declineUser(userId) {
            const notes = prompt('Enter decline reason (required):');
            if (!notes || notes.trim() === '') {
                alert('Reason is required');
                return;
            }

            assignOrApproveAction(`/api/v1/users/${userId}/decline`, { notes }, 'decline');
        }

        function openAssignModal() {
            document.getElementById('assignModal').classList.remove('hidden');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.add('hidden');
        }

        function removeFromCampaign(userId, campaignId, campaignTitle) {
            if (!confirm(`Are you sure you want to remove this user from "${campaignTitle}"?`)) {
                return;
            }

            assignOrApproveAction(`/api/v1/users/${userId}/remove-campaign`, { campaign_id: campaignId }, 'remove');
        }

        async function assignOrApproveAction(url, data, action) {
            try {
                const formData = new FormData();
                for (const key in data) {
                    formData.append(key, data[key]);
                }
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const result = await response.json();
                if (result.success) {
                    alert('Action completed successfully');
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to perform action'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        document.getElementById('assignForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const userId = document.getElementById('assignUserId').value;
            const campaignId = document.getElementById('campaignId').value;
            const notes = document.getElementById('assignNotes').value;

            if (!campaignId) {
                alert('Please select a campaign');
                return;
            }

            await assignOrApproveAction(`/api/v1/users/${userId}/assign-campaign`, { campaign_id: campaignId, notes }, 'assign');
            closeAssignModal();
        });
    </script>
</x-app-layout>

