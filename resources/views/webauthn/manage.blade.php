
@extends('layouts.webauthn')

@section('content')
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-semibold mb-6">Manage Security Keys</h2>
                
                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mb-6">
                    <button onclick="registerNewKey()" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                        Register New Security Key
                    </button>
                </div>

                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        @forelse ($credentials as $credential)
                            <li>
                                <div class="px-4 py-4 flex items-center sm:px-6">
                                    <div class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div class="truncate">
                                            <div class="flex text-sm">
                                                <p class="font-medium text-indigo-600 truncate">{{ $credential->name }}</p>
                                                <p class="ml-1 flex-shrink-0 font-normal text-gray-500">({{ $credential->created_at->diffForHumans() }})</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-5 flex-shrink-0">
                                        <form action="{{ route('webauthn.keys.destroy', $credential->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return window.confirm('Are you sure you want to remove this security key?')">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-center py-4 text-gray-500">
                                No security keys registered yet.
                            </li>
                        @endforelse
                    </ul>
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

    async function registerNewKey() {
        const button = event.target;
        const originalText = button.innerHTML;
        
        try {
            // Show loading state
            button.disabled = true;
            button.innerHTML = 'Preparing...';
            
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
            
            // Ensure required parameters are set
            options.authenticatorSelection = options.authenticatorSelection || {
                requireResidentKey: false,
                userVerification: 'preferred'
            };
            
            options.attestation = options.attestation || 'none';
            
            // Create the credential
            button.innerHTML = 'Waiting for security key...';
            
            const credential = await navigator.credentials.create({
                publicKey: options
            });
            
            // Convert ArrayBuffers to base64url for JSON
            const publicKeyCredential = {
                id: credential.id,
                rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId)))
                    .replace(/\+/g, '-')
                    .replace(/\//g, '_')
                    .replace(/=+$/, ''),
                type: credential.type,
                response: {
                    clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON)))
                        .replace(/\+/g, '-')
                        .replace(/\//g, '_')
                        .replace(/=+$/, ''),
                    attestationObject: btoa(String.fromCharCode(...new Uint8Array(credential.response.attestationObject)))
                        .replace(/\+/g, '-')
                        .replace(/\//g, '_')
                        .replace(/=+$/, ''),
                    transports: credential.response.getTransports ? credential.response.getTransports() : [],
                },
                clientExtensionResults: credential.getClientExtensionResults(),
            };
            
            // Send the credential to the server
            button.innerHTML = 'Verifying...';
            
            const response = await fetch('/webauthn/register/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(publicKeyCredential)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                alert('Security key registered successfully!');
                window.location.reload();
            } else {
                throw new Error(result.message || 'Registration failed');
            }
        } catch (error) {
            console.error('WebAuthn error:', error);
            alert('Failed to register security key: ' + (error.message || 'Unknown error occurred'));
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    </script>
    @endpush
@endsection