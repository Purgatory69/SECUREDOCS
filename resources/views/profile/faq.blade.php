<x-faq-layout>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet"/>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <!-- Back Button -->
            <button onclick="window.history.back()" class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
            </button>

           <!-- Centered Logo and Title -->
           <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                <h2 class="font-semibold text-xl text-[#f89c00] font-['Poppins']">
                    Help and Support
                </h2>
            </div>

            <!-- Empty div for spacing -->
            <div></div>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 space-y-8" style="background-color: #24243B;">

            <!-- FAQ Accordion Section -->
            <section class="p-6 rounded-xl bg-[#3c3f58]">
                <h2 class="text-xl md:text-2xl font-semibold text-white mb-4">Frequently Asked Questions</h2>

                <!-- FAQ 1 -->
                <div class="faq-item mb-2 rounded-lg overflow-hidden">
                    <button class="faq-toggle flex justify-between items-center w-full py-4 px-4 text-left text-white hover:bg-[#34374f] transition-colors duration-200" data-target="faq-1">
                        <span class="font-semibold text-lg">How do I change my email address?</span>
                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="chevron mr-4 w-2 h-2 transition-transform duration-300">
                    </button>
                    <div id="faq-1" class="faq-content closed px-4 pb-4 text-gray-200 transition-all duration-300 ease-in-out overflow-hidden">
                        <p class="text-sm">
                            Go to <span class="font-medium text-[#f89c00]"> <a href="{{ route('profile.show') }}"> Profile → Update Information </a> </span>, then confirm via the verification link sent to your new email address.
                        </p>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="faq-item bg-[#2a2d42] mb-2 rounded-lg overflow-hidden">
                    <button class="faq-toggle flex justify-between items-center w-full py-4 px-4 text-left text-white hover:bg-[#34374f] transition-colors duration-200" data-target="faq-2">
                        <span class="font-semibold text-lg">Why is my upload failing?</span>
                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="chevron mr-4 w-2 h-2 transition-transform duration-300">
                    </button>
                    <div id="faq-2" class="faq-content closed px-4 pb-4 text-gray-200 transition-all duration-300 ease-in-out overflow-hidden">
                        <p class="text-sm">
                            Check file size (max 50MB) and format. Try switching network or clearing browser cache if the error persists.
                        </p>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="faq-item bg-[#2a2d42] mb-2 rounded-lg overflow-hidden">
                    <button class="faq-toggle flex justify-between items-center w-full py-4 px-4 text-left text-white hover:bg-[#34374f] transition-colors duration-200" data-target="faq-3">
                        <span class="font-semibold text-lg">How do I enable two-factor authentication?</span>
                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="chevron mr-4 w-2 h-2 transition-transform duration-300">
                    </button>
                    <div id="faq-3" class="faq-content closed px-4 pb-4 text-gray-200 transition-all duration-300 ease-in-out overflow-hidden">
                        <p class="text-sm">
                            Visit <span class="font-medium text-[#f89c00]">Security → Two-factor Authentication</span> and follow the app setup instructions.
                        </p>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="faq-item bg-[#2a2d42] mb-2 rounded-lg overflow-hidden">
                    <button class="faq-toggle flex justify-between items-center w-full py-4 px-4 text-left text-white hover:bg-[#34374f] transition-colors duration-200" data-target="faq-4">
                        <span class="font-semibold text-lg">What if I forgot my password?</span>
                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="chevron mr-4 w-2 h-2 transition-transform duration-300">
                    </button>
                    <div id="faq-4" class="faq-content closed px-4 pb-4 text-gray-200 transition-all duration-300 ease-in-out overflow-hidden">
                        <p class="text-sm">
                            Use the <span class="font-medium text-[#f89c00]">Forgot Password</span> option on the login page to reset via email.
                        </p>
                    </div>
                </div>

                <!-- FAQ 5 (Optional - you can add more) -->
                <div class="faq-item bg-[#2a2d42] mb-2 rounded-lg overflow-hidden">
                    <button class="faq-toggle flex justify-between items-center w-full py-4 px-4 text-left text-white hover:bg-[#34374f] transition-colors duration-200" data-target="faq-5">
                        <span class="font-semibold text-lg">How do I contact support?</span>
                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="chevron mr-4 w-2 h-2 transition-transform duration-300">
                    </button>
                    <div id="faq-5" class="faq-content closed px-4 pb-4 text-gray-200 transition-all duration-300 ease-in-out overflow-hidden">
                        <p class="text-sm">
                            You can reach our support team through the contact form or email us directly at support@yoursite.com. We typically respond within 24 hours.
                        </p>
                    </div>
                </div>
            </section>

            <x-section-border />

            <!-- Account Tips Section -->
            <section class="p-6 rounded-xl bg-[#3c3f58] shadow">
                <h2 class="text-xl md:text-2xl font-semibold mb-3 text-white">Account Tips</h2>
                <ul class="list-disc list-inside space-y-1 text-sm md:text-base text-gray-200">
                    <li>Keep your profile info up to date.</li>
                    <li>Use a strong, unique password.</li>
                    <li>Enable two-factor authentication.</li>
                    <li>Review connected devices regularly.</li>
                    <li>Check activity logs for suspicious sign-ins.</li>
                </ul>
            </section>

            <x-section-border />

            <!-- Final Help Section -->
            <section class="p-6 rounded-xl bg-[#3c3f58] shadow text-center">
                <h3 class="text-lg md:text-xl font-semibold text-white">Still need help?</h3>
                <p class="mt-2 text-sm italic text-gray-300">
                    Contact support or submit a ticket — screenshots and short descriptions help us solve your issue faster.
                </p>
            </section>

        </div>
    </div>

    <style>
        /* Enhanced FAQ Animations */
        .faq-content {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-top: 0;
            margin-bottom: 0;
        }
        
        .faq-content.open {
            opacity: 1;
            padding-top: 0.5rem;
            padding-bottom: 1rem;
        }
        
        .faq-content.closed {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
        }
        
        .chevron.rotated {
            transform: rotate(180deg);
        }
        
        .faq-toggle:hover {
            filter: brightness(1.1);
        }
        
        /* Smooth transitions */
        .faq-item {
            background-color: #24243B;
            transition: all 0.2s ease;
        }
        
        /* .faq-item:hover {
            transform: translateY(-1px);
             box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }*/


        /* More specific targeting for section borders */
        div[class*="border-t"], div[class*="border-gray"] {
            border-color: #3c3f58 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqToggles = document.querySelectorAll('.faq-toggle');
            
            faqToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const content = document.getElementById(targetId);
                    const chevron = this.querySelector('.chevron');
                    const isCurrentlyOpen = content.classList.contains('open');
                    
                    // Close all other FAQ items first
                    faqToggles.forEach(otherToggle => {
                        if (otherToggle !== this) {
                            const otherTargetId = otherToggle.getAttribute('data-target');
                            const otherContent = document.getElementById(otherTargetId);
                            const otherChevron = otherToggle.querySelector('.chevron');
                            
                            // Close other items
                            otherContent.style.maxHeight = '0px';
                            otherContent.classList.remove('open');
                            otherContent.classList.add('closed');
                            otherChevron.classList.remove('rotated');
                        }
                    });
                    
                    // Toggle current FAQ item
                    if (isCurrentlyOpen) {
                        // Close current item
                        content.style.maxHeight = '0px';
                        content.classList.remove('open');
                        content.classList.add('closed');
                        chevron.classList.remove('rotated');
                    } else {
                        // Open current item
                        content.classList.remove('closed');
                        content.classList.add('open');
                        
                        // Calculate and set the height for smooth animation
                        const scrollHeight = content.scrollHeight;
                        content.style.maxHeight = scrollHeight + 20 + 'px'; // Add some padding
                        
                        chevron.classList.add('rotated');
                    }
                });
            });
        });
    </script>

</x-faq-layout>