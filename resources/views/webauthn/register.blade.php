<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <head>
                <title>Register</title>
            </head>

            <a href="{{ url('/') }}">
            <header class="-mt-2 mb-2 flex flex-col items-center py-8">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-12 h-12">
                    <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-[#f89c00] text-xl font-semibold tracking-wide">Create a SecureDocs account!</p>
                </div>
            </header>
            </a>

            

        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}" class="bg-[#3c3f58] flex justify-center w-4/ min-w-[850px] rounded-4xl w-full px-8 py-2 pb-6 space-y-8 flex flex-col items-center">
            @csrf
            <div>
            <div class=" w-4/6 min-w-[420px] mb-4">
                <label class="-mb-6 block text-white text-l font-extrabold tracking-wide text-left">PERSONAL INFORMATION</label>
            </div>

            <div class="w-4/6 min-w-[420px] -pb-4 mb-6">
                <label for="name" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">NAME</label>
                <input id="name" name="name" type="text" required autofocus autocomplete="name" value="{{ old('name') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="w-4/6 min-w-[420px] -pb-4">
                <label for="email" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">EMAIL</label>
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
            <label class="block text-white text-l font-extrabold tracking-wide text-left">CREATE A PASSWORD</label>
        </div>

        <!-- PASSWORD FIELD -->
        <div class="relative w-4/6 min-w-[420px] mb-6">
            <label for="password" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">PASSWORD</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            <button type="button" class="absolute right-3 flex items-center top-1/2 -mt-4 toggle-both">
                <img src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-8 h-8 toggle-icon">
            </button>
        </div>

        <!-- CONFIRM PASSWORD FIELD -->
        <div class="relative w-4/6 min-w-[420px]">
            <label for="password_confirmation" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">CONFIRM PASSWORD</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" style="padding-right: 60px;" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            <button type="button" class="absolute right-3 flex items-center top-1/2 -mt-4 toggle-both">
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
                <button type="submit" class="bg-[#f89c00] text-black font-extrabold text-base rounded-full py-2.5 px-10 tracking-wide hover:bg-[#d17f00] transition-colors">REGISTER</button>
            </div>
        </form>
    </x-authentication-card>

    <footer class="mt-5 pt-5 mb-10 text-center">
        <p class="text-white text-base tracking-wide">
            Already have an account?
            <a href="{{ route('login') }}" class="font-extrabold text-[#f89c00] hover:underline">Log in!</a>
        </p>
    </footer>
</x-guest-layout>
