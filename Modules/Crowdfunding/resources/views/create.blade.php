<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Create New Campaign</h2>
                <p class="text-sm text-gray-600 mt-1">Fill out the form below to create a new crowdfunding campaign</p>
            </div>
            <a href="{{ route('crowdfunding.index') }}"
               class="text-gray-600 hover:text-gray-900 font-medium">
                ← Back to Campaigns
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <form method="POST" action="{{ route('crowdfunding.store') }}" class="space-y-6">
                    @csrf

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Campaign Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="title"
                               name="title"
                               value="{{ old('title') }}"
                               required
                               maxlength="255"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-500 @enderror"
                               placeholder="Enter a compelling campaign title">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category"
                                name="category"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('category') border-red-500 @enderror">
                            <option value="">Select a category</option>
                            <option value="emergency" {{ old('category') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                            <option value="project" {{ old('category') === 'project' ? 'selected' : '' }}>Project</option>
                            <option value="community" {{ old('category') === 'community' ? 'selected' : '' }}>Community</option>
                            <option value="education" {{ old('category') === 'education' ? 'selected' : '' }}>Education</option>
                            <option value="health" {{ old('category') === 'health' ? 'selected' : '' }}>Health</option>
                            <option value="environment" {{ old('category') === 'environment' ? 'selected' : '' }}>Environment</option>
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-500 font-normal">(Minimum 50 characters)</span>
                        </label>
                        <textarea id="description"
                                  name="description"
                                  rows="6"
                                  required
                                  minlength="50"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Describe your campaign in detail...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Goal Amount & Currency -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="goal_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Goal Amount <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-500 font-normal">(Minimum: $100)</span>
                            </label>
                            <input type="number"
                                   id="goal_amount"
                                   name="goal_amount"
                                   value="{{ old('goal_amount') }}"
                                   required
                                   min="100"
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('goal_amount') border-red-500 @enderror"
                                   placeholder="10000">
                            @error('goal_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select id="currency"
                                    name="currency"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('currency') border-red-500 @enderror">
                                <option value="USD" {{ old('currency', 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP</option>
                                <option value="KES" {{ old('currency') === 'KES' ? 'selected' : '' }}>KES</option>
                            </select>
                            @error('currency')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                Deadline
                            </label>
                            <input type="date"
                                   id="deadline"
                                   name="deadline"
                                   value="{{ old('deadline') }}"
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('deadline') border-red-500 @enderror">
                            @error('deadline')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">
                                Start Date
                            </label>
                            <input type="datetime-local"
                                   id="starts_at"
                                   name="starts_at"
                                   value="{{ old('starts_at') }}"
                                   min="{{ date('Y-m-d\TH:i') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('starts_at') border-red-500 @enderror">
                            @error('starts_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-2">
                                End Date
                            </label>
                            <input type="datetime-local"
                                   id="ends_at"
                                   name="ends_at"
                                   value="{{ old('ends_at') }}"
                                   min="{{ date('Y-m-d\TH:i') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('ends_at') border-red-500 @enderror">
                            @error('ends_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">Note:</p>
                                <p>Your campaign will be created as a <strong>draft</strong> and will not be visible to regular users until you activate it. You can activate it from the campaigns list or admin panel.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <a href="{{ route('crowdfunding.index') }}"
                           class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-md transition-colors">
                            Create Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

