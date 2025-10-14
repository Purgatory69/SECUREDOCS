<x-profile-dashboard>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet"/>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <!-- Back Button -->
            <a href="{{ route('user.dashboard') }}" class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
            </a>

            <!-- Centered Logo and Title -->
            <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                <h2 class="font-semibold text-xl text-[#f89c00] font-['Poppins']">Your Profile</h2>
            </div>

            <!-- Empty div for spacing -->
            <div></div>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8" style="background-color: #24243B;">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif


            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>

    <style>
        .settings-form-wrapper .bg-white {background-color: #3c3f58 !important;}
        .settings-form-wrapper form > div {background-color: #3c3f58 !important;}
        .settings-form-wrapper input, .settings-form-wrapper textarea, .settings-form-wrapper select {color: initial !important;}

        .settings-form-wrapper *:not(input):not(textarea):not(select):not(button[type="submit"]):not(button[type="button"]):not(.settings-description):not(.settings-unverified-text):not(.settings-verification-link):not(.text-green-500) {color: rgba(255,255,255,0.9) !important;}
        .settings-form-wrapper .settings-title {color: rgba(255, 255, 255, 0.9) !important;}
        .settings-form-wrapper .settings-label {color: rgba(255, 255, 255, 0.9) !important;}
        .settings-form-wrapper .settings-description {color: rgba(255, 255, 255, 0.6) !important;}
        .settings-form-wrapper .settings-verification-link {color: rgba(255, 255, 255, 0.9) !important;}
        .settings-form-wrapper .settings-verification-link:hover {color: #f89c00 !important; transition: color 0.2s ease !important;}

        .settings-form-wrapper .settings-button {background-color: #f89c00 !important; color: #000000 !important; border-color: #f89c00 !important;}
        .settings-form-wrapper .settings-button:hover:not(:disabled) {background-color: #ffb033 !important; border-color: #ffb033 !important;}
    
        /* Change x-section-border color */
        .border-t,
            .border-gray-200,
            [class*="border"],
            .section-border,
            .border {
                border-color: #3c3f58 !important;
            }
            
            /* More specific targeting for section borders */
            div[class*="border-t"],
            div[class*="border-gray"] {
                border-color: #3c3f58 !important;
            }
    </style>
</x-profile-dashboard>