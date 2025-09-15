<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" charset="UTF-8">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Dashboard</title>
        <link rel="icon" href="{{ asset('logo-white.png') }}" type="image/png"/>
        <!-- Styles -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet"/>
        <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- N8N Chat Widget -->
        <script type="module">
            import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';
            window.createChat = createChat;
        </script>
        
        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js"></script>
        @auth
        <script>
            window.userId = {{ auth()->id() }};
            window.userEmail = '{{ auth()->user()->email }}';
            window.username = '{{ auth()->user()->name }}';
            window.userIsPremium = {{ auth()->user()->is_premium ? 'true' : 'false' }};
            window.chatWebhookUrl = '{{ auth()->user()->is_premium ? config('services.n8n.premium_chat_webhook') : config('services.n8n.default_chat_webhook') }}';
        </script>
        @endauth
        <script>
            window.SUPABASE_URL = "{{ config('services.supabase.url') }}";
            window.SUPABASE_KEY = "{{ config('services.supabase.key') }}";
        </script>
    </head>
    <body style="background-color: #141326;" class="h-screen @if(Route::currentRouteName() !== 'file-preview')  grid grid-rows-[64px_1fr] grid-cols-[260px_1fr] grid-areas-layout @endif text-text-main">
        @yield('content')
        <div id="notification-container" class="fixed bottom-4 right-4 z-[1000] space-y-2"></div>
        
        @stack('scripts')
    
    </body>
</html>
