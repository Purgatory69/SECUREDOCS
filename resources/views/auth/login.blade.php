<x-guest-layout>
    <!-- <div class="min-h-screen flex flex-col justify-between bg-[#1c1d2b] px-4"> -->
    <x-authentication-card>
        <x-slot name="logo">

            <head>
                <title>Login</title>
            </head>

            <a href="{{ url('/') }}">
                <header class="-mt-2 mb-2 flex flex-col items-center py-8">
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-12 h-12">
                        <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
                    </div>

                    <div class="mt-8 text-center">
                        <p class="text-[#f89c00] text-xl font-semibold tracking-wide">Login to your account!</p>
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

        <form method="POST" action="{{ route('login') }}"
            class="bg-[#3c3f58] rounded-4xl w-full px-8 py-2 space-y-8 flex flex-col items-center">
            @csrf

            <div class="w-4/6 min-w-[420px]">
                <label for="email"
                    class="block text-white text-sm font-normal mb-3 tracking-wide text-left">EMAIL</label>
                <input id="email" name="email" type="email" required autofocus autocomplete="username"
                    :value="old('email')"
                    class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="relative w-4/6 min-w-[420px]">
                <label for="password"
                    class="block text-white text-sm font-normal mb-3 tracking-wide text-left">PASSWORD</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    style="padding-right: 60px;"
                    class="w-full rounded-full py-2.5 pr-20 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
                <button type="button" id="togglePassword" class="absolute right-3 flex items-center top-1/2 -mt-4">
                    <img id="password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility"
                        class="w-8 h-8 mt-4">
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
                    <a href="{{ route('password.request') }}"
                        class="text-white text-sm underline tracking-wide hover:text-[#f89c00]">Forgot Password?</a>
                @endif
                <button type="submit"
                    class="bg-[#f89c00] text-black font-extrabold text-base rounded-full py-2.5 px-10 tracking-wide hover:bg-[#d17f00] transition-colors">LOGIN</button>
            </div>

            <button type="button" id="biometric-login-button"
                class="w-1/2 min-w-[320px] bg-[#9ba0f9] text-black font-extrabold text-base rounded-full py-2.5 px-10 tracking-wide hover:bg-[#7a7ef0] transition-colors">
                LOGIN WITH BIOMETRICS
            </button>
            <p id="biometric-login-status" class="text-sm text-red-600 mt-2 text-center"></p>

        </form>

    </x-authentication-card>

    <footer class="mt-10 mb-10 text-center">
        <p class="text-white text-base tracking-wide">
            Don’t have an account?
            <a href="{{ route('register') }}" class="font-extrabold text-[#f89c00] hover:underline">Sign Up!</a>
        </p>
    </footer>

    <script src="{{ asset('vendor/webauthn/webauthn.js') }}" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
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