<div class="fixed top-6 left-6 z-50 pt-4 pl-2">
    <button id="back-button" class="pl-4 ml-4 text-white p-3 rounded-full shadow-lg transition-colors">
        <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const backButton = document.getElementById('back-button');
        
        // Hide button if there's no history to go back to
        if (window.history.length <= 1) {
            backButton.style.display = 'none';
        }
        
        backButton.addEventListener('click', function() {
            // Check if there's a previous page in history
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
            } else {
                // Fallback to home page if no referrer
                window.location.href = "{{ url('/') }}";
            }
        });
    });
</script>

<!-- Language Toggle Button - Fixed Position Bottom Right -->
<div class="fixed bottom-6 right-6 z-50">
    <div class="relative">
        <!-- Toggle Button -->
        <button id="language-toggle" class="bg-[#3c3f58] text-white p-3 rounded-full shadow-lg transition
            style="transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#55597C';"
            onmouseout="this.style.backgroundColor='';">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div id="language-dropdown" class="absolute bottom-full right-0 mb-2 hidden bg-[#3c3f58] rounded-lg shadow-xl overflow-hidden min-w-[140px]">
            <a href="{{ route('language.switch', 'en') }}"
                class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'en' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                @if(app()->getLocale() != 'en')
                    style="transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#55597C';"
                    onmouseout="this.style.backgroundColor='';"
                @endif>
                <span class="mr-2">🇺🇸</span>
                English
            </a>
            <a href="{{ route('language.switch', 'fil') }}"
                class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'fil' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                @if(app()->getLocale() != 'fil')
                    style="transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#55597C';"
                    onmouseout="this.style.backgroundColor='';"
                @endif>
                <span class="mr-2">🇵🇭</span>
                Filipino
            </a>
        </div>
    </div>
</div>

<script>
// Language Dropdown Toggle
document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('language-toggle');
        const dropdown = document.getElementById('language-dropdown');
        
        if (toggleButton && dropdown) {
            toggleButton.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!toggleButton.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }
    });
</script>

<x-guest-layout>
<div class="absolute left-1/2 transform -translate-x-1/2 w-full" style="top: 60px;">
    <x-authentication-card>
        <x-slot name="logo">
            <head>
                <title>{{ __('auth.forgot_password_header') }}</title>

                <style>
                    .custom-form-position {
                        transform: translateY(-80px);
                    }
                </style>
            </head>
            

            <a href="{{ url('/') }}">
            <header class="-pt-2 mb-2 flex flex-col items-center py-4">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-12 h-12">
                    <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
                </div>
            </a>
                <div class="mt-8 text-center">
                    <p class="text-[#f89c00] text-xl font-semibold tracking-wide">{{ __('auth.forgot_password_header') }}</p>
                </div>
            </header>
        </x-slot>
    
        <form method="POST" action="{{ route('password.email') }}" class="bg-[#3c3f58] flex flex-col items-center justify-center w-full max-w-4xl mx-auto rounded-4xl px-8 py-6 pb-8 space-y-6">            @csrf

            <div class=" w-4/6 min-w-[420px] ">
                <label class="-pb-4 text-white text-l font-extrabold tracking-wide text-left leading-snug whitespace-pre-line">{{ __('auth.forgot_password_title')}}</label>
            </div>


            <div class="w-4/6 min-w-[420px] -pb-4 mb-6">
            <div class="text-justify">
                <label for="email" class="-pt-2 pb-4 block text-white text-sm font-normal mb-3 tracking-wide text-justify">{{ __('auth.type_email_here') }}</label>
            </div>
                <input id="email" name="email" type="email" required autofocus autocomplete="username" :value="old('email')" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]"/>
            </div>

            <div class="flex items-center justify-end mt-4">
                <button class="bg-[#f89c00] text-black font-extrabold text-base rounded-full py-2.5 px-10 pt-2 tracking-wide hover:bg-[#d17f00] transition-colors">
                    {{ __('auth.password_reset_btn') }}
                </button>
            </div>
        </form>

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession
        <x-validation-errors class="mb-4" />
        </div>
    </x-authentication-card>
</x-guest-layout>
