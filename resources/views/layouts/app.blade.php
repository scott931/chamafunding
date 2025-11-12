<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Additional versioned assets with cache busting --}}
        {{-- Use asset_versioned() helper for automatic cache busting --}}
        {{-- <link href="{{ asset_versioned('css/custom.css') }}" rel="stylesheet"> --}}
        {{-- <script src="{{ asset_versioned('js/custom.js') }}"></script> --}}

        {{-- Page-specific styles --}}
        @stack('styles')
    </head>
    <body class="font-sans antialiased bg-gradient-to-br from-slate-50 via-indigo-50/30 to-purple-50/20 min-h-screen">
        <div class="min-h-screen">
            <x-sidebar>
                <!-- Page Heading -->
                @if(isset($header))
                    <header class="bg-white/70 backdrop-blur-xl shadow-sm border-b border-slate-200/60 sticky top-0 z-30">
                        <div class="max-w-[1920px] mx-auto py-4 sm:py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50/20 to-purple-50/10">
                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endisset
                </main>
            </x-sidebar>
        </div>

        {{-- Page-specific scripts --}}
        @stack('scripts')
    </body>
</html>
