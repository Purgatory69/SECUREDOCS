<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin Dashboard - SecureDocs</title>
        <link rel="icon" href="{{ asset('logo-white.png') }}" type="image/png"/>
        <!-- Styles -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet"/>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- No chat widgets or user-facing scripts here -->
    </head>
    <body style="background-color: #141326;" class="h-screen grid grid-rows-[64px_1fr] grid-cols-[260px_1fr] grid-areas-layout text-text-main">
        @yield('content')
        <div id="notification-container" class="fixed bottom-4 right-4 z-[1000] space-y-2"></div>
        @stack('scripts')
    </body>
</html>
