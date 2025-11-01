<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Admin Settings</h2>
                <p class="text-sm text-gray-600 mt-1">Manage platform configuration and settings</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if (session('status'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Settings Tabs">
                    @foreach($accessibleCategories as $category)
                        <a href="{{ route('admin.settings.index', ['tab' => $category]) }}"
                           class="@if($activeTab === $category) border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ $categoryNames[$category] ?? ucfirst($category) }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                @php
                    $tabViewMap = [
                        'platform' => 'admin::settings.tabs.platform',
                        'campaigns' => 'admin::settings.tabs.campaigns',
                        'users' => 'admin::settings.tabs.users',
                        'financial' => 'admin::settings.tabs.financial',
                        'communication' => 'admin::settings.tabs.communication',
                        'appearance' => 'admin::settings.tabs.appearance',
                        'advanced' => 'admin::settings.tabs.advanced',
                    ];
                    $viewName = $tabViewMap[$activeTab] ?? 'admin::settings.tabs.platform';
                @endphp
                @if(view()->exists($viewName))
                    @include($viewName, ['settings' => $settings ?? []])
                @else
                    <div class="p-6">
                        <p class="text-gray-600">Settings view for "{{ $activeTab }}" is not available.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
