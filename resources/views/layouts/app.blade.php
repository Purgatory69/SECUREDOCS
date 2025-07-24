<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Dashboard - Securedocs</title>
        <link rel="icon" href="{{ asset('logo-white.png') }}" type="image/png"/>
        <script type="module">
            import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';
            window.createChat = createChat;
        </script>
        <!-- Styles -->
        <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
        
        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js"></script>
        @auth
        <script>
            window.userId = {{ auth()->id() }};
            window.userEmail = "{{ auth()->user()->email }}";
            window.username = "{{ auth()->user()->name }}";
            window.userIsPremium = {{ auth()->user()->is_premium ? 'true' : 'false' }};
            // Determine the correct chat webhook URL on the backend
            window.chatWebhookUrl = "{{ auth()->user()->is_premium ? config('services.n8n.premium_chat_webhook') : config('services.n8n.default_chat_webhook') }}";
        </script>
        @endauth
        <script>
            window.SUPABASE_URL = "{{ config('services.supabase.url') }}";
            window.SUPABASE_KEY = "{{ config('services.supabase.key') }}";
        </script>
    </head>
    <body class="h-screen grid grid-rows-[64px_1fr] grid-cols-[260px_1fr] grid-areas-layout text-text-main">
        @yield('content')
        
        @stack('scripts')
    </body>
</html>
