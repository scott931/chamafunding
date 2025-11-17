<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Additional versioned assets with cache busting --}}
        {{-- Use asset_versioned() helper for automatic cache busting --}}
        {{-- <link href="{{ asset_versioned('css/custom.css') }}" rel="stylesheet"> --}}
        {{-- <script src="{{ asset_versioned('js/custom.js') }}"></script> --}}
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gradient-to-br from-gray-50 via-indigo-50 to-purple-50">
                {{ $slot }}

        @if(session('clear_storage'))
        <script>
            // Clear all authentication tokens from client-side storage after logout
            (function() {
                // Clear localStorage tokens
                try {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('authToken');
                    localStorage.removeItem('token');
                    // Clear any other potential token keys
                    Object.keys(localStorage).forEach(key => {
                        if (key.toLowerCase().includes('token') || key.toLowerCase().includes('auth')) {
                            localStorage.removeItem(key);
                        }
                    });
                } catch (e) {
                    console.warn('Could not clear localStorage:', e);
                }

                // Clear sessionStorage tokens
                try {
                    sessionStorage.removeItem('auth_token');
                    sessionStorage.removeItem('authToken');
                    sessionStorage.removeItem('token');
                    // Clear any other potential token keys
                    Object.keys(sessionStorage).forEach(key => {
                        if (key.toLowerCase().includes('token') || key.toLowerCase().includes('auth')) {
                            sessionStorage.removeItem(key);
                        }
                    });
                } catch (e) {
                    console.warn('Could not clear sessionStorage:', e);
                }

                // Clear window.authToken if it exists
                if (window.authToken) {
                    window.authToken = null;
                    delete window.authToken;
                }
            })();
        </script>
        @endif
    </body>
</html>
