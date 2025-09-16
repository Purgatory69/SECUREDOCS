<div class="settings-form-wrapper">
<x-action-section>
    <x-slot name="title">
        <span class="settings-title">{{ __('Delete Account') }}</span>
    </x-slot>

    <x-slot name="description">
        <span class="settings-description">{{ __('Permanently delete your account.') }}</span>
    </x-slot>

    <x-slot name="content">
        <div class="settings-description max-w-xl text-sm">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </div>

        <div class="mt-5">
            <x-danger-button wire:click="confirmUserDeletion" wire:loading.attr="disabled">
                {{ __('Delete Account') }}
            </x-danger-button>
        </div>

        <!-- Delete User Confirmation Modal -->
        <x-dialog-modal wire:model.live="confirmingUserDeletion">
            <x-slot name="title">
                {{ __('Delete Account') }}
            </x-slot>

            <x-slot name="content">
                {{ __('Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}

                <div class="mt-4" x-data="{}" x-on:confirming-delete-user.window="setTimeout(() => $refs.password.focus(), 250)">
                    <div class="relative">
                        <x-input type="password" 
                                 id="delete_password_input"
                                 class="mt-1 block w-3/4 pr-12"
                                 autocomplete="current-password"
                                 placeholder="{{ __('Password') }}"
                                 x-ref="password"
                                 wire:model="password"
                                 wire:keydown.enter="deleteUser" />
                        <button type="button" style="margin-right: 158px;"
                                id="toggleDeletePassword" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center">
                            <img id="delete-password-toggle-icon" 
                                 src="{{ asset('eye-close.png') }}" 
                                 alt="Toggle Password Visibility" 
                                 class="w-6 h-6">
                        </button>
                    </div>
                    <x-input-error for="password" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingUserDeletion')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3 modal-danger-button" wire:click="deleteUser" wire:loading.attr="disabled">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </x-slot>
        </x-dialog-modal>
    </x-slot>
</x-action-section>

<style>
/* Danger Button Styling for Delete Modal */
.modal-danger-button {
    background-color: #dc2626 !important;
    color: white !important;
}

.modal-danger-button:hover:not(:disabled) {
    background-color: #b91c1c !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle for delete account modal
    const deletePasswordInput = document.getElementById('delete_password_input');
    const toggleDeleteButton = document.getElementById('toggleDeletePassword');
    const deleteToggleIcon = document.getElementById('delete-password-toggle-icon');
    
    if (toggleDeleteButton && deletePasswordInput && deleteToggleIcon) {
        toggleDeleteButton.addEventListener('click', function() {
            const isPassword = deletePasswordInput.getAttribute('type') === 'password';
            deletePasswordInput.setAttribute('type', isPassword ? 'text' : 'password');
            deleteToggleIcon.src = isPassword ? "{{ asset('eye-open.png') }}" : "{{ asset('eye-close.png') }}";
        });
    }
});
</script>
</div>