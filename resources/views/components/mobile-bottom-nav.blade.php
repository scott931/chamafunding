<!-- Mobile Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-200/60 shadow-2xl z-50 lg:hidden mobile-bottom-nav">
    <div class="flex items-center justify-around h-20 px-2 safe-area-bottom">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 py-2.5 {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-slate-500' }} transition-all duration-200 rounded-lg mx-1 {{ request()->routeIs('dashboard') ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('dashboard') ? '2.5' : '2' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="text-xs font-semibold">Home</span>
        </a>

        <a href="{{ route('crowdfunding.index') }}" class="flex flex-col items-center justify-center flex-1 py-2.5 {{ request()->routeIs('crowdfunding.*') ? 'text-indigo-600' : 'text-slate-500' }} transition-all duration-200 rounded-lg mx-1 {{ request()->routeIs('crowdfunding.*') ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('crowdfunding.*') ? '2.5' : '2' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs font-semibold">Campaigns</span>
        </a>

        <a href="{{ route('payments.index') }}" class="flex flex-col items-center justify-center flex-1 py-2.5 {{ request()->routeIs('payments.*') ? 'text-indigo-600' : 'text-slate-500' }} transition-all duration-200 rounded-lg mx-1 {{ request()->routeIs('payments.*') ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('payments.*') ? '2.5' : '2' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span class="text-xs font-semibold">Payments</span>
        </a>

        <a href="{{ route('reports.index') }}" class="flex flex-col items-center justify-center flex-1 py-2.5 {{ request()->routeIs('reports.*') ? 'text-indigo-600' : 'text-slate-500' }} transition-all duration-200 rounded-lg mx-1 {{ request()->routeIs('reports.*') ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('reports.*') ? '2.5' : '2' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-xs font-semibold">Reports</span>
        </a>

        @php
            $isAdmin = auth()->check() && auth()->user()->isAdmin();
        @endphp
        @if($isAdmin)
        <a href="{{ route('admin.campaigns.index') }}" class="flex flex-col items-center justify-center flex-1 py-2.5 {{ request()->routeIs('admin.*') ? 'text-indigo-600' : 'text-slate-500' }} transition-all duration-200 rounded-lg mx-1 {{ request()->routeIs('admin.*') ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('admin.*') ? '2.5' : '2' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="text-xs font-semibold">Admin</span>
        </a>
        @else
        <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center flex-1 py-2.5 {{ request()->routeIs('profile.*') ? 'text-indigo-600' : 'text-slate-500' }} transition-all duration-200 rounded-lg mx-1 {{ request()->routeIs('profile.*') ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('profile.*') ? '2.5' : '2' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-xs font-semibold">Profile</span>
        </a>
        @endif
    </div>
</nav>

