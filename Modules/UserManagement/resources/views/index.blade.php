<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">User Management</h2>
                <p class="text-sm text-gray-600 mt-1">Approve, decline, and manage user assignments to campaigns</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Name or email..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Approval Status</label>
                        <select name="approval_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="declined" {{ request('approval_status') === 'declined' ? 'selected' : '' }}>Declined</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Roles</option>
                            @foreach($roles ?? [] as $role)
                                <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                                    {{ $role }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            Apply Filters
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Approval Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaigns</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($users ?? [] as $user)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-sm mr-3">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-xs text-gray-500">ID: {{ $user->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                            {{ $user->getRoleNames()->implode(', ') ?: 'Member' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->approval_status === 'approved')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">Approved</span>
                                        @elseif($user->approval_status === 'declined')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">Declined</span>
                                        @else
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <span>{{ $user->assignedCampaigns->count() }} assigned</span>
                                            <span class="text-gray-400">•</span>
                                            <span>{{ $user->campaigns_count ?? 0 }} created</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-3">
                                            <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                            @if($user->approval_status === 'pending')
                                                <button onclick="approveUser({{ $user->id }})" class="text-green-600 hover:text-green-900">Approve</button>
                                                <button onclick="declineUser({{ $user->id }})" class="text-red-600 hover:text-red-900">Decline</button>
                                            @elseif($user->approval_status === 'declined')
                                                <button onclick="approveUser({{ $user->id }})" class="text-green-600 hover:text-green-900">Approve</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <p class="text-lg font-medium">No users found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($users) && $users->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Approve User</h3>
                <form id="approveForm">
                    <input type="hidden" id="approveUserId" name="user_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optional)</label>
                        <textarea id="approveNotes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeApproveModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Decline User</h3>
                <form id="declineForm">
                    <input type="hidden" id="declineUserId" name="user_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason <span class="text-red-500">*</span></label>
                        <textarea id="declineNotes" name="notes" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeclineModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Decline</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function approveUser(userId) {
            document.getElementById('approveUserId').value = userId;
            document.getElementById('approveNotes').value = '';
            document.getElementById('approveModal').classList.remove('hidden');
        }

        function closeApproveModal() {
            document.getElementById('approveModal').classList.add('hidden');
        }

        function declineUser(userId) {
            document.getElementById('declineUserId').value = userId;
            document.getElementById('declineNotes').value = '';
            document.getElementById('declineModal').classList.remove('hidden');
        }

        function closeDeclineModal() {
            document.getElementById('declineModal').classList.add('hidden');
        }

        document.getElementById('approveForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const userId = document.getElementById('approveUserId').value;
            const notes = document.getElementById('approveNotes').value;

            try {
                const formData = new FormData();
                formData.append('notes', notes);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch(`/api/v1/users/${userId}/approve`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json();
                if (data.success) {
                    alert('User approved successfully');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to approve user'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        document.getElementById('declineForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const userId = document.getElementById('declineUserId').value;
            const notes = document.getElementById('declineNotes').value;

            if (!notes.trim()) {
                alert('Please provide a reason for declining');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('notes', notes);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch(`/api/v1/users/${userId}/decline`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json();
                if (data.success) {
                    alert('User declined successfully');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to decline user'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });
    </script>
</x-app-layout>
