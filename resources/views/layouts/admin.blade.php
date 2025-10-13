<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" charset="UTF-8">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin Dashboard</title>
        <link rel="icon" href="{{ asset('logo-favicon.png') }}" type="image/png"/>
        <!-- Styles -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet"/>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- No chat widgets or user-facing scripts here -->

         <!-- Critical CSS to prevent FOUC -->
         <style>
            /* Hide body until CSS loads */
            body { 
                background-color: #141326 !important;
                font-family: 'Poppins', sans-serif;
            }
            body.loaded { 
                visibility: visible; 
            }
            
            /* Loading spinner */
            .loading-spinner {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 40px;
                height: 40px;
                border: 3px solid #3C3F58;
                border-top: 3px solid #f89c00;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                z-index: 9999;
            }
            
            @keyframes spin {
                0% { transform: translate(-50%, -50%) rotate(0deg); }
                100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
        </style>

    </head>
    <body style="background-color: #141326;" class="h-screen grid grid-rows-[64px_1fr] grid-cols-[260px_1fr] grid-areas-layout text-text-main">
        @yield('content')
        <div id="notification-container" class="fixed bottom-4 left-4 z-[1000] space-y-2"></div>
        @stack('scripts')
    </body>
</html>
