@if ($errors->any())
    <!-- Validation Error Modal -->
    <div id="validation-error-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center transition-opacity duration-300">
        <div class="bg-[#3c3f58] rounded-2xl p-8 max-w-md w-full mx-4 border-2 border-[#f89c00] transform transition-transform duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center animate-pulse">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Validation Error</h3>
                </div>
                <button id="close-validation-modal" class="text-gray-400 hover:text-white transition-colors duration-200 hover:rotate-90 transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                @foreach ($errors->all() as $error)
                    <div class="flex items-start space-x-3 p-3 bg-red-900 bg-opacity-20 rounded-lg border border-red-500 border-opacity-30">
                        <div class="w-5 h-5 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <p class="text-red-300 text-sm leading-relaxed">{{ $error }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button id="dismiss-validation-modal" class="bg-[#f89c00] text-black font-bold px-6 py-2 rounded-full hover:bg-[#d17f00] transition-all duration-200 transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#f89c00] focus:ring-opacity-50">
                    Got it
                </button>
            </div>
        </div>
    </div>

    <!-- Validation Modal JavaScript -->
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .validation-error-animate {
            animation: fadeIn 0.3s ease-in-out;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('validation-error-modal');
            const closeBtn = document.getElementById('close-validation-modal');
            const dismissBtn = document.getElementById('dismiss-validation-modal');

            if (modal) {
                // Add animation class
                modal.classList.add('validation-error-animate');

                // Function to hide modal
                function hideModal() {
                    modal.style.opacity = '0';
                    modal.querySelector('div > div').style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }

                // Event listeners
                if (closeBtn) {
                    closeBtn.addEventListener('click', hideModal);
                }
                
                if (dismissBtn) {
                    dismissBtn.addEventListener('click', hideModal);
                }

                // Close when clicking outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        hideModal();
                    }
                });

                // Focus on dismiss button for accessibility
                setTimeout(() => {
                    if (dismissBtn) {
                        dismissBtn.focus();
                    }
                }, 100);
            }
        });
    </script>
@endif
