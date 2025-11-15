<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Required - SecureDocs</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css'])
    <style>
        body {
            background-color: #1D1D2F;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body style="background-color: #1D1D2F;" class="min-h-screen text-white flex flex-col">

    <div class="bg-[#141326] px-6 py-6">
        <div class="flex items-center justify-between w-full">
            <button id="back-button" style="margin-left: 10px;" class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
            </button>
            <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                <h2 class="font-bold text-xl text-[#f89c00] font-['Poppins']">File Sharing</h2>
            </div>
            <!-- Right: Login / Sign Up Buttons -->
            <div class="flex items-center gap-6">
                <a href="/login" class="text-sm font-medium transition-all duration-200 hover:text-[#ff9c00]">Login</a>
                <a href="/register" class="bg-[#ff9c00] text-black px-4 py-2 rounded-full font-bold transition-all duration-200 hover:brightness-110">Sign Up</a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8 flex-1 flex items-center justify-center">
        <div class="bg-[#3C3F58] w-full max-w-lg p-8 mb-4 md:p-12 rounded-2xl">
            
            <div class="flex items-center justify-center gap-3 mb-6">
                <svg class="w-6 h-6 text-[#f89c00]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <h2 class="text-xl font-bold text-[#f89c00] tracking-wider uppercase">PASSWORD REQUIRED</h2>
            </div>

            <!-- File Info -->
            <div class="bg-[#33354A] p-4 rounded-lg mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 flex-shrink-0 bg-blue-100 rounded-lg flex items-center justify-center">
                        @if($share->file->is_folder)
                            📁
                        @else
                            📄
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-white truncate">{{ $share->file->file_name }}</p>
                        <p class="text-sm text-gray-400">Shared by {{ $share->user->name }}</p>
                    </div>
                </div>
            </div>

            <!-- Password Form -->
            <form id="passwordForm" class="space-y-6">
                <div>
                    <label for="password" class="block text-white text-sm font-normal mb-3 tracking-wide text-left">Password :</label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               style="padding-right: 60px;"
                               class="w-full rounded-full py-2.5 pr-20 px-5 text-black text-sm focus:outline-none bg-[#eaeaf3]"
                               placeholder="Enter password here">
                        <button type="button" id="togglePassword" class="absolute right-3 flex items-center top-1/2 -translate-y-1/2">
                            <img id="password-toggle-icon" src="{{ asset('eye-close.png') }}" alt="Toggle Password Visibility" class="w-8 h-8">
                        </button>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="hidden p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm text-red-800" id="errorText"></span>
                    </div>
                </div>

                <button type="submit" 
                        id="submitBtn"
                        class="w-full py-3 px-4 bg-[#f89c00] hover:brightness-110 text-black font-semibold rounded-full text-center block transition-all duration-200">
                    Access File
                </button>
            </form>

            <!-- Help Text -->
            <p class="text-center text-sm text-gray-400 mt-6">
                Get the password from the file owner who gain access!
            </p>
        </div>
    </div>

    <!-- Language Toggle -->
    <div class="fixed bottom-6 right-6 z-50">
        <div class="relative">
            <button id="language-toggle" class="bg-[#3c3f58] text-white p-3 rounded-full shadow-lg transition"
                style="transition: background-color 0.2s;"
                onmouseover="this.style.backgroundColor='#55597C';"
                onmouseout="this.style.backgroundColor='';">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
            </button>

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
        // Back button functionality
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

        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('password-toggle-icon');

            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    if (type === 'text') {
                        toggleIcon.src = "{{ asset('eye-open.png') }}";
                    } else {
                        toggleIcon.src = "{{ asset('eye-close.png') }}";
                    }
                });
            }
        });

        // Form submission functionality (from original file)
        const form = document.getElementById('passwordForm');
        const submitBtn = document.getElementById('submitBtn');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const passwordInput = document.getElementById('password');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = passwordInput.value.trim();
            if (!password) {
                showError('Please enter a password');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Verifying...';
            hideError();

            try {
                const response = await fetch(`/s/{{ $share->share_token }}/verify-password`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ password })
                });

                const data = await response.json();

                if (data.success) {
                    submitBtn.innerHTML = '✅ Access Granted';
                    submitBtn.classList.add('bg-green-600');
                    
                    // Redirect to file
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Incorrect password');
                }
            } catch (error) {
                showError(error.message || 'Failed to verify password');
                
                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Access File';
                
                // Focus password input
                passwordInput.focus();
                passwordInput.select();
            }
        });

        function showError(message) {
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
        }

        function hideError() {
            errorMessage.classList.add('hidden');
        }

        // Focus password input on load
        if (passwordInput) {
            passwordInput.focus();

            // Hide error when user starts typing
            passwordInput.addEventListener('input', hideError);
        }
    </script>
</body>
</html>