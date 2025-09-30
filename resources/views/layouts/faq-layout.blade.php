<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Help & Support</title>
    <link rel="icon" href="{{ asset('logo-favicon.png') }}" type="image/png"/>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"/>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen" style="background-color: #24243B;">
        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-[#141326] shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif
        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
    @stack('modals')
    @livewireScripts
</body>
</html>

<!--
<style>
/* Override Livewire component backgrounds */
.bg-white {
    background-color: #3c3f58 !important;
}

/* Style form inputs */
input[type="text"], input[type="email"], input[type="password"] {
    background-color: #2a2d42 !important;
    color: white !important;
    border-color: #4a4d66 !important;
}

/* Style labels */
label {
    color: white !important;
}
</style>
-->
