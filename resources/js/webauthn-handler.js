/**
 * WebAuthn handler for user interactions
 * Provides UI feedback during WebAuthn operations
 */

// Import WebAuthn if using as a module
import './vendor/webauthn';

// Helper function to convert base64url string to ArrayBuffer
function base64urlToArrayBuffer(base64url) {
    try {
        // First, ensure the input is a string
        const str = String(base64url);
        
        // Remove any URL-safe characters and convert to standard base64
        let base64 = str.replace(/-/g, '+').replace(/_/g, '/');
        
        // Add padding if needed
        const pad = base64.length % 4;
        if (pad) {
            base64 += '===='.slice(pad);
        }
        
        // Decode the base64 string
        const binaryString = window.atob(base64);
        
        // Convert to ArrayBuffer
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        
        return bytes.buffer;
    } catch (e) {
        console.error('Error in base64urlToArrayBuffer:', e, 'Input was:', base64url);
        throw new Error('Invalid base64url string: ' + e.message);
    }
}

// Helper function to convert ArrayBuffer to base64
function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

// Helper function to convert ArrayBuffer to base64url string
function arrayBufferToBase64Url(buffer) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(buffer)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}

// Helper function to show error messages
function showError(button, message, statusDisplay = null) {
    console.error('WebAuthn error:', message);

    if (statusDisplay) {
        statusDisplay.textContent = message;
        statusDisplay.className = 'text-sm text-red-600 mt-2 text-center';
    }

    if (button && typeof button.classList !== 'undefined') {
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed', 'bg-indigo-600', 'hover:bg-indigo-700', 'bg-green-600'); // Remove general and success classes
        button.classList.add('bg-red-600'); // Error color

        let icon = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`;
        let text = 'Error';
        if (button.id === 'biometric-login-button') text = 'Login Failed';
        if (button.id === 'register-device-button') text = 'Reg. Failed';
        button.innerHTML = `${icon} ${text}`;
    }
}

// Helper function to show success messages
function showSuccess(button, message, statusDisplay = null) {
    console.log("WebAuthn Success:", message);

    if (statusDisplay) {
        statusDisplay.textContent = message;
        statusDisplay.className = 'text-sm text-green-600 mt-2 text-center';
    }

    if (button && typeof button.classList !== 'undefined') {
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed', 'bg-indigo-600', 'hover:bg-indigo-700', 'bg-red-600'); // Remove general and error classes
        button.classList.add('bg-green-600'); // Success color

        let icon = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
        let text = 'Success!';
        // if (button.id === 'biometric-login-button') text = 'Login Succeeded'; // Already handled by redirect
        if (button.id === 'register-device-button') text = 'Registered!';
        button.innerHTML = `${icon} ${text}`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize WebAuthn object
    const webAuthn = window.WebAuthn;

    if (!webAuthn) {
        console.error('WebAuthn library not found!');
        // Optionally, disable WebAuthn features or show a general error message
        // You might want to update a general status area here if the button isn't available yet
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;

    // Track if a request is in progress
    let isRequestInProgress = false;
    
    // Expose the biometric login handler globally
    window.handleBiometricLogin = async function(userEmail, biometricButton, statusDisplay) {
        // Prevent multiple simultaneous requests
        if (isRequestInProgress) {
            console.log('[WebAuthn] A request is already in progress');
            return;
        }
        
        // Set request in progress
        isRequestInProgress = true;
        
        // Clear previous messages
        if (statusDisplay) {
            statusDisplay.textContent = '';
            statusDisplay.className = 'text-sm text-red-600 mt-2 text-center'; // Reset to default error class
        }

        console.log('[WebAuthn] Attempting biometrics login for email:', userEmail);

        if (!userEmail) {
            showError(biometricButton, 'Please enter your email address to use biometric login.', statusDisplay);
            isRequestInProgress = false;
            return;
        }

        const originalButtonContent = biometricButton.innerHTML; // Store original button content
        
        // Disable button and show loading state
        const disableButton = () => {
            biometricButton.disabled = true;
            biometricButton.classList.add('opacity-75', 'cursor-not-allowed');
            biometricButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Verifying...
            `;
        };
        
        // Re-enable button and restore original content
        const resetButton = () => {
            biometricButton.disabled = false;
            biometricButton.classList.remove('opacity-75', 'cursor-not-allowed');
            biometricButton.innerHTML = originalButtonContent;
            isRequestInProgress = false;
        };
        
        // Set initial button state
        disableButton();

        try {
            const optionsResponse = await fetch('/webauthn/login/options', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ email: userEmail }),
            });

            if (!optionsResponse.ok) {
                const errorData = await optionsResponse.json().catch(() => ({ message: 'Failed to parse server error response.' }));
                console.error('Server response (options):', errorData);
                throw new Error(`Login options request failed: ${optionsResponse.status} ${errorData.message || ''}`.trim());
            }

            const options = await optionsResponse.json();

            // Check if user has any registered keys
            if (!options.challenge) {
                console.error('No challenge found in options:', options);
                showError(biometricButton, 'Authentication error: Missing challenge', statusDisplay);
                return;
            }
            
            if (!options.allowCredentials || options.allowCredentials.length === 0) {
                console.error('No credentials found in options:', options);
                showError(biometricButton, 'No biometric keys registered for this account. Please register a key first.', statusDisplay);
                return;
            }
            
            // Ensure all credential IDs are valid base64url strings
            const validCredentials = options.allowCredentials.filter(cred => {
                if (!cred.id) {
                    console.warn('Credential missing ID:', cred);
                    return false;
                }
                try {
                    base64urlToArrayBuffer(cred.id);
                    return true;
                } catch (e) {
                    console.error('Invalid credential ID:', cred.id, 'Error:', e);
                    return false;
                }
            });
            
            if (validCredentials.length === 0) {
                console.error('No valid credentials found after validation');
                showError(biometricButton, 'No valid biometric keys found. Please register a new key.', statusDisplay);
                return;
            }

            // Log the options for debugging
            console.log('WebAuthn options:', {
                challenge: options.challenge,
                rpId: options.rpId,
                allowCredentials: options.allowCredentials
            });

            try {
                // Convert challenge and credential IDs from base64 to ArrayBuffer
                const publicKey = {
                    challenge: base64urlToArrayBuffer(options.challenge),
                    allowCredentials: options.allowCredentials.map(cred => ({
                        ...cred,
                        id: base64urlToArrayBuffer(cred.id),
                        type: 'public-key',
                    })),
                    rpId: options.rpId,
                    userVerification: options.userVerification || 'preferred',
                    timeout: options.timeout || 60000,
                };

                console.log('Calling navigator.credentials.get with:', publicKey);
                
                // Add a small delay to ensure any previous modal is dismissed
                await new Promise(resolve => setTimeout(resolve, 100));
                
                const credential = await navigator.credentials.get({
                    publicKey: publicKey
                });
                
                console.log('Received credential from authenticator:', credential);
                
                // Convert ArrayBuffers to base64url for server
                const authenticatorData = new Uint8Array(credential.response.authenticatorData);
                const clientDataJSON = new Uint8Array(credential.response.clientDataJSON);
                const signature = new Uint8Array(credential.response.signature);
                const userHandle = credential.response.userHandle ? new Uint8Array(credential.response.userHandle) : null;

                const attestationResponseForServer = {
                    id: credential.id,
                    rawId: arrayBufferToBase64Url(credential.rawId), // Correctly encode rawId
                    type: credential.type,
                    response: {
                        authenticatorData: arrayBufferToBase64Url(credential.response.authenticatorData),
                        clientDataJSON: arrayBufferToBase64Url(credential.response.clientDataJSON),
                        signature: arrayBufferToBase64Url(credential.response.signature),
                        userHandle: credential.response.userHandle ? arrayBufferToBase64Url(credential.response.userHandle) : null,
                    },
                };
                
                // Send the credential to the server for verification
                console.log('Sending credential to server for verification:', attestationResponseForServer);
                
                try {
                    const verificationResponse = await fetch('/webauthn/login/verify', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(attestationResponseForServer),
                    });

                    console.log('Verification response status:', verificationResponse.status);
                    
                    if (!verificationResponse.ok) {
                        const errorData = await verificationResponse.json().catch(() => ({
                            message: 'Failed to parse server error response.'
                        }));
                        console.error('Server response (verify):', errorData);
                        throw new Error(`Login verification failed: ${verificationResponse.status} ${errorData.message || ''}`.trim());
                    }

                    const verificationData = await verificationResponse.json();
                    console.log('Verification data:', verificationData);

                    if (verificationData.verified || verificationData.redirect) {
                        showSuccess(biometricButton, verificationData.message || 'Login successful! Redirecting...', statusDisplay);
                        setTimeout(() => {
                            window.location.href = verificationData.redirect || verificationData.redirect_url || '/dashboard';
                        }, 1500);
                        return true;
                    } else {
                        throw new Error(verificationData.message || 'Verification failed');
                    }
                } catch (error) {
                    console.error('Error during verification:', error);
                    throw error;
                }
            } catch (error) {
                console.error('Error in WebAuthn credential request:', error);
                throw error;
            } finally {
                // Ensure we always clean up, even if there's an error
                resetButton();
            }

            // This code is now moved above and integrated into the main flow

        } catch (err) {
            console.error('WebAuthn login process error:', err);
            let displayErrorMessage = 'An unexpected error occurred during biometric login. Please try again.';

            if (err.name === 'NotAllowedError') {
                displayErrorMessage = 'Biometric login canceled or no matching authenticator found. Please try again, or ensure your authenticator is connected and recognized.';
            } else if (err.name === 'SecurityError') {
                displayErrorMessage = 'Biometric login failed due to a security policy. Ensure you\'re on a secure connection (HTTPS or localhost) and the domain is correctly configured.';
            } else if (err.name === 'InvalidStateError') {
                displayErrorMessage = 'Biometric login failed: your authenticator is in an invalid state. Please try again. If the issue persists, you may need to re-register your key.';
            } else if (err.message) {
                // Use specific messages from fetch errors if available
                if (err.message.startsWith('Login options request failed:') || err.message.startsWith('Login verification failed:')) {
                    displayErrorMessage = err.message;
                } else {
                    // For other generic errors with a message property
                    displayErrorMessage = 'Biometric login failed: ' + (err.message.startsWith('Error: ') ? err.message.substring('Error: '.length) : err.message);
                }
            }
            showError(biometricButton, displayErrorMessage, statusDisplay);
        } finally {
            // The button state is now handled by showError/showSuccess, but if no error/success, reset it
            if (!biometricButton.classList.contains('bg-green-600') && !biometricButton.classList.contains('bg-red-600')) {
                biometricButton.disabled = false;
                biometricButton.classList.remove('opacity-75', 'cursor-not-allowed');
                biometricButton.innerHTML = originalButtonContent;
            }
        }
    };

    // Handle biometric login button
    const biometricLoginButton = document.getElementById('biometric-login-button');
    const statusDisplay = document.getElementById('status-display');
    if (biometricLoginButton) {
        biometricLoginButton.addEventListener('click', async function(e) {
            e.preventDefault();

            const userEmailInput = document.getElementById('email');
            const userEmail = userEmailInput ? userEmailInput.value : null;

            await window.handleBiometricLogin(userEmail, biometricLoginButton, statusDisplay);
        });
    }

    // Handle device registration button (existing code, ensure showError calls are updated if needed)
    const registerDeviceButton = document.getElementById('register-device-button');
    if (registerDeviceButton) {
        const originalRegisterButtonContent = registerDeviceButton.innerHTML;
        registerDeviceButton.addEventListener('click', function(e) {
            e.preventDefault();

            const deviceName = document.getElementById('device-name').value.trim();
            if (!deviceName) {
                showError(registerDeviceButton, 'Please enter a name for your device.'); // No statusDisplay for this context yet
                return;
            }

            registerDeviceButton.disabled = true;
            registerDeviceButton.classList.add('opacity-75', 'cursor-not-allowed');
            registerDeviceButton.innerHTML = `Registering...`; // Simplified loading text

            fetch('/webauthn/register/options', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name: deviceName }) // Assuming backend expects 'name'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Failed to get registration options.'); });
                }
                return response.json();
            })
            .then(options => {
                try {
                    // Convert challenge and user.id from base64url to ArrayBuffer
                    options.challenge = base64urlToArrayBuffer(options.challenge);
                    if (options.user) {
                        options.user.id = base64urlToArrayBuffer(options.user.id);
                    }

                    // Convert each credential ID in excludeCredentials from base64url to ArrayBuffer
                    if (options.excludeCredentials && Array.isArray(options.excludeCredentials)) {
                        options.excludeCredentials = options.excludeCredentials
                            .filter(cred => cred && cred.id) // Filter out invalid entries
                            .map(cred => {
                                try {
                                    return {
                                        ...cred,
                                        id: base64urlToArrayBuffer(cred.id),
                                        type: 'public-key'
                                    };
                                } catch (e) {
                                    console.warn('Invalid credential in excludeCredentials:', cred, e);
                                    return null;
                                }
                            })
                            .filter(Boolean); // Remove any null entries
                    }

                    // Ensure required algorithms are present
                    if (!options.pubKeyCredParams || options.pubKeyCredParams.length === 0) {
                        options.pubKeyCredParams = [
                            { type: 'public-key', alg: -7 },  // ES256
                            { type: 'public-key', alg: -257 } // RS256
                        ];
                    }
                    
                    // Set reasonable defaults if not provided
                    options.timeout = options.timeout || 60000; // 60 seconds
                    options.attestation = options.attestation || 'none';
                    
                    console.log('Creating credential with options:', options);
                    
                    return navigator.credentials.create({ publicKey: options });
                } catch (error) {
                    console.error('Error processing WebAuthn options:', error);
                    throw error;
                }
            })
            .then(credential => {
                // Prepare credential for server
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

                // Log the credential for debugging
                console.log('Sending credential to server:', publicKeyCredential);
                
                return fetch('/webauthn/register/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        name: deviceName,
                        credential: publicKeyCredential  // Send the complete credential object
                    }),
                });
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Failed to verify registration.'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.verified) { // Ensure your backend returns 'verified' or adjust key accordingly
                    showSuccess(registerDeviceButton, data.message || 'Device registered successfully!');
                    // Optionally redirect or update UI further, e.g., reload to show the new key
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showError(registerDeviceButton, data.message || 'Device registration failed.');
                }
            })
            .catch(error => {
                console.error('WebAuthn registration process error:', error);
                let displayErrorMessage = 'An unexpected error occurred during device registration. Please try again.';

                if (error.name === 'NotAllowedError') {
                    displayErrorMessage = 'Device registration canceled or not allowed. Please try again.';
                } else if (error.name === 'SecurityError') {
                    displayErrorMessage = 'Device registration failed due to a security policy. Ensure you\'re on a secure connection (HTTPS or localhost).';
                } else if (error.message) {
                    // Check if it's a server-generated error message from our throw statements
                    if (error.message.includes('Failed to get registration options') || error.message.includes('Failed to verify registration')) {
                        displayErrorMessage = error.message;
                    } else {
                        displayErrorMessage = 'Device registration failed: ' + error.message;
                    }
                }
                showError(registerDeviceButton, displayErrorMessage);
            })
            .finally(() => {
                // Re-enable button and restore original content if not showing success/error message
                if (!registerDeviceButton.classList.contains('bg-green-600') && !registerDeviceButton.classList.contains('bg-red-600')) {
                    registerDeviceButton.disabled = false;
                    registerDeviceButton.innerHTML = originalRegisterButtonContent;
                }
            });
        });
    }
});
