{{-- Page-specific assets with cache busting --}}
@push('styles')
    {{-- Uncomment when you have CSS files to load --}}
    {{-- <link href="{{ asset_versioned('css/financial.css') }}" rel="stylesheet"> --}}
@endpush

@push('scripts')
    {{-- Uncomment when you have JS files to load --}}
    {{-- <script src="{{ asset_versioned('js/financial.js') }}"></script> --}}
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Financial Overview</h2>
                <p class="text-sm text-gray-600 mt-1">Platform revenue and financial analytics</p>
            </div>
            <a href="{{ route('admin.transactions.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                View Transaction Log
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Revenue Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 text-white shadow-lg">
                    <p class="text-emerald-100 text-sm font-medium mb-1">Fees This Month</p>
                    <p class="text-3xl font-bold">${{ number_format($stats['total_fees_this_month'], 2) }}</p>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-6 text-white shadow-lg">
                    <p class="text-blue-100 text-sm font-medium mb-1">Fees This Quarter</p>
                    <p class="text-3xl font-bold">${{ number_format($stats['total_fees_quarter'], 2) }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
                    <p class="text-purple-100 text-sm font-medium mb-1">Fees This Year</p>
                    <p class="text-3xl font-bold">${{ number_format($stats['total_fees_year'], 2) }}</p>
                </div>
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
                    <p class="text-amber-100 text-sm font-medium mb-1">Volume This Month</p>
                    <p class="text-3xl font-bold">${{ number_format($stats['total_volume_this_month'], 2) }}</p>
                </div>
            </div>

            <!-- Fee Revenue Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Fee Revenue Over Time (Last 90 Days)</h3>
                <div class="h-64 flex items-end justify-between space-x-1">
                    @if($feeRevenueOverTime->count() > 0)
                        @php
                            $maxFee = $feeRevenueOverTime->max('total');
                        @endphp
                        @foreach($feeRevenueOverTime as $day)
                            <div class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-gradient-to-t from-emerald-500 to-teal-600 rounded-t"
                                     style="height: {{ $maxFee > 0 ? ($day->total / $maxFee * 240) : 0 }}px; min-height: 2px;">
                                </div>
                                <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left">
                                    {{ \Carbon\Carbon::parse($day->date)->format('M j') }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <p>No revenue data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

