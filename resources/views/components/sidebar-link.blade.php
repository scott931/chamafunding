@props(['active', 'href'])

@php
$classes = ($active ?? false)
            ? 'flex items-center px-4 py-3 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500/20 to-purple-500/20 border-l-4 border-indigo-400 rounded-lg shadow-lg shadow-indigo-500/10 transition-all duration-200 relative group'
            : 'flex items-center px-4 py-3 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition-all duration-200 relative group';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    <span class="absolute inset-0 rounded-lg bg-gradient-to-r from-indigo-500/0 to-purple-500/0 group-hover:from-indigo-500/10 group-hover:to-purple-500/10 transition-all duration-200"></span>
    <span class="relative flex items-center w-full">
        {{ $slot }}
    </span>
</a>

