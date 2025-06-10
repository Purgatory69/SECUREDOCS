<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts - Fixed Poppins with proper weights -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        <style>
            body {
                font-family: "Poppins", sans-serif;
            }
        </style>

        <!-- WebAuthn Library -->
        <script src="{{ asset('vendor/webauthn/webauthn.js') }}" defer></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="bg-[#141326]">
        <div class="bg-[#141326] min-h-screen flex flex-col justify-center items-center px-4">
            {{ $slot }}
        </div>

        @livewireScripts
        @stack('scripts')
    </body>
</html>