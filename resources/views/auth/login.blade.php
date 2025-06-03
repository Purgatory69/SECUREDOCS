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
    </x-authentication-card>
</x-guest-layout>
