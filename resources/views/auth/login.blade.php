<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        {{-- Custom lockout error message with countdown --}}
        @if ($errors->has('email'))
            @php
                $lockoutMessage = $errors->first('email');
                preg_match('/(\d+) seconds?/', $lockoutMessage, $matches);
                $seconds = $matches[1] ?? 0;
            @endphp
            @if ($seconds > 0)
                <div class="mb-4 font-medium text-sm text-red-600">
                    You have been locked out due to too many failed login attempts.<br>
                    Please try again in <span id="lockout-countdown">{{ $seconds }}</span> seconds.
                </div>
                <script>
                    let lockoutSeconds = {{ $seconds }};
                    let countdownElem = document.getElementById('lockout-countdown');
                    let interval = setInterval(function() {
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
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-button class="ms-4">
                    {{ __('Log in') }}
                </x-button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-center text-lg font-medium text-gray-600 mb-4">{{ __('Or use biometric login') }}</h3>
            <div class="flex justify-center">
                <button type="button" id="biometric-login-button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6.625 2.655A9 9 0 0119 11a1 1 0 11-2 0 7 7 0 00-9.625-6.492 1 1 0 11-.75-1.853zM4.662 4.959A1 1 0 014.75 6.37 6.97 6.97 0 003 11a1 1 0 11-2 0 8.97 8.97 0 012.25-5.953 1 1 0 011.412-.088z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M5 11a5 5 0 1110 0 1 1 0 11-2 0 3 3 0 10-6 0c0 1.677-.345 3.276-.968 4.729a1 1 0 11-1.838-.789A9.964 9.964 0 005 11zm8.921 2.012a1 1 0 01.831 1.145 19.86 19.86 0 01-.545 2.436 1 1 0 11-1.92-.558c.207-.713.371-1.445.49-2.192a1 1 0 011.144-.83z" clip-rule="evenodd" />
                    </svg>
                    {{ __('Login with Biometrics') }}
                </button>
            </div>
            <p id="biometric-login-status" class="text-sm text-red-600 mt-2 text-center"></p>
        </div>
    </x-authentication-card>
</x-guest-layout>

<script src="{{ asset('vendor/webauthn/webauthn.js') }}" defer></script>
@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailInput = document.getElementById('email');
        const biometricButton = document.getElementById('biometric-login-button');
        const statusDisplay = document.getElementById('biometric-login-status');

        if (biometricButton) {
            biometricButton.addEventListener('click', async function() {
                if (!emailInput.value.trim()) {
                    statusDisplay.textContent = 'Please enter your email address first.';
                    statusDisplay.className = 'text-sm text-red-600 mt-2';
                    return;
                }

                if (window.handleBiometricLogin) {
                    // Pass the button and status display element for UI updates
                    window.handleBiometricLogin(emailInput.value, biometricButton, statusDisplay);
                } else {
                    console.error('WebAuthn handler (handleBiometricLogin) not found.');
                    statusDisplay.textContent = 'Biometric login script not loaded correctly.';
                    statusDisplay.className = 'text-sm text-red-600 mt-2';
                }
            });
        }
    });
</script>
