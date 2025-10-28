@extends('layouts.app')
@section('title', 'Passwordless Login - WebAuthn')
@section('content')

<!-- Back Button - Fixed Position Top Left -->
<div class="fixed top-6 left-6 z-50 pt-4 pl-2">
    <button id="back-button" class="pl-4 ml-4 text-white p-3 rounded-full shadow-lg transition-colors">
        <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const backButton = document.getElementById('back-button');

        // Hide button if there's no history to go back to
        if (window.history.length <= 1) {
            backButton.style.display = 'none';
        }

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

<div style="background-color: #141326;" class="min-h-screen flex flex-col items-center justify-center text-white px-4">
    <div style="margin-top: -20px;" class="w-full max-w-2xl">

    <header class="mb-8 flex flex-col items-center">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-12 h-12">
            <h1 class="text-white text-xl font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></h1>
        </div>
    </header>

    <div class="text-center mb-12">
        <h2 class="text-[#f89c00] text-xl font-semibold tracking-wide">Biometric Login</h2>
        <p class="text-white text-sm mt-2">Sign up with your security keys for a password-less authentication!</p>
    </div>

    <div class="bg-[#3c3f58] mx-auto max-w-lg rounded-xl p-8">
    <form id="webauthnLoginForm">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-white mb-2">EMAIL</label>
            <input id="email" name="email" type="email" 
                class="w-full px-4 py-2 bg-white border-transparent rounded-full text-black placeholder-gray-500 focus:outline-none"
                onfocus="this.style.boxShadow = '0 0 0 2px #55597C'" onblur="this.style.boxShadow = 'none'">
        </div>

        <button type="submit" id="loginButton" class="flex justify-center items-center px-10 py-2 bg-[#9ba0f9] hover:brightness-110 rounded-full font-bold text-black transition-all duration-200 shadow-lg mt-6" style="width: fit-content; margin-left: auto; margin-right: auto;">
            <span id="buttonText">LOGIN WITH SECURITY KEY</span>
        </button>
    </form>
</div>

<div class="mt-16 text-center">
    <h3 class="text-lg font-semibold text-white mt-[20px] mb-6">Supported Authentications</h3>
    <div class="flex w-full items-start text-center">

        <div class="flex-1 flex flex-col items-center">
            <img src="{{ asset('padlock-gold.png') }}" class="w-6 h-6 mb-2">
            <p class="text-sm text-[#f89c00] font-semibold">Windows Hello</p>
        </div>

        <div class="flex-1 flex flex-col items-center">
            <img src="{{ asset('key-gold.png') }}" class="w-6 h-6 mb-2">
            <p class="text-sm text-[#f89c00] font-semibold">Security Keys</p>
        </div>

        <div class="flex-1 flex flex-col items-center">
            <img src="{{ asset('fingerprint-gold.png') }}" class="w-6 h-6 mb-2">
            <p class="text-sm text-[#f89c00] font-semibold">Touch ID</p>
        </div>

        <div class="flex-1 flex flex-col items-center">
            <img src="{{ asset('face-id-gold.png') }}" class="w-6 h-6 mb-2">
            <p class="text-sm text-[#f89c00] font-semibold">Face ID</p>
        </div>

    </div>
</div>

    </div>
</div>

<!-- Authentication Modal -->
<div id="authModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div style="background-color: #141326; opacity: 0.8;" class="fixed inset-0"></div>
        <div style="background-color: #24243B;" class="relative rounded-xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <img src="{{ asset('key-gold.png') }}" class="w-12 h-12">
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Authenticate</h3>
                <p id="authMessage" style="color: #9CA3AF;" class="text-sm mb-6">Please use your security key or biometric authentication when prompted.</p>
                
                <div id="authSpinner" class="mb-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#f89c00] mx-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
// Helper functions
function base64urlToArrayBuffer(base64url) {
    const base64 = base64url.replace(/\-/g, '+').replace(/_/g, '/');
    const binaryString = window.atob(base64);
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

function arrayBufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

function showAuthModal(message = 'Please use your security key or biometric authentication when prompted.') {
    document.getElementById('authMessage').textContent = message;
    document.getElementById('authModal').classList.remove('hidden');
}

function hideAuthModal() {
    document.getElementById('authModal').classList.add('hidden');
}

// Main login function
async function performWebAuthnLogin(email) {
    try {
        showAuthModal('Preparing authentication...');
        
        // Get authentication options
        const optionsResponse = await fetch('/webauthn/login/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email })
        });
        
        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            throw new Error(error.message || 'Failed to get authentication options');
        }
        
        const options = await optionsResponse.json();
        
        // Convert challenge to ArrayBuffer
        if (options.challenge) {
            options.challenge = base64urlToArrayBuffer(options.challenge);
        }
        
        // Convert allowCredentials
        if (options.allowCredentials && Array.isArray(options.allowCredentials)) {
            options.allowCredentials = options.allowCredentials.map(cred => ({
                ...cred,
                id: base64urlToArrayBuffer(cred.id)
            }));
        }
        
        showAuthModal('Please authenticate with your security key or biometrics...');
        
        // Get credential
        const credential = await navigator.credentials.get({
            publicKey: options
        });
        
        if (!credential) {
            throw new Error('Authentication failed');
        }
        
        showAuthModal('Verifying authentication...');
        
        // Prepare credential for server
        const credentialData = {
            id: credential.id,
            rawId: arrayBufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: arrayBufferToBase64url(credential.response.clientDataJSON),
                authenticatorData: arrayBufferToBase64url(credential.response.authenticatorData),
                signature: arrayBufferToBase64url(credential.response.signature),
                userHandle: credential.response.userHandle ? arrayBufferToBase64url(credential.response.userHandle) : null
            }
        };
        
        // Verify with server
        const verifyResponse = await fetch('/webauthn/login/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(credentialData)
        });
        
        const result = await verifyResponse.json();
        
        if (!verifyResponse.ok || !result.verified) {
            throw new Error(result.message || 'Authentication failed');
        }
        
        // Success! Redirect
        hideAuthModal();
        window.location.href = result.redirect || '/dashboard';
        
    } catch (error) {
        hideAuthModal();
        
        let errorMessage = 'Authentication failed';
        
        if (error.name === 'NotAllowedError') {
            errorMessage = 'Authentication was cancelled or timed out';
        } else if (error.name === 'InvalidStateError') {
            errorMessage = 'No security keys found for this account';
        } else if (error.name === 'NotSupportedError') {
            errorMessage = 'Your browser doesn\'t support WebAuthn';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        alert(errorMessage);
        console.error('WebAuthn login error:', error);
        
        // Reset button
        const button = document.getElementById('loginButton');
        const buttonText = document.getElementById('buttonText');
        button.disabled = false;
        buttonText.textContent = 'Sign In with Security Key';
    }
}

// Form submission handler
document.getElementById('webauthnLoginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    if (!email) {
        alert('Please enter your email address');
        return;
    }
    
    const button = document.getElementById('loginButton');
    const buttonText = document.getElementById('buttonText');
    
    // Disable button and show loading
    button.disabled = true;
    buttonText.textContent = 'Preparing...';
    
    await performWebAuthnLogin(email);
});

// Check WebAuthn support
if (!window.PublicKeyCredential) {
    document.getElementById('loginButton').disabled = true;
    document.getElementById('buttonText').textContent = 'WebAuthn Not Supported';
    alert('Your browser does not support WebAuthn. Please use a modern browser or try password login.');
}
</script>
@endpush
@endsection
