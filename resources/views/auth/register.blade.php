<!-- Back Button - Fixed Position Top Left -->
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
    <x-authentication-card>
        <x-slot name="logo">
            <head>
                <title>{{ __('auth.register_header') }}</title>
            </head>

            <a href="{{ url('/') }}">
            <header class="-mt-2 mb-2 flex flex-col items-center py-8">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-12 h-12">
                    <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
                </div>
            </a>
                <div class="mt-8 text-center">
                    <p class="text-[#f89c00] text-xl font-semibold tracking-wide">{{ __('auth.signup_title') }}</p>
                </div>
            </header>

            

        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}" class="bg-[#3c3f58] flex flex-col items-center justify-center w-full max-w-4xl mx-auto rounded-4xl px-8 py-2 pb-6 space-y-8">
            @csrf
            <div>
            <div class=" w-4/6 min-w-[420px] mb-4">
                <label class="-pb-2 block text-white text-l font-extrabold tracking-wide text-left">{{ __('auth.personal_info') }}</label>
            </div>


            <div class="w-4/6 min-w-[420px] -pb-4 mb-6">
                <label for="name" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('auth.name') }}</label>
                <input id="name" name="name" type="text" required autofocus autocomplete="name" value="{{ old('name') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="w-4/6 min-w-[420px] -pb-4">
                <label for="email" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('auth.email') }}</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="{{ old('email') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>
            </div>

            <!-- Separate toggle for textfield -->
            <!--
            <div>
                <div class="w-4/6 min-w-[420px] mt-8 mb-4">
                    <label class="block text-white text-l font-extrabold tracking-wide text-left">CREATE A PASSWORD</label>
                </div>

                <div class="relative w-4/6 min-w-[420px] mb-6">
                    <label for="password" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">PASSWORD</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
                    <button type="button" id="togglePassword" class="absolute right-3 flex items-center top-1/2 -mt-4">
                        <img id="password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-8 h-8">
                    </button>
                </div>

                <div class="relative w-4/6 min-w-[420px]">
                    <label for="password_confirmation" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">CONFIRM PASSWORD</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
                    <button type="button" id="togglePasswordConfirm" class="absolute right-3 flex items-center top-1/2 -mt-4">
                        <img id="password-confirm-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Confirm Password Visibility" class="w-8 h-8">
                    </button>
                </div>

                <script>
                    const toggleVisibility = (inputId, buttonId, iconId) => {
                        const input = document.getElementById(inputId);
                        const icon = document.getElementById(iconId);
                        const button = document.getElementById(buttonId);

                        button.addEventListener('click', () => {
                            const isPassword = input.getAttribute('type') === 'password';
                            input.setAttribute('type', isPassword ? 'text' : 'password');
                            icon.src = isPassword ? "{{ asset('eye-open.png') }}" : "{{ asset('eye-close.png') }}";
                        });
                    };

                    toggleVisibility('password', 'togglePassword', 'password-toggle-icon');
                    toggleVisibility('password_confirmation', 'togglePasswordConfirm', 'password-confirm-toggle-icon');
                </script>
            </div>
            -->

        <!-- Dual Toggle for textfields -->
        <div>
            <div class="w-4/6 min-w-[420px] mt-8 mb-4">
            <label class="block text-white text-l font-extrabold tracking-wide text-left">{{ __('auth.create_password') }}</label>
        </div>

        <!-- PASSWORD FIELD -->
        <div class="relative w-4/6 min-w-[420px] mb-6">
            <label for="password" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('auth.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            <button type="button" class="absolute right-3 flex items-center top-1/2 toggle-both">
                <img src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-8 h-8 toggle-icon">
            </button>
        </div>

        <!-- CONFIRM PASSWORD FIELD -->
        <div class="relative w-4/6 min-w-[420px]">
            <label for="password_confirmation" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('auth.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            <button type="button" class="absolute right-3 flex items-center top-1/2 toggle-both">
                <img src="{{ asset('eye-close.png') }}" alt="Toggle Confirm Password Visibility" class="w-8 h-8 toggle-icon">
            </button>
        </div>

        <script>
            const toggleButtons = document.querySelectorAll('.toggle-both');
            const icons = document.querySelectorAll('.toggle-icon');
            const fields = [
                document.getElementById('password'),
                document.getElementById('password_confirmation')
            ];

            toggleButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const anyHidden = fields.some(f => f.type === 'password');
                    const newType = anyHidden ? 'text' : 'password';

                    fields.forEach(f => f.type = newType);
                    icons.forEach(icon => {
                        icon.src = newType === 'text'
                            ? "{{ asset('eye-open.png') }}"
                            : "{{ asset('eye-close.png') }}";
                    });
                });
            });
        </script>

            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="text-white text-sm mt-4 text-center w-4/6 min-w-[420px]">
                    {!! __('By signing up, you agree to the :terms_of_service and :privacy_policy', [
                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline hover:text-[#f89c00]">Terms of Service</a>',
                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline hover:text-[#f89c00]">Privacy Policy</a>',
                    ]) !!}
                </div>
            @endif

            <div class="flex justify-center w-4/6 min-w-[420px] pb-3">
                <button type="submit" class="bg-[#f89c00] text-black font-extrabold text-base rounded-full py-2.5 px-10 pt-2 tracking-wide hover:bg-[#d17f00] transition-colors">{{ __('auth.register') }}</button>
            </div>
        </form>
    </x-authentication-card>

    <footer class="mt-5 pt-5 mb-10 text-center">
        <p class="text-white text-base tracking-wide">
            {{ __('auth.have_an_account') }}
            <a href="{{ route('login') }}" class="font-extrabold text-[#f89c00] hover:underline">{{ __('auth.login_here') }}</a>
        </p>
    </footer>
</x-guest-layout>
