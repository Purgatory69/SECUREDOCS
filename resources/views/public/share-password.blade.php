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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .password-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .submit-btn:disabled {
            opacity: 0.6;
            transform: none;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="password-card w-full max-w-md p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-4">
                    <img src="{{ asset('logo-white.png') }}" alt="SecureDocs" class="h-8 w-auto">
                    <span class="ml-3 text-2xl font-bold text-gray-800">SecureDocs</span>
                </div>
                <div class="w-16 h-16 mx-auto mb-4 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Password Required</h1>
                <p class="text-gray-600">This file is password protected</p>
            </div>

            <!-- File Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        @if($share->file->is_folder)
                            📁
                        @else
                            📄
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate">{{ $share->file->file_name }}</p>
                        <p class="text-sm text-gray-500">Shared by {{ $share->user->name }}</p>
                    </div>
                </div>
            </div>

            <!-- Password Form -->
            <form id="passwordForm" class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Enter Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                           placeholder="Enter the password to access this file">
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
                        class="submit-btn w-full py-3 px-4 text-white font-semibold rounded-lg">
                    🔓 Access File
                </button>
            </form>

            <!-- Help Text -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-start space-x-2">
                    <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium">Need help?</p>
                        <p class="mt-1">Contact the person who shared this file with you to get the password.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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
                    submitBtn.classList.remove('submit-btn');
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
                submitBtn.innerHTML = '🔓 Access File';
                
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
        passwordInput.focus();

        // Hide error when user starts typing
        passwordInput.addEventListener('input', hideError);
    </script>
</body>
</html>
