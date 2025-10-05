<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Securedocs') }} - WebAuthn</title>
    <link rel="icon" href="{{ asset('logo-favicon.png') }}" type="image/png"/>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/webauthn-handler.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow mb-8">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="{{ url('/') }}" class="font-bold text-xl text-blue-600">{{ config('app.name', 'Securedocs') }}</a>
            @auth
                <span class="text-gray-600">{{ Auth::user()->name }}</span>
            @endauth
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="bg-white shadow mt-8 py-4 text-center text-gray-500 text-sm">
        &copy; {{ date('Y') }} {{ config('app.name', 'Securedocs') }}. All rights reserved.
    </footer>

    @stack('scripts')
</body>
</html>
