<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>SecureDocs</title>
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
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <!-- WebAuthn Library -->
        <script src="{{ asset('vendor/webauthn/webauthn.js') }}" defer></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        
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
    <body class="bg-[#141326]">
        <!-- Loading spinner -->
        <div class="loading-spinner"></div>
        
        <div class="bg-[#141326] min-h-screen flex flex-col justify-center items-center px-4">
            {{ $slot }}
        </div>

        @livewireScripts
        @stack('scripts')
    </body>
</html>