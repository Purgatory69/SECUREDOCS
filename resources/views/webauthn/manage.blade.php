
@extends('layouts.app')

@section('title', 'Security Keys - WebAuthn')

@section('content')
<div class="min-h-screen bg-[#141326] text-white">
    <!-- Header -->
    <div class="bg-[#141326] border-b border-[#3C3F58]">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-18 py-4">
                <div class="flex items-center">
                    <a href="{{ route('user.dashboard') }}" class="text-gray-300 hover:text-white transition-colors mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-[#3C3F58] rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#f89c00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">Security Keys</h1>
                            <p class="text-sm text-gray-400">Manage your WebAuthn authenticators</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8">
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
        <div class="mb-8 flex flex-col sm:flex-row gap-4">
            <button onclick="registerNewKey()" 
                class="inline-flex items-center px-6 py-3 bg-[#f89c00] hover:bg-[#e68900] text-black font-bold rounded-full transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New Security Key
            </button>
            
            <a href="{{ route('webauthn.login') }}" 
                class="inline-flex items-center px-6 py-3 bg-[#3C3F58] hover:bg-[#55597C] text-white font-medium rounded-full transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Test Passwordless Login
            </a>
        </div>

        <!-- Security Keys List -->
        <div class="bg-[#3C3F58] rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-[#55597C]">
                <h2 class="text-lg font-bold text-white">Your Security Keys</h2>
                <p class="text-sm text-gray-300 mt-1">Manage your registered WebAuthn authenticators</p>
            </div>

            <div class="divide-y divide-[#55597C]">
                @forelse ($credentials as $credential)
                    <div class="px-6 py-4 hover:bg-[#55597C] transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Authenticator Icon -->
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-[#f89c00] rounded-lg flex items-center justify-center text-2xl">
                                        {{ $credential->authenticator_icon }}
                                    </div>
                                </div>
                                
                                <!-- Credential Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-lg font-medium text-white truncate">
                                            {{ $credential->name ?: 'Unnamed Key' }}
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#f89c00]/20 text-[#f89c00]">
                                            {{ $credential->authenticator_display_name }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-1 flex items-center space-x-4 text-sm text-gray-300">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Added {{ $credential->created_at->diffForHumans() }}
                                        </span>
                                        
                                        @if($credential->attachment_type)
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ ucfirst($credential->attachment_type) }}
                                            </span>
                                        @endif
                                        
                                        @if($credential->updated_at && $credential->updated_at != $credential->created_at)
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                Last used {{ $credential->updated_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center space-x-2">
                                <form action="{{ route('webauthn.keys.destroy', $credential->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                        onclick="return confirm('Are you sure you want to remove this security key? You may lose access to your account if this is your only authentication method.')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-[#f89c00]/20 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#f89c00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-white mb-2">No security keys registered</h3>
                        <p class="text-gray-300 mb-6">Add a security key to enable passwordless authentication</p>
                        <button onclick="registerNewKey()" 
                            class="inline-flex items-center px-4 py-2 bg-[#f89c00] hover:bg-[#e68900] text-black font-bold rounded-full transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Your First Key
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Registration Modal -->
<div id="registrationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-[#3C3F58] rounded-lg border border-[#55597C] max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-[#f89c00]/20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-[#f89c00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Register Security Key</h3>
                <p id="modalMessage" class="text-gray-300 mb-6">Follow your browser's prompts to register your security key.</p>
                
                <div id="loadingSpinner" class="hidden mb-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#f89c00] mx-auto"></div>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="closeRegistrationModal()" 
                        class="flex-1 px-4 py-2 text-gray-300 hover:text-white border border-[#55597C] hover:border-gray-400 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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
        showRegistrationModal('Please use your Windows Hello, Touch ID, or security key when prompted...');
        
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