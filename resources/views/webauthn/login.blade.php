@extends('layouts.app')

@section('title', 'Passwordless Login - WebAuthn')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-[#1a1a2e] via-[#16213e] to-[#0f0f23] flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <!-- Logo and Title -->
        <div class="text-center animate-fade-in">
            <div class="mx-auto h-20 w-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 shadow-2xl animate-pulse-slow">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                </svg>
            </div>
            <h2 class="text-4xl font-bold text-white mb-3 bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                Passwordless Login
            </h2>
            <p class="text-gray-400 text-lg">Sign in with your security key or biometrics</p>
            <div class="mt-4 inline-flex items-center px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Secure & Fast
            </div>
        </div>

        <!-- Login Form -->
        <div class="bg-[#1F2235] rounded-xl border border-[#4A4D6A] p-8">
            <form id="webauthnLoginForm" class="space-y-6">
                @csrf
                
                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                        Email Address
                    </label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        required 
                        class="w-full px-4 py-3 bg-[#2A2D47] border border-[#4A4D6A] rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                        placeholder="Enter your email address"
                    >
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    id="loginButton"
                    class="w-full flex justify-center items-center px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-lg font-medium text-white transition-all duration-200 transform hover:scale-105 shadow-lg"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                    </svg>
                    <span id="buttonText">Sign In with Security Key</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="mt-6 pt-6 border-t border-[#4A4D6A]">
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                        Back to password login
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="bg-[#1F2235] rounded-xl border border-[#4A4D6A] p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Supported Authenticators</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto mb-2 bg-blue-500/20 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">🔒</span>
                    </div>
                    <p class="text-sm text-gray-400">Windows Hello</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto mb-2 bg-green-500/20 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">🔑</span>
                    </div>
                    <p class="text-sm text-gray-400">Security Keys</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto mb-2 bg-purple-500/20 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">👆</span>
                    </div>
                    <p class="text-sm text-gray-400">Touch ID</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto mb-2 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">👤</span>
                    </div>
                    <p class="text-sm text-gray-400">Face ID</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Authentication Modal -->
<div id="authModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-[#1F2235] rounded-xl border border-[#4A4D6A] max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Authenticate</h3>
                <p id="authMessage" class="text-gray-400 mb-6">Please use your security key or biometric authentication when prompted.</p>
                
                <div id="authSpinner" class="mb-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-400 mx-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse-slow {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-fade-in {
    animation: fade-in 0.8s ease-out;
}

.animate-pulse-slow {
    animation: pulse-slow 3s ease-in-out infinite;
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.glass-effect {
    backdrop-filter: blur(10px);
    background: rgba(31, 34, 53, 0.8);
}
</style>
@endpush

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
