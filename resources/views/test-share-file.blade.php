@extends('layouts.app')

@section('content')
<!-- 
    ======================================================================
    MAIN WRAPPER
    - This structure is copied from your upgrade.blade.php
    - The background color #1D1D2F matches your app's theme.
    ======================================================================
-->
<div style="background-color: #1D1D2F;" class="min-h-screen text-white flex flex-col">
    <div class="bg-[#141326] px-6 py-6">
        <div class="flex items-center justify-between w-full">
            <button id="back-button" style="margin-left: 10px;" class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
            </button>
            <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                <h2 class="font-bold text-xl text-[#f89c00] font-['Poppins']">File Sharing</h2>
            </div>
            <!-- Right: Login / Sign Up Buttons (from storyboard) -->
            <div class="flex items-center gap-6">
                <a href="/login" class="text-sm font-medium transition-all duration-200 hover:text-[#ff9c00]">{{ __('auth.login') }}</a>
                <a href="/register" class="bg-[#ff9c00] text-black px-4 py-2 rounded-full font-bold transition-all duration-200 hover:brightness-110">{{ __('auth.signup') }}</a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const backButton = document.getElementById('back-button');

        backButton.addEventListener('click', function() {
            // Check if there's a previous page in history
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
            } else {
                // Fallback to home page if no referrer
                window.location.href = "{{ url('/') }}";
            }
        });
    });
</script>

<!-- Language Toggle Button - Fixed Position Bottom Right -->
<div class="fixed bottom-6 right-6 z-50">
    <div class="relative">
        <!-- Toggle Button -->
        <button id="language-toggle" class="bg-[#3c3f58] text-white p-3 rounded-full shadow-lg transition
            style="transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#55597C';"
            onmouseout="this.style.backgroundColor='';">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div id="language-dropdown" style="background-color: #3c3f58; border: 3px solid #1F1F33" class="absolute bottom-full right-0 mb-2 hidden bg-[#3c3f58] rounded-lg shadow-xl overflow-hidden min-w-[140px]">
            <a href="{{ route('language.switch', 'en') }}"
                class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'en' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                @if(app()->getLocale() != 'en')
                    style="transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#55597C';"
                    onmouseout="this.style.backgroundColor='';"
                @endif>
                <span class="mr-2">🇺🇸</span>
                English
            </a>
            <a href="{{ route('language.switch', 'fil') }}"
                class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'fil' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                @if(app()->getLocale() != 'fil')
                    style="transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#55597C';"
                    onmouseout="this.style.backgroundColor='';"
                @endif>
                <span class="mr-2">🇵🇭</span>
                Filipino
            </a>
        </div>
    </div>
</div>

<script>
        // Language Dropdown Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('language-toggle');
            const dropdown = document.getElementById('language-dropdown');

            if (toggleButton && dropdown) {
                toggleButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdown.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!toggleButton.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
        });
</script>

    <!-- 
        ======================================================================
        MAIN CONTENT AREA
        - This wrapper is copied from your upgrade.blade.php
        - It centers the content card.
        ======================================================================
    -->
    <div class="container mx-auto px-6 py-8 flex-1 flex items-center justify-center">


            <div class="bg-[#3C3F58] w-full max-w-lg p-8 mb-4 md:p-12 rounded-2xl">
                
                <!-- 
                    File Icon
                    - Changed from an SVG to an <img> tag to match your upgrade.blade.php structure
                    - You will need to replace this asset path with your own icon.
                -->
                <div class="flex justify-center mb-6">
                    <!-- 
                        PLEASE NOTE: I have to guess the name of this file.
                        You might need to change 'file-icon-white.png' to the real name.
                    -->
                    <img src="{{ asset('file.png') }}" alt="File Icon" class="w-12 h-12">
                </div>

                <!-- 
                    File Name
                -->
                <h2 class="text-xl font-semibold text-white text-center mb-8 truncate">
                This-is-a-very-very-long-file-name-that-should-be-truncated.pdf
                </h2>

                <!-- 
                    File Details (Left-Aligned)
                -->
                <div class="space-y-2 text-sm text-gray-300 mb-10">
                    <p>
                        <span class="font-medium text-gray-100">Shared By :</span>
                        File Owner
                    </p>
                    <p>
                        <span class="font-medium text-gray-100">Share Link Expires in :</span>
                        Expiry Date
                    </p>
                </div>

                <!-- 
                    Button Layout (Side-by-side)
                -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- 
                        Save to My Files Button (Gray)
                    -->
                    <button onclick="saveToMyFiles()" 
                            class="w-full py-3 px-4 bg-[#55597C] hover:brightness-110 text-white font-medium rounded-lg text-center transition-all duration-200">
                        Save to My Files
                    </button>
                    
                    <!-- 
                        Download Button (Orange)
                        - This now uses your app's correct orange color: #f89c00
                    -->
                    <a href="#" 
                       class="w-full py-3 px-4 bg-[#f89c00] hover:brightness-110 text-black font-semibold rounded-lg text-center block transition-all duration-200">
                        Download
                    </a>
                </div>
            </div>
    </div>
    
@endsection

<!-- 
    ======================================================================
    STATIC JAVASCRIPT
    - This is the same JS from v4
    - It is now correctly placed inside @push('scripts')
    ======================================================================
-->
@push('scripts')
<script>
    // --- Static Meta Values (Replaced from Blade) ---
    const META_SHARE_TOKEN = "static-share-token-67890";
    const META_EXPIRES_AT = '2025-11-20T20:00:00Z'; 
    const META_IS_ONE_TIME = 'false';
    const META_DOWNLOAD_COUNT = '0';
    // We don't need META_CSRF_TOKEN for this static page,
    // but you would get it from the 'meta' tag in your layouts.app

    // Check if share has expired
    function isShareExpired() {
        if (!META_EXPIRES_AT) {
            return false; // No expiration set
        }
        const expiryDate = new Date(META_EXPIRES_AT);
        return expiryDate < new Date();
    }

    // Check if share is one-time and already used
    function isShareUsed() {
        if (META_IS_ONE_TIME !== 'true') {
            return false; // Not a one-time link
        }
        const downloadCount = parseInt(META_DOWNLOAD_COUNT);
        return downloadCount > 0;
    }

    // Redirect to expired page
    function redirectToExpired() {
        console.log("Mock Redirect: Share has expired or is used.");
        alert("This share has expired or has already been used.");
    }

    // Check share validity on page load
    function checkShareValidity() {
        if (isShareExpired()) {
            console.log('Share has expired, redirecting...');
            redirectToExpired();
            return false;
        }
        
        if (isShareUsed()) {
            console.log('One-time share already used, redirecting...');
            redirectToExpired();
            return false;
        }
        
        return true;
    }

    // Intercept all download button clicks
    function interceptDownloadButtons() {
        const downloadButtons = document.querySelectorAll('a[href*="/download"], button[onclick*="download"]');
        
        downloadButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!checkShareValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                console.log("Mock Download Clicked. Validity OK.");
                e.preventDefault();
            }, true);
        });
    }

    // Check validity on page load
    document.addEventListener('DOMContentLoaded', function() {
        checkShareValidity();
        interceptDownloadButtons();
        setInterval(checkShareValidity, 30000);
    });

    // Save to My Files functionality
    async function saveToMyFiles() {
        console.log("Mock 'Save to My Files'");
        try {
            const data = { success: true, message: "File saved (mock)" }; 
            
            if (data.success) {
                alert('File saved to your account successfully!');
            } else {
                alert(data.message || 'Failed to save file');
            }
        } catch (error) {
            alert('An error occurred while saving the file');
        }
    }
</script>
@endpush