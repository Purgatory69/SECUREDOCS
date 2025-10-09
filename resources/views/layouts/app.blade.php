<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" charset="UTF-8">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Dashboard</title>
        <link rel="icon" href="{{ asset('logo-favicon.png') }}" type="image/png"/>
        
        <!-- Critical CSS to prevent FOUC -->
        <style>
            /* Hide body until CSS loads */
            body { 
                visibility: hidden; 
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
        
        <!-- Preload critical fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
        
        <!-- Preload N8N chat styles -->
        <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
        
        <!-- Main styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- User context for AI categorization -->
        <script>
            window.userId = {{ auth()->id() ?? 'null' }};
            window.aiUsePublicStatus = true;
        </script>
        
        <!-- Bundlr Dependencies for Real Arweave Integration -->
        <script src="https://cdn.jsdelivr.net/npm/ethers@5.7.2/dist/ethers.umd.min.js"></script>
        <script src="https://unpkg.com/@bundlr-network/client@0.7.3/build/web/bundle.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bignumber.js@9.1.2/bignumber.min.js"></script>
        
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
            // window.chatWebhookUrl disabled with n8n chat to prevent loading the widget
             window.chatWebhookUrl = '{{ auth()->user()->is_premium ? config('services.n8n.premium_chat_webhook') : config('services.n8n.default_chat_webhook') }}';
        </script>
        @endauth
        <script>
            window.SUPABASE_URL = "{{ config('services.supabase.url') }}";
            window.SUPABASE_KEY = "{{ config('services.supabase.key') }}";
        </script>
        <!-- FOUC Prevention Script -->
        <script>
            // Show body when DOM and CSS are ready
            document.addEventListener('DOMContentLoaded', function() {
                // Add a small delay to ensure CSS is fully applied
                setTimeout(function() {
                    document.body.classList.add('loaded');
                    const spinner = document.querySelector('.loading-spinner');
                    if (spinner) {
                        spinner.remove();
                    }
                }, 50);
            });
            
            // Fallback: show body after 2 seconds regardless
            setTimeout(function() {
                if (!document.body.classList.contains('loaded')) {
                    document.body.classList.add('loaded');
                    const spinner = document.querySelector('.loading-spinner');
                    if (spinner) {
                        spinner.remove();
                    }
                }
            }, 2000);
        </script>
    </head>
    <body style="background-color: #141326;" class="h-screen @if(Route::currentRouteName() === 'user.dashboard')  grid grid-rows-[64px_1fr] grid-cols-[260px_1fr] grid-areas-layout @endif text-text-main">
        <!-- Loading spinner -->
        <div class="loading-spinner"></div>
        
        @yield('content')
        <div id="notification-container" class="fixed bottom-4 right-4 z-[1000] space-y-2"></div>
        
        @stack('scripts')
    
    </body>
</html>
