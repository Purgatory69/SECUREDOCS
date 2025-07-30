@props([
    'id' => 'confirmationModal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to perform this action?',
    'content' => '',
    'confirmButtonText' => 'Confirm',
    'cancelButtonText' => 'Cancel',
    'confirmAction' => 'confirm',
    'show' => false,
])

<div x-data="{
    show: false,
    open() { this.show = true; },
    close() { this.show = false; },
    confirm() {
        this.$el.dispatchEvent(new CustomEvent('confirm'));
        this.close();
    },
    cancel() {
        this.$el.dispatchEvent(new CustomEvent('cancel'));
        this.close();
    }
}"
    x-init="() => {
        $el.addEventListener('show-modal', () => open());
        $el.addEventListener('hide-modal', () => close());
    }"
    x-show="show"
    x-cloak
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed z-50 inset-0 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    id="{{ $id }}">
    
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             aria-hidden="true"
             @click="close">
        </div>

        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title" data-title>
                        {{ $title }}
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500" data-message>
                            {{ $message }}
                        </p>
                        @if($content)
                            <div class="mt-2 text-sm text-gray-600">
                                {{ $content }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        @click="confirm"
                        data-confirm-btn
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ $confirmButtonText }}
                </button>
                <button type="button" 
                        @click="cancel"
                        data-cancel-btn
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ $cancelButtonText }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Global modal state
    const modalState = {
        currentResolve: null,
        currentReject: null,
        currentModal: null
    };

    function showConfirmationModal(options = {}) {
        const modal = document.getElementById('{{ $id }}');
        if (!modal) {
            console.error('Confirmation modal not found');
            return Promise.resolve(false);
        }

        // Set modal content from options
        const title = modal.querySelector('[data-title]');
        const message = modal.querySelector('[data-message]');
        const confirmBtn = modal.querySelector('[data-confirm-btn]');
        const cancelBtn = modal.querySelector('[data-cancel-btn]');
        
        if (options.title && title) title.textContent = options.title;
        if (options.message && message) message.textContent = options.message;
        if (options.confirmButtonText && confirmBtn) confirmBtn.textContent = options.confirmButtonText;
        if (options.cancelButtonText && cancelBtn) cancelBtn.textContent = options.cancelButtonText;
        
        // Set button classes if provided
        if (options.confirmButtonClass && confirmBtn) {
            confirmBtn.className = options.confirmButtonClass;
        }
        
        // Show the modal
        modal.dispatchEvent(new CustomEvent('show-modal'));
        
        // Return a promise that resolves when the user confirms or rejects
        return new Promise((resolve) => {
            const handleConfirm = () => {
                cleanup();
                resolve(true);
            };
            
            const handleCancel = () => {
                cleanup();
                resolve(false);
            };
            
            const cleanup = () => {
                modal.removeEventListener('confirm', handleConfirm);
                modal.removeEventListener('cancel', handleCancel);
                modal.dispatchEvent(new CustomEvent('hide-modal'));
            };
            
            modal.addEventListener('confirm', handleConfirm);
            modal.addEventListener('cancel', handleCancel);
        });
    }
    
    // Make function available globally
    window.showConfirmationModal = showConfirmationModal;
</script>
@endpush
