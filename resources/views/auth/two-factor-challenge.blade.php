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

<div class="fixed bottom-6 right-6 z-50">
    <div class="relative">
        <button id="language-toggle" class="bg-[#3c3f58] text-white p-3 rounded-full shadow-lg transition
            style="transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#55597C';"
            onmouseout="this.style.backgroundColor='';">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
        </button>

        <div id="language-dropdown" style="background-color: #3c3f58; border: 3px solid #1F1F33" class="absolute bottom-full right-0 mb-2 hidden bg-[#3c3f58] rounded-lg shadow-xl overflow-hidden min-w-[140px]">
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

            <a href="{{ url('/') }}">
            <header class="-pt-2 mb-2 flex flex-col items-center py-4">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-12 h-12">
                    <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
                </div>
            </a>
                <div class="mt-8 text-center">
                    <p class="text-[#f89c00] text-xl font-semibold tracking-wide">{{ __('Two-Factor Authentication') }}</p>
                </div>
            </header>
        </x-slot>

        <div x-data="{ recovery: false }" style="margin-left: -10px; margin-top:20px;" class="bg-[#3c3f58] flex flex-col justify-center w-full rounded-4xl px-8 py-6 pb-8 space-y-6">
        <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('two-factor.login') }}" class="w-full">
                @csrf

                {{-- DYNAMIC BLOCK 1: Authentication + Input --}}
                <div class="w-4/6 min-w-[420px] space-y-4 mx-auto" x-show="! recovery">
                    {{-- Header --}}
                    <label class="-pb-4 text-white text-l font-extrabold tracking-wide text-left leading-snug whitespace-pre-line">{{ __('Enter Authentication Code')}}</label>
                    {{-- Subtext --}}
                    <div class="text-sm text-white text-left">
                        {{ __('Please confirm access to your account by entering the code provided by your authenticator application.') }}
                    </div>
                    {{-- Input --}}
                    <div>
                        <x-input id="code" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]" type="text" inputmode="numeric" name="code" placeholder="{{ __('Authentication Code') }}" autofocus x-ref="code" autocomplete="one-time-code" />
                    </div>
                </div>

                {{-- DYNAMIC BLOCK 2: Recovery + Input --}}
                <div class="w-4/6 min-w-[420px] space-y-4 mx-auto" x-cloak x-show="recovery">
                    {{-- Header --}}
                    <label class="-pb-4 text-white text-l font-extrabold tracking-wide text-left leading-snug whitespace-pre-line">{{ __('Enter Recovery Code')}}</label>
                    {{-- Subtext --}}
                    <div class="text-sm text-white text-left">
                        {{ __('Please confirm access to your account by entering one of your recovery codes.') }}
                    </div>
                    {{-- Input --}}
                    <div>
                        <x-input id="recovery_code" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]" type="text" name="recovery_code" placeholder="{{ __('Recovery Code') }}" x-ref="recovery_code" autocomplete="one-time-code" />
                    </div>
                </div>

                {{-- Added width class to match inputs --}}
                <div class="flex items-center justify-end mt-4 w-4/6 min-w-[420px] mx-auto">                    
                    {{-- Styled toggle button text to be visible --}}
                    <button type="button" class="text-sm text-gray-300 hover:text-white underline cursor-pointer"
                                x-show="! recovery"
                                x-on:click="
                                    recovery = true;
                                    $nextTick(() => { $refs.recovery_code.focus() })
                                ">
                        {{ __('Use a recovery code') }}
                    </button>

                    {{-- Styled toggle button text to be visible --}}
                    <button type="button" class="text-sm text-gray-300 hover:text-white underline cursor-pointer"
                                x-cloak
                                x-show="recovery"
                                x-on:click="
                                    recovery = false;
                                    $nextTick(() => { $refs.code.focus() })
                                ">
                        {{ __('Use an authentication code') }}
                    </button>

                    {{-- Replaced <x-button> with the custom <button> --}}
                    <button type="submit" class="ms-4 bg-[#f89c00] text-black font-extrabold text-base rounded-full py-2.5 px-10 pt-2 tracking-wide hover:bg-[#d17f00] transition-colors">
                        {{ __('Log in') }}
                    </button>
                </div>
            </form>
        </div>
    </x-authentication-card>
</div>
</x-guest-layout>