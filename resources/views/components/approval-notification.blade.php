@if (session('approval_pending'))
    <!-- Approval Notification Modal -->
    <div id="approval-notification-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center transition-opacity duration-300">
        <div class="bg-[#3c3f58] rounded-2xl p-8 max-w-md w-full mx-4 border-2 border-[#f89c00] transform transition-transform duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center animate-pulse">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Account Pending Approval</h3>
                </div>
                <button id="close-approval-modal" class="text-gray-400 hover:text-white transition-colors duration-200 hover:rotate-90 transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <div class="flex items-start space-x-3 p-4 bg-blue-900 bg-opacity-20 rounded-lg border border-blue-400 border-opacity-30">
                    <div class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="text-blue-200">
                        <p class="text-sm leading-relaxed mb-2">{{ session('approval_pending') }}</p>
                        <p class="text-xs text-blue-300 leading-relaxed">
                            Your account has been successfully created and is currently being reviewed by our administrators. 
                            You'll receive an email notification once your account is approved and ready to use.
                        </p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3 p-3 bg-orange-900 bg-opacity-20 rounded-lg border border-orange-400 border-opacity-30">
                    <div class="w-5 h-5 bg-[#f89c00] rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-orange-200 text-xs leading-relaxed">
                        Need help? Contact our support team if you have any questions about your account status.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button id="dismiss-approval-modal" class="bg-[#f89c00] text-black font-bold px-6 py-2 rounded-full hover:bg-[#d17f00] transition-all duration-200 transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#f89c00] focus:ring-opacity-50">
                    Understood
                </button>
            </div>
        </div>
    </div>

    <!-- Approval Notification Modal JavaScript -->
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
        .approval-notification-animate {
            animation: fadeIn 0.3s ease-in-out;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('approval-notification-modal');
            const closeBtn = document.getElementById('close-approval-modal');
            const dismissBtn = document.getElementById('dismiss-approval-modal');

            if (modal) {
                // Add animation class
                modal.classList.add('approval-notification-animate');

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

                // Close when clicking outside modal
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        hideModal();
                    }
                });

                // Close modal with Escape key for accessibility
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && modal.style.display !== 'none') {
                        hideModal();
                    }
                });

                // Focus on dismiss button for accessibility
                setTimeout(() => {
                    if (dismissBtn) {
                        dismissBtn.focus();
                    }
                }, 100);

                // Trap focus within modal for accessibility
                const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const firstFocusableElement = focusableElements[0];
                const lastFocusableElement = focusableElements[focusableElements.length - 1];

                modal.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            if (document.activeElement === firstFocusableElement) {
                                lastFocusableElement.focus();
                                e.preventDefault();
                            }
                        } else {
                            if (document.activeElement === lastFocusableElement) {
                                firstFocusableElement.focus();
                                e.preventDefault();
                            }
                        }
                    }
                });
            }
        });
    </script>
@endif