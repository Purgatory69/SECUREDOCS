<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Dashboard - Securedocs</title>
        <script type="module">
            import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';
            window.createChat = createChat;
        </script>
        <!-- Styles -->
        <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
        
        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js"></script>
        <script>
            @if(Auth::check())
                window.userId = @json(Auth::user()->id);
                window.userEmail = @json(Auth::user()->email);
                window.username = @json(Auth::user()->name);
                window.userIsPremium = @json(Auth::user()->is_premium ?? false);
                window.userN8nWebhookUrl = @json(Auth::user()->n8n_webhook_url);
            @else
                window.userId = null;
                window.userEmail = null;
                window.username = null;
                window.userIsPremium = false;
                window.userN8nWebhookUrl = null;
            @endif
            window.SUPABASE_URL = "{{ config('services.supabase.url') }}";
            window.SUPABASE_KEY = "{{ config('services.supabase.key') }}";
            window.DEFAULT_N8N_WEBHOOK_URL = 'YOUR_DEFAULT_N8N_WEBHOOK_URL_HERE'; // <-- IMPORTANT: Replace with your actual default URL
        </script>
    </head>
    <body class="h-screen grid grid-rows-[64px_1fr] grid-cols-[260px_1fr] grid-areas-layout text-text-main">
        @yield('content')
        
        @stack('scripts')
    </body>
</html>
