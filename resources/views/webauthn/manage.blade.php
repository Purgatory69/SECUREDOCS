@extends('layouts.app')

@section('title', 'Security Keys - WebAuthn')

@section('content')
<div class="min-h-screen bg-[#24243B] text-white">
    <!-- Header -->
    <div class="bg-[#141326] px-6 py-6">
        <div class="flex items-center justify-between w-full">
            <a href="{{ route('user.dashboard') }}" style="margin-left: 100px;"
            class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
            </a>
            <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                <h2 class="font-bold text-xl text-[#f89c00] font-['Poppins']">Manage Biometrics</h2>
            </div>
            
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-6 mt-8 lg:px-8 py-8">
        <!-- Success Message -->
        @if (session('status'))
            <div class="mb-6 bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-green-300">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        

        <!-- Action Buttons -->
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-white">Manage Security Keys</h2>
                <p class="text-sm text-gray-300 mt-1">Manage your password-less authenticators here.</p>
                <p class="text-sm text-gray-300">Use them for your biometric logins.</p>
            </div>
            <button onclick="registerNewKey()" 
                class="inline-flex items-center px-6 py-3 bg-[#f89c00] hover:brightness-110 text-black font-bold rounded-full transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New Security Key
            </button>
            <!-- <a href="{{ route('webauthn.login') }}" 
                class="inline-flex items-center px-6 py-3 bg-[#3C3F58] hover:bg-[#55597C] text-white font-medium rounded-full transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Test Passwordless Login
            </a> -->
        </div>

        <!-- Security Keys List -->
        <div class="bg-[#3C3F58] rounded-lg max-h-96 overflow-y-auto custom-scrollbar">
            <div class="divide-y divide-[#55597C]">
                <!-- Temp Design Divs
                Old Layout
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-[#f89c00] rounded-lg flex items-center justify-center text-2xl">
                                    💻
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-lg font-medium text-white truncate">
                                        My Laptop Fingerprint
                                    </h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#f89c00]/20 text-[#f89c00]">
                                        Windows Hello
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-300">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Added 2 days ago
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Platform
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-[#3C3F58] rounded-lg flex items-center justify-center">
                                    Same image source for all keys
                                    <img src="{{ asset('key-gold.png') }}" alt="Cross-Platform Authenticator" class="w-8 h-8">
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-white truncate">
                                    My YubiKey
                                </h3>
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-400">
                                    <span class="font-medium text-gray-300">Cross-platform</span>
                                    <span>&bull;</span>
                                    <span>Added 1 week ago</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                        <button title="Remove" aria-label="Remove" class="flex items-center p-2 text-red-400 hover:text-white rounded-lg transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                        </div>
                    </div>
                </div>
                -->

                @forelse ($credentials as $credential)
                <div class="px-6 py-4 hover:bg-[#55597C] transition-colors group-hover:bg-[#55597C] transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-[#3C3F58] rounded-lg flex items-center justify-center">
                                    @if($credential->attachment_type === 'platform')
                                        <img src="{{ asset('responsive.png') }}" alt="Platform Authenticator" class="w-8 h-8">
                                    @else
                                        <img src="{{ asset('key-gold.png') }}" alt="Cross-Platform Authenticator" class="w-8 h-8">
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-white truncate">
                                    {{ Str::limit($credential->name ?: 'Unnamed Key', 35) }}
                                </h3>
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-400">
                                    @if($credential->attachment_type)
                                        <span class="font-medium text-gray-300">{{ ucfirst($credential->attachment_type) }}</span>
                                        <span>&bull;</span>
                                    @endif
                                    <span>Added {{ $credential->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <form action="{{ route('webauthn.keys.destroy', $credential->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        onclick="return confirm('Are you sure you want to remove this security key? You may lose access to your account if this is your only authentication method.')"
                                        title="Remove" aria-label="Remove" 
                                        class="flex items-center p-2 text-red-400 hover:text-white rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <img src="{{ asset('key-gold.png') }}" alt="Back" class="w-12 h-12 mb-2">
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">No security keys registered</h3>
                    <p class="text-sm text-gray-300 -mt-2">Add a security key to enable password-less authentication</p>
                </div>
            @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Registration Modal -->
<div id="registrationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div style="background-color: #141326; opacity: 0.8;" class="fixed inset-0"></div>
        <div style="background-color: #24243B;" class="relative rounded-lg max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-2 flex items-center justify-center">
                    <img src="{{ asset('key-gold.png') }}" alt="Back" class="w-12 h-12">
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Register Security Key</h3>
                <p id="modalMessage" class="text-sm text-gray-300 mb-6">Follow your browser's prompts to register your security key.</p>
                
                <div id="loadingSpinner" class="hidden mb-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#f89c00] mx-auto"></div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button onclick="closeRegistrationModal()" 
                        class="cancel-button px-4 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Cancel button */
    .cancel-button {background-color: #3C3F58; color: rgba(255, 255, 255, 0.5);; font-weight: 400; transition: background-color 0.2s ease;}
    .cancel-button:hover {background-color: #55597C;}

    /* --- WebKit Scrollbar Styling (Chrome, Safari, Edge) --- */
    .custom-scrollbar::-webkit-scrollbar-track {background: transparent;}
    .custom-scrollbar {scrollbar-width: auto;scrollbar-color: #55597C transparent;}

    /* Scrollbarless version
    .custom-scrollbar::-webkit-scrollbar {display: none;}
    .custom-scrollbar {scrollbar-width: none;} */
</style>

@push('scripts')
<script>
// Helper function to convert base64url to ArrayBuffer
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

// Helper function to convert ArrayBuffer to base64url
function arrayBufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

function showRegistrationModal(message = 'Follow your browser\'s prompts to register your security key.') {
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('registrationModal').classList.remove('hidden');
    document.getElementById('loadingSpinner').classList.remove('hidden');
}

function closeRegistrationModal() {
    document.getElementById('registrationModal').classList.add('hidden');
    document.getElementById('loadingSpinner').classList.add('hidden');
}

async function registerNewKey() {
    try {
        // Show modal
        showRegistrationModal('Preparing registration...');
        
        // Get registration options
        const optionsResponse = await fetch('/webauthn/register/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: 'Security Key ' + new Date().toLocaleString()
            })
        });
        
        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            throw new Error(error.message || 'Failed to get registration options');
        }
        
        const options = await optionsResponse.json();
        
        // Convert challenge and user.id to ArrayBuffer
        if (options.challenge) {
            options.challenge = base64urlToArrayBuffer(options.challenge);
        }
        
        if (options.user && options.user.id) {
            options.user.id = base64urlToArrayBuffer(options.user.id);
        }
        
        // Handle excludeCredentials
        if (options.excludeCredentials && Array.isArray(options.excludeCredentials)) {
            options.excludeCredentials = options.excludeCredentials
                .filter(cred => cred && cred.id)
                .map(cred => {
                    try {
                        return {
                            ...cred,
                            id: base64urlToArrayBuffer(cred.id)
                        };
                    } catch (e) {
                        console.warn('Failed to convert credential ID:', e);
                        return null;
                    }
                })
                .filter(cred => cred !== null);
        }
        
        // Update modal message
        showRegistrationModal('Please use your Windows Hello, Touch ID, or security key when prompted.');
        
        // Create credential
        const credential = await navigator.credentials.create({
            publicKey: options
        });
        
        if (!credential) {
            throw new Error('Failed to create credential');
        }
        
        // Update modal
        showRegistrationModal('Saving your security key...');
        
        // Prepare credential for server
        const credentialData = {
            id: credential.id,
            rawId: arrayBufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: arrayBufferToBase64url(credential.response.clientDataJSON),
                attestationObject: arrayBufferToBase64url(credential.response.attestationObject)
            }
        };
        
        // Send to server
        const verifyResponse = await fetch('/webauthn/register/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(credentialData)
        });
        
        const result = await verifyResponse.json();
        
        if (!verifyResponse.ok || !result.success) {
            throw new Error(result.message || 'Registration failed');
        }
        
        // Success!
        closeRegistrationModal();
        
        // Show success message and reload
        alert('Security key registered successfully!');
        window.location.reload();
        
    } catch (error) {
        closeRegistrationModal();
        
        let errorMessage = 'Registration failed';
        
        if (error.name === 'NotAllowedError') {
            errorMessage = 'Registration was cancelled or timed out';
        } else if (error.name === 'InvalidStateError') {
            errorMessage = 'This security key is already registered';
        } else if (error.name === 'NotSupportedError') {
            errorMessage = 'Your browser doesn\'t support WebAuthn';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        alert(errorMessage);
        console.error('WebAuthn registration error:', error);
    }
}
</script>
@endpush
@endsection