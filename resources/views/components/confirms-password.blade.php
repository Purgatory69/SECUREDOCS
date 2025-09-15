@props(['title' => __('Confirm Password'), 'content' => __('For your security, please confirm your password to continue.'), 'button' => __('Confirm')])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<span
    {{ $attributes->wire('then') }}
    x-data
    x-ref="span"
    x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
    x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);"
>
    {{ $slot }}
</span>

@once
<x-dialog-modal wire:model.live="confirmingPassword">
    <x-slot name="title">
        {{ $title }}
    </x-slot>

    <x-slot name="content" class="modal-content-text" style="color: rgba(255, 255, 255, 0.7);">
        {{ $content }}

        <div class="mt-4" x-data="{}" x-on:confirming-password.window="setTimeout(() => $refs.confirmable_password.focus(), 250)">
            <div class="relative">
                <x-input type="password" 
                         id="confirmable_password_input" 
                         class="mt-1 block w-3/4 pr-12" 
                         placeholder="{{ __('Password') }}" 
                         autocomplete="current-password"
                         x-ref="confirmable_password"
                         wire:model="confirmablePassword"
                         wire:keydown.enter="confirmPassword" />
                <button type="button" style="margin-right: 158px;" id="toggleConfirmablePassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center z-10">
                    <img id="confirmable-password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-6 h-6">
                </button>
            </div>
            <x-input-error for="confirmable_password" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="stopConfirmingPassword" wire:loading.attr="disabled">
            {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ms-3 modal-confirm-button" dusk="confirm-password-button" wire:click="confirmPassword" wire:loading.attr="disabled">
            {{ $button }}
        </x-button>
    </x-slot>
</x-dialog-modal>

<style>
/* Custom Modal Styling */
[x-data] .fixed.inset-0.z-50 {
    backdrop-filter: blur(4px);
}

/* Modal Background Overlay - Force #24243b background */
[x-data] .fixed.inset-0.px-4.py-6 {
    background-color: rgba(36, 36, 59, 0.95) !important;
}

/* Modal Container - Force #24243b background */
[x-data] .bg-white {
    background-color: #24243b !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
}

/* Modal Title */
[x-data] .text-lg.font-medium {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 600 !important;
}

/* Modal Footer - Force #24243b background */
[x-data] .px-6.py-4.bg-gray-100 {
    background-color: #24243b !important;
}

/* Modal Content Area - Force #24243b background */
[x-data] .px-6.py-4:not(.bg-gray-100) {
    background-color: #24243b !important;
}

/* Modal Input Field */
[x-data] input[type="password"] {
    background-color: white !important;
    color: black !important;
}

/* Custom focus styling for password input */
[x-data] input[type="password"]:focus {
    border-color: #141326 !important;
    outline: none !important;
}

[x-data] input[type="text"] {
    background-color: white !important;
    color: black !important;
    padding-right: 3rem !important; /* Add padding for the toggle button */
}

/* Custom focus styling for text input (when password is toggled) */
[x-data] input[type="text"]:focus {
    border-color: #141326 !important;
    outline: none !important;}

/* Override Tailwind's default focus styles */
[x-data] input:focus {
    border-color: #141326 !important;
    outline: none !important;
}

/* Modal Buttons */
[x-data] .modal-confirm-button {
    background-color: #f89c00 !important;
    color: #000000 !important;
}

[x-data] .modal-confirm-button:hover:not(:disabled) {
    background-color: #ffb033 !important;
}

/* Secondary Button (Cancel) */
[x-data] button.bg-white {
    background-color: #4a4d6b !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[x-data] button.bg-white:hover {
    background-color: #5a5d7b !important;
}

/* Password Toggle Button - Positioned inside input */
#toggleConfirmablePassword {
    z-index: 10;
}

#toggleConfirmablePassword img {
    opacity: 1 !important; /* Full opacity for toggle button */
    transition: opacity 0.2s ease;
}

#toggleConfirmablePassword:hover img {
    opacity: 0.8 !important; /* Slight hover effect */
}

/* Error Messages */
[x-data] .text-red-600 {
    color: #ef4444 !important;
}

/* Additional specificity to override show.blade.php styles */
.fixed.inset-0.z-50 > .fixed.inset-0.px-4.py-6 {
    background-color: rgba(36, 36, 59, 0.95) !important;
}

.fixed.inset-0.z-50 > .fixed.inset-0.px-4.py-6 > * {
    background-color: #24243b !important;
}

/* Ensure modal dialog specifically uses #24243b */
[x-data] [role="dialog"] {
    background-color: #24243b !important;
}

[x-data] [role="dialog"] > * {
    background-color: #24243b !important;
}

.settings-form-wrapper [role="dialog"] .modal-content-text { color: rgba(255,255,255,0.7) !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle for confirmable password
    const confirmablePasswordInput = document.getElementById('confirmable_password_input');
    const toggleConfirmableButton = document.getElementById('toggleConfirmablePassword');
    const confirmableToggleIcon = document.getElementById('confirmable-password-toggle-icon');
    
    if (toggleConfirmableButton && confirmablePasswordInput && confirmableToggleIcon) {
        toggleConfirmableButton.addEventListener('click', function() {
            const isPassword = confirmablePasswordInput.getAttribute('type') === 'password';
            confirmablePasswordInput.setAttribute('type', isPassword ? 'text' : 'password');
            confirmableToggleIcon.src = isPassword ? "{{ asset('eye-open.png') }}" : "{{ asset('eye-close.png') }}";
        });
    }
});
</script>
@endonce