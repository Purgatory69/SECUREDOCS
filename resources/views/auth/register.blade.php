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
                <label for="firstname" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('FIRST NAME') }}</label>
                <input id="firstname" name="firstname" type="text" required autofocus autocomplete="given-name" value="{{ old('firstname') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="w-4/6 min-w-[420px] -pb-4 mb-6">
                <label for="lastname" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('LAST NAME') }}</label>
                <input id="lastname" name="lastname" type="text" required autocomplete="family-name" value="{{ old('lastname') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="w-4/6 min-w-[420px] -pb-4 mb-6">
                <label for="birthday" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('BIRTHDAY') }}</label>
                <input id="birthday" name="birthday" type="date" autocomplete="bday" value="{{ old('birthday') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
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
            <div class="relative">
                <input id="password" name="password" type="password" required autocomplete="new-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
                <button type="button" id="toggle-password" class="absolute right-4 top-1/2 transform -translate-y-1/2 flex items-center justify-center">
                    <img id="password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-6 h-6">
                </button>
            </div>

            <!-- Password Strength Indicator -->
            <div id="password-strength-container" class="mt-2 hidden">
                <div class="flex space-x-1 mb-2">
                    <div id="strength-bar-1" class="h-1 flex-1 rounded-full transition-colors duration-300"></div>
                    <div id="strength-bar-2" class="h-1 flex-1 rounded-full transition-colors duration-300"></div>
                    <div id="strength-bar-3" class="h-1 flex-1 rounded-full transition-colors duration-300"></div>
                    <div id="strength-bar-4" class="h-1 flex-1 rounded-full transition-colors duration-300"></div>
                </div>
                <p id="strength-text" class="text-xs text-gray-300"></p>
            </div>

            <!-- Password Requirements Checklist -->
            <div id="password-requirements" class="mt-3 space-y-1 hidden">
                <div id="req-length" class="flex items-center text-xs">
                    <span id="req-length-icon" class="w-3 h-3 rounded-full mr-2"></span>
                    <span>At least 8 characters</span>
                </div>
                <div id="req-uppercase" class="flex items-center text-xs">
                    <span id="req-uppercase-icon" class="w-3 h-3 rounded-full mr-2"></span>
                    <span>One uppercase letter (A-Z)</span>
                </div>
                <div id="req-number" class="flex items-center text-xs">
                    <span id="req-number-icon" class="w-3 h-3 rounded-full mr-2"></span>
                    <span>One number (0-9)</span>
                </div>
                <div id="req-special" class="flex items-center text-xs">
                    <span id="req-special-icon" class="w-3 h-3 rounded-full mr-2"></span>
                    <span>One special character (!@#$%^&*)</span>
                </div>
            </div>
        </div>

        <!-- CONFIRM PASSWORD FIELD -->
        <div class="relative w-4/6 min-w-[420px]">
            <label for="password_confirmation" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">{{ __('auth.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
        </div>

        <script>
            // Password visibility toggle for main password field only
            document.addEventListener('DOMContentLoaded', function() {
                const toggleButton = document.getElementById('toggle-password');
                const passwordField = document.getElementById('password');
                const toggleIcon = document.getElementById('password-toggle-icon');

                if (toggleButton && passwordField && toggleIcon) {
                    toggleButton.addEventListener('click', function() {
                        const isPassword = passwordField.type === 'password';
                        passwordField.type = isPassword ? 'text' : 'password';
                        toggleIcon.src = isPassword 
                            ? "{{ asset('eye-open.png') }}" 
                            : "{{ asset('eye-close.png') }}";
                    });
                }
            });
        </script>

        <!-- Password Strength Validation Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const passwordInput = document.getElementById('password');
                const strengthContainer = document.getElementById('password-strength-container');
                const requirementsContainer = document.getElementById('password-requirements');
                const strengthBars = [
                    document.getElementById('strength-bar-1'),
                    document.getElementById('strength-bar-2'),
                    document.getElementById('strength-bar-3'),
                    document.getElementById('strength-bar-4')
                ];
                const strengthText = document.getElementById('strength-text');

                // Requirement elements
                const reqElements = {
                    length: { icon: document.getElementById('req-length-icon'), text: document.getElementById('req-length') },
                    uppercase: { icon: document.getElementById('req-uppercase-icon'), text: document.getElementById('req-uppercase') },
                    number: { icon: document.getElementById('req-number-icon'), text: document.getElementById('req-number') },
                    special: { icon: document.getElementById('req-special-icon'), text: document.getElementById('req-special') }
                };

                function checkPasswordRequirements(password) {
                    return {
                        length: password.length >= 8,
                        uppercase: /[A-Z]/.test(password),
                        number: /[0-9]/.test(password),
                        special: /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password)
                    };
                }

                function updateRequirementDisplay(requirements) {
                    Object.keys(requirements).forEach(req => {
                        const isValid = requirements[req];
                        const element = reqElements[req];

                        element.icon.className = `w-3 h-3 rounded-full mr-2 ${isValid ? 'bg-green-500' : 'bg-red-500'}`;
                        element.text.className = `flex items-center text-xs ${isValid ? 'text-green-400' : 'text-red-400'}`;
                    });
                }

                function calculateStrength(requirements) {
                    const validCount = Object.values(requirements).filter(Boolean).length;

                    if (validCount === 0) return { level: 0, text: '', color: '' };
                    if (validCount === 1) return { level: 1, text: 'Weak', color: 'bg-red-500' };
                    if (validCount === 2) return { level: 2, text: 'Fair', color: 'bg-yellow-500' };
                    if (validCount === 3) return { level: 3, text: 'Good', color: 'bg-blue-500' };
                    return { level: 4, text: 'Strong', color: 'bg-green-500' };
                }

                function updateStrengthIndicator(password) {
                    if (password.length === 0) {
                        strengthContainer.classList.add('hidden');
                        requirementsContainer.classList.add('hidden');
                        return;
                    }

                    strengthContainer.classList.remove('hidden');
                    requirementsContainer.classList.remove('hidden');

                    const requirements = checkPasswordRequirements(password);
                    updateRequirementDisplay(requirements);

                    const strength = calculateStrength(requirements);
                    strengthText.textContent = strength.text;
                    strengthText.className = `text-xs ${strength.level >= 3 ? 'text-green-400' : strength.level >= 2 ? 'text-yellow-400' : 'text-red-400'}`;

                    // Update strength bars
                    strengthBars.forEach((bar, index) => {
                        if (index < strength.level) {
                            bar.className = `h-1 flex-1 rounded-full transition-colors duration-300 ${strength.color}`;
                        } else {
                            bar.className = 'h-1 flex-1 rounded-full transition-colors duration-300 bg-gray-600';
                        }
                    });
                }

                passwordInput.addEventListener('input', function() {
                    updateStrengthIndicator(this.value);
                });

                // Show requirements on focus
                passwordInput.addEventListener('focus', function() {
                    if (this.value.length > 0) {
                        requirementsContainer.classList.remove('hidden');
                    }
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
