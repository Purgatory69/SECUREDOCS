<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <header class="-mt-2 mb-2 flex items-center space-x-3 py-8">
                <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-10 h-10">
                <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
            </header>
        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}" class="bg-[#3c3f58] rounded-4xl w-full px-8 py-2 pb-6 space-y-8 flex flex-col items-center">
            @csrf

            <div class="w-4/6 min-w-[420px]">
                <label for="name" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">NAME</label>
                <input id="name" name="name" type="text" required autofocus autocomplete="name" value="{{ old('name') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="w-4/6 min-w-[420px]">
                <label for="email" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">EMAIL</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="{{ old('email') }}" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="w-4/6 min-w-[420px]">
                <label for="password" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">PASSWORD</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
            </div>

            <div class="w-4/6 min-w-[420px]">
                <label for="password_confirmation" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">CONFIRM PASSWORD</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-full py-2.5 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]">
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

    <footer class="mt-5 mb-10 text-center">
        <p class="text-white text-base tracking-wide">
            Already have an account?
            <a href="{{ route('login') }}" class="font-extrabold text-[#f89c00] hover:underline">Log in!</a>
        </p>
    </footer>
</x-guest-layout>
