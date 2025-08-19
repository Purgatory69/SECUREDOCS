<!-- Language Toggle Button - Fixed Position Bottom Right -->
<div class="fixed bottom-6 right-6 z-50">
    <div class="relative">
        <!-- Toggle Button -->
        <button id="language-toggle" class="bg-[#3c3f58] hover:bg-gray-600 text-white p-3 rounded-full shadow-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
        </button>
        
        <!-- Dropdown Menu -->
        <div id="language-dropdown" class="absolute bottom-full right-0 mb-2 hidden bg-[#3c3f58] rounded-lg shadow-xl overflow-hidden min-w-[140px]">
            <a href="{{ route('language.switch', 'en') }}" 
               class="flex items-center px-4 py-3 text-sm transition-colors hover:bg-gray-600 {{ app()->getLocale() == 'en' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}">
                <span class="mr-2">🇺🇸</span>
                English
            </a>
            <a href="{{ route('language.switch', 'fil') }}" 
               class="flex items-center px-4 py-3 text-sm transition-colors hover:bg-gray-600 {{ app()->getLocale() == 'fil' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}">
                <span class="mr-2">🇵🇭</span>
                Filipino
            </a>
        </div>
    </div>
</div>

<x-guest-layout>
    
    <x-authentication-card>
        <x-slot name="logo">
            <head>
                <title>Login</title>
                <link rel="icon" type="image/png" href="{{ asset('logo-white.png') }}">
            </head>

            <a href="{{ url('/') }}">
            <header class="-mt-2 mb-2 flex flex-col items-center py-8">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-12 h-12">
                    <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-[#f89c00] text-xl font-semibold tracking-wide">{{ __('auth.login_title') }}</p>
                </div>
            </header>
            </a>
        </x-slot>

        @if ($errors->has('email'))
            @php
                $lockoutMessage = $errors->first('email');
                preg_match('/(\d+) seconds?/', $lockoutMessage, $matches);
                $seconds = $matches[1] ?? 0;
            @endphp
            @if ($seconds > 0)
                <div class="font-medium text-sm text-red-600 text-center">
                    You have been locked out due to too many failed login attempts.<br>
                    Please try again in <span id="lockout-countdown">{{ $seconds }}</span> seconds.
                </div>
                
                <script>
                    let lockoutSeconds = {{ $seconds }};
                    let countdownElem = document.getElementById('lockout-countdown');
                    let interval = setInterval(function () {
                        if (lockoutSeconds > 0) {
                            lockoutSeconds--;
                            countdownElem.textContent = lockoutSeconds;
                        }
                        if (lockoutSeconds <= 0) {
                            clearInterval(interval);
                            location.reload();
                        }
                    }, 1000);
                </script>
            @endif
        @endif

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600 text-center">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="bg-[#3c3f58] rounded-4xl w-full px-8 py-2 space-y-8 flex flex-col items-center">
        @csrf

        <div class="w-4/6 min-w-[420px]">
            <label for="email" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('auth.email') }}</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username" :value="old('email')" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
        </div>

        <div class="relative w-4/6 min-w-[420px]">
            <label for="password" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('auth.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 pr-20 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            <button type="button" id="togglePassword" class="absolute right-3 flex items-center top-1/2 -mt-4">
                <img id="password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-8 h-8">
            </button>
        </div>

        <script>
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('password-toggle-icon');

        toggleButton.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'text') {
                toggleIcon.src = "{{ asset('eye-open.png') }}";
            } else {
                toggleIcon.src = "{{ asset('eye-close.png') }}";
            }
        });
        </script>

        <div class="flex justify-end w-4/6 min-w-[420px] items-center space-x-6">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-white text-sm underline tracking-wide hover:text-[#f89c00]">{{ __('auth.forgot_password') }}</a>
            @endif
            <button type="submit" class="bg-[#f89c00] text-black font-extrabold text-base rounded-full py-2.5 px-10 tracking-wide hover:bg-[#d17f00] transition-colors">{{ __('auth.login') }}</button>
        </div>

        <button type="button" id="biometric-login-button" class="w-1/2 min-w-[320px] bg-[#9ba0f9] text-black font-extrabold text-base rounded-full py-2.5 px-10 tracking-wide hover:bg-[#7a7ef0] transition-colors">
            {{ __('auth.login_biometrics') }}
        </button>
        <p id="biometric-login-status" class="text-sm text-red-600 mt-2 text-center"></p>

        </form>

    </x-authentication-card>

    <footer class="mt-10 mb-10 text-center">
    <p class="text-white text-base tracking-wide">
        {{ __('auth.signup_prompt') }}
        <a href="{{ route('register') }}" class="font-extrabold text-[#f89c00] hover:underline">{{ __('auth.signup') }}</a>
    </p>
    </footer>

<script src="{{ asset('vendor/webauthn/webauthn.js') }}" defer></script>
@vite(['resources/css/app.css', 'resources/js/app.js'])

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

    // Biometric Login
    document.addEventListener('DOMContentLoaded', function () {
        const emailInput = document.getElementById('email');
        const biometricButton = document.getElementById('biometric-login-button');
        const statusDisplay = document.getElementById('biometric-login-status');

        if (biometricButton) {
            biometricButton.addEventListener('click', async function () {
                if (!emailInput.value.trim()) {
                    statusDisplay.textContent = 'Please enter your email address first.';
                    return;
                }

                if (window.handleBiometricLogin) {
                    window.handleBiometricLogin(emailInput.value, biometricButton, statusDisplay);
                } else {
                    statusDisplay.textContent = 'Biometric login script not loaded correctly.';
                }
            });
        }
    });
</script>

</x-guest-layout>