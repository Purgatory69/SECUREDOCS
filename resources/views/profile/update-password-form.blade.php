<div class="settings-form-wrapper">
<x-form-section submit="updatePassword">
    <x-slot name="title">
        <span class="settings-title">{{ __('Update Password') }}</span>
    </x-slot>

    <x-slot name="description">
        <span class="settings-description">{{ __('Ensure your account is using a long, random password to stay secure.') }}</span>
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-label for="current_password" class="settings-label" value="{{ __('Current Password') }}" />
            <div class="relative">
                <x-input id="current_password" type="password" class="mt-1 block w-full pr-12" wire:model="state.current_password" autocomplete="current-password" />
                <button type="button" id="toggleCurrentPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center">
                    <img id="current-password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-6 h-6">
                </button>
            </div>
            <x-input-error for="current_password" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="password" class="settings-label" value="{{ __('New Password') }}" />
            <div class="relative">
                <x-input id="password" type="password" class="mt-1 block w-full pr-12" wire:model="state.password" autocomplete="new-password" />
                <button type="button" id="toggleNewPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center">
                    <img id="new-password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-6 h-6">
                </button>
            </div>
            <x-input-error for="password" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="password_confirmation" class="settings-label" value="{{ __('Confirm Password') }}" />
            <div class="relative">
                <x-input id="password_confirmation" type="password" class="mt-1 block w-full pr-12" wire:model="state.password_confirmation" autocomplete="new-password" />
                <button type="button" id="toggleConfirmPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center">
                    <img id="confirm-password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-6 h-6">
                </button>
            </div>
            <x-input-error for="password_confirmation" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button class="settings-button transition-all duration-200 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password field elements
    const currentPasswordInput = document.getElementById('current_password');
    const newPasswordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    
    // Toggle buttons
    const toggleCurrentButton = document.getElementById('toggleCurrentPassword');
    const toggleNewButton = document.getElementById('toggleNewPassword');
    const toggleConfirmButton = document.getElementById('toggleConfirmPassword');
    
    // Toggle icons
    const currentToggleIcon = document.getElementById('current-password-toggle-icon');
    const newToggleIcon = document.getElementById('new-password-toggle-icon');
    const confirmToggleIcon = document.getElementById('confirm-password-toggle-icon');
    
    // Track global visibility state
    let allPasswordsVisible = false;
    
    // Function to toggle ALL password fields at once
    function toggleAllPasswords() {
        allPasswordsVisible = !allPasswordsVisible;
        
        const inputs = [currentPasswordInput, newPasswordInput, confirmPasswordInput];
        const icons = [currentToggleIcon, newToggleIcon, confirmToggleIcon];
        
        // Update all password fields to the same state
        inputs.forEach((input, index) => {
            if (input && icons[index]) {
                input.setAttribute('type', allPasswordsVisible ? 'text' : 'password');
                icons[index].src = allPasswordsVisible ? "{{ asset('eye-open.png') }}" : "{{ asset('eye-close.png') }}";
            }
        });
    }
    
    // Add event listeners - each button toggles ALL passwords
    if (toggleCurrentButton) {
        toggleCurrentButton.addEventListener('click', function() {
            toggleAllPasswords();
        });
    }
    
    if (toggleNewButton) {
        toggleNewButton.addEventListener('click', function() {
            toggleAllPasswords();
        });
    }
    
    if (toggleConfirmButton) {
        toggleConfirmButton.addEventListener('click', function() {
            toggleAllPasswords();
        });
    }
});
</script>
</div>